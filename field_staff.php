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
    hooks()->add_action('app_admin_head', 'field_staff_inject_assets');
}

function field_staff_admin_url($path)
{
    if (function_exists('admin_url')) {
        return admin_url($path);
    }

    return $path;
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

    if (!$CI->db->table_exists($migration_table)) {
        $CI->db->query("CREATE TABLE `{$migration_table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration_name` VARCHAR(191) NOT NULL,
            `applied_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_fs_schema_migrations_name` (`migration_name`)
        ) ENGINE=InnoDB;");
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
            "ALTER TABLE `{$payroll_master_table}` ADD COLUMN IF NOT EXISTS `cutoff_id` INT UNSIGNED NULL AFTER `end_date`",
        ],
        '2026_10_14_payroll_group_and_duplicates' => [
            "ALTER TABLE `{$payroll_profiles_table}` ADD COLUMN IF NOT EXISTS `payroll_group` VARCHAR(80) NOT NULL DEFAULT 'default' AFTER `staff_id`",
            "ALTER TABLE `{$payroll_profiles_table}` ADD INDEX IF NOT EXISTS `idx_fs_payroll_profiles_group` (`payroll_group`)",
            "ALTER TABLE `{$payroll_master_table}` ADD UNIQUE INDEX IF NOT EXISTS `uq_fs_payroll_master_staff_cutoff` (`staff_id`,`cutoff_id`)",
        ],
        '2026_10_16_payroll_component_uniqueness' => [
            "ALTER TABLE `{$prefix}fs_payroll_values` ADD UNIQUE INDEX `uq_fs_payroll_values_payroll_attribute` (`payroll_id`, `attribute_id`)",
        ],
        '2026_10_17_employee_classification_fields' => [
            "ALTER TABLE `{$payroll_profiles_table}` ADD COLUMN IF NOT EXISTS `position` VARCHAR(32) NOT NULL DEFAULT 'worker' AFTER `payroll_group`",
            "ALTER TABLE `{$payroll_profiles_table}` ADD COLUMN IF NOT EXISTS `employment_arrangement` VARCHAR(32) NOT NULL DEFAULT 'permanent' AFTER `position`",
            "ALTER TABLE `{$payroll_profiles_table}` ADD COLUMN IF NOT EXISTS `payment_basis` VARCHAR(32) NOT NULL DEFAULT 'hourly' AFTER `employment_arrangement`",
            "ALTER TABLE `{$payroll_profiles_table}` ADD COLUMN IF NOT EXISTS `attendance_requirement` VARCHAR(32) NOT NULL DEFAULT 'required' AFTER `payment_basis`",
        ],
    ];

    foreach ($migrations as $migration_name => $statement_set) {
        $existing = $CI->db
            ->select('id')
            ->from($migration_table)
            ->where('migration_name', $migration_name)
            ->limit(1)
            ->get()
            ->row_array();

        if (is_array($existing) && !empty($existing)) {
            continue;
        }

        foreach ($statement_set as $statement) {
            if (trim((string) $statement) === '') {
                continue;
            }

            if (stripos($statement, 'CREATE TABLE') !== false) {
                $table_name = preg_replace('/^.*`([^`]+)`.*$/', '$1', $statement);
                if ($table_name !== $statement && $CI->db->table_exists($table_name)) {
                    continue;
                }
            }

            if (stripos($statement, 'ALTER TABLE') !== false) {
                if (preg_match('/ADD COLUMN IF NOT EXISTS `?([A-Za-z0-9_]+)`?/i', $statement, $match)) {
                    $table_name = preg_match('/ALTER TABLE `?([^` ]+)`?/i', $statement, $table_match) ? $table_match[1] : '';
                    $column_name = $match[1];
                    if ($table_name !== '' && $CI->db->table_exists($table_name) && $CI->db->field_exists($column_name, $table_name)) {
                        continue;
                    }
                }
            }

            try {
                $CI->db->query($statement);
            } catch (Throwable $e) {
                if (stripos((string) $e->getMessage(), 'already exists') !== false || stripos((string) $e->getMessage(), 'Duplicate') !== false || stripos((string) $e->getMessage(), 'exists') !== false) {
                    continue;
                }

                throw $e;
            }
        }

        $CI->db->insert($migration_table, [
            'migration_name' => $migration_name,
            'applied_at' => date('Y-m-d H:i:s'),
        ]);
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

        if (field_staff_can_manage_hr_workspace()) {
            $CI->app_menu->add_sidebar_children_item(FS_MODULE_NAME, [
                'slug' => 'field_staff_hr_management',
                'name' => 'HR Management Workspace',
                'href' => field_staff_admin_url('field_staff/hr_management'),
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

            if (field_staff_can_manage_hr_workspace()) {
                add_submenu_item(FS_MODULE_NAME, [
                    'name' => 'HR Management Workspace',
                    'href' => field_staff_admin_url('field_staff/hr_management'),
                ]);
            }
        }
    }
}

function field_staff_inject_assets()
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

    echo '<link href="' . base_url('modules/' . FS_MODULE_NAME . '/assets/css/field_staff.css') . '" rel="stylesheet" type="text/css" />';
    echo '<script src="' . base_url('modules/' . FS_MODULE_NAME . '/assets/js/field_staff.js') . '"></script>';
}
