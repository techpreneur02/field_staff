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
$snapshotsTable  = $prefix . 'fs_payroll_snapshots';
$workflowTable   = $prefix . 'fs_payroll_workflow_history';
$cutoffTable     = $prefix . 'fs_payroll_cutoffs';
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
        `raw_punches` TINYINT(1) NOT NULL DEFAULT 0,
        `dispatch_id` INT UNSIGNED NULL,
        `notes` TEXT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_fs_attendance_staff_id` (`staff_id`),
        KEY `idx_fs_attendance_date` (`date`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $attendanceTable, 'debug');
}

$breakSessionsTable = $prefix . 'fs_break_sessions';
$policyOverridesTable = $prefix . 'fs_policy_overrides';
$attendanceCorrectionsTable = $prefix . 'fs_attendance_corrections';

if (!fs_table_exists($CI, $breakSessionsTable)) {
    fs_install_exec($CI, 'create_fs_break_sessions', "CREATE TABLE `{$breakSessionsTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT UNSIGNED NOT NULL,
        `attendance_id` INT UNSIGNED NOT NULL,
        `break_out_at` DATETIME NULL,
        `break_in_at` DATETIME NULL,
        `ended_at` DATETIME NULL,
        `break_type` ENUM('paid','unpaid') NOT NULL DEFAULT 'paid',
        `notes` TEXT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_fs_break_sessions_staff_id` (`staff_id`),
        KEY `idx_fs_break_sessions_attendance_id` (`attendance_id`),
        KEY `idx_fs_break_sessions_active` (`staff_id`,`ended_at`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $breakSessionsTable, 'debug');
}

if (!fs_table_exists($CI, $policyOverridesTable)) {
    fs_install_exec($CI, 'create_fs_policy_overrides', "CREATE TABLE `{$policyOverridesTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT UNSIGNED NOT NULL,
        `field_name` VARCHAR(90) NOT NULL,
        `value` TEXT NULL,
        `effective_from` DATE NOT NULL,
        `effective_to` DATE NULL,
        `source` VARCHAR(32) NOT NULL DEFAULT 'manual',
        `verification_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
        `notes` TEXT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_fs_policy_overrides_staff_field_date` (`staff_id`, `field_name`, `effective_from`),
        KEY `idx_fs_policy_overrides_staff_id` (`staff_id`),
        KEY `idx_fs_policy_overrides_effective` (`effective_from`,`effective_to`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $policyOverridesTable, 'debug');
}

if (!fs_table_exists($CI, $attendanceCorrectionsTable)) {
    fs_install_exec($CI, 'create_fs_attendance_corrections', "CREATE TABLE `{$attendanceCorrectionsTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `attendance_id` INT UNSIGNED NOT NULL,
        `staff_id` INT UNSIGNED NOT NULL,
        `requested_by` INT UNSIGNED NOT NULL,
        `approved_by` INT UNSIGNED NULL,
        `correction_type` VARCHAR(50) NOT NULL DEFAULT 'attendance',
        `before_value` TEXT NULL,
        `after_value` TEXT NULL,
        `reason` TEXT NOT NULL,
        `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `notes` TEXT NULL,
        `approved_at` DATETIME NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_fs_attendance_corrections_staff_id` (`staff_id`),
        KEY `idx_fs_attendance_corrections_attendance_id` (`attendance_id`),
        KEY `idx_fs_attendance_corrections_status` (`status`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $attendanceCorrectionsTable, 'debug');
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
        UNIQUE KEY `uq_fs_payroll_values_payroll_attribute` (`payroll_id`, `attribute_id`),
        KEY `idx_fs_payroll_values_payroll_id` (`payroll_id`),
        KEY `idx_fs_payroll_values_attribute_id` (`attribute_id`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $valuesTable, 'debug');

    try {
        $index_check = $CI->db->query("SHOW INDEX FROM `{$valuesTable}` WHERE Key_name = 'uq_fs_payroll_values_payroll_attribute'");
        if (!$index_check || !method_exists($index_check, 'result_array') || (method_exists($index_check, 'num_rows') && $index_check->num_rows() === 0)) {
            $alter_result = $CI->db->query("ALTER TABLE `{$valuesTable}` ADD UNIQUE INDEX `uq_fs_payroll_values_payroll_attribute` (`payroll_id`, `attribute_id`)");
            if ($alter_result === false) {
                $error = method_exists($CI->db, 'error') ? $CI->db->error() : ['message' => 'Unknown DB error'];
                $message = is_array($error) && isset($error['message']) ? (string) $error['message'] : (string) $error;
                fs_install_log('Failed to add unique payroll attribute index: ' . $valuesTable . ' | ' . $message, 'warning');
                return;
            }

            fs_install_log('Added unique payroll attribute index: ' . $valuesTable, 'debug');
            return;
        }

        $rows = $index_check->result_array();
        if (empty($rows)) {
            $alter_result = $CI->db->query("ALTER TABLE `{$valuesTable}` ADD UNIQUE INDEX `uq_fs_payroll_values_payroll_attribute` (`payroll_id`, `attribute_id`)");
            if ($alter_result === false) {
                $error = method_exists($CI->db, 'error') ? $CI->db->error() : ['message' => 'Unknown DB error'];
                $message = is_array($error) && isset($error['message']) ? (string) $error['message'] : (string) $error;
                fs_install_log('Failed to add unique payroll attribute index: ' . $valuesTable . ' | ' . $message, 'warning');
                return;
            }

            fs_install_log('Added unique payroll attribute index: ' . $valuesTable, 'debug');
        }
    } catch (Throwable $e) {
        $message = strtolower((string) $e->getMessage());
        if (strpos($message, 'duplicate') === false && strpos($message, 'already exists') === false && strpos($message, 'exist') === false) {
            throw $e;
        }

        fs_install_log('Payroll value dedupe index already present or could not be repaired safely: ' . $valuesTable . ' | ' . $message, 'warning');
    }
}

if (!fs_table_exists($CI, $masterTable)) {
    fs_install_exec($CI, 'create_fs_payroll_master', "CREATE TABLE `{$masterTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT UNSIGNED NOT NULL,
        `cutoff_id` INT UNSIGNED NULL,
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
        `status` ENUM('draft','under_review','approved','paid') NOT NULL DEFAULT 'draft',
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_fs_payroll_master_staff_cutoff` (`staff_id`,`cutoff_id`),
        KEY `idx_fs_payroll_master_staff_id` (`staff_id`),
        KEY `idx_fs_payroll_master_cutoff_id` (`cutoff_id`),
        KEY `idx_fs_payroll_master_date_range` (`start_date`,`end_date`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $masterTable, 'debug');
}

if (!fs_table_exists($CI, $snapshotsTable)) {
    fs_install_exec($CI, 'create_fs_payroll_snapshots', "CREATE TABLE `{$snapshotsTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `payroll_id` INT UNSIGNED NOT NULL,
        `staff_id` INT UNSIGNED NOT NULL,
        `cutoff_id` INT UNSIGNED NULL,
        `calculation_version` VARCHAR(80) NOT NULL DEFAULT '2026.10.19.payroll.v1',
        `snapshot_status` ENUM('draft','under_review','approved','paid') NOT NULL DEFAULT 'draft',
        `snapshot_data` LONGTEXT NOT NULL,
        `generated_at` DATETIME NOT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_fs_payroll_snapshots_payroll_id` (`payroll_id`),
        KEY `idx_fs_payroll_snapshots_staff_id` (`staff_id`),
        KEY `idx_fs_payroll_snapshots_cutoff_id` (`cutoff_id`),
        KEY `idx_fs_payroll_snapshots_status` (`snapshot_status`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $snapshotsTable, 'debug');
}

if (!fs_table_exists($CI, $workflowTable)) {
    fs_install_exec($CI, 'create_fs_payroll_workflow_history', "CREATE TABLE `{$workflowTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `payroll_id` INT UNSIGNED NOT NULL,
        `staff_id` INT UNSIGNED NOT NULL,
        `cutoff_id` INT UNSIGNED NULL,
        `action` VARCHAR(40) NOT NULL,
        `from_status` VARCHAR(20) NULL,
        `to_status` VARCHAR(20) NULL,
        `actor_staff_id` INT UNSIGNED NULL,
        `notes` TEXT NULL,
        `payment_date` DATE NULL,
        `payment_method` VARCHAR(32) NULL,
        `payment_reference` VARCHAR(120) NULL,
        `payment_amount` DECIMAL(12,2) NULL,
        `snapshot_id` INT UNSIGNED NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_fs_payroll_workflow_payroll_id` (`payroll_id`),
        KEY `idx_fs_payroll_workflow_staff_id` (`staff_id`),
        KEY `idx_fs_payroll_workflow_action` (`action`),
        KEY `idx_fs_payroll_workflow_cutoff_id` (`cutoff_id`)
    ) ENGINE=InnoDB;");
} else {
    fs_install_log('Table exists, skipped: ' . $workflowTable, 'debug');
}

if (!fs_table_exists($CI, $profilesTable)) {
    fs_install_exec($CI, 'create_fs_payroll_profiles', "CREATE TABLE `{$profilesTable}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT UNSIGNED NOT NULL,
        `payroll_group` VARCHAR(80) NOT NULL DEFAULT 'default',
        `base_hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `overtime_multiplier` DECIMAL(5,2) NOT NULL DEFAULT 1.50,
        `daily_field_allowance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `payment_method` ENUM('cash','online_transfer','check') NOT NULL DEFAULT 'online_transfer',
        `bank_account_info` TEXT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_fs_payroll_profiles_staff_id` (`staff_id`),
        KEY `idx_fs_payroll_profiles_staff_id` (`staff_id`),
        KEY `idx_fs_payroll_profiles_group` (`payroll_group`)
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

fs_install_add_column($CI, $profilesTable, 'payroll_group', 'VARCHAR(80) NOT NULL DEFAULT "default" AFTER `staff_id`');
fs_install_add_column($CI, $profilesTable, 'position', 'VARCHAR(32) NOT NULL DEFAULT "worker" AFTER `payroll_group`');
fs_install_add_column($CI, $profilesTable, 'employment_arrangement', 'VARCHAR(32) NOT NULL DEFAULT "permanent" AFTER `position`');
fs_install_add_column($CI, $profilesTable, 'payment_basis', 'VARCHAR(32) NOT NULL DEFAULT "hourly" AFTER `employment_arrangement`');
fs_install_add_column($CI, $profilesTable, 'attendance_requirement', 'VARCHAR(32) NOT NULL DEFAULT "required" AFTER `payment_basis`');
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
