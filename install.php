<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

if (!function_exists('fs_install_log')) {
    /**
     * Temporary installer logger for activation diagnostics.
     */
    function fs_install_log($message, $level = 'error')
    {
        $line = '[field_staff][install] ' . $message;

        $local_log_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'install_debug.log';
        @file_put_contents($local_log_file, date('Y-m-d H:i:s') . ' ' . $line . PHP_EOL, FILE_APPEND);

        if (function_exists('log_message')) {
            log_message($level, $line);
            return;
        }

        error_log($line);
    }
}

if (!function_exists('fs_install_exec')) {
    /**
     * Run install SQL safely and emit detailed diagnostics.
     */
    function fs_install_exec($CI, $step, $sql)
    {
        fs_install_log('Running step: ' . $step, 'debug');

        try {
            $ok = $CI->db->query($sql);
        } catch (Throwable $e) {
            $exception_code = (string) $e->getCode();
            $exception_message = (string) $e->getMessage();

            // MySQL code 1050 means CREATE TABLE hit an already existing table.
            if ($exception_code === '1050' || stripos($exception_message, 'already exists') !== false) {
                fs_install_log('Step skipped (already exists): ' . $step . ' | code=' . $exception_code . ' | message=' . $exception_message, 'debug');
                return true;
            }

            fs_install_log('Step exception: ' . $step . ' | type=' . get_class($e) . ' | code=' . $exception_code . ' | message=' . $exception_message);
            fs_install_log('Failing SQL: ' . $sql);
            throw new Exception('Field Staff install exception at step [' . $step . ']: ' . $exception_message, 0, $e);
        }

        if ($ok) {
            fs_install_log('Step completed: ' . $step, 'debug');
            return true;
        }

        $error = method_exists($CI->db, 'error') ? $CI->db->error() : ['code' => -1, 'message' => 'Unknown DB error'];
        $code = isset($error['code']) ? (string) $error['code'] : '-1';
        $message = isset($error['message']) ? (string) $error['message'] : 'Unknown DB error';

        fs_install_log('Step failed: ' . $step . ' | code=' . $code . ' | message=' . $message);
        fs_install_log('Failing SQL: ' . $sql);

        throw new Exception('Field Staff install failed at step [' . $step . ']: ' . $message);
    }
}

$prefix = function_exists('db_prefix') ? db_prefix() : '';

$previous_db_debug = isset($CI->db->db_debug) ? (bool) $CI->db->db_debug : null;
if ($previous_db_debug !== null) {
    $CI->db->db_debug = false;
}

fs_install_log('Activation installer started', 'debug');

if (!function_exists('fs_table_exists')) {
    /**
     * Check table existence with optional database prefix.
     */
    function fs_table_exists($CI, $tableName)
    {
        if (method_exists($CI->db, 'table_exists')) {
            return (bool) $CI->db->table_exists($tableName);
        }

        $query = $CI->db->query(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
            [$tableName]
        );

        return $query && $query->num_rows() > 0;
    }
}

if (!function_exists('fs_column_exists')) {
    /**
     * Check whether a table column exists.
     */
    function fs_column_exists($CI, $tableName, $columnName)
    {
        return method_exists($CI->db, 'field_exists')
            ? (bool) $CI->db->field_exists($columnName, $tableName)
            : false;
    }
}

if (!function_exists('fs_install_add_column')) {
    /**
     * Add a missing table column idempotently.
     */
    function fs_install_add_column($CI, $tableName, $columnName, $definition)
    {
        if (fs_column_exists($CI, $tableName, $columnName)) {
            fs_install_log('Column exists, skipped: ' . $tableName . '.' . $columnName, 'debug');
            return true;
        }

        return fs_install_exec($CI, 'alter_' . $tableName . '_' . $columnName, "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$definition}");
    }
}

$attendanceTable = $prefix . 'fs_attendance';
$attributesTable = $prefix . 'fs_payroll_attributes';
$valuesTable     = $prefix . 'fs_payroll_values';
$masterTable     = $prefix . 'fs_payroll_master';
$profilesTable   = $prefix . 'fs_payroll_profiles';
$departmentsTable = $prefix . 'fs_departments';
$shiftsTable = $prefix . 'fs_shifts';
$shiftDistributionsTable = $prefix . 'fs_shift_distributions';
$leavesTable = $prefix . 'fs_leaves';

if (!fs_table_exists($CI, $attendanceTable)) {
    fs_install_exec($CI, 'create_fs_attendance', "CREATE TABLE `{$attendanceTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT UNSIGNED NOT NULL,
        `date` DATE NOT NULL,
        `clock_in` DATETIME NULL,
        `clock_out` DATETIME NULL,
        `in_latitude` DECIMAL(10,8) NULL,
        `in_longitude` DECIMAL(11,8) NULL,
        `out_latitude` DECIMAL(10,8) NULL,
        `out_longitude` DECIMAL(11,8) NULL,
        `tracking_mode` ENUM('assigned','free-form') NOT NULL DEFAULT 'free-form',
        `dispatch_id` INT UNSIGNED NULL,
        `notes` TEXT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_fs_attendance_staff_id` (`staff_id`),
        KEY `idx_fs_attendance_date` (`date`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $attendanceTable, 'debug');
}

if (!fs_table_exists($CI, $attributesTable)) {
    fs_install_exec($CI, 'create_fs_payroll_attributes', "CREATE TABLE `{$attributesTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `code` VARCHAR(50) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `type` ENUM('addition','deduction') NOT NULL,
        `is_taxable` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_fs_payroll_attributes_code` (`code`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $attributesTable, 'debug');
}

if (!fs_table_exists($CI, $valuesTable)) {
    fs_install_exec($CI, 'create_fs_payroll_values', "CREATE TABLE `{$valuesTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `payroll_id` INT UNSIGNED NOT NULL,
        `attribute_id` INT UNSIGNED NOT NULL,
        `value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (`id`),
        KEY `idx_fs_payroll_values_payroll_id` (`payroll_id`),
        KEY `idx_fs_payroll_values_attribute_id` (`attribute_id`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $valuesTable, 'debug');
}

if (!fs_table_exists($CI, $masterTable)) {
    fs_install_exec($CI, 'create_fs_payroll_master', "CREATE TABLE `{$masterTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT UNSIGNED NOT NULL,
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `regular_hours` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
        `ot_hours` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
        `hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `gross_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `nib_ee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `nib_er` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `nhip_ee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `nhip_er` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `net_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `payment_method` ENUM('cash','online_transfer','check') NOT NULL DEFAULT 'online_transfer',
        `status` ENUM('draft','approved','paid') NOT NULL DEFAULT 'draft',
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_fs_payroll_master_staff_id` (`staff_id`),
        KEY `idx_fs_payroll_master_date_range` (`start_date`,`end_date`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $masterTable, 'debug');
}

if (!fs_table_exists($CI, $profilesTable)) {
    fs_install_exec($CI, 'create_fs_payroll_profiles', "CREATE TABLE `{$profilesTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT UNSIGNED NOT NULL,
        `base_hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `overtime_multiplier` DECIMAL(5,2) NOT NULL DEFAULT 1.50,
        `daily_field_allowance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `payment_method` ENUM('cash','online_transfer','check') NOT NULL DEFAULT 'online_transfer',
        `bank_account_info` TEXT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_fs_payroll_profiles_staff_id` (`staff_id`),
        KEY `idx_fs_payroll_profiles_staff_id` (`staff_id`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $profilesTable, 'debug');
}

if (!fs_table_exists($CI, $departmentsTable)) {
    fs_install_exec($CI, 'create_fs_departments', "CREATE TABLE `{$departmentsTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(191) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_fs_departments_name` (`name`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $departmentsTable, 'debug');
}

if (!fs_table_exists($CI, $shiftsTable)) {
    fs_install_exec($CI, 'create_fs_shifts', "CREATE TABLE `{$shiftsTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `shift_name` VARCHAR(191) NOT NULL,
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `grace_period_mins` INT NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $shiftsTable, 'debug');
}

if (!fs_table_exists($CI, $shiftDistributionsTable)) {
    fs_install_exec($CI, 'create_fs_shift_distributions', "CREATE TABLE `{$shiftDistributionsTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT UNSIGNED NOT NULL,
        `shift_id` INT UNSIGNED NOT NULL,
        `date` DATE NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_fs_shift_distributions_staff_date` (`staff_id`,`date`),
        KEY `idx_fs_shift_distributions_shift_id` (`shift_id`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $shiftDistributionsTable, 'debug');
}

if (!fs_table_exists($CI, $leavesTable)) {
    fs_install_exec($CI, 'create_fs_leaves', "CREATE TABLE `{$leavesTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT UNSIGNED NOT NULL,
        `leave_type` ENUM('Vacation','Sick','Maternity','Unpaid') NOT NULL DEFAULT 'Vacation',
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `status` ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
        `reason` TEXT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_fs_leaves_staff_id` (`staff_id`),
        KEY `idx_fs_leaves_date_range` (`start_date`,`end_date`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $leavesTable, 'debug');
}

fs_install_add_column($CI, $profilesTable, 'department_id', 'INT UNSIGNED NULL AFTER `staff_id`');
fs_install_add_column($CI, $profilesTable, 'default_shift_id', 'INT UNSIGNED NULL AFTER `department_id`');
fs_install_add_column($CI, $profilesTable, 'employee_nib_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 5.50 AFTER `daily_field_allowance`');
fs_install_add_column($CI, $profilesTable, 'employer_nib_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 6.50 AFTER `employee_nib_rate`');
fs_install_add_column($CI, $profilesTable, 'employee_nhip_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 3.00 AFTER `employer_nib_rate`');
fs_install_add_column($CI, $profilesTable, 'vacation_pay', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `employee_nhip_rate`');
fs_install_add_column($CI, $profilesTable, 'outstanding_loan', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `vacation_pay`');
fs_install_add_column($CI, $profilesTable, 'loan_repayment', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `outstanding_loan`');

// Seed default EAV payroll attributes in an idempotent way.
fs_install_exec($CI, 'seed_fs_payroll_attributes', "INSERT IGNORE INTO `{$attributesTable}` (`code`, `name`, `type`, `is_taxable`) VALUES
    ('loan_adjustment', 'Loan Adjustment', 'deduction', 0),
    ('advance', 'Advance', 'deduction', 0),
    ('commission', 'Commission', 'addition', 1),
    ('vacation_pay', 'Vacation Pay', 'addition', 1)");

if ($previous_db_debug !== null) {
    $CI->db->db_debug = $previous_db_debug;
}

fs_install_log('Activation installer completed', 'debug');
