<?php

/*
Module Name: Field Staff module by Sherwin Armas
Description: White-labeled workforce module with geolocation-verified attendance, employee self-service, payroll support with statutory deductions (NIB, NHIP), and HR operations workspace controls.
Version: 1.2.0
Author: Sherwin Armas
Requires at least: 2.3.0
*/

defined('BASEPATH') or exit('No direct script access allowed');

if (!defined('FS_MODULE_NAME')) {
    define('FS_MODULE_NAME', 'field_staff');
}

if (!defined('FS_MODULE_PATH')) {
    $base_modules_path = defined('APP_MODULES_PATH') ? APP_MODULES_PATH : dirname(__FILE__) . DIRECTORY_SEPARATOR;
    define('FS_MODULE_PATH', $base_modules_path . FS_MODULE_NAME . '/');
}

if (!defined('FS_WEEKLY_REGULAR_HOURS_CAP')) {
    define('FS_WEEKLY_REGULAR_HOURS_CAP', 44.0);
}

if (!defined('FS_OT_MULTIPLIER')) {
    define('FS_OT_MULTIPLIER', 1.5);
}

if (!defined('FS_NIB_EMPLOYEE_RATE')) {
    define('FS_NIB_EMPLOYEE_RATE', 0.055);
}

if (!defined('FS_NIB_EMPLOYER_RATE')) {
    define('FS_NIB_EMPLOYER_RATE', 0.065);
}

if (!defined('FS_NHIP_EMPLOYEE_RATE')) {
    define('FS_NHIP_EMPLOYEE_RATE', 0.03);
}

if (!defined('FS_NHIP_EMPLOYER_RATE')) {
    define('FS_NHIP_EMPLOYER_RATE', 0.03);
}

if (!defined('FS_NHIP_MONTHLY_CEILING')) {
    define('FS_NHIP_MONTHLY_CEILING', 7800.00);
}

if (!defined('FS_MANAGER_SUPERVISOR_ROLE_IDS')) {
    // Set explicit role IDs allowed for project assignment access, for example: [3, 7]
    define('FS_MANAGER_SUPERVISOR_ROLE_IDS', []);
}

if (!defined('FS_HR_PAYROLL_STAFF_IDS')) {
    // Set explicit staff IDs allowed for Master Payroll HR and HR Management Workspace, for example: [1, 5, 9]
    define('FS_HR_PAYROLL_STAFF_IDS', []);
}

if (function_exists('register_activation_hook')) {
    register_activation_hook('field_staff', 'field_staff_activation_hook');
}

if (function_exists('hooks')) {
    hooks()->add_action('admin_init', 'field_staff_init_menu_items');
    hooks()->add_action('admin_init', 'field_staff_register_capabilities');
    hooks()->add_action('app_admin_head', 'field_staff_inject_css');
    hooks()->add_action('app_admin_footer', 'field_staff_inject_js');
}

function field_staff_admin_url($path)
{
    if (function_exists('admin_url')) {
        return admin_url($path);
    }

    return $path;
}

function field_staff_asset_url($relative_path)
{
    if (!function_exists('base_url')) {
        return 'modules/' . FS_MODULE_NAME . '/assets/' . ltrim($relative_path, '/');
    }

    $module_base = 'modules/' . FS_MODULE_NAME . '/assets/';
    $base_rel = ltrim($relative_path, '/');
    $filename = basename($base_rel);
    $dirname = dirname($base_rel);
    $candidates = [];

    $filename_variants = [$filename];
    if (stripos($filename, 'field_staff') !== false) {
        $filename_variants[] = str_replace('field_staff', 'Field_staff', $filename);
        $filename_variants[] = str_replace('field_staff', 'Field_Staff', $filename);
    }

    foreach (array_unique($filename_variants) as $candidate_name) {
        $candidates[] = $module_base . $dirname . '/' . $candidate_name;
        $candidates[] = 'modules/' . FS_MODULE_NAME . '/' . $dirname . '/' . $candidate_name;
    }

    foreach ($candidates as $candidate) {
        if ($candidate !== '' && strpos($candidate, '://') === false) {
            return base_url($candidate);
        }
    }

    return base_url($module_base . $base_rel);
}

function field_staff_activation_hook()
{
    try {
        $installer = FS_MODULE_PATH . 'install.php';
        if (file_exists($installer)) {
            require_once $installer;
        }

        if (function_exists('field_staff_run_pending_migrations')) {
            field_staff_run_pending_migrations();
        }
    } catch (Throwable $e) {
        if (function_exists('log_message')) {
            log_message('error', 'Field Staff activation error: ' . $e->getMessage());
        }

        throw $e;
    }
}

function field_staff_register_capabilities()
{
    if (!function_exists('register_staff_capabilities')) {
        return;
    }

    register_staff_capabilities(FS_MODULE_NAME, [
        'view_own' => 'View Own Attendance Only',
        'view'     => 'View All Global Staff Attendance / Supervisor Access',
        'edit'     => 'Edit/Modify Attendance Records',
        'create'   => 'Manually Log Attendance',
        'manage_settings' => 'Manage HR Settings and Payroll Controls',
    ]);
}

function field_staff_can_manage_settings()
{
    if (function_exists('is_admin') && (bool) is_admin()) {
        return true;
    }

    if (function_exists('has_permission') && has_permission(FS_MODULE_NAME, '', 'manage_settings')) {
        return true;
    }

    $allowed_staff_ids = field_staff_get_hr_payroll_staff_ids();
    $staff_id = 0;

    if (function_exists('get_staff_user_id')) {
        $staff_id = (int) get_staff_user_id();
    }

    if ($staff_id <= 0 && function_exists('get_instance')) {
        $CI = &get_instance();
        if ($CI && isset($CI->session)) {
            $staff_id = (int) $CI->session->userdata('staff_user_id');
        }
    }

    if ($staff_id <= 0) {
        return false;
    }

    return !empty($allowed_staff_ids) && in_array($staff_id, $allowed_staff_ids, true);
}

function field_staff_can_manage_hr_workspace()
{
    return field_staff_can_manage_settings();
}

function field_staff_can_access_hr_payroll_workspace()
{
    if (field_staff_can_manage_settings()) {
        return true;
    }

    $allowed_staff_ids = field_staff_get_hr_payroll_staff_ids();
    $staff_id = 0;
    if (function_exists('get_staff_user_id')) {
        $staff_id = (int) get_staff_user_id();
    }

    if ($staff_id <= 0 && function_exists('get_instance')) {
        $CI = &get_instance();
        if ($CI && isset($CI->session)) {
            $staff_id = (int) $CI->session->userdata('staff_user_id');
        }
    }

    if ($staff_id <= 0) {
        return false;
    }

    return !empty($allowed_staff_ids) && in_array($staff_id, $allowed_staff_ids, true);
}

function field_staff_is_manager_or_supervisor()
{
    return field_staff_can_manage_settings();
}

function field_staff_get_manager_supervisor_role_ids()
{
    $allowed_staff_ids = field_staff_get_hr_payroll_staff_ids();
    if (!empty($allowed_staff_ids)) {
        return $allowed_staff_ids;
    }

    $legacy_role_ids = [];
    if (function_exists('get_instance') && function_exists('db_prefix')) {
        $CI = &get_instance();
        if ($CI && isset($CI->db)) {
            $settings_table = db_prefix() . 'fs_settings';
            if ($CI->db->table_exists($settings_table)) {
                $row = $CI->db
                    ->select('setting_value')
                    ->from($settings_table)
                    ->where('setting_key', 'manager_supervisor_role_ids')
                    ->limit(1)
                    ->get()
                    ->row_array();

                $raw_value = is_array($row) && isset($row['setting_value']) ? trim((string) $row['setting_value']) : '';
                if ($raw_value !== '') {
                    foreach (preg_split('/[^0-9]+/', $raw_value) as $value) {
                        if ($value !== '') {
                            $legacy_role_ids[] = (int) $value;
                        }
                    }
                }
            }
        }
    }

    if (empty($legacy_role_ids) && defined('FS_MANAGER_SUPERVISOR_ROLE_IDS') && is_array(FS_MANAGER_SUPERVISOR_ROLE_IDS)) {
        $legacy_role_ids = array_map('intval', FS_MANAGER_SUPERVISOR_ROLE_IDS);
    }

    return array_values(array_unique(array_filter(array_map('intval', $legacy_role_ids), function ($value) {
        return (int) $value > 0;
    })));
}

function field_staff_get_hr_payroll_staff_ids()
{
    $allowed_staff_ids = [];

    if (function_exists('get_instance') && function_exists('db_prefix')) {
        $CI = &get_instance();
        if ($CI && isset($CI->db)) {
            $settings_table = db_prefix() . 'fs_settings';
            if ($CI->db->table_exists($settings_table)) {
                $row = $CI->db
                    ->select('setting_value')
                    ->from($settings_table)
                    ->where('setting_key', 'hr_payroll_staff_ids')
                    ->limit(1)
                    ->get()
                    ->row_array();

                $raw_value = is_array($row) && isset($row['setting_value']) ? trim((string) $row['setting_value']) : '';
                if ($raw_value !== '') {
                    foreach (preg_split('/[^0-9]+/', $raw_value) as $value) {
                        if ($value !== '') {
                            $allowed_staff_ids[] = (int) $value;
                        }
                    }
                }
            }
        }
    }

    if (empty($allowed_staff_ids) && defined('FS_HR_PAYROLL_STAFF_IDS') && is_array(FS_HR_PAYROLL_STAFF_IDS)) {
        $allowed_staff_ids = array_map('intval', FS_HR_PAYROLL_STAFF_IDS);
    }

    $allowed_staff_ids = array_values(array_unique(array_filter($allowed_staff_ids, function ($value) {
        return (int) $value > 0;
    })));

    return $allowed_staff_ids;
}

function field_staff_run_pending_migrations()
{
    if (!function_exists('get_instance') || !function_exists('db_prefix')) {
        return;
    }

    $CI = &get_instance();
    if (!$CI || !isset($CI->db)) {
        return;
    }

    $prefix = db_prefix();
    $migration_table = $prefix . 'fs_schema_migrations';
    $payroll_master_table = $prefix . 'fs_payroll_master';
    $payroll_profiles_table = $prefix . 'fs_payroll_profiles';
    $cutoff_table = $prefix . 'fs_payroll_cutoffs';
    $payroll_values_table = $prefix . 'fs_payroll_values';

    $table_exists = function ($table) use ($CI) {
        return is_object($CI->db) && method_exists($CI->db, 'table_exists') && $CI->db->table_exists($table);
    };

    $field_exists = function ($table, $column) use ($CI, $table_exists) {
        if (!$table_exists($table) || !is_object($CI->db) || !method_exists($CI->db, 'field_exists')) {
            return false;
        }

        return (bool) $CI->db->field_exists($column, $table);
    };

    $enum_value_exists = function ($table_name, $column_name, $value) use ($CI, $table_exists) {
        if (!$table_exists($table_name) || !is_object($CI->db) || !method_exists($CI->db, 'query')) {
            return false;
        }

        try {
            $result = $CI->db->query("SHOW COLUMNS FROM `{$table_name}` LIKE '{$column_name}'");
            if (!$result || !method_exists($result, 'row_array')) {
                return false;
            }

            $row = $result->row_array();
            if (!is_array($row) || !isset($row['Type'])) {
                return false;
            }

            $type = strtolower((string) $row['Type']);
            if (strpos($type, 'enum(') === false) {
                return false;
            }

            $matches = [];
            preg_match('/enum\((.*)\)/i', $type, $matches);
            if (empty($matches[1])) {
                return false;
            }

            $enum_values = preg_split('/\s*,\s*/', trim($matches[1], "'"));
            foreach ($enum_values as $enum_value) {
                if (trim((string) $enum_value, "'\"") === trim((string) $value, "'\"")) {
                    return true;
                }
            }
        } catch (Throwable $e) {
            return false;
        }

        return false;
    };

    $schema_index_exists = function ($table_name, $index_name) use ($CI, $table_exists) {
        if (!$table_exists($table_name) || !is_object($CI->db) || !method_exists($CI->db, 'query')) {
            return false;
        }

        try {
            $result = $CI->db->query('SHOW INDEX FROM `' . $table_name . '`');
            if (!$result || !method_exists($result, 'result_array')) {
                return false;
            }

            foreach ($result->result_array() as $row) {
                if (isset($row['Key_name']) && trim((string) $row['Key_name']) === $index_name) {
                    return true;
                }
            }
        } catch (Throwable $e) {
            return false;
        }

        return false;
    };

    $migration_has_required_schema = function ($migration_name) use ($CI, $prefix, $payroll_master_table, $payroll_profiles_table, $cutoff_table, $payroll_values_table, $table_exists, $field_exists, $enum_value_exists, $schema_index_exists) {
        switch ($migration_name) {
            case '2026_10_13_payroll_cutoff_foundation':
                return $table_exists($cutoff_table)
                    && $field_exists($payroll_master_table, 'cutoff_id')
                    && $field_exists($payroll_master_table, 'status')
                    && $enum_value_exists($payroll_master_table, 'status', 'under_review');
            case '2026_10_14_payroll_group_and_duplicates':
                return $field_exists($payroll_profiles_table, 'payroll_group')
                    && $schema_index_exists($payroll_profiles_table, 'idx_fs_payroll_profiles_group')
                    && $schema_index_exists($payroll_master_table, 'uq_fs_payroll_master_staff_cutoff');
            case '2026_10_16_payroll_component_uniqueness':
                return $schema_index_exists($payroll_values_table, 'uq_fs_payroll_values_payroll_attribute');
            case '2026_10_17_employee_classification_fields':
                return $field_exists($payroll_profiles_table, 'position')
                    && $field_exists($payroll_profiles_table, 'employment_arrangement')
                    && $field_exists($payroll_profiles_table, 'payment_basis')
                    && $field_exists($payroll_profiles_table, 'attendance_requirement');
            case '2026_10_18_policy_and_break_tracking':
                return $table_exists($prefix . 'fs_break_sessions')
                    && $table_exists($prefix . 'fs_policy_overrides')
                    && $table_exists($prefix . 'fs_attendance_corrections');
            case '2026_10_19_payroll_snapshots':
                return $table_exists($prefix . 'fs_payroll_snapshots')
                    && $field_exists($prefix . 'fs_payroll_snapshots', 'snapshot_status')
                    && $enum_value_exists($prefix . 'fs_payroll_snapshots', 'snapshot_status', 'under_review');
            case '2026_10_20_payroll_workflow_history':
                return $table_exists($prefix . 'fs_payroll_workflow_history');
            case '2026_10_22_payroll_work_records':
                return $table_exists($prefix . 'fs_payroll_work_records');
            case '2026_10_22_attendance_raw_punches':
                return $field_exists($prefix . 'fs_attendance', 'raw_punches');
            default:
                return true;
        }
    };

    $migration_conflict_query = function ($table_name, $columns) use ($CI) {
        if (!is_object($CI->db) || !method_exists($CI->db, 'query')) {
            return null;
        }

        $column_list = implode(', ', array_map(function ($column) {
            return '`' . trim((string) $column, '`') . '`';
        }, $columns));
        $select_sql = 'SELECT ' . $column_list . ', COUNT(*) AS duplicate_count FROM `' . $table_name . '` GROUP BY ' . $column_list . ' HAVING COUNT(*) > 1';

        try {
            return $CI->db->query($select_sql);
        } catch (Throwable $e) {
            if (function_exists('log_message')) {
                log_message('error', 'Field Staff migration duplicate check failed for ' . $table_name . ': ' . $e->getMessage());
            }
            return null;
        }
    };

    $log_migration_issue = function ($message) {
        if (function_exists('log_message')) {
            log_message('error', 'Field Staff migration: ' . $message);
        }
    };

    if (!$table_exists($migration_table)) {
        $result = $CI->db->query("CREATE TABLE `{$migration_table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration_name` VARCHAR(191) NOT NULL,
            `applied_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_fs_schema_migrations_name` (`migration_name`)
        ) ENGINE=InnoDB;");

        if ($result === false) {
            $log_migration_issue('Unable to create migration registry table.');
            return;
        }
    }

    $migrations = [
        '2026_10_13_payroll_cutoff_foundation' => [
            "CREATE TABLE IF NOT EXISTS `{$cutoff_table}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `cutoff_code` VARCHAR(60) NOT NULL,
                `payroll_group` VARCHAR(80) NOT NULL DEFAULT 'default',
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `payment_date` DATE NOT NULL,
                `status` ENUM('open','under_review','approved','closed') NOT NULL DEFAULT 'open',
                `created_by` INT UNSIGNED NULL,
                `updated_by` INT UNSIGNED NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_fs_payroll_cutoffs_code` (`cutoff_code`),
                KEY `idx_fs_payroll_cutoffs_group` (`payroll_group`),
                KEY `idx_fs_payroll_cutoffs_dates` (`start_date`,`end_date`),
                KEY `idx_fs_payroll_cutoffs_status` (`status`)
            ) ENGINE=InnoDB;",
            "ALTER TABLE `{$payroll_master_table}` ADD COLUMN `cutoff_id` INT UNSIGNED NULL AFTER `end_date`",
        ],
        '2026_10_14_payroll_group_and_duplicates' => [
            "ALTER TABLE `{$payroll_profiles_table}` ADD COLUMN `payroll_group` VARCHAR(80) NOT NULL DEFAULT 'default' AFTER `staff_id`",
            "ALTER TABLE `{$payroll_profiles_table}` ADD INDEX `idx_fs_payroll_profiles_group` (`payroll_group`)",
            "ALTER TABLE `{$payroll_master_table}` ADD UNIQUE INDEX `uq_fs_payroll_master_staff_cutoff` (`staff_id`,`cutoff_id`)",
        ],
        '2026_10_16_payroll_component_uniqueness' => [
            "ALTER TABLE `{$payroll_values_table}` ADD UNIQUE INDEX `uq_fs_payroll_values_payroll_attribute` (`payroll_id`, `attribute_id`)",
        ],
        '2026_10_17_employee_classification_fields' => [
            "ALTER TABLE `{$payroll_profiles_table}` ADD COLUMN `position` VARCHAR(32) NOT NULL DEFAULT 'worker' AFTER `payroll_group`",
            "ALTER TABLE `{$payroll_profiles_table}` ADD COLUMN `employment_arrangement` VARCHAR(32) NOT NULL DEFAULT 'permanent' AFTER `position`",
            "ALTER TABLE `{$payroll_profiles_table}` ADD COLUMN `payment_basis` VARCHAR(32) NOT NULL DEFAULT 'hourly' AFTER `employment_arrangement`",
            "ALTER TABLE `{$payroll_profiles_table}` ADD COLUMN `attendance_requirement` VARCHAR(32) NOT NULL DEFAULT 'required' AFTER `payment_basis`",
        ],
        '2026_10_18_policy_and_break_tracking' => [
            "CREATE TABLE IF NOT EXISTS `{$prefix}fs_break_sessions` (
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
            ) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS `{$prefix}fs_policy_overrides` (
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
            ) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS `{$prefix}fs_attendance_corrections` (
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
            ) ENGINE=InnoDB;",
        ],
        '2026_10_19_payroll_snapshots' => [
            "CREATE TABLE IF NOT EXISTS `{$prefix}fs_payroll_snapshots` (
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
            ) ENGINE=InnoDB;",
            "ALTER TABLE `{$prefix}fs_payroll_snapshots` MODIFY COLUMN `snapshot_status` ENUM('draft','under_review','approved','paid') NOT NULL DEFAULT 'draft'",
            "ALTER TABLE `{$payroll_master_table}` MODIFY COLUMN `status` ENUM('draft','under_review','approved','paid') NOT NULL DEFAULT 'draft'",
        ],
        '2026_10_20_payroll_workflow_history' => [
            "CREATE TABLE IF NOT EXISTS `{$prefix}fs_payroll_workflow_history` (
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
            ) ENGINE=InnoDB;",
        ],
    ];

    $migrations['2026_10_22_payroll_work_records'] = [
        "CREATE TABLE IF NOT EXISTS `{$prefix}fs_payroll_work_records` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `staff_id` INT UNSIGNED NOT NULL,
            `cutoff_id` INT UNSIGNED NOT NULL,
            `payment_basis` VARCHAR(20) NOT NULL,
            `start_date` DATE NOT NULL,
            `end_date` DATE NOT NULL,
            `quantity` DECIMAL(12,4) NOT NULL,
            `rate` DECIMAL(12,4) NOT NULL,
            `reference` VARCHAR(120) NOT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'approved',
            `approved_by` INT UNSIGNED NOT NULL,
            `approved_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_fs_work_reference` (`staff_id`, `cutoff_id`, `reference`),
            KEY `idx_fs_work_period` (`staff_id`, `start_date`, `end_date`)
        ) ENGINE=InnoDB;"
    ];

    $migrations['2026_10_22_attendance_raw_punches'] = [
        "ALTER TABLE `{$prefix}fs_attendance` ADD COLUMN `raw_punches` TINYINT(1) NOT NULL DEFAULT 0",
    ];

    foreach ($migrations as $migration_name => $statement_set) {
        $migration_result = $CI->db
            ->select('id')
            ->from($migration_table)
            ->where('migration_name', $migration_name)
            ->limit(1)
            ->get();

        $migration_record = $migration_result && method_exists($migration_result, 'row_array') ? $migration_result->row_array() : null;
        $should_run = empty($migration_record) || !$migration_has_required_schema($migration_name);
        if (!$should_run) {
            continue;
        }

        $migration_failed = false;

        foreach ($statement_set as $statement) {
            $statement = trim((string) $statement);
            if ($statement === '') {
                continue;
            }

            $table_name = '';
            $column_name = '';
            $index_name = '';

            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/`?([A-Za-z0-9_]+)`?\s*\(/i', $statement, $table_match);
                $table_name = isset($table_match[1]) ? $table_match[1] : '';
                if ($table_name !== '' && $table_exists($table_name)) {
                    continue;
                }
            }

            if (stripos($statement, 'ALTER TABLE') !== false) {
                preg_match('/ALTER TABLE\s+`?([A-Za-z0-9_]+)`?/i', $statement, $table_match);
                $table_name = isset($table_match[1]) ? $table_match[1] : '';

                if (stripos($statement, 'ADD COLUMN') !== false) {
                    preg_match('/ADD COLUMN\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_]+)`?/i', $statement, $column_match);
                    $column_name = isset($column_match[1]) ? $column_match[1] : '';
                    if ($table_name !== '' && $column_name !== '' && $field_exists($table_name, $column_name)) {
                        continue;
                    }
                }

                if ((stripos($statement, 'ADD INDEX') !== false) || (stripos($statement, 'ADD UNIQUE') !== false) || (stripos($statement, 'ADD KEY') !== false)) {
                    preg_match('/(?:INDEX|KEY|UNIQUE\s+INDEX|UNIQUE\s+KEY)\s+`?([A-Za-z0-9_]+)`?/i', $statement, $index_match);
                    $index_name = isset($index_match[1]) ? $index_match[1] : '';
                    if ($table_name !== '' && $index_name !== '' && $schema_index_exists($table_name, $index_name)) {
                        continue;
                    }

                    if (stripos($statement, 'UNIQUE') !== false) {
                        $duplicate_columns = [];
                        if (stripos($statement, 'staff_id') !== false && stripos($statement, 'cutoff_id') !== false) {
                            $duplicate_columns = ['staff_id', 'cutoff_id'];
                        } elseif (stripos($statement, 'payroll_id') !== false && stripos($statement, 'attribute_id') !== false) {
                            $duplicate_columns = ['payroll_id', 'attribute_id'];
                        } elseif (stripos($statement, 'staff_id') !== false && stripos($statement, 'field_name') !== false && stripos($statement, 'effective_from') !== false) {
                            $duplicate_columns = ['staff_id', 'field_name', 'effective_from'];
                        }

                        if (!empty($duplicate_columns)) {
                            $duplicate_result = $migration_conflict_query($table_name, $duplicate_columns);
                            if ($duplicate_result === null) {
                                $log_migration_issue('Unable to verify duplicate data in `' . $table_name . '` before creating unique index ' . $index_name . '.');
                                $migration_failed = true;
                                break;
                            }

                            if (method_exists($duplicate_result, 'num_rows') && $duplicate_result->num_rows() > 0) {
                                $log_migration_issue('Duplicate data exists in `' . $table_name . '` for columns ' . implode(', ', $duplicate_columns) . '; preserving existing payroll data and skipping unique index creation for ' . $index_name . '.');
                                $migration_failed = true;
                                break;
                            }
                        }
                    }
                }
            }

            try {
                $result = $CI->db->query($statement);
                if ($result === false) {
                    $db_error = method_exists($CI->db, 'error') ? $CI->db->error() : ['message' => 'Unknown database error'];
                    $error_message = is_array($db_error) && isset($db_error['message']) ? $db_error['message'] : (string) $db_error;
                    $log_migration_issue('Migration statement failed for ' . $migration_name . ': ' . $statement . ' | ' . $error_message);
                    $migration_failed = true;
                    break;
                }
            } catch (Throwable $e) {
                $log_migration_issue('Migration statement exception for ' . $migration_name . ': ' . $e->getMessage() . ' | SQL: ' . $statement);
                $migration_failed = true;
                break;
            }

            if ($table_name !== '' && $column_name !== '' && stripos($statement, 'ALTER TABLE') !== false && stripos($statement, 'ADD COLUMN') !== false && !$field_exists($table_name, $column_name)) {
                $log_migration_issue('Migration column still missing after execution: ' . $table_name . '.' . $column_name . ' for ' . $migration_name . '.');
                $migration_failed = true;
                break;
            }

            if ($table_name !== '' && $index_name !== '' && stripos($statement, 'ALTER TABLE') !== false && (stripos($statement, 'ADD INDEX') !== false || stripos($statement, 'ADD UNIQUE') !== false || stripos($statement, 'ADD KEY') !== false) && !$schema_index_exists($table_name, $index_name)) {
                $log_migration_issue('Migration index still missing after execution: ' . $table_name . ' -> ' . $index_name . ' for ' . $migration_name . '.');
                $migration_failed = true;
                break;
            }
        }

        if ($migration_failed) {
            continue;
        }

        if (!$migration_has_required_schema($migration_name)) {
            $log_migration_issue('Migration ' . $migration_name . ' finished without the required schema being present.');
            continue;
        }

        $migration_data = [
            'migration_name' => $migration_name,
            'applied_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($migration_record)) {
            $result = $CI->db->where('migration_name', $migration_name)->update($migration_table, [
                'applied_at' => $migration_data['applied_at'],
            ]);
            if ($result === false) {
                $log_migration_issue('Unable to refresh migration marker for ' . $migration_name . '.');
            }
            continue;
        }

        $insert_result = $CI->db->insert($migration_table, $migration_data);
        if ($insert_result === false) {
            $log_migration_issue('Unable to record migration completion for ' . $migration_name . '.');
        }
    }
}

function field_staff_init_menu_items()
{
    if (!function_exists('get_instance')) {
        return;
    }

    $CI = &get_instance();
    if (!$CI) {
        return;
    }

    $is_logged_in = false;
    if (function_exists('is_staff_logged_in')) {
        $is_logged_in = (bool) is_staff_logged_in();
    } elseif (isset($CI->session)) {
        $is_logged_in = (bool) $CI->session->userdata('staff_user_id');
    }

    if (!$is_logged_in) {
        return;
    }

    if (
        isset($CI->app_menu)
        && method_exists($CI->app_menu, 'add_sidebar_menu_item')
        && method_exists($CI->app_menu, 'add_sidebar_children_item')
    ) {
        $CI->app_menu->add_sidebar_menu_item(FS_MODULE_NAME, [
            'name'     => 'Field Staff & Payroll',
            'href'     => field_staff_admin_url('field_staff/attendance'),
            'icon'     => 'fa fa-users',
            'position' => 30,
        ]);

        $CI->app_menu->add_sidebar_children_item(FS_MODULE_NAME, [
            'slug' => 'field_staff_attendance',
            'name' => 'Field Attendance',
            'href' => field_staff_admin_url('field_staff/attendance'),
        ]);

        if (field_staff_can_access_hr_payroll_workspace()) {
            $CI->app_menu->add_sidebar_children_item(FS_MODULE_NAME, [
                'slug' => 'field_staff_payroll',
                'name' => 'Master Payroll HR',
                'href' => field_staff_admin_url('field_staff/payroll'),
            ]);
        }

        if (field_staff_can_manage_settings()) {
            $CI->app_menu->add_sidebar_children_item(FS_MODULE_NAME, [
                'slug' => 'field_staff_hr_settings',
                'name' => 'HR Settings',
                'href' => field_staff_admin_url('field_staff/hr_settings'),
            ]);
        }

        return;
    }

    if (function_exists('add_menu_item')) {
        add_menu_item(FS_MODULE_NAME, [
            'name'     => 'Field Staff & Payroll',
            'href'     => field_staff_admin_url('field_staff/attendance'),
            'icon'     => 'fa fa-users',
            'position' => 30,
        ]);

        if (function_exists('add_submenu_item')) {
            add_submenu_item(FS_MODULE_NAME, [
                'name' => 'Field Attendance',
                'href' => field_staff_admin_url('field_staff/attendance'),
            ]);

            if (field_staff_can_access_hr_payroll_workspace()) {
                add_submenu_item(FS_MODULE_NAME, [
                    'name' => 'Master Payroll HR',
                    'href' => field_staff_admin_url('field_staff/payroll'),
                ]);
            }

            if (field_staff_can_manage_settings()) {
                add_submenu_item(FS_MODULE_NAME, [
                    'name' => 'HR Settings',
                    'href' => field_staff_admin_url('field_staff/hr_settings'),
                ]);
            }
        }
    }
}

function field_staff_inject_assets()
{
    field_staff_inject_css();
    field_staff_inject_js();
}

function field_staff_inject_css()
{
    if (!function_exists('get_instance') || !function_exists('base_url')) {
        return;
    }

    $CI = &get_instance();
    if (!$CI || !isset($CI->uri)) {
        return;
    }

    $segment = (string) $CI->uri->segment(2);
    if ($segment !== 'field_staff') {
        return;
    }

    echo '<link href="' . field_staff_asset_url('css/field_staff.css') . '" rel="stylesheet" type="text/css" />';
}

function field_staff_inject_js()
{
    if (!function_exists('get_instance') || !function_exists('base_url') || !function_exists('json_encode')) {
        return;
    }

    $CI = &get_instance();
    if (!$CI || !isset($CI->uri)) {
        return;
    }

    $segment = (string) $CI->uri->segment(2);
    if ($segment !== 'field_staff') {
        return;
    }

    $script_src = field_staff_asset_url('js/field_staff.js');
    echo '<script>';
    echo '(function(){';
    echo 'var fieldStaffScriptUrls = ' . json_encode([
        $script_src,
        str_replace('/field_staff.js', '/Field_staff.js', $script_src),
        str_replace('/field_staff.js', '/Field_Staff.js', $script_src),
    ]) . ';';
    echo 'var scriptState = window.__field_staff_script_state__ || {loaded:false, loading:false, failed:false, attempts:0, index:0};';
    echo 'window.__field_staff_script_state__ = scriptState;';
    echo 'function loadFieldStaffScript(){';
    echo 'if (scriptState.loaded || scriptState.loading) { return; }';
    echo 'scriptState.loading = true;';
    echo 'var script = document.createElement("script");';
    echo 'script.src = fieldStaffScriptUrls[scriptState.index] || fieldStaffScriptUrls[0];';
    echo 'script.async = false;';
    echo 'script.setAttribute("data-field-staff-module", "true");';
    echo 'script.onload = function(){ scriptState.loaded = true; scriptState.loading = false; scriptState.failed = false; };';
    echo 'script.onerror = function(){ var nextIndex = scriptState.index + 1; if (fieldStaffScriptUrls[nextIndex]) { scriptState.index = nextIndex; scriptState.loading = false; loadFieldStaffScript(); return; } scriptState.loading = false; scriptState.failed = true; if (window.console && console.error) { console.error("Field Staff module script failed to load:", script.src); } };';
    echo 'document.head.appendChild(script);';
    echo '}';
    echo 'function waitForJQuery(){';
    echo 'if (window.jQuery && window.jQuery.fn) { loadFieldStaffScript(); return; }';
    echo 'if (scriptState.attempts >= 60) { scriptState.loading = false; if (window.console && console.error) { console.error("Field Staff module script timed out waiting for jQuery:", fieldStaffScriptUrls[scriptState.index] || fieldStaffScriptUrls[0]); } return; }';
    echo 'scriptState.attempts += 1;';
    echo 'setTimeout(waitForJQuery, 100);';
    echo '}';
    echo 'if (scriptState.failed) { scriptState.failed = false; }';
    echo 'waitForJQuery();';
    echo '}());';
    echo '</script>';
}
