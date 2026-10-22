<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Field_staff extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        // Load field staff data services for module workflows.
        // Schema upgrades are triggered via module activation/upgrade hooks instead of normal page requests.
        $this->load->model('field_staff_model');
    }

    /**
     * Dedicated HR settings workspace for configuration that is not part of operations.
     */
    public function hr_settings()
    {
        $this->enforce_manage_settings_access();

        $staff_ids = $this->field_staff_model->get_hr_payroll_staff_ids();
        $cutoffs = $this->field_staff_model->get_payroll_cutoffs();
        $selected_cutoff_id = (int) $this->input->get('cutoff_id', true);
        $selected_cutoff = $selected_cutoff_id > 0 ? $this->field_staff_model->get_payroll_cutoff_by_id($selected_cutoff_id) : null;
        $staff_directory = $this->field_staff_model->get_staff_directory();
        $department_rows = $this->field_staff_model->get_departments();

        $data['title'] = 'HR Settings';
        $data['staff_directory'] = $staff_directory;
        $data['departments'] = $department_rows;
        $data['hr_payroll_staff_ids'] = $staff_ids;
        $data['current_user_staff_id'] = $this->resolve_session_staff_id();
        $data['cutoffs'] = $cutoffs;
        $data['selected_cutoff'] = $selected_cutoff;
        $data['selected_cutoff_id'] = $selected_cutoff_id;
        $data['save_hr_access_url'] = field_staff_admin_url('field_staff/save_hr_payroll_staff_ids');
        $data['save_cutoff_url'] = field_staff_admin_url('field_staff/save_payroll_cutoff');
        $data['save_hr_settings_section_url'] = field_staff_admin_url('field_staff/save_hr_settings_section');
        $data['current_url'] = field_staff_admin_url('field_staff/hr_settings');
        $data['default_employee_policy'] = [
            'position' => 'worker',
            'employment_arrangement' => 'permanent',
            'payment_basis' => 'hourly',
            'attendance_requirement' => 'required',
            'base_hourly_rate' => '0.00',
            'payroll_group' => 'default',
        ];
        $data['policy_settings'] = [
            'grace_period_mins' => $this->field_staff_model->get_module_setting('field_staff_grace_period_mins', '10'),
            'attendance_rounding' => $this->field_staff_model->get_module_setting('field_staff_attendance_rounding', 'nearest_15'),
            'fixed_start_time_enabled' => $this->field_staff_model->get_module_setting('field_staff_fixed_start_time_enabled', '0'),
            'fixed_start_time' => $this->field_staff_model->get_module_setting('field_staff_fixed_start_time', '07:00'),
            'break_policy' => $this->field_staff_model->get_module_setting('field_staff_break_policy', 'paid'),
            'break_duration_limit_mins' => $this->field_staff_model->get_module_setting('field_staff_break_duration_limit_mins', '60'),
            'regular_hours_cap' => $this->field_staff_model->get_module_setting('field_staff_regular_hours_cap', '44'),
            'workweek_start_day' => $this->field_staff_model->get_module_setting('field_staff_workweek_start_day', '1'),
            'holiday_multiplier' => $this->field_staff_model->get_module_setting('field_staff_holiday_multiplier', ''),
            'overtime_enabled' => $this->field_staff_model->get_module_setting('field_staff_overtime_enabled', '1'),
            'overtime_multiplier' => $this->field_staff_model->get_module_setting('field_staff_overtime_multiplier', '1.5'),
            'daily_field_allowance' => $this->field_staff_model->get_module_setting('field_staff_daily_field_allowance', '0'),
            'employee_nib_rate' => $this->field_staff_model->get_module_setting('field_staff_nib_employee_rate', '5.5'),
            'employer_nib_rate' => $this->field_staff_model->get_module_setting('field_staff_nib_employer_rate', '6.5'),
            'employee_nhip_rate' => $this->field_staff_model->get_module_setting('field_staff_nhip_employee_rate', '3.0'),
            'employer_nhip_rate' => $this->field_staff_model->get_module_setting('field_staff_nhip_employer_rate', '3.0'),
            'verification_status' => $this->field_staff_model->get_module_setting('field_staff_statutory_verification_status', 'pending'),
            'verification_source' => $this->field_staff_model->get_module_setting('field_staff_statutory_verification_source', 'manual'),
        ];

        $this->load->view('hr_settings', $data);
    }

    public function ui_preview()
    {
        $data['title'] = 'Field Staff UI Preview';
        $data['preview_mode'] = true;
        $data['screen_map'] = [
            ['id' => 'my-work', 'label' => 'My Work', 'description' => 'Attendance, leave, and payslip actions'],
            ['id' => 'team-operations', 'label' => 'Team Operations', 'description' => 'Attendance monitor and approval queue'],
            ['id' => 'payroll', 'label' => 'Payroll', 'description' => 'Cutoff review and saved payruns'],
            ['id' => 'reports', 'label' => 'Reports', 'description' => 'Payroll register and exports'],
            ['id' => 'hr-settings', 'label' => 'HR Settings', 'description' => 'Centralized workforce configuration'],
        ];
        $data['employee'] = [
            'name' => 'Maria Thompson',
            'staff_id' => 'FS-2045',
            'role' => 'Field Supervisor',
            'department' => 'Operations',
            'payment_basis' => 'Hourly',
            'position' => 'Supervisor',
            'employment_arrangement' => 'Permanent',
            'attendance_requirement' => 'Required',
        ];
        $data['attendance_rows'] = [
            ['date' => '2026-10-15', 'clock_in' => '07:20', 'clock_out' => '16:40', 'hours' => '9.25', 'status' => 'Approved'],
            ['date' => '2026-10-14', 'clock_in' => '07:15', 'clock_out' => '16:30', 'hours' => '9.00', 'status' => 'Approved'],
            ['date' => '2026-10-13', 'clock_in' => '08:00', 'clock_out' => '17:05', 'hours' => '8.75', 'status' => 'Pending'],
            ['date' => '2026-10-12', 'clock_in' => '07:45', 'clock_out' => '16:15', 'hours' => '8.25', 'status' => 'Review'],
        ];
        $data['leave_rows'] = [
            ['type' => 'Annual Leave', 'dates' => '2026-10-20 to 2026-10-22', 'status' => 'Approved'],
            ['type' => 'Sick Leave', 'dates' => '2026-10-08', 'status' => 'Submitted'],
        ];
        $data['payslip_rows'] = [
            ['cutoff' => 'CW-41', 'net' => '$1,248.60', 'status' => 'Issued'],
            ['cutoff' => 'CW-40', 'net' => '$1,235.40', 'status' => 'Issued'],
        ];
        $data['team_rows'] = [
            ['name' => 'Dylan Brown', 'status' => 'On shift', 'hours' => '7.5', 'location' => 'North Dock'],
            ['name' => 'Ella Harper', 'status' => 'Break', 'hours' => '5.25', 'location' => 'Warehouse B'],
            ['name' => 'Noah Scott', 'status' => 'Late', 'hours' => '3.5', 'location' => 'Plant 2'],
        ];
        $data['payroll_rows'] = [
            ['employee' => 'Maria Thompson', 'base' => '$1,120.00', 'allowance' => '$80.00', 'deductions' => '$52.40', 'net' => '$1,147.60', 'status' => 'Draft'],
            ['employee' => 'Dylan Brown', 'base' => '$980.00', 'allowance' => '$60.00', 'deductions' => '$45.10', 'net' => '$994.90', 'status' => 'Approved'],
            ['employee' => 'Ella Harper', 'base' => '$875.00', 'allowance' => '$40.00', 'deductions' => '$38.25', 'net' => '$876.75', 'status' => 'Awaiting review'],
        ];
        $data['report_rows'] = [
            ['cutoff' => 'CW-41', 'gross' => '$18,420.00', 'employee_nib' => '$640.35', 'employer_nib' => '$786.50', 'net' => '$16,993.15', 'exports' => 'Ready'],
            ['cutoff' => 'CW-40', 'gross' => '$17,860.00', 'employee_nib' => '$610.20', 'employer_nib' => '$750.25', 'net' => '$16,499.55', 'exports' => 'Issued'],
        ];
        $data['settings_sections'] = [
            'Employees',
            'Departments',
            'Attendance & Break Policies',
            'Pay Policies',
            'Payroll Groups & Calendar',
            'Holidays',
            'Statutory Configuration',
            'Access & Approvals',
        ];

        $this->load->view('ui_preview', $data);
    }

    /**
     * Save a centralized HR settings form section.
     */
    public function get_employee_profile()
    {
        if (!$this->can_manage_hr_settings()) {
            $this->respond_unauthorized_json();
        }

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'GET') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $staff_id = (int) $this->input->get('staff_id', true);
        if ($staff_id <= 0) {
            $this->respond_json(['success' => false, 'message' => 'A valid employee is required.']);
        }

        $this->respond_json([
            'success' => true,
            'staff_id' => $staff_id,
            'profile' => $this->field_staff_model->get_payroll_profile($staff_id),
        ]);
    }

    public function save_hr_settings_section()
    {
        if (!$this->can_manage_hr_settings()) {
            $this->respond_unauthorized_json();
        }

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $section = strtolower(trim((string) $this->input->post('section', true)));
        if ($section === '') {
            $this->respond_json(['success' => false, 'message' => 'A settings section is required.']);
        }

        try {
            switch ($section) {
                case 'department':
                    $name = trim((string) $this->input->post('department_name', true));
                    if ($name === '') {
                        $this->respond_json(['success' => false, 'message' => 'Department name is required.']);
                    }
                    $saved = $this->field_staff_model->save_department($name);
                    $this->respond_json([
                        'success' => $saved,
                        'message' => $saved ? 'Department saved successfully.' : 'Department could not be saved.',
                        'departments' => $this->field_staff_model->get_departments(),
                    ]);
                    break;

                case 'employee_profile':
                    $staff_id = (int) $this->input->post('staff_id', true);
                    if ($staff_id <= 0) {
                        $this->respond_json(['success' => false, 'message' => 'A valid employee is required.']);
                    }
                    $saved = $this->field_staff_model->save_payroll_profile($staff_id, [
                        'position' => $this->input->post('position', true),
                        'employment_arrangement' => $this->input->post('employment_arrangement', true),
                        'payment_basis' => $this->input->post('payment_basis', true),
                        'attendance_requirement' => $this->input->post('attendance_requirement', true),
                        'department_id' => $this->input->post('department_id', true),
                        'base_hourly_rate' => $this->input->post('base_hourly_rate', true),
                        'payroll_group' => $this->input->post('payroll_group', true),
                        'payment_method' => $this->input->post('payment_method', true),
                    ]);
                    $this->respond_json([
                        'success' => $saved,
                        'message' => $saved ? 'Employee configuration saved successfully.' : 'Employee configuration could not be saved.',
                        'profile' => $this->field_staff_model->get_payroll_profile($staff_id),
                    ]);
                    break;

                case 'attendance_policy':
                    $settings = [
                        'field_staff_grace_period_mins' => (string) $this->input->post('grace_period_mins', true),
                        'field_staff_attendance_rounding' => (string) $this->input->post('attendance_rounding', true),
                        'field_staff_fixed_start_time_enabled' => (string) $this->input->post('fixed_start_time_enabled', true),
                        'field_staff_fixed_start_time' => (string) $this->input->post('fixed_start_time', true),
                        'field_staff_break_policy' => (string) $this->input->post('break_policy', true),
                        'field_staff_break_duration_limit_mins' => (string) $this->input->post('break_duration_limit_mins', true),
                    ];
                    $saved = true;
                    foreach ($settings as $key => $value) {
                        $saved = $this->field_staff_model->save_module_setting($key, $value) && $saved;
                    }
                    $this->respond_json([
                        'success' => $saved,
                        'message' => $saved ? 'Attendance and break policy saved successfully.' : 'Attendance and break policy could not be saved.',
                    ]);
                    break;

                case 'break_policy':
                    $settings = [
                        'field_staff_break_policy' => (string) $this->input->post('break_policy', true),
                        'field_staff_break_duration_limit_mins' => (string) $this->input->post('break_duration_limit_mins', true),
                    ];
                    $saved = true;
                    foreach ($settings as $key => $value) {
                        $saved = $this->field_staff_model->save_module_setting($key, $value) && $saved;
                    }
                    $this->respond_json([
                        'success' => $saved,
                        'message' => $saved ? 'Break policy saved successfully.' : 'Break policy could not be saved.',
                    ]);
                    break;

                case 'pay_policy':
                    $week_start = (string) $this->input->post('workweek_start_day', true);
                    $weekly_cap = $this->input->post('regular_hours_cap', true);
                    $ot_multiplier = $this->input->post('overtime_multiplier', true);
                    $holiday_multiplier = trim((string) $this->input->post('holiday_multiplier', true));
                    $allowance = $this->input->post('daily_field_allowance', true);
                    if (!preg_match('/^[0-6]$/', $week_start) || !is_numeric($weekly_cap) || (float) $weekly_cap <= 0 || (float) $weekly_cap > 168
                        || !is_numeric($ot_multiplier) || (float) $ot_multiplier < 1
                        || ($holiday_multiplier !== '' && (!is_numeric($holiday_multiplier) || (float) $holiday_multiplier < 1))
                        || !is_numeric($allowance) || (float) $allowance < 0) {
                        $this->respond_json(['success' => false, 'message' => 'Enter a valid workweek, weekly cap, multipliers and non-negative allowance.']);
                    }
                    $settings = [
                        'field_staff_workweek_start_day' => $week_start,
                        'field_staff_holiday_multiplier' => $holiday_multiplier,
                        'field_staff_regular_hours_cap' => (string) $this->input->post('regular_hours_cap', true),
                        'field_staff_overtime_enabled' => (string) $this->input->post('overtime_enabled', true),
                        'field_staff_overtime_multiplier' => (string) $this->input->post('overtime_multiplier', true),
                        'field_staff_daily_field_allowance' => (string) $this->input->post('daily_field_allowance', true),
                    ];
                    $saved = true;
                    foreach ($settings as $key => $value) {
                        $saved = $this->field_staff_model->save_module_setting($key, $value) && $saved;
                    }
                    $this->respond_json([
                        'success' => $saved,
                        'message' => $saved ? 'Pay policy saved successfully.' : 'Pay policy could not be saved.',
                    ]);
                    break;

                case 'statutory_policy':
                    $settings = [
                        'field_staff_nib_employee_rate' => (string) $this->input->post('employee_nib_rate', true),
                        'field_staff_nib_employer_rate' => (string) $this->input->post('employer_nib_rate', true),
                        'field_staff_nhip_employee_rate' => (string) $this->input->post('employee_nhip_rate', true),
                        'field_staff_nhip_employer_rate' => (string) $this->input->post('employer_nhip_rate', true),
                        'field_staff_statutory_verification_status' => (string) $this->input->post('verification_status', true),
                        'field_staff_statutory_verification_source' => (string) $this->input->post('verification_source', true),
                    ];
                    $saved = true;
                    foreach ($settings as $key => $value) {
                        $saved = $this->field_staff_model->save_module_setting($key, $value) && $saved;
                    }
                    $this->respond_json([
                        'success' => $saved,
                        'message' => $saved ? 'Statutory settings saved successfully.' : 'Statutory settings could not be saved.',
                    ]);
                    break;

                default:
                    $this->respond_json(['success' => false, 'message' => 'Unsupported settings section.']);
            }
        } catch (Throwable $e) {
            $this->respond_json(['success' => false, 'message' => 'Settings could not be saved due to a validation error.']);
        }
    }

    /**
     * Attendance dashboard entry point.
     */
    public function attendance()
    {
        $staff_id = $this->resolve_session_staff_id();
        if ($staff_id <= 0) {
            access_denied('field_staff');
        }

        $start_date = trim((string) $this->input->get('start_date', true));
        $end_date = trim((string) $this->input->get('end_date', true));

        if ($start_date !== '' || $end_date !== '') {
            $attendance_records = $this->field_staff_model->get_attendance_history_by_staff($staff_id, $start_date, $end_date, 100);
        } else {
            $attendance_records = $this->field_staff_model->get_recent_attendance_by_staff($staff_id, 20);
        }
        $leave_rows = $this->field_staff_model->get_leave_records([
            'staff_id' => $staff_id,
        ]);
        $payslip_rows = $this->field_staff_model->get_employee_payslips($staff_id, 12);

        $data['title'] = 'Employee Workforce Portal';
        $data['start_date'] = $start_date !== '' ? $start_date : date('Y-m-d', strtotime('-14 days'));
        $data['end_date'] = $end_date !== '' ? $end_date : date('Y-m-d');
        $data['is_clocked_in'] = $this->field_staff_model->has_open_clock($staff_id);
        $data['attendance_records'] = $attendance_records;
        $data['leave_rows'] = $leave_rows;
        $data['payslip_rows'] = $payslip_rows;
        $data['save_leave_request_url'] = field_staff_admin_url('field_staff/save_leave_request');
        $data['get_payslip_statement_url'] = field_staff_admin_url('field_staff/get_employee_payslip_statement');
        $data['download_payslip_url'] = field_staff_admin_url('field_staff/download_payslip_statement');
        $data['clock_action_url'] = field_staff_admin_url('field_staff/clock_action');
        $data['attendance_state_url'] = field_staff_admin_url('field_staff/get_attendance_state');

        $this->load->view('attendance_dashboard', $data);
    }

    /**
     * Unified HR management workspace for profile setup, attendance analytics, and payrun execution.
     */
    public function hr_management()
    {
        if (field_staff_can_manage_settings()) {
            redirect(field_staff_admin_url('field_staff/hr_settings'));
            return;
        }

        $this->enforce_hr_management_access();

        $can_manage_pay_setup = $this->can_access_pay_setup_tab();
        $can_manage_operations = $this->can_access_operations_tab();
        $can_manage_reporting = $this->can_access_reporting_tab();
        $can_manage_project_assignment = $this->can_access_project_assignment_tab();
        $hr_payroll_staff_ids = $this->field_staff_model->get_hr_payroll_staff_ids();
        $current_user_staff_id = $this->resolve_session_staff_id();
        $payroll_cutoffs = $this->field_staff_model->get_payroll_cutoffs();
        $selected_payrun_cutoff_id = (int) $this->input->get_post('payrun_cutoff_id', true);

        list($start_date, $end_date) = $this->resolve_filter_dates();
        $report_date = trim((string) $this->input->get('report_date', true));
        if ($report_date === '') {
            $report_date = date('Y-m-d');
        }

        $report_month = trim((string) $this->input->get('report_month', true));
        if ($report_month === '') {
            $report_month = date('Y-m');
        }

        $selected_department_id = (int) $this->input->get('department_id', true);
        $staff_directory = $this->field_staff_model->get_staff_directory();
        $selected_staff_id = $this->resolve_filtered_staff_id($staff_directory);
        $profile_map = $can_manage_pay_setup ? $this->field_staff_model->get_payroll_profile_map($staff_directory) : [];
        $departments = $this->field_staff_model->get_departments();
        $shifts = $this->field_staff_model->get_shifts();
        $attendance_record_rows = $can_manage_reporting ? $this->field_staff_model->get_attendance_record_report($start_date, $end_date, $selected_staff_id, $selected_department_id) : [];
        $attendance_summary_rows = $can_manage_reporting ? $this->field_staff_model->get_attendance_summary_report($start_date, $end_date, $selected_staff_id, $selected_department_id) : [];
        $daily_attendance_rows = $can_manage_reporting ? $this->field_staff_model->get_daily_attendance_report($report_date, $selected_department_id) : [];
        $monthly_attendance = $can_manage_reporting ? $this->field_staff_model->get_monthly_attendance_report($report_month, $selected_department_id) : ['days' => [], 'rows' => []];
        $department_wise_rows = $can_manage_reporting ? $this->field_staff_model->get_department_wise_report($start_date, $end_date, $selected_department_id) : [];
        $issued_payslip_rows = $can_manage_reporting
            ? $this->field_staff_model->get_issued_payslips($start_date, $end_date, $selected_staff_id, $selected_department_id, 150)
            : [];
        $leave_rows = $can_manage_operations ? $this->field_staff_model->get_leave_records([
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]) : [];
        $project_assignment_rows = $can_manage_project_assignment
            ? $this->field_staff_model->get_project_assignments([
                'start_date' => $start_date,
                'end_date' => $end_date,
                'staff_id' => $selected_staff_id,
            ])
            : [];
        $attendance_summary = $this->build_attendance_tiles($attendance_summary_rows);

        $data['title'] = 'HR Management Workspace';
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['report_date'] = $report_date;
        $data['report_month'] = $report_month;
        $data['staff_directory'] = $staff_directory;
        $data['selected_staff_id'] = $selected_staff_id;
        $data['selected_department_id'] = $selected_department_id;
        $data['profile_map'] = $profile_map;
        $data['departments'] = $departments;
        $data['shifts'] = $shifts;
        $data['selected_profile'] = $selected_staff_id > 0 && isset($profile_map[$selected_staff_id]) ? $profile_map[$selected_staff_id] : [];
        $data['attendance_summary'] = $attendance_summary;
        $data['attendance_record_rows'] = $attendance_record_rows;
        $data['attendance_summary_rows'] = $attendance_summary_rows;
        $data['daily_attendance_rows'] = $daily_attendance_rows;
        $data['monthly_attendance'] = $monthly_attendance;
        $data['department_wise_rows'] = $department_wise_rows;
        $data['issued_payslip_rows'] = $issued_payslip_rows;
        $data['leave_rows'] = $leave_rows;
        $data['project_assignment_rows'] = $project_assignment_rows;
        $data['can_manage_pay_setup'] = $can_manage_pay_setup;
        $data['can_manage_operations'] = $can_manage_operations;
        $data['can_manage_reporting'] = $can_manage_reporting;
        $data['can_manage_project_assignment'] = $can_manage_project_assignment;
        $data['can_manage_hr_payroll_staff_allowlist'] = $this->is_admin_user();
        $data['hr_payroll_staff_ids'] = $hr_payroll_staff_ids;
        $data['current_user_staff_id'] = (int) $current_user_staff_id;
        $data['payroll_cutoffs'] = $payroll_cutoffs;
        $data['selected_payrun_cutoff_id'] = $selected_payrun_cutoff_id;
        $data['default_hr_tab'] = $this->resolve_default_hr_tab($can_manage_pay_setup, $can_manage_operations, $can_manage_reporting, $can_manage_project_assignment);
        $data['export_urls'] = [
            'attendance_record' => field_staff_admin_url('field_staff/export_operations_report?type=attendance_record&' . $this->build_hr_filter_query($start_date, $end_date, $selected_staff_id, $selected_department_id, $report_date, $report_month)),
            'attendance_summary' => field_staff_admin_url('field_staff/export_operations_report?type=attendance_summary&' . $this->build_hr_filter_query($start_date, $end_date, $selected_staff_id, $selected_department_id, $report_date, $report_month)),
            'daily_attendance' => field_staff_admin_url('field_staff/export_operations_report?type=daily_attendance&' . $this->build_hr_filter_query($start_date, $end_date, $selected_staff_id, $selected_department_id, $report_date, $report_month)),
            'monthly_attendance' => field_staff_admin_url('field_staff/export_operations_report?type=monthly_attendance&' . $this->build_hr_filter_query($start_date, $end_date, $selected_staff_id, $selected_department_id, $report_date, $report_month)),
            'department_wise' => field_staff_admin_url('field_staff/export_operations_report?type=department_wise&' . $this->build_hr_filter_query($start_date, $end_date, $selected_staff_id, $selected_department_id, $report_date, $report_month)),
        ];
        $data['save_profile_url'] = field_staff_admin_url('field_staff/save_payroll_profile');
        $data['save_department_url'] = field_staff_admin_url('field_staff/save_department');
        $data['save_shift_url'] = field_staff_admin_url('field_staff/save_shift');
        $data['distribute_shift_url'] = field_staff_admin_url('field_staff/distribute_shift');
        $data['save_manual_attendance_url'] = field_staff_admin_url('field_staff/save_manual_attendance');
        $data['save_leave_url'] = field_staff_admin_url('field_staff/save_leave_record');
        $data['update_leave_status_url'] = field_staff_admin_url('field_staff/update_leave_status');
        $data['generate_payrun_url'] = field_staff_admin_url('field_staff/generate_payrun');
        $data['apply_payrun_url'] = field_staff_admin_url('field_staff/apply_payrun');
        $data['save_project_assignment_url'] = field_staff_admin_url('field_staff/save_project_assignment');
        $data['save_hr_payroll_staff_allowlist_url'] = field_staff_admin_url('field_staff/save_hr_payroll_staff_ids');
        $data['download_payslip_url'] = field_staff_admin_url('field_staff/download_payslip_statement');
        $data['can_access_hr_workspace'] = $this->can_access_hr_workspace();

        $this->load->view('hr_management', $data);
    }

    /**
     * Payroll reporting entry point.
     */
    public function payroll()
    {
        $data = [
            'title' => 'HR Master Payroll Summary',
            'start_date' => '',
            'end_date' => '',
            'selected_cutoff_id' => 0,
            'selected_cutoff' => null,
            'cutoff_options' => [],
            'legacy_rows' => [],
            'payroll_records' => [],
            'payroll_rows' => [],
            'export_csv_url' => field_staff_admin_url('field_staff/export_payroll_register'),
            'print_url' => field_staff_admin_url('field_staff/print_payroll_register'),
            'pdf_url' => field_staff_admin_url('field_staff/download_payroll_register_pdf'),
            'summary_error' => '',
        ];

        $request_id = 'FS-' . strtoupper(substr(hash('sha256', (string) microtime(true) . uniqid('', true)), 0, 12));
        $timings = [
            'request_started_at' => microtime(true),
            'access_check_ms' => null,
            'cutoff_options_ms' => null,
            'selected_cutoff_ms' => null,
            'date_resolution_ms' => null,
            'payroll_rows_ms' => null,
        ];

        try {
            $timer = microtime(true);
            $this->enforce_payroll_access();
            $timings['access_check_ms'] = round((microtime(true) - $timer) * 1000, 2);

            $selected_cutoff_id = (int) $this->input->get_post('cutoff_id', true);

            $timer = microtime(true);
            $cutoff_options = $this->field_staff_model->get_payroll_cutoffs();
            $timings['cutoff_options_ms'] = round((microtime(true) - $timer) * 1000, 2);

            $timer = microtime(true);
            $selected_cutoff = $selected_cutoff_id > 0 ? $this->field_staff_model->get_payroll_cutoff_by_id($selected_cutoff_id) : null;
            $timings['selected_cutoff_ms'] = round((microtime(true) - $timer) * 1000, 2);

            if ($selected_cutoff_id > 0 && empty($selected_cutoff)) {
                throw new RuntimeException('Selected payroll cutoff is missing or invalid.');
            }

            $start_input = trim((string) $this->input->get_post('start_date', true));
            $end_input   = trim((string) $this->input->get_post('end_date', true));

            $today = new DateTimeImmutable('today');

            $timer = microtime(true);
            if ($selected_cutoff_id > 0 && is_array($selected_cutoff)) {
                $start_date = (string) ($selected_cutoff['start_date'] ?? '');
                $end_date = (string) ($selected_cutoff['end_date'] ?? '');
            } elseif ($start_input === '' || $end_input === '') {
                $start_date = $today->modify('monday this week')->format('Y-m-d');
                $end_date   = $today->modify('sunday this week')->format('Y-m-d');
            } else {
                $start_date = $this->normalize_date($start_input) ?: $today->modify('monday this week')->format('Y-m-d');
                $end_date   = $this->normalize_date($end_input) ?: $today->modify('sunday this week')->format('Y-m-d');
            }

            if ($start_date > $end_date) {
                $tmp        = $start_date;
                $start_date = $end_date;
                $end_date   = $tmp;
            }
            $timings['date_resolution_ms'] = round((microtime(true) - $timer) * 1000, 2);

            $data['start_date'] = $start_date;
            $data['end_date'] = $end_date;
            $data['selected_cutoff_id'] = $selected_cutoff_id;
            $data['selected_cutoff'] = $selected_cutoff;
            $data['cutoff_options'] = $cutoff_options;

            if ($selected_cutoff_id <= 0) {
                $data['legacy_rows'] = [];
                $data['payroll_records'] = [];
                $data['payroll_rows'] = [];
                $data['summary_error'] = '';
                $this->load->view('payroll_report', $data);
                return;
            }

            $timer = microtime(true);
            $data['payroll_records'] = $this->field_staff_model->get_cutoff_payroll_rows($selected_cutoff_id, $start_date, $end_date);
            $data['payroll_rows'] = $data['payroll_records'];
            $timings['payroll_rows_ms'] = round((microtime(true) - $timer) * 1000, 2);

            if (!is_array($data['payroll_records'])) {
                throw new RuntimeException('Payroll rows returned an invalid payload.');
            }

            $this->load->view('payroll_report', $data);
        } catch (Throwable $e) {
            $timings['failed_at'] = microtime(true);
            $stack_trace = $e->getTraceAsString();
            error_log(sprintf(
                '[field_staff][payroll_summary][%s] %s | total_ms=%.2f | timings=%s | trace=%s',
                $request_id,
                $e->getMessage(),
                round((microtime(true) - $timings['request_started_at']) * 1000, 2),
                json_encode($timings),
                $stack_trace
            ));

            $data['summary_error'] = 'The payroll summary could not be loaded. Reference ID: ' . $request_id . '. Please retry or select a different cutoff.';
            $data['legacy_rows'] = [];
            $data['payroll_records'] = [];
            $data['payroll_rows'] = [];
            $this->load->view('payroll_report', $data);
        }
    }

    /**
     * Save or update a staff payroll profile for HR administration.
     */
    public function save_payroll_profile()
    {
        $this->require_pay_setup_json();

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json([
                'success' => false,
                'message' => 'Request validation failed.',
            ]);
        }

        $staff_id = (int) $this->input->post('staff_id', true);
        if ($staff_id <= 0) {
            $this->respond_json([
                'success' => false,
                'message' => 'A valid employee selection is required before saving the payroll profile.',
            ]);
        }

        $saved = $this->field_staff_model->save_payroll_profile($staff_id, [
            'position' => $this->input->post('position', true),
            'employment_arrangement' => $this->input->post('employment_arrangement', true),
            'payment_basis' => $this->input->post('payment_basis', true),
            'attendance_requirement' => $this->input->post('attendance_requirement', true),
            'department_id' => $this->input->post('department_id', true),
            'default_shift_id' => $this->input->post('default_shift_id', true),
            'base_hourly_rate' => $this->input->post('base_hourly_rate', true),
            'overtime_multiplier' => $this->input->post('overtime_multiplier', true),
            'daily_field_allowance' => $this->input->post('daily_field_allowance', true),
            'employee_nib_rate' => $this->input->post('employee_nib_rate', true),
            'employer_nib_rate' => $this->input->post('employer_nib_rate', true),
            'employee_nhip_rate' => $this->input->post('employee_nhip_rate', true),
            'vacation_pay' => $this->input->post('vacation_pay', true),
            'outstanding_loan' => $this->input->post('outstanding_loan', true),
            'loan_repayment' => $this->input->post('loan_repayment', true),
            'payment_method' => $this->input->post('payment_method', true),
            'bank_account_info' => $this->input->post('bank_account_info', true),
        ]);

        if (!$saved) {
            $this->respond_json([
                'success' => false,
                'message' => 'The payroll profile could not be saved at this time.',
            ]);
        }

        $this->respond_json([
            'success' => true,
            'message' => 'Payroll profile saved successfully.',
            'profile' => $this->field_staff_model->get_payroll_profile($staff_id),
        ]);
    }

    /**
     * Save a department master row.
     */
    public function save_department()
    {
        $this->require_operations_json();

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $saved = $this->field_staff_model->save_department($this->input->post('name', true));
        $this->respond_json([
            'success' => $saved,
            'message' => $saved ? 'Department saved successfully.' : 'Department could not be saved at this time.',
            'departments' => $this->field_staff_model->get_departments(),
        ]);
    }

    /**
     * Save a shift template row.
     */
    public function save_shift()
    {
        $this->require_operations_json();

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $saved = $this->field_staff_model->save_shift([
            'shift_id' => $this->input->post('shift_id', true),
            'shift_name' => $this->input->post('shift_name', true),
            'start_time' => $this->input->post('start_time', true),
            'end_time' => $this->input->post('end_time', true),
            'grace_period_mins' => $this->input->post('grace_period_mins', true),
        ]);

        $this->respond_json([
            'success' => $saved,
            'message' => $saved ? 'Shift template saved successfully.' : 'Shift template could not be saved at this time.',
            'shifts' => $this->field_staff_model->get_shifts(),
        ]);
    }

    /**
     * Distribute a shift assignment across workers or departments.
     */
    public function distribute_shift()
    {
        $this->require_operations_json();

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $shift_id = (int) $this->input->post('shift_id', true);
        $department_id = (int) $this->input->post('department_id', true);
        $staff_ids = $this->input->post('staff_ids', true);
        $staff_id_list = [];

        if (is_array($staff_ids)) {
            foreach ($staff_ids as $staff_value) {
                $staff_id_list[] = (int) $staff_value;
            }
        } elseif (trim((string) $staff_ids) !== '') {
            foreach (explode(',', (string) $staff_ids) as $staff_value) {
                $staff_id_list[] = (int) trim($staff_value);
            }
        }

        if ($department_id > 0) {
            foreach ($this->field_staff_model->get_staff_directory($department_id) as $staff_row) {
                $staff_id_list[] = (int) $staff_row['staff_id'];
            }
        }

        $staff_id_list = array_values(array_unique(array_filter($staff_id_list)));
        $saved = $this->field_staff_model->distribute_shift(
            $shift_id,
            $staff_id_list,
            trim((string) $this->input->post('start_date', true)),
            trim((string) $this->input->post('end_date', true))
        );

        $this->respond_json([
            'success' => $saved,
            'message' => $saved ? 'Shift distribution completed successfully.' : 'Shift distribution could not be completed.',
        ]);
    }

    /**
     * Save a manual attendance override.
     */
    public function save_manual_attendance()
    {
        $this->require_operations_json();

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $saved = $this->field_staff_model->save_manual_attendance([
            'staff_id' => $this->input->post('staff_id', true),
            'date' => $this->input->post('date', true),
            'clock_in' => $this->input->post('clock_in', true),
            'clock_out' => $this->input->post('clock_out', true),
            'notes' => $this->input->post('notes', true),
        ]);

        $this->respond_json([
            'success' => $saved,
            'message' => $saved ? 'Manual attendance adjustment saved successfully.' : 'Manual attendance adjustment could not be saved.',
        ]);
    }

    /**
     * Save a leave record.
     */
    public function save_leave_record()
    {
        $this->require_operations_json();

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $saved = $this->field_staff_model->save_leave_record([
            'leave_id' => $this->input->post('leave_id', true),
            'staff_id' => $this->input->post('staff_id', true),
            'leave_type' => $this->input->post('leave_type', true),
            'start_date' => $this->input->post('start_date', true),
            'end_date' => $this->input->post('end_date', true),
            'status' => $this->input->post('status', true),
            'reason' => $this->input->post('reason', true),
        ]);

        $this->respond_json([
            'success' => $saved,
            'message' => $saved ? 'Leave record saved successfully.' : 'Leave record could not be saved.',
        ]);
    }

    /**
     * Approve or reject a leave request.
     */
    public function update_leave_status()
    {
        $this->require_operations_json();

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $saved = $this->field_staff_model->update_leave_status(
            (int) $this->input->post('leave_id', true),
            $this->input->post('status', true)
        );

        $this->respond_json([
            'success' => $saved,
            'message' => $saved ? 'Leave status updated successfully.' : 'Leave status update failed.',
        ]);
    }

    /**
     * Submit a self-service leave request from the employee portal.
     */
    public function save_leave_request()
    {
        $staff_id = $this->resolve_session_staff_id();
        if ($staff_id <= 0) {
            $this->respond_unauthorized_json();
        }

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $saved = $this->field_staff_model->save_leave_record([
            'staff_id' => $staff_id,
            'leave_type' => $this->input->post('leave_type', true),
            'start_date' => $this->input->post('start_date', true),
            'end_date' => $this->input->post('end_date', true),
            'status' => 'Pending',
            'reason' => $this->input->post('reason', true),
        ]);

        $this->respond_json([
            'success' => $saved,
            'message' => $saved ? 'Leave request submitted for approval.' : 'Leave request could not be submitted.',
        ]);
    }

    /**
     * Return a secure compiled payslip statement for the current staff member.
     */
    public function get_employee_payslip_statement()
    {
        $payroll_id = (int) $this->input->get_post('payroll_id', true);
        if ($payroll_id <= 0) {
            $this->respond_json(['success' => false, 'message' => 'A valid payslip selection is required.']);
        }

        $viewer_staff_id = $this->resolve_session_staff_id();
        if ($viewer_staff_id <= 0) {
            $this->respond_unauthorized_json();
        }

        $statement = $this->field_staff_model->get_employee_payslip_statement($payroll_id, true, $viewer_staff_id);
        if (empty($statement)) {
            $this->respond_json(['success' => false, 'message' => 'The selected payslip could not be loaded.']);
        }

        $this->respond_json([
            'success' => true,
            'statement' => $statement,
        ]);
    }

    /**
     * Download one payslip statement as an HTML file.
     */
    public function download_payslip_statement()
    {
        $payroll_id = (int) $this->input->get('payroll_id', true);
        if ($payroll_id <= 0) {
            show_404();
        }

        $viewer_staff_id = $this->resolve_session_staff_id();
        if ($viewer_staff_id <= 0) {
            access_denied('field_staff');
        }

        $can_admin_download = $this->can_access_reporting_tab();
        $statement = $this->field_staff_model->get_employee_payslip_statement(
            $payroll_id,
            !$can_admin_download,
            $viewer_staff_id
        );

        if (empty($statement)) {
            show_404();
        }

        $adjustments = isset($statement['adjustments']) && is_array($statement['adjustments']) ? $statement['adjustments'] : [];
        $filename = 'payslip_' . (int) ($statement['staff_id'] ?? 0) . '_' . preg_replace('/[^0-9\-]/', '', (string) ($statement['end_date'] ?? date('Y-m-d'))) . '.html';

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo '<!doctype html><html><head><meta charset="utf-8"><title>Payslip Statement</title>';
        echo '<style>body{font-family:Arial,sans-serif;margin:24px;color:#111}h2{margin:0 0 8px 0}.muted{color:#555}.card{border:1px solid #ddd;border-radius:8px;padding:12px;margin:10px 0}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f4f4f4}</style>';
        echo '</head><body>';
        echo '<h2>Payslip Statement</h2>';
        echo '<p class="muted">Field Staff module by Sherwin Armas</p>';
        echo '<div class="card"><strong>Employee:</strong> ' . htmlspecialchars((string) ($statement['worker_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
        echo '<strong>Staff ID:</strong> ' . (int) ($statement['staff_id'] ?? 0) . '<br>';
        echo '<strong>Period:</strong> ' . htmlspecialchars((string) ($statement['start_date'] ?? ''), ENT_QUOTES, 'UTF-8') . ' to ' . htmlspecialchars((string) ($statement['end_date'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
        echo '<strong>Status:</strong> ' . htmlspecialchars((string) ($statement['status'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
        echo '<strong>Payment Method:</strong> ' . htmlspecialchars((string) ($statement['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8') . '</div>';
        echo '<table><thead><tr><th>Item</th><th>Amount (USD)</th></tr></thead><tbody>';
        echo '<tr><td>Gross Salary</td><td>$' . number_format((float) ($statement['gross_salary'] ?? 0), 2) . '</td></tr>';
        echo '<tr><td>Employee NIB</td><td>$' . number_format((float) ($statement['nib_ee'] ?? 0), 2) . '</td></tr>';
        echo '<tr><td>Employee NHIP</td><td>$' . number_format((float) ($statement['nhip_ee'] ?? 0), 2) . '</td></tr>';
        echo '<tr><td>Commission</td><td>$' . number_format((float) ($adjustments['commission'] ?? 0), 2) . '</td></tr>';
        echo '<tr><td>Loan Adjustment</td><td>$' . number_format((float) ($adjustments['loan_adjustment'] ?? 0), 2) . '</td></tr>';
        echo '<tr><td>Advance</td><td>$' . number_format((float) ($adjustments['advance'] ?? 0), 2) . '</td></tr>';
        echo '<tr><td>Vacation Pay</td><td>$' . number_format((float) ($adjustments['vacation_pay'] ?? 0), 2) . '</td></tr>';
        echo '<tr><td><strong>Net Salary</strong></td><td><strong>$' . number_format((float) ($statement['net_salary'] ?? 0), 2) . '</strong></td></tr>';
        echo '</tbody></table>';
        echo '</body></html>';
        exit;
    }

    /**
     * Build the payroll register rows in the saved-cutoff format used by the report UI and exports.
     */
    private function get_payroll_register_rows_for_period($cutoff_id, $start_date = '', $end_date = '')
    {
        $cutoff_id = (int) $cutoff_id;
        if ($cutoff_id <= 0) {
            return [];
        }

        $cutoff = $this->field_staff_model->get_payroll_cutoff_by_id($cutoff_id);
        if (!is_array($cutoff) || empty($cutoff)) {
            return [];
        }

        $rows = $this->field_staff_model->get_cutoff_payroll_rows($cutoff_id, $start_date, $end_date);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Sum payroll register totals from the saved snapshot-backed values in the row set.
     */
    private function summarize_payroll_register_rows(array $rows)
    {
        $totals = [
            'regular_hours' => 0.0,
            'overtime_hours' => 0.0,
            'hourly_rate' => 0.0,
            'gross_salary' => 0.0,
            'nib_ee' => 0.0,
            'nib_er' => 0.0,
            'nhip_ee' => 0.0,
            'nhip_er' => 0.0,
            'net_salary' => 0.0,
        ];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $totals['regular_hours'] += (float) ($row['regular_hours'] ?? $row['regular_pay'] ?? 0);
            $totals['overtime_hours'] += (float) ($row['overtime_hours'] ?? $row['ot_hours'] ?? 0);
            $totals['hourly_rate'] += (float) ($row['hourly_rate'] ?? 0);
            $totals['gross_salary'] += (float) ($row['gross_salary'] ?? $row['gross_earnings'] ?? 0);
            $totals['nib_ee'] += (float) ($row['nib_ee'] ?? $row['employee_nib'] ?? 0);
            $totals['nib_er'] += (float) ($row['nib_er'] ?? $row['employer_nib'] ?? 0);
            $totals['nhip_ee'] += (float) ($row['nhip_ee'] ?? $row['employee_nhip'] ?? 0);
            $totals['nhip_er'] += (float) ($row['nhip_er'] ?? $row['employer_nhip'] ?? 0);
            $totals['net_salary'] += (float) ($row['net_salary'] ?? 0);
        }

        return $totals;
    }

    /**
     * Render the current payroll register in a print-friendly HTML layout matched to the saved register row set.
     */
    private function render_payroll_register_html($cutoff, array $rows, array $totals)
    {
        $cutoff_label = 'Payroll register';
        if (is_array($cutoff) && !empty($cutoff)) {
            $cutoff_label = trim((string) ($cutoff['cutoff_code'] ?? '')) !== '' ? (string) $cutoff['cutoff_code'] : 'Payroll register';
        }

        $base_rows = '';
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $worker_name = trim((string) ($row['worker_name'] ?? 'Unknown'));
            $regular_hours = (float) ($row['regular_hours'] ?? $row['regular_pay'] ?? 0);
            $overtime_hours = (float) ($row['overtime_hours'] ?? $row['ot_hours'] ?? 0);
            $hourly_rate = (float) ($row['hourly_rate'] ?? 0);
            $gross_salary = (float) ($row['gross_salary'] ?? $row['gross_earnings'] ?? 0);
            $nib_ee = (float) ($row['nib_ee'] ?? $row['employee_nib'] ?? 0);
            $nib_er = (float) ($row['nib_er'] ?? $row['employer_nib'] ?? 0);
            $nhip_ee = (float) ($row['nhip_ee'] ?? $row['employee_nhip'] ?? 0);
            $nhip_er = (float) ($row['nhip_er'] ?? $row['employer_nhip'] ?? 0);
            $net_salary = (float) ($row['net_salary'] ?? 0);

            $base_rows .= '<tr>'
                . '<td>' . htmlspecialchars($worker_name, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td class="text-right">' . number_format($regular_hours, 2) . '</td>'
                . '<td class="text-right">' . number_format($overtime_hours, 2) . '</td>'
                . '<td class="text-right">$' . number_format($hourly_rate, 2) . '</td>'
                . '<td class="text-right">$' . number_format($gross_salary, 2) . '</td>'
                . '<td class="text-right">$' . number_format($nib_ee, 2) . '</td>'
                . '<td class="text-right">$' . number_format($nib_er, 2) . '</td>'
                . '<td class="text-right">$' . number_format($nhip_ee, 2) . '</td>'
                . '<td class="text-right">$' . number_format($nhip_er, 2) . '</td>'
                . '<td class="text-right"><strong>$' . number_format($net_salary, 2) . '</strong></td>'
                . '</tr>';
        }

        if ($base_rows === '') {
            $base_rows = '<tr><td colspan="10" class="text-center">No payroll results for the selected cutoff.</td></tr>';
        }

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($cutoff_label, ENT_QUOTES, 'UTF-8') . '</title>'
            . '<style>body{font-family:Arial,sans-serif;margin:24px;color:#111}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f4f4f4}.text-right{text-align:right}.muted{color:#555}h2{margin-bottom:8px}tfoot td{font-weight:700;background:#fafafa}</style>'
            . '</head><body>'
            . '<h2>' . htmlspecialchars($cutoff_label, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<p class="muted">Saved register totals reflect the cutoff snapshot entries, not ad hoc recalculation.</p>'
            . '<table><thead><tr>'
            . '<th>Worker Name</th><th class="text-right">Regular Hours</th><th class="text-right">Overtime Hours</th><th class="text-right">Hourly Rate</th><th class="text-right">Gross</th><th class="text-right">EE NIB</th><th class="text-right">ER NIB</th><th class="text-right">EE NHIP</th><th class="text-right">ER NHIP</th><th class="text-right">Net</th>'
            . '</tr></thead><tbody>' . $base_rows . '</tbody>'
            . '<tfoot><tr>'
            . '<td>Total</td><td class="text-right">' . number_format((float) ($totals['regular_hours'] ?? 0), 2) . '</td>'
            . '<td class="text-right">' . number_format((float) ($totals['overtime_hours'] ?? 0), 2) . '</td>'
            . '<td class="text-right">$' . number_format((float) ($totals['hourly_rate'] ?? 0), 2) . '</td>'
            . '<td class="text-right">$' . number_format((float) ($totals['gross_salary'] ?? 0), 2) . '</td>'
            . '<td class="text-right">$' . number_format((float) ($totals['nib_ee'] ?? 0), 2) . '</td>'
            . '<td class="text-right">$' . number_format((float) ($totals['nib_er'] ?? 0), 2) . '</td>'
            . '<td class="text-right">$' . number_format((float) ($totals['nhip_ee'] ?? 0), 2) . '</td>'
            . '<td class="text-right">$' . number_format((float) ($totals['nhip_er'] ?? 0), 2) . '</td>'
            . '<td class="text-right">$' . number_format((float) ($totals['net_salary'] ?? 0), 2) . '</td>'
            . '</tr></tfoot></table></body></html>';

        return $html;
    }

    /**
     * Export the selected payroll register as CSV using the saved cutoff snapshot rows as the source of truth.
     */
    public function export_payroll_register()
    {
        if (!$this->can_access_reporting_tab()) {
            access_denied('field_staff');
        }

        $cutoff_id = (int) $this->input->get('cutoff_id', true);
        if ($cutoff_id <= 0) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="field_staff_payroll_register_empty_' . date('Ymd_His') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Worker Name', 'Regular Hours', 'Overtime Hours', 'Hourly Rate', 'Gross', 'EE NIB', 'ER NIB', 'EE NHIP', 'ER NHIP', 'Net']);
            fclose($out);
            exit;
        }

        $start_date = trim((string) $this->input->get('start_date', true));
        $end_date = trim((string) $this->input->get('end_date', true));
        $cutoff = $this->field_staff_model->get_payroll_cutoff_by_id($cutoff_id);
        if (!is_array($cutoff) || empty($cutoff)) {
            show_404();
        }

        $rows = $this->get_payroll_register_rows_for_period($cutoff_id, $start_date, $end_date);
        $totals = $this->summarize_payroll_register_rows($rows);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="field_staff_payroll_register_' . (int) $cutoff_id . '_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Worker Name', 'Regular Hours', 'Overtime Hours', 'Hourly Rate', 'Gross', 'EE NIB', 'ER NIB', 'EE NHIP', 'ER NHIP', 'Net']);
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            fputcsv($output, [
                trim((string) ($row['worker_name'] ?? 'Unknown')),
                (float) ($row['regular_hours'] ?? $row['regular_pay'] ?? 0),
                (float) ($row['overtime_hours'] ?? $row['ot_hours'] ?? 0),
                (float) ($row['hourly_rate'] ?? 0),
                (float) ($row['gross_salary'] ?? $row['gross_earnings'] ?? 0),
                (float) ($row['nib_ee'] ?? $row['employee_nib'] ?? 0),
                (float) ($row['nib_er'] ?? $row['employer_nib'] ?? 0),
                (float) ($row['nhip_ee'] ?? $row['employee_nhip'] ?? 0),
                (float) ($row['nhip_er'] ?? $row['employer_nhip'] ?? 0),
                (float) ($row['net_salary'] ?? 0),
            ]);
        }

        fputcsv($output, ['TOTAL', $totals['regular_hours'], $totals['overtime_hours'], $totals['hourly_rate'], $totals['gross_salary'], $totals['nib_ee'], $totals['nib_er'], $totals['nhip_ee'], $totals['nhip_er'], $totals['net_salary']]);
        fclose($output);
        exit;
    }

    /**
     * Print the selected payroll register using the same saved cutoff rows as the report and CSV export.
     */
    public function print_payroll_register()
    {
        if (!$this->can_access_reporting_tab()) {
            access_denied('field_staff');
        }

        $cutoff_id = (int) $this->input->get('cutoff_id', true);
        $start_date = trim((string) $this->input->get('start_date', true));
        $end_date = trim((string) $this->input->get('end_date', true));
        $cutoff = $cutoff_id > 0 ? $this->field_staff_model->get_payroll_cutoff_by_id($cutoff_id) : null;
        $rows = $this->get_payroll_register_rows_for_period($cutoff_id, $start_date, $end_date);
        $totals = $this->summarize_payroll_register_rows($rows);

        header('Content-Type: text/html; charset=utf-8');
        echo $this->render_payroll_register_html($cutoff, $rows, $totals);
        exit;
    }

    /**
     * Download a best-effort PDF for the selected payroll register from the saved cutoff register rows.
     */
    public function download_payroll_register_pdf()
    {
        if (!$this->can_access_reporting_tab()) {
            access_denied('field_staff');
        }

        $cutoff_id = (int) $this->input->get('cutoff_id', true);
        $start_date = trim((string) $this->input->get('start_date', true));
        $end_date = trim((string) $this->input->get('end_date', true));
        $cutoff = $cutoff_id > 0 ? $this->field_staff_model->get_payroll_cutoff_by_id($cutoff_id) : null;
        $rows = $this->get_payroll_register_rows_for_period($cutoff_id, $start_date, $end_date);
        $totals = $this->summarize_payroll_register_rows($rows);
        $html = $this->render_payroll_register_html($cutoff, $rows, $totals);
        $filename = 'payroll_register_' . ($cutoff_id > 0 ? (int) $cutoff_id : 'all') . '_' . date('Ymd_His');

        if (class_exists('Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
            echo $dompdf->output();
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.html"');
        echo $html;
        exit;
    }

    /**
     * Export the filtered attendance analytics dataset as a CSV file.
     */
    public function export_operations_report()
    {
        $this->require_reporting_json();

        list($start_date, $end_date) = $this->resolve_filter_dates();
        $report_type = strtolower(trim((string) $this->input->get('type', true)));
        $report_date = trim((string) $this->input->get('report_date', true));
        if ($report_date === '') {
            $report_date = date('Y-m-d');
        }
        $report_month = trim((string) $this->input->get('report_month', true));
        if ($report_month === '') {
            $report_month = date('Y-m');
        }
        $selected_department_id = (int) $this->input->get('department_id', true);
        $staff_directory = $this->field_staff_model->get_staff_directory();
        $selected_staff_id = $this->resolve_filtered_staff_id($staff_directory);
        $rows = [];
        $headers = [];

        switch ($report_type) {
            case 'attendance_record':
                $rows = $this->field_staff_model->get_attendance_record_report($start_date, $end_date, $selected_staff_id, $selected_department_id);
                $headers = ['Staff Name', 'Department', 'Date', 'Clock In', 'Clock Out', 'Total Hours', 'Late In Minutes', 'Early Out Minutes', 'Over Work Minutes', 'In GPS', 'Out GPS', 'Notes'];
                break;
            case 'daily_attendance':
                $rows = $this->field_staff_model->get_daily_attendance_report($report_date, $selected_department_id);
                $headers = ['Staff Name', 'Department', 'Status', 'Clock In', 'Clock Out', 'Late In Minutes'];
                break;
            case 'monthly_attendance':
                $monthly = $this->field_staff_model->get_monthly_attendance_report($report_month, $selected_department_id);
                $headers = array_merge(['Staff Name', 'Department'], $monthly['days']);
                foreach ($monthly['rows'] as $monthly_row) {
                    $line = [$monthly_row['worker_name'], $monthly_row['department_name']];
                    foreach ($monthly['days'] as $day_value) {
                        $line[] = isset($monthly_row['days'][$day_value]) ? $monthly_row['days'][$day_value] : 'A';
                    }
                    $rows[] = $line;
                }
                break;
            case 'department_wise':
                $rows = $this->field_staff_model->get_department_wise_report($start_date, $end_date, $selected_department_id);
                $headers = ['Department', 'Headcount', 'Total Regular Hours', 'Total Overtime Hours', 'Total Hours Worked'];
                break;
            case 'attendance_summary':
            default:
                $report_type = 'attendance_summary';
                $rows = $this->field_staff_model->get_attendance_summary_report($start_date, $end_date, $selected_staff_id, $selected_department_id);
                $headers = ['Staff Name', 'Department', 'Total Days Worked', 'Total Late Ins', 'Total Early Outs', 'Total Hours Worked', 'Regular Hours', 'Overtime Hours', 'Total Allowance Due'];
                break;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="field_staff_' . $report_type . '_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            if ($report_type === 'monthly_attendance') {
                fputcsv($output, $row);
                continue;
            }

            if ($report_type === 'attendance_record') {
                fputcsv($output, [
                    $row['worker_name'] ?? '',
                    $row['department_name'] ?? '',
                    $row['date'] ?? '',
                    $row['clock_in'] ?? '',
                    $row['clock_out'] ?? '',
                    $row['total_hours'] ?? 0,
                    $row['late_in_minutes'] ?? 0,
                    $row['early_out_minutes'] ?? 0,
                    $row['over_work_minutes'] ?? 0,
                    trim(($row['in_latitude'] ?? '') . ', ' . ($row['in_longitude'] ?? '')),
                    trim(($row['out_latitude'] ?? '') . ', ' . ($row['out_longitude'] ?? '')),
                    $row['notes'] ?? '',
                ]);
                continue;
            }

            if ($report_type === 'daily_attendance') {
                fputcsv($output, [
                    $row['worker_name'] ?? '',
                    $row['department_name'] ?? '',
                    $row['status'] ?? '',
                    $row['clock_in'] ?? '',
                    $row['clock_out'] ?? '',
                    $row['late_in_minutes'] ?? 0,
                ]);
                continue;
            }

            if ($report_type === 'department_wise') {
                fputcsv($output, [
                    $row['department_name'] ?? '',
                    $row['headcount'] ?? 0,
                    $row['total_regular_hours'] ?? 0,
                    $row['total_overtime_hours'] ?? 0,
                    $row['total_hours_worked'] ?? 0,
                ]);
                continue;
            }

            fputcsv($output, [
                $row['worker_name'] ?? '',
                $row['department_name'] ?? '',
                $row['total_days_worked'] ?? 0,
                $row['total_late_ins'] ?? 0,
                $row['total_early_outs'] ?? 0,
                $row['total_hours_worked'] ?? 0,
                $row['regular_hours'] ?? 0,
                $row['overtime_hours'] ?? 0,
                $row['total_allowance_due'] ?? 0,
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Generate a payrun ledger for the selected HR filter range.
     */
    public function generate_payrun()
    {
        $this->require_reporting_json();

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json([
                'success' => false,
                'message' => 'Request validation failed.',
            ]);
        }

        $cutoff_id = (int) $this->input->post('cutoff_id', true);
        $cutoff = $cutoff_id > 0 ? $this->field_staff_model->get_payroll_cutoff_by_id($cutoff_id) : null;
        if ($cutoff_id <= 0 || !is_array($cutoff)) {
            $this->respond_json([
                'success' => false,
                'message' => 'A valid payroll cutoff is required before generating a payrun.',
            ]);
        }

        $group = trim((string) ($cutoff['payroll_group'] ?? 'default'));
        $start_date = trim((string) ($cutoff['start_date'] ?? ''));
        $end_date = trim((string) ($cutoff['end_date'] ?? ''));
        if ($start_date === '' || $end_date === '') {
            $this->respond_json([
                'success' => false,
                'message' => 'The selected payroll cutoff has no valid date range.',
            ]);
        }

        $selected_department_id = (int) $this->input->post('department_id', true);
        $staff_directory = $this->field_staff_model->get_staff_directory();
        $selected_staff_id = $this->resolve_filtered_staff_id($staff_directory, 'post');
        if ($selected_department_id > 0 && empty($this->field_staff_model->get_staff_directory($selected_department_id))) {
            $this->respond_json(['success' => false, 'message' => 'No staff were found for the selected department.']);
        }

        $eligible_staff_ids = [];
        foreach ($staff_directory as $staff_row) {
            $staff_id = isset($staff_row['staff_id']) ? (int) $staff_row['staff_id'] : 0;
            if ($staff_id <= 0) {
                continue;
            }

            $profile = $this->field_staff_model->get_payroll_profile($staff_id);
            $employee_group = trim((string) ($profile['payroll_group'] ?? 'default'));
            if ($employee_group === '' || $employee_group === $group) {
                $eligible_staff_ids[] = $staff_id;
            }
        }

        if ($selected_staff_id > 0 && !in_array($selected_staff_id, $eligible_staff_ids, true)) {
            $this->respond_json([
                'success' => false,
                'message' => 'The selected employee does not belong to the selected payroll group.',
            ]);
        }

        try {
            $context = $this->read_payroll_adjustment_context();
            if (!empty($context) && $selected_staff_id <= 0) {
                throw new DomainException('Select one employee before previewing explicit payroll adjustments.');
            }
            $statement = $this->field_staff_model->generate_payrun_statement($start_date, $end_date, $selected_staff_id, $selected_department_id, $cutoff, $context);
        } catch (DomainException $e) {
            $this->respond_json(['success' => false, 'message' => $e->getMessage()]);
        }
        $statement['cutoff_id'] = (int) $cutoff_id;
        $statement['cutoff_code'] = trim((string) ($cutoff['cutoff_code'] ?? ''));
        $statement['payroll_group'] = $group;

        $statement['rows'] = array_values(array_filter($statement['rows'], function ($row) use ($group, $selected_staff_id, $selected_department_id) {
            if (!is_array($row)) {
                return false;
            }

            $staff_id = (int) ($row['staff_id'] ?? 0);
            $profile = $this->field_staff_model->get_payroll_profile($staff_id);
            $employee_group = trim((string) ($profile['payroll_group'] ?? 'default'));
            if ($employee_group !== '' && $employee_group !== $group) {
                return false;
            }

            if ($selected_staff_id > 0 && $staff_id !== $selected_staff_id) {
                return false;
            }

            if ($selected_department_id > 0) {
                $department_name = trim((string) ($row['department_name'] ?? ''));
                if ($department_name === '') {
                    return false;
                }
            }

            return true;
        }));

        if (empty($statement['rows'])) {
            $this->respond_json([
                'success' => false,
                'message' => 'No completed attendance records were found for the selected cutoff and payroll group.',
            ]);
        }

        $this->respond_json([
            'success' => true,
            'message' => 'Payrun ledger compiled successfully.',
            'statement' => $statement,
        ]);
    }

    public function save_payroll_work_record()
    {
        if (!$this->can_manage_hr_settings()) {
            $this->respond_unauthorized_json();
        }
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST'
            || (string) $this->input->post('attest_approved', true) !== '1') {
            $this->respond_json(['success' => false, 'message' => 'An authorized approval attestation is required.']);
        }
        $input = [];
        foreach (['staff_id', 'cutoff_id', 'start_date', 'end_date', 'quantity', 'rate', 'reference'] as $key) {
            $input[$key] = $this->input->post($key, true);
        }
        $this->db->trans_begin();
        try {
            if (!$this->lock_payroll_cutoff_for_update((int) $input['cutoff_id'])) {
                throw new DomainException('The selected cutoff could not be locked.');
            }
            if (!$this->field_staff_model->save_approved_payroll_work_record($input, $this->resolve_session_staff_id())
                || $this->db->trans_status() === false) {
                throw new RuntimeException('Work record persistence failed.');
            }
            $this->db->trans_commit();
        } catch (DomainException $e) {
            $this->db->trans_rollback();
            $this->respond_json(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'Field Staff work record save: ' . $e->getMessage());
            $this->respond_json(['success' => false, 'message' => 'Work record could not be saved; the transaction was rolled back.']);
        }
        $this->respond_json(['success' => true, 'message' => 'Approved work record saved with actor and source reference. Recalculate any existing draft payroll.']);
    }

    /**
     * Apply (save/issue) payrun to create individual payslip records in the database.
     */
    public function apply_payrun()
    {
        if (!$this->can_manage_hr_settings()) {
            $this->respond_unauthorized_json();
        }
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json([
                'success' => false,
                'message' => 'Request validation failed.',
            ]);
        }

        $cutoff_id = (int) $this->input->post('cutoff_id', true);
        if ($cutoff_id <= 0) {
            $this->respond_json([
                'success' => false,
                'message' => 'A valid payroll cutoff is required before applying a payrun.',
            ]);
        }

        $selected_staff_ids = $this->extract_requested_staff_ids(
            $this->input->post('employee_ids', true),
            $this->input->post('staff_ids', true),
            $this->input->post('rows', true)
        );

        if (empty($selected_staff_ids)) {
            $this->respond_json([
                'success' => false,
                'message' => 'At least one employee must be selected before applying a payrun.',
            ]);
        }

        if (count($selected_staff_ids) !== count(array_unique($selected_staff_ids))) {
            $this->respond_json([
                'success' => false,
                'message' => 'Duplicate employee selections are not allowed within a payrun request.',
            ]);
        }

        $this->db->trans_begin();
        try {
            $cutoff = $this->lock_payroll_cutoff_for_update($cutoff_id);
            if (!is_array($cutoff)) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'A valid payroll cutoff is required before applying a payrun.',
                ]);
            }

            $group = trim((string) ($cutoff['payroll_group'] ?? 'default'));
            $start_date = trim((string) ($cutoff['start_date'] ?? ''));
            $end_date = trim((string) ($cutoff['end_date'] ?? ''));
            $status = strtolower(trim((string) ($cutoff['status'] ?? 'open')));

            if ($start_date === '' || $end_date === '' || !in_array($status, ['open', 'under_review'], true)) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'Selected cutoff is not editable.',
                ]);
            }

            foreach ($selected_staff_ids as $staff_id) {
                $staff_row = $this->get_staff_row_by_id($staff_id);
                if (empty($staff_row)) {
                    $this->db->trans_rollback();
                    $this->respond_json([
                        'success' => false,
                        'message' => 'One or more selected employees could not be found.',
                    ]);
                }

                $profile = $this->field_staff_model->get_payroll_profile($staff_id);
                $employee_group = trim((string) ($profile['payroll_group'] ?? ''));
                if ($employee_group !== '' && $employee_group !== $group) {
                    $this->db->trans_rollback();
                    $this->respond_json([
                        'success' => false,
                        'message' => 'One or more employees do not belong to the selected payroll group.',
                    ]);
                }
            }

            $statement_rows = [];
            $context = $this->read_payroll_adjustment_context();
            if (!empty($context) && count($selected_staff_ids) !== 1) {
                throw new DomainException('Explicit adjustments require a single selected employee.');
            }
            foreach ($selected_staff_ids as $selected_id) {
                $statement = $this->field_staff_model->generate_payrun_statement($start_date, $end_date, $selected_id, 0, $cutoff, $context);
                if (count($statement['rows']) !== 1) {
                    throw new DomainException('Every selected employee must have one valid payroll calculation.');
                }
                $statement_rows[] = $statement['rows'][0];
            }

            if (empty($statement_rows)) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'No eligible payroll rows were found for the selected employees and cutoff.',
                ]);
            }

            $saved_count = 0;
            foreach ($statement_rows as $row) {
                $staff_id = isset($row['staff_id']) ? (int) $row['staff_id'] : 0;
                if ($staff_id <= 0) {
                    continue;
                }

                $profile = $this->field_staff_model->get_payroll_profile($staff_id, $start_date);
                $payment_method = isset($profile['payment_method']) ? $profile['payment_method'] : 'online_transfer';
                $payload = [
                    'staff_id' => $staff_id,
                    'cutoff_id' => $cutoff_id,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'regular_hours' => round((float) ($row['regular_hours'] ?? 0), 2),
                    'ot_hours' => round((float) ($row['overtime_hours'] ?? 0), 2),
                    'hourly_rate' => round((float) ($row['base_hourly_rate'] ?? 0), 2),
                    'gross_salary' => round((float) ($row['gross_pay'] ?? 0), 2),
                    'nib_ee' => round((float) ($row['nib_ee'] ?? 0), 2),
                    'nib_er' => round((float) ($row['nib_er'] ?? 0), 2),
                    'nhip_ee' => round((float) ($row['nhip_ee'] ?? 0), 2),
                    'nhip_er' => round((float) ($row['nhip_er'] ?? 0), 2),
                    'net_salary' => round((float) ($row['net_pay'] ?? 0), 2),
                    'status' => 'draft',
                    'payment_method' => $payment_method,
                ];

                $existing = $this->db
                    ->select('id, status')
                    ->from(db_prefix() . 'fs_payroll_master')
                    ->where('staff_id', $staff_id)
                    ->where('cutoff_id', $cutoff_id)
                    ->limit(1)
                    ->get()
                    ->row_array();

                if (!empty($existing) && isset($existing['status'])) {
                    $existing_status = strtolower(trim((string) $existing['status']));
                    if (in_array($existing_status, ['approved', 'paid'], true)) {
                        $this->db->trans_rollback();
                        $this->respond_json([
                            'success' => false,
                            'message' => 'Approved or paid payroll cannot be silently overwritten for this cutoff.',
                        ]);
                    }
                    if ($existing_status === 'under_review') {
                        $this->db->trans_rollback();
                        $this->respond_json([
                            'success' => false,
                            'message' => 'Payroll under review cannot be recalculated. Return it to draft through the audited workflow first.',
                        ]);
                    }
                }

                if (!empty($existing) && isset($existing['id'])) {
                    $this->db->where('id', (int) $existing['id']);
                    $saved = (bool) $this->db->update(db_prefix() . 'fs_payroll_master', $payload);
                    $payroll_id = (int) $existing['id'];
                } else {
                    $payload['created_at'] = date('Y-m-d H:i:s');
                    $saved = (bool) $this->db->insert(db_prefix() . 'fs_payroll_master', $payload);
                    $payroll_id = $saved ? (int) $this->db->insert_id() : 0;
                }

                if (!$saved || $payroll_id <= 0) {
                    $this->db->trans_rollback();
                    $this->respond_json([
                        'success' => false,
                        'message' => 'Payrun processing failed during database transaction.',
                    ]);
                }

                $this->persist_payroll_components($payroll_id, $row);

                $snapshot_payload = $this->field_staff_model->build_payroll_snapshot_payload($payroll_id, $payload, $row, $profile, $cutoff);
                if (!$this->field_staff_model->save_payroll_snapshot($payroll_id, 'draft', $snapshot_payload)) {
                    $this->db->trans_rollback();
                    $this->respond_json([
                        'success' => false,
                        'message' => 'Payrun snapshot could not be saved. The transaction was rolled back.',
                    ]);
                }

                $saved_count++;
            }

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'Payrun processing failed during finalization.',
                ]);
            }

            $this->db->trans_commit();
            $this->respond_json([
                'success' => true,
                'message' => sprintf('Payrun applied successfully. %d payroll rows saved.', $saved_count),
                'saved_count' => $saved_count,
            ]);
        } catch (DomainException $e) {
            $this->db->trans_rollback();
            $this->respond_json(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'Field Staff payrun save: ' . $e->getMessage());
            $this->respond_json([
                'success' => false,
                'message' => 'Payrun could not be saved due to a duplicate or database conflict.',
            ]);
        }
    }

    /**
     * Enforce the explicit payroll workflow state transitions.
     */
    public function payroll_workflow_action()
    {
        $this->require_reporting_json();

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json([
                'success' => false,
                'message' => 'Request validation failed.',
            ]);
        }

        $payroll_id = (int) $this->input->post('payroll_id', true);
        if ($payroll_id <= 0) {
            $this->respond_json([
                'success' => false,
                'message' => 'A valid payroll record is required for this action.',
            ]);
        }

        $action = strtolower(trim((string) $this->input->post('action', true)));
        $allowed_actions = ['submit_for_review', 'return_to_draft', 'approve', 'record_payment'];
        if (!in_array($action, $allowed_actions, true)) {
            $this->respond_json([
                'success' => false,
                'message' => 'A supported payroll workflow action is required.',
            ]);
        }

        $actor_staff_id = (int) $this->resolve_session_staff_id();
        $details = [
            'notes' => trim((string) $this->input->post('notes', true)),
            'payment_method' => trim((string) $this->input->post('payment_method', true)),
            'payment_reference' => trim((string) $this->input->post('payment_reference', true)),
            'payment_amount' => $this->input->post('payment_amount', true),
            'payment_date' => trim((string) $this->input->post('payment_date', true)),
        ];

        $this->db->trans_begin();
        try {
            $locked_payroll = $this->lock_payroll_master_for_update($payroll_id);
            if (!is_array($locked_payroll)) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'The selected payroll record could not be locked for update.',
                ]);
            }

            $result = $this->field_staff_model->apply_payroll_workflow_action($payroll_id, $action, $actor_staff_id, $details);
            if (empty($result) || !isset($result['success']) || $result['success'] !== true) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => isset($result['message']) ? (string) $result['message'] : 'Payroll workflow update failed.',
                ]);
            }

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'Payroll workflow update failed during the database transaction.',
                ]);
            }

            $this->db->trans_commit();
            $this->respond_json([
                'success' => true,
                'message' => isset($result['message']) ? (string) $result['message'] : 'Payroll workflow updated successfully.',
                'status' => isset($result['status']) ? (string) $result['status'] : '',
            ]);
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'Field Staff payroll workflow: ' . $e->getMessage());
            $this->respond_json([
                'success' => false,
                'message' => 'Payroll workflow action could not be completed due to a database conflict.',
            ]);
        }
    }

    /**
     * Save project assignment rows for one or multiple staff members.
     */
    public function save_project_assignment() {
        $this->require_project_assignment_json();

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $staff_ids = $this->input->post('staff_ids', true);
        $staff_id_list = [];
        if (is_array($staff_ids)) {
            foreach ($staff_ids as $staff_id) {
                $staff_id_list[] = (int) $staff_id;
            }
        }

        $saved_count = 0;
        $supervisor_id = $this->resolve_session_staff_id();

        foreach (array_values(array_unique(array_filter($staff_id_list))) as $staff_id) {
            $saved = $this->field_staff_model->save_project_assignment([
                'project_name' => $this->input->post('project_name', true),
                'staff_id' => $staff_id,
                'supervisor_id' => $supervisor_id,
                'start_date' => $this->input->post('start_date', true),
                'end_date' => $this->input->post('end_date', true),
                'status' => $this->input->post('status', true),
                'notes' => $this->input->post('notes', true),
            ]);

            if ($saved) {
                $saved_count++;
            }
        }

        if ($saved_count <= 0) {
            $this->respond_json(['success' => false, 'message' => 'Project assignment could not be saved.']);
        }

        $this->respond_json([
            'success' => true,
            'message' => 'Project assignment saved successfully for ' . $saved_count . ' staff member(s).',
        ]);
    }

    /**
     * Save one employee policy override and return the current snapshot.
     */
    public function save_employee_policy_override()
    {
        if (!$this->can_manage_hr_settings()) {
            $this->respond_unauthorized_json();
        }

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $staff_id = (int) $this->input->post('staff_id', true);
        $field_name = strtolower(trim((string) $this->input->post('field_name', true)));

        if ($staff_id <= 0 || $field_name === '') {
            $this->respond_json(['success' => false, 'message' => 'A valid employee and policy field are required.']);
        }

        $saved = $this->field_staff_model->save_employee_policy_override($staff_id, [
            'field_name' => $field_name,
            'value' => $this->input->post('value', true),
            'effective_from' => trim((string) $this->input->post('effective_from', true)),
            'effective_to' => trim((string) $this->input->post('effective_to', true)),
            'source' => strtolower(trim((string) $this->input->post('source', true))),
            'verification_status' => strtolower(trim((string) $this->input->post('verification_status', true))),
            'notes' => trim((string) $this->input->post('notes', true)),
        ]);

        if (!$saved) {
            $this->respond_json(['success' => false, 'message' => 'The employee policy override could not be saved.']);
        }

        $this->respond_json([
            'success' => true,
            'message' => 'Policy override saved successfully.',
            'snapshot' => $this->field_staff_model->get_employee_policy_snapshot($staff_id),
            'history' => $this->field_staff_model->get_employee_policy_overrides($staff_id),
        ]);
    }

    /**
     * Return effective policy records for one employee.
     */
    public function get_employee_policy_overrides()
    {
        if (!$this->can_manage_hr_settings()) {
            $this->respond_unauthorized_json();
        }

        $staff_id = (int) $this->input->get('staff_id', true);
        $as_of_date = trim((string) $this->input->get('as_of_date', true));

        if ($staff_id <= 0) {
            $this->respond_json(['success' => false, 'message' => 'A valid employee is required.']);
        }

        $this->respond_json([
            'success' => true,
            'snapshot' => $this->field_staff_model->get_employee_policy_snapshot($staff_id, $as_of_date),
            'history' => $this->field_staff_model->get_employee_policy_overrides($staff_id, $as_of_date),
        ]);
    }

    /**
     * Create an attendance correction request for review.
     */
    public function save_attendance_correction_request()
    {
        if (!$this->can_manage_hr_settings() && !$this->can_access_operations_tab()) {
            $this->respond_unauthorized_json();
        }

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $payload = [
            'attendance_id' => (int) $this->input->post('attendance_id', true),
            'staff_id' => (int) $this->input->post('staff_id', true),
            'requested_by' => $this->resolve_session_staff_id(),
            'correction_type' => trim((string) $this->input->post('correction_type', true)),
            'before_value' => trim((string) $this->input->post('before_value', true)),
            'after_value' => trim((string) $this->input->post('after_value', true)),
            'reason' => trim((string) $this->input->post('reason', true)),
        ];

        $saved = $this->field_staff_model->save_attendance_correction_request($payload);
        if (!$saved) {
            $this->respond_json(['success' => false, 'message' => 'Attendance correction request could not be created.']);
        }

        $this->respond_json([
            'success' => true,
            'message' => 'Attendance correction request submitted for review.',
            'history' => $this->field_staff_model->get_attendance_correction_history((int) $payload['staff_id']),
        ]);
    }

    /**
     * Approve or reject an attendance correction request.
     */
    public function approve_attendance_correction_request()
    {
        if (!$this->can_manage_hr_settings()) {
            $this->respond_unauthorized_json();
        }

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $request_id = (int) $this->input->post('request_id', true);
        $status = strtolower(trim((string) $this->input->post('status', true)));
        $notes = trim((string) $this->input->post('notes', true));
        $approved_by = $this->resolve_session_staff_id();

        $saved = $this->field_staff_model->approve_attendance_correction($request_id, $approved_by, $status, $notes);
        if (!$saved) {
            $this->respond_json(['success' => false, 'message' => 'Correction request could not be updated.']);
        }

        $this->respond_json([
            'success' => true,
            'message' => 'Correction request updated successfully.',
            'history' => $this->field_staff_model->get_attendance_correction_history(),
        ]);
    }

    /**
     * Get a live status snapshot for the current team.
     */
    public function get_team_monitor()
    {
        if (!$this->can_access_operations_tab()) {
            $this->respond_unauthorized_json();
        }

        $department_id = (int) $this->input->get('department_id', true);
        $date = trim((string) $this->input->get('date', true));

        $this->respond_json([
            'success' => true,
            'team' => $this->field_staff_model->get_live_team_monitor($department_id, $date),
        ]);
    }

    /**
     * Return the current attendance state for the logged-in employee.
     */
    public function get_attendance_state()
    {
        if (!$this->input || !isset($this->input)) {
            $this->respond_json(['success' => false, 'reason' => 'server_error', 'message' => 'Attendance state is unavailable.']);
        }

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'GET') {
            $this->respond_json(['success' => false, 'reason' => 'invalid_request', 'message' => 'Request validation failed.']);
        }

        $staff_id = $this->resolve_session_staff_id();
        if ($staff_id <= 0) {
            $this->respond_json(['success' => false, 'reason' => 'not_authorized', 'message' => 'Session is not active.']);
        }

        $open_attendance = $this->field_staff_model->get_open_attendance_id($staff_id);
        $active_break = $this->field_staff_model->get_active_break_session($staff_id);
        $csrf_name = isset($this->security) && method_exists($this->security, 'get_csrf_token_name') ? (string) $this->security->get_csrf_token_name() : '';
        $csrf_hash = isset($this->security) && method_exists($this->security, 'get_csrf_hash') ? (string) $this->security->get_csrf_hash() : '';

        $is_on_break = is_array($active_break) && !empty($active_break);
        $status = 'clocked_out';
        if ($open_attendance > 0) {
            $status = $is_on_break ? 'on_break' : 'working';
        }

        $this->respond_json([
            'success' => true,
            'state' => [
                'staff_id' => $staff_id,
                'clocked_in' => $open_attendance > 0,
                'on_break' => $is_on_break,
                'status' => $status,
                'break_type' => $is_on_break ? strtolower(trim((string) ($active_break['break_type'] ?? 'paid'))) : null,
                'pending_action' => null,
                'last_updated' => date('c'),
            ],
            'csrf' => $csrf_name !== '' || $csrf_hash !== '' ? ['name' => $csrf_name, 'hash' => $csrf_hash] : null,
        ]);
    }

    /**
     * Save explicit manager/supervisor role ID allowlist.
     */
    public function save_manager_supervisor_roles()
    {
        $this->save_hr_payroll_staff_ids();
    }

    /**
     * Save explicit staff ID allowlist for HR settings and payroll controls.
     */
    public function save_hr_payroll_staff_ids()
    {
        if (!$this->can_manage_hr_settings()) {
            $this->respond_unauthorized_json();
        }

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $staff_ids = [];
        $raw_value = $this->input->post('staff_ids', true);

        if (is_array($raw_value)) {
            foreach ($raw_value as $value) {
                $id = (int) $value;
                if ($id > 0) {
                    $staff_ids[] = $id;
                }
            }
        } else {
            $raw_text = trim((string) $raw_value);
            if ($raw_text !== '') {
                foreach (preg_split('/[^0-9]+/', $raw_text) as $value) {
                    if ($value !== '') {
                        $staff_ids[] = (int) $value;
                    }
                }
            }
        }

        $saved = $this->field_staff_model->save_hr_payroll_staff_ids($staff_ids);
        if (!$saved) {
            $this->respond_json(['success' => false, 'message' => 'HR/payroll staff allowlist could not be saved.']);
        }

        $this->respond_json([
            'success' => true,
            'message' => 'HR/pass payroll staff allowlist updated successfully.',
            'staff_ids' => $this->field_staff_model->get_hr_payroll_staff_ids(),
        ]);
    }

    public function save_payroll_cutoff()
    {
        if (!$this->can_manage_hr_settings()) {
            $this->respond_unauthorized_json();
        }

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->respond_json(['success' => false, 'message' => 'Request validation failed.']);
        }

        $cutoff_id = (int) $this->input->post('id', true);
        $data = [
            'id' => $cutoff_id,
            'cutoff_code' => trim((string) $this->input->post('cutoff_code', true)),
            'payroll_group' => trim((string) $this->input->post('payroll_group', true)),
            'start_date' => trim((string) $this->input->post('start_date', true)),
            'end_date' => trim((string) $this->input->post('end_date', true)),
            'payment_date' => trim((string) $this->input->post('payment_date', true)),
            'status' => strtolower(trim((string) $this->input->post('status', true))),
            'created_by' => $this->resolve_session_staff_id(),
            'updated_by' => $this->resolve_session_staff_id(),
        ];

        $this->db->trans_begin();
        try {
            if ($cutoff_id > 0) {
                $locked_cutoff = $this->lock_payroll_cutoff_for_update($cutoff_id);
                if (!is_array($locked_cutoff)) {
                    $this->db->trans_rollback();
                    $this->respond_json(['success' => false, 'message' => 'The selected payroll cutoff is no longer available.']);
                }
                $data['start_date'] = trim((string) ($locked_cutoff['start_date'] ?? $data['start_date']));
                $data['end_date'] = trim((string) ($locked_cutoff['end_date'] ?? $data['end_date']));
                $data['payroll_group'] = trim((string) ($locked_cutoff['payroll_group'] ?? $data['payroll_group']));
                $status_from_locked_row = strtolower(trim((string) ($locked_cutoff['status'] ?? 'open')));
                if (!in_array($status_from_locked_row, ['open', 'under_review', 'approved', 'closed'], true)) {
                    $status_from_locked_row = 'open';
                }
                if (!in_array($data['status'], ['open', 'under_review', 'approved', 'closed'], true)) {
                    $data['status'] = $status_from_locked_row;
                }
            }

            $saved = $this->field_staff_model->save_payroll_cutoff($data, $data['updated_by']);
            if (!$saved) {
                $this->db->trans_rollback();
                $this->respond_json(['success' => false, 'message' => 'Payroll cutoff could not be saved. Ensure the dates are valid and do not overlap within the same payroll group.']);
            }

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                $this->respond_json(['success' => false, 'message' => 'Payroll cutoff processing failed during finalization.']);
            }

            $this->db->trans_commit();
            $this->respond_json([
                'success' => true,
                'message' => 'Payroll cutoff saved successfully.',
                'cutoff' => $this->field_staff_model->get_payroll_cutoffs(['payroll_group' => $data['payroll_group']]),
            ]);
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            $this->respond_json(['success' => false, 'message' => 'Payroll cutoff could not be saved due to a database conflict.']);
        }
    }

    /**
     * Save, approve, or settle weekly payroll records.
     */
    public function save_payroll()
    {
        $this->require_reporting_json();

        $is_post = strtoupper((string) $this->input->server('REQUEST_METHOD')) === 'POST';
        if (!$is_post) {
            $this->respond_json([
                'success' => false,
                'message' => 'Request validation failed.',
            ]);
        }

        $cutoff_id = (int) $this->input->post('cutoff_id', true);
        if ($cutoff_id <= 0) {
            $this->respond_json([
                'success' => false,
                'message' => 'A valid payroll cutoff is required before saving payroll.',
            ]);
        }

        $staff_id = (int) $this->input->post('staff_id', true);
        if ($staff_id <= 0) {
            $this->respond_json([
                'success' => false,
                'message' => 'Required payroll fields are missing or invalid.',
            ]);
        }

        $status_input = strtolower(trim((string) $this->input->post('status', true)));
        if (in_array($status_input, ['approved', 'paid'], true)) {
            $this->respond_json([
                'success' => false,
                'message' => 'Payroll approval and payment must be recorded through the explicit workflow actions.',
            ]);
        }
        $status = in_array($status_input, ['draft'], true) ? $status_input : 'draft';

        $this->db->trans_begin();
        try {
            $cutoff = $this->lock_payroll_cutoff_for_update($cutoff_id);
            if (!is_array($cutoff)) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'A valid payroll cutoff is required before saving payroll.',
                ]);
            }

            $cutoff_status = strtolower(trim((string) ($cutoff['status'] ?? 'open')));
            $cutoff_group = trim((string) ($cutoff['payroll_group'] ?? 'default'));
            $start_date = trim((string) ($cutoff['start_date'] ?? ''));
            $end_date = trim((string) ($cutoff['end_date'] ?? ''));

            if ($start_date === '' || $end_date === '' || !in_array($cutoff_status, ['open', 'under_review'], true)) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'Selected cutoff is not editable.',
                ]);
            }

            $staff_row = $this->get_staff_row_by_id($staff_id);
            if (empty($staff_row)) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'The selected employee could not be found.',
                ]);
            }

            $payroll_profile = $this->field_staff_model->get_payroll_profile($staff_id, $start_date);
            $employee_group = trim((string) ($payroll_profile['payroll_group'] ?? ''));
            if ($employee_group !== '' && $employee_group !== $cutoff_group) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'Employee does not belong to the selected payroll group.',
                ]);
            }

            $context = $this->read_payroll_adjustment_context();
            $baseline = $this->field_staff_model->calculate_payroll_for_staff($staff_id, $start_date, $end_date, $context);
            $hourly_rate = $baseline['base_hourly_rate'];
            $gross_salary = (float) $baseline['gross_salary'];
            $regular_hours = isset($baseline['regular_hours']) ? (float) $baseline['regular_hours'] : 0.00;
            $ot_hours = isset($baseline['ot_hours']) ? (float) $baseline['ot_hours'] : 0.00;

            $nib_ee = $baseline['nib_ee'];
            $nib_er = $baseline['nib_er'];
            $nhip_ee = $baseline['nhip_ee'];
            $nhip_er = $baseline['nhip_er'];
            $net_salary = $baseline['net_salary'];

            $master_table = db_prefix() . 'fs_payroll_master';
            $payload = [
                'staff_id' => $staff_id,
                'cutoff_id' => $cutoff_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'regular_hours' => round($regular_hours, 2),
                'ot_hours' => round($ot_hours, 2),
                'hourly_rate' => round((float) $hourly_rate, 2),
                'gross_salary' => round($gross_salary, 2),
                'nib_ee' => round($nib_ee, 2),
                'nib_er' => round($nib_er, 2),
                'nhip_ee' => round($nhip_ee, 2),
                'nhip_er' => round($nhip_er, 2),
                'net_salary' => round($net_salary, 2),
                'status' => $status,
                'payment_method' => isset($payroll_profile['payment_method']) ? $payroll_profile['payment_method'] : 'online_transfer',
            ];

            $existing = $this->db
                ->select('id, status')
                ->from($master_table)
                ->where('staff_id', $staff_id)
                ->where('cutoff_id', $cutoff_id)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()
                ->row_array();

            if (!empty($existing) && isset($existing['id'])) {
                $status_value = strtolower(trim((string) ($existing['status'] ?? 'draft')));
                if (in_array($status_value, ['approved', 'paid'], true)) {
                    $this->db->trans_rollback();
                    $this->respond_json([
                        'success' => false,
                        'message' => 'Approved or paid payroll cannot be silently overwritten for this cutoff.',
                    ]);
                }
                if ($status_value === 'under_review') {
                    $this->db->trans_rollback();
                    $this->respond_json([
                        'success' => false,
                        'message' => 'Payroll under review cannot be recalculated. Return it to draft through the audited workflow first.',
                    ]);
                }
                $payroll_id = (int) $existing['id'];
                $this->db->where('id', $payroll_id);
                $saved = (bool) $this->db->update($master_table, $payload);
            } else {
                $payload['created_at'] = date('Y-m-d H:i:s');
                $saved = (bool) $this->db->insert($master_table, $payload);
                $payroll_id = $saved ? (int) $this->db->insert_id() : 0;
            }

            if (!$saved || $payroll_id <= 0) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'Weekly payroll could not be saved at this time.',
                ]);
            }

            $snapshot_row = $baseline;
            $this->persist_payroll_components($payroll_id, $snapshot_row);
            $snapshot_payload = $this->field_staff_model->build_payroll_snapshot_payload($payroll_id, $payload, $snapshot_row, $payroll_profile, $cutoff);
            if (!$this->field_staff_model->save_payroll_snapshot($payroll_id, $status, $snapshot_payload)) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'Payroll snapshot could not be saved. The transaction was rolled back.',
                ]);
            }

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'Weekly payroll processing failed during finalization.',
                ]);
            }

            $this->db->trans_commit();
            $this->respond_json([
                'success' => true,
                'payroll_id' => $payroll_id,
                'message' => 'Weekly payroll has been saved successfully.',
            ]);
        } catch (DomainException $e) {
            $this->db->trans_rollback();
            $this->respond_json(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'Field Staff individual payroll save: ' . $e->getMessage());
            $this->respond_json([
                'success' => false,
                'message' => 'Weekly payroll could not be saved due to a duplicate or database conflict.',
            ]);
        }
    }

    /**
     * Save a holiday date to fs_settings.
     */
    public function save_holiday()
    {
        if (!$this->can_access_pay_setup_tab()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        $this->require_pay_setup_json();

        $holiday_date = trim((string) $this->input->post('date', true));
        $holiday_name = trim((string) $this->input->post('name', true));

        if (!$holiday_date || !$holiday_name) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Holiday date and name are required.']);
            exit;
        }

        if (!$this->field_staff_model->save_holiday($holiday_date, $holiday_name)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to save holiday.']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Holiday saved successfully.']);
        exit;
    }

    /**
     * Delete a holiday from fs_settings.
     */
    public function delete_holiday()
    {
        if (!$this->can_access_pay_setup_tab()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        $this->require_pay_setup_json();

        $holiday_date = trim((string) $this->input->post('date', true));

        if (!$holiday_date) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Holiday date is required.']);
            exit;
        }

        if (!$this->field_staff_model->delete_holiday($holiday_date)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to delete holiday.']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Holiday deleted successfully.']);
        exit;
    }

    /**
     * Get all registered holidays.
     */
    public function get_holidays()
    {
        if (!$this->can_access_pay_setup_tab()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'holidays' => []]);
            exit;
        }

        $holidays = $this->field_staff_model->get_holidays();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'holidays' => $holidays]);
        exit;
    }

    /**
     * Normalize incoming date strings to Y-m-d.
     *
     * @param string $value
     *
     * @return string|null
     */
    private function normalize_date($value)
    {
        try {
            return (new DateTimeImmutable($value))->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Resolve worker display name from available row fields.
     *
     * @param array $staff_row
     * @param int   $worker_id
     *
     * @return string
     */
    private function resolve_worker_name(array $staff_row, $worker_id)
    {
        $first_name = isset($staff_row['firstname']) ? trim((string) $staff_row['firstname']) : '';
        $last_name  = isset($staff_row['lastname']) ? trim((string) $staff_row['lastname']) : '';
        $full_name  = trim($first_name . ' ' . $last_name);

        if ($full_name !== '') {
            return $full_name;
        }

        if (isset($staff_row['name']) && trim((string) $staff_row['name']) !== '') {
            return trim((string) $staff_row['name']);
        }

        return 'Worker #' . (int) $worker_id;
    }

    /**
     * Lock a payroll cutoff row for update while preserving the transaction boundary.
     *
     * @param int $cutoff_id
     *
     * @return array|null
     */
    private function lock_payroll_cutoff_for_update($cutoff_id)
    {
        $cutoff_id = (int) $cutoff_id;
        if ($cutoff_id <= 0) {
            return null;
        }

        $table = db_prefix() . 'fs_payroll_cutoffs';
        $query = $this->db->query('SELECT * FROM ' . $table . ' WHERE id = ' . $cutoff_id . ' FOR UPDATE');
        $row = $query ? $query->row_array() : null;

        return is_array($row) ? $row : null;
    }

    /**
     * Lock a payroll master row for update while preserving the transaction boundary.
     *
     * @param int $payroll_id
     *
     * @return array|null
     */
    private function lock_payroll_master_for_update($payroll_id)
    {
        $payroll_id = (int) $payroll_id;
        if ($payroll_id <= 0) {
            return null;
        }

        $table = db_prefix() . 'fs_payroll_master';
        $query = $this->db->query('SELECT * FROM ' . $table . ' WHERE id = ' . $payroll_id . ' FOR UPDATE');
        $row = $query ? $query->row_array() : null;

        return is_array($row) ? $row : null;
    }

    /**
     * Extract a deduplicated list of employee IDs from request payloads.
     *
     * @param mixed $employee_ids
     * @param mixed $staff_ids
     * @param mixed $rows
     *
     * @return array
     */
    private function extract_requested_staff_ids($employee_ids = null, $staff_ids = null, $rows = null)
    {
        $requested_ids = [];

        foreach ([$employee_ids, $staff_ids] as $input_value) {
            if (is_array($input_value)) {
                foreach ($input_value as $value) {
                    $requested_ids[] = (int) $value;
                }
                continue;
            }

            if (is_string($input_value) && trim($input_value) !== '') {
                foreach (preg_split('/[^0-9]+/', trim((string) $input_value)) as $value) {
                    if ($value !== '') {
                        $requested_ids[] = (int) $value;
                    }
                }
            }
        }

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $staff_id = isset($row['staff_id']) ? (int) $row['staff_id'] : 0;
                if ($staff_id > 0) {
                    $requested_ids[] = $staff_id;
                }
            }
        }

        $requested_ids = array_values(array_filter(array_unique($requested_ids), function ($value) {
            return (int) $value > 0;
        }));

        return $requested_ids;
    }

    /**
     * Persist the payroll component values used to reconstruct the payroll statement.
     *
     * @param int   $payroll_id
     * @param array $row
     *
     * @return bool
     */
    private function read_payroll_adjustment_context()
    {
        $context = [];
        foreach (['commission' => 'commission_pay', 'vacation_pay' => 'vacation_pay', 'loan_adjustment' => 'loan_repayment', 'advance' => 'advance_repayment'] as $input => $component) {
            $value = $this->input->post($input, true);
            if ($value === null || $value === '') {
                continue;
            }
            if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0) {
                throw new DomainException('Payroll adjustments must be explicit non-negative numbers.');
            }
            $context[$component] = round((float) $value, 2);
        }
        return $context;
    }

    private function persist_payroll_components($payroll_id, array $row)
    {
        $payroll_id = (int) $payroll_id;
        if ($payroll_id <= 0) {
            return false;
        }

        $components = [
            'vacation_pay' => (float) ($row['vacation_pay'] ?? 0),
            'commission' => (float) ($row['commission_pay'] ?? 0),
            'loan_adjustment' => (float) ($row['loan_repayment'] ?? 0),
            'advance' => (float) ($row['advance_repayment'] ?? 0),
        ];

        foreach ($components as $attribute_code => $value) {
            if (!$this->field_staff_model->add_eav_payroll_value($payroll_id, $attribute_code, $value)) {
                throw new RuntimeException('Payroll component could not be persisted: ' . $attribute_code);
            }
        }

        return true;
    }

    /**
     * Resolve hourly rate using row fields with a safe default fallback.
     *
     * @param array $staff_row
     *
     * @return float
     */
    private function resolve_hourly_rate(array $staff_row)
    {
        $candidates = ['hourly_rate', 'default_hourly_rate', 'rate'];

        foreach ($candidates as $key) {
            if (isset($staff_row[$key]) && is_numeric($staff_row[$key])) {
                $value = (float) $staff_row[$key];
                if ($value >= 0) {
                    return $value;
                }
            }
        }

        return 0.00;
    }

    /**
     * Retrieve a worker row for payroll rate resolution.
     *
     * @param int $staff_id
     *
     * @return array
     */
    private function get_staff_row_by_id($staff_id)
    {
        $staff_table = db_prefix() . 'staff';
        $staff_id = (int) $staff_id;

        if ($staff_id <= 0) {
            return [];
        }

        $query = $this->db->from($staff_table);
        if ($this->db->field_exists('staffid', $staff_table)) {
            $query->where('staffid', $staff_id);
        } else {
            $query->where('id', $staff_id);
        }

        $row = $query->limit(1)->get()->row_array();

        return is_array($row) ? $row : [];
    }

    /**
     * Handle attendance clock in/out requests from the field dashboard.
     */
    public function clock_action()
    {
        $reference_id = 'fs-' . bin2hex(random_bytes(6));

        try {
            $is_post = strtoupper((string) $this->input->server('REQUEST_METHOD')) === 'POST';

            if (!$is_post) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'reason' => 'invalid_request',
                    'message' => 'Request validation failed.',
                ]);
                exit;
            }

            $action = strtolower(trim((string) $this->input->post('action', true)));
            $allowed_actions = ['clock_in', 'clock_out', 'break_out', 'break_in'];

            $latitude  = trim((string) $this->input->post('latitude', true));
            $longitude = trim((string) $this->input->post('longitude', true));
            $notes     = trim((string) $this->input->post('notes', true));
            $break_type = strtolower(trim((string) $this->input->post('break_type', true)));
            $staff_id  = $this->resolve_session_staff_id();

            if ($staff_id <= 0) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'reason' => 'not_authorized',
                    'message' => 'Session is not active.',
                ]);
                exit;
            }

            $can_manage_attendance = $this->can_access_operations_tab() || $this->can_manage_hr_settings();
            if (!$can_manage_attendance && !isset($this->session)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'reason' => 'not_authorized',
                    'message' => 'You do not have permission to record attendance.',
                ]);
                exit;
            }

            if (!in_array($action, $allowed_actions, true)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'reason' => 'invalid_action',
                    'message' => 'The selected attendance action is not supported.',
                ]);
                exit;
            }

            if (!is_numeric($latitude) || !is_numeric($longitude)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'reason' => 'missing_location',
                    'message' => 'Location details are required to record attendance.',
                ]);
                exit;
            }

            $transition_valid = false;
            switch ($action) {
                case 'clock_in':
                    $transition_valid = $this->field_staff_model->validate_attendance_transition($staff_id, 'clock_in');
                    break;
                case 'clock_out':
                    $transition_valid = $this->field_staff_model->validate_attendance_transition($staff_id, 'clock_out');
                    break;
                case 'break_out':
                    $transition_valid = $this->field_staff_model->validate_break_transition($staff_id, 'break_out');
                    break;
                case 'break_in':
                    $transition_valid = $this->field_staff_model->validate_break_transition($staff_id, 'break_in');
                    break;
                default:
                    $transition_valid = false;
                    break;
            }

            if (!$transition_valid) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'reason' => 'invalid_transition',
                    'message' => 'The current attendance state does not allow that action. Refresh to confirm the latest status before retrying.',
                ]);
                exit;
            }

            $latitude  = (float) $latitude;
            $longitude = (float) $longitude;

            $saved = (bool) $this->field_staff_model->record_clock_action(
                $staff_id,
                $action,
                $latitude,
                $longitude,
                $notes,
                in_array($break_type, ['paid', 'unpaid'], true) ? $break_type : 'paid'
            );

            if ($saved) {
                $latest_record = $this->field_staff_model->get_latest_attendance_by_staff($staff_id);

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Attendance recorded successfully',
                    'record' => $latest_record,
                ]);
                exit;
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'reason' => 'server_error',
                'message' => 'The attendance action could not be completed. Please refresh and verify the current status before retrying.',
                'reference_id' => $reference_id,
            ]);
            exit;
        } catch (Throwable $e) {
            error_log('Field Staff attendance action failed [' . $reference_id . ']: ' . $e->getMessage());
            if (method_exists($e, 'getTraceAsString')) {
                error_log($e->getTraceAsString());
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'reason' => 'server_error',
                'message' => 'The attendance action could not be completed. Please refresh and verify the current status before retrying.',
                'reference_id' => $reference_id,
            ]);
            exit;
        }
    }

    /**
     * Resolve logged-in staff ID with helper and session fallbacks.
     *
     * @return int
     */
    private function resolve_session_staff_id()
    {
        if (function_exists('get_staff_user_id')) {
            $id = (int) get_staff_user_id();
            if ($id > 0) {
                return $id;
            }
        }

        if (isset($this->session)) {
            $id = (int) $this->session->userdata('staff_user_id');
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    /**
     * Restrict HR management workflow access to administrators and global field staff supervisors.
     */
    private function enforce_hr_management_access()
    {
        if (!$this->can_access_hr_workspace()) {
            access_denied('field_staff');
        }
    }

    private function enforce_payroll_access()
    {
        if (!$this->can_access_hr_payroll_workspace()) {
            access_denied('field_staff');
        }
    }

    private function enforce_pay_setup_access()
    {
        if (!$this->can_access_pay_setup_tab()) {
            access_denied('field_staff');
        }
    }

    private function enforce_operations_access()
    {
        if (!$this->can_access_operations_tab()) {
            access_denied('field_staff');
        }
    }

    private function enforce_reporting_access()
    {
        if (!$this->can_access_reporting_tab()) {
            access_denied('field_staff');
        }
    }

    private function enforce_manage_settings_access()
    {
        if (!$this->can_manage_hr_settings()) {
            access_denied('field_staff');
        }
    }

    private function enforce_project_assignment_access()
    {
        if (!$this->can_access_project_assignment_tab()) {
            access_denied('field_staff');
        }
    }

    private function require_hr_workspace_json()
    {
        if (!$this->can_access_hr_workspace()) {
            $this->respond_unauthorized_json();
        }
    }

    private function require_pay_setup_json()
    {
        if (!$this->can_access_pay_setup_tab()) {
            $this->respond_unauthorized_json();
        }
    }

    private function require_operations_json()
    {
        if (!$this->can_access_operations_tab()) {
            $this->respond_unauthorized_json();
        }
    }

    private function require_reporting_json()
    {
        if (!$this->can_access_reporting_tab()) {
            $this->respond_unauthorized_json();
        }
    }

    private function require_project_assignment_json()
    {
        if (!$this->can_access_project_assignment_tab()) {
            $this->respond_unauthorized_json();
        }
    }

    private function can_manage_hr_settings()
    {
        if (function_exists('field_staff_can_manage_settings')) {
            return (bool) field_staff_can_manage_settings();
        }

        if ($this->is_admin_user()) {
            return true;
        }

        if (function_exists('has_permission') && has_permission('field_staff', '', 'manage_settings')) {
            return true;
        }

        $staff_id = $this->resolve_session_staff_id();
        if ($staff_id <= 0) {
            return false;
        }

        $allowed_staff_ids = $this->field_staff_model->get_hr_payroll_staff_ids();
        return !empty($allowed_staff_ids) && in_array($staff_id, $allowed_staff_ids, true);
    }

    private function can_access_hr_workspace()
    {
        return $this->can_manage_hr_settings();
    }

    private function can_access_hr_payroll_workspace()
    {
        return $this->can_manage_hr_settings();
    }

    private function can_access_pay_setup_tab()
    {
        return $this->can_manage_hr_settings();
    }

    private function can_access_operations_tab()
    {
        return $this->can_manage_hr_settings();
    }

    private function can_access_reporting_tab()
    {
        return $this->can_manage_hr_settings();
    }

    private function can_access_project_assignment_tab()
    {
        return $this->can_manage_hr_settings();
    }

    private function respond_unauthorized_json($message = 'Authorization denied.')
    {
        http_response_code(403);
        $this->respond_json([
            'success' => false,
            'message' => $message,
        ]);
    }

    private function is_admin_user()
    {
        return function_exists('is_admin') ? (bool) is_admin() : false;
    }

    private function has_module_permission($capability)
    {
        if (!function_exists('has_permission')) {
            return false;
        }

        return (bool) has_permission('field_staff', '', $capability);
    }

    private function is_hr_payroll_allowed_staff()
    {
        $staff_id = $this->resolve_session_staff_id();
        if ($staff_id <= 0) {
            return false;
        }

        return in_array($staff_id, $this->field_staff_model->get_hr_payroll_staff_ids(), true);
    }

    private function is_manager_or_supervisor()
    {
        return $this->can_manage_hr_settings();
    }

    private function resolve_default_hr_tab($can_manage_pay_setup, $can_manage_operations, $can_manage_reporting, $can_manage_project_assignment)
    {
        $requested_tab = strtolower(trim((string) $this->input->get('tab', true)));
        $allowed_tabs = [];

        if ($can_manage_operations) {
            $allowed_tabs[] = 'shift_setup';
            $allowed_tabs[] = 'manual_attendance';
            $allowed_tabs[] = 'leave_tracking';
        }

        if ($can_manage_pay_setup) {
            $allowed_tabs[] = 'pay_setup';
        }

        if ($can_manage_reporting) {
            $allowed_tabs[] = 'reporting_payrun';
        }

        if ($can_manage_project_assignment) {
            $allowed_tabs[] = 'project_assignment';
        }

        if ($requested_tab === 'operations') {
            $requested_tab = 'shift_setup';
        } elseif ($requested_tab === 'reporting') {
            $requested_tab = 'reporting_payrun';
        }

        if ($requested_tab !== '' && in_array($requested_tab, $allowed_tabs, true)) {
            return $requested_tab;
        }

        return !empty($allowed_tabs) ? $allowed_tabs[0] : 'pay_setup';
    }

    private function get_current_user_role_context()
    {
        $staff_id = $this->resolve_session_staff_id();
        if ($staff_id <= 0) {
            return ['staff_id' => 0, 'allowlisted' => false];
        }

        return [
            'staff_id' => $staff_id,
            'allowlisted' => $this->is_hr_payroll_allowed_staff(),
        ];
    }

    /**
     * Keep the workforce operations schema available for already-activated module installs.
     */
    private function ensure_operations_schema()
    {
        $prefix = db_prefix();
        $profile_table = $prefix . 'fs_payroll_profiles';
        $departments_table = $prefix . 'fs_departments';
        $shifts_table = $prefix . 'fs_shifts';
        $shift_distributions_table = $prefix . 'fs_shift_distributions';
        $leaves_table = $prefix . 'fs_leaves';
        $project_assignments_table = $prefix . 'fs_project_assignments';
        $settings_table = $prefix . 'fs_settings';
        $cutoffs_table = $prefix . 'fs_payroll_cutoffs';

        $this->ensure_table($cutoffs_table, "CREATE TABLE `{$cutoffs_table}` (
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
        ) ENGINE=InnoDB;");

        $this->ensure_table($profile_table, "CREATE TABLE `{$profile_table}` (
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

        $this->ensure_table($departments_table, "CREATE TABLE `{$departments_table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(191) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_fs_departments_name` (`name`)
        ) ENGINE=InnoDB;");

        $this->ensure_table($shifts_table, "CREATE TABLE `{$shifts_table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `shift_name` VARCHAR(191) NOT NULL,
            `start_time` TIME NOT NULL,
            `end_time` TIME NOT NULL,
            `grace_period_mins` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB;");

        $this->ensure_table($shift_distributions_table, "CREATE TABLE `{$shift_distributions_table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `staff_id` INT UNSIGNED NOT NULL,
            `shift_id` INT UNSIGNED NOT NULL,
            `date` DATE NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_fs_shift_distributions_staff_date` (`staff_id`,`date`),
            KEY `idx_fs_shift_distributions_shift_id` (`shift_id`)
        ) ENGINE=InnoDB;");

        $this->ensure_table($leaves_table, "CREATE TABLE `{$leaves_table}` (
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

        $this->ensure_table($project_assignments_table, "CREATE TABLE `{$project_assignments_table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `project_name` VARCHAR(191) NOT NULL,
            `staff_id` INT UNSIGNED NOT NULL,
            `supervisor_id` INT UNSIGNED NULL,
            `start_date` DATE NOT NULL,
            `end_date` DATE NULL,
            `status` ENUM('Planned','Active','Completed','On Hold') NOT NULL DEFAULT 'Planned',
            `notes` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_fs_project_assignments_staff_id` (`staff_id`),
            KEY `idx_fs_project_assignments_supervisor_id` (`supervisor_id`),
            KEY `idx_fs_project_assignments_dates` (`start_date`,`end_date`)
        ) ENGINE=InnoDB;");

        $this->ensure_table($settings_table, "CREATE TABLE `{$settings_table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `setting_key` VARCHAR(191) NOT NULL,
            `setting_value` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_fs_settings_key` (`setting_key`)
        ) ENGINE=InnoDB;");

        $this->ensure_column($profile_table, 'department_id', 'INT UNSIGNED NULL AFTER `staff_id`');
        $this->ensure_column($profile_table, 'default_shift_id', 'INT UNSIGNED NULL AFTER `department_id`');
        $this->ensure_column($profile_table, 'employee_nib_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 5.50 AFTER `daily_field_allowance`');
        $this->ensure_column($profile_table, 'employer_nib_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 6.50 AFTER `employee_nib_rate`');
        $this->ensure_column($profile_table, 'employee_nhip_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 3.00 AFTER `employer_nib_rate`');
        $this->ensure_column($profile_table, 'vacation_pay', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `employee_nhip_rate`');
        $this->ensure_column($profile_table, 'outstanding_loan', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `vacation_pay`');
        $this->ensure_column($profile_table, 'loan_repayment', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `outstanding_loan`');
        $this->ensure_column($master_table, 'cutoff_id', 'INT UNSIGNED NULL AFTER `end_date`');
    }

    private function ensure_table($table_name, $create_sql)
    {
        if ($this->db->table_exists($table_name)) {
            return;
        }

        $this->db->query($create_sql);
    }

    private function ensure_column($table_name, $column_name, $definition)
    {
        if (!$this->db->table_exists($table_name) || $this->db->field_exists($column_name, $table_name)) {
            return;
        }

        $this->db->query("ALTER TABLE `{$table_name}` ADD COLUMN `{$column_name}` {$definition}");
    }

    /**
     * Resolve sanitized filter period inputs.
     *
     * @param string $source
     *
     * @return array
     */
    private function resolve_filter_dates($source = 'get')
    {
        $today = new DateTimeImmutable('today');
        $default_start = $today->modify('monday this week')->format('Y-m-d');
        $default_end = $today->modify('sunday this week')->format('Y-m-d');

        if ($source === 'post') {
            $start_input = trim((string) $this->input->post('start_date', true));
            $end_input = trim((string) $this->input->post('end_date', true));
        } else {
            $start_input = trim((string) $this->input->get('start_date', true));
            $end_input = trim((string) $this->input->get('end_date', true));
        }

        $start_date = $start_input !== '' ? $this->normalize_date($start_input) : $default_start;
        $end_date = $end_input !== '' ? $this->normalize_date($end_input) : $default_end;

        if ($start_date === null) {
            $start_date = $default_start;
        }

        if ($end_date === null) {
            $end_date = $default_end;
        }

        if ($start_date > $end_date) {
            $temp = $start_date;
            $start_date = $end_date;
            $end_date = $temp;
        }

        return [$start_date, $end_date];
    }

    /**
     * Resolve a valid staff filter against the current staff directory.
     *
     * @param array  $staff_directory
     * @param string $source
     *
     * @return int
     */
    private function resolve_filtered_staff_id(array $staff_directory, $source = 'get')
    {
        $selected_staff_id = $source === 'post'
            ? (int) $this->input->post('staff_id', true)
            : (int) $this->input->get('staff_id', true);

        if ($selected_staff_id <= 0) {
            return 0;
        }

        foreach ($staff_directory as $staff_row) {
            if (isset($staff_row['staff_id']) && (int) $staff_row['staff_id'] === $selected_staff_id) {
                return $selected_staff_id;
            }
        }

        return 0;
    }

    /**
     * Build a stable HR filter query string.
     *
     * @param string $start_date
     * @param string $end_date
     * @param int    $staff_id
     *
     * @return string
     */
    private function build_hr_filter_query($start_date, $end_date, $staff_id, $department_id = 0, $report_date = '', $report_month = '')
    {
        $query = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        if ((int) $staff_id > 0) {
            $query['staff_id'] = (int) $staff_id;
        }

        if ((int) $department_id > 0) {
            $query['department_id'] = (int) $department_id;
        }

        if ($report_date !== '') {
            $query['report_date'] = $report_date;
        }

        if ($report_month !== '') {
            $query['report_month'] = $report_month;
        }

        return http_build_query($query);
    }

    private function build_attendance_tiles(array $attendance_summary_rows)
    {
        $tiles = [
            'total_hours_worked' => 0.0,
            'regular_hours' => 0.0,
            'overtime_hours' => 0.0,
            'total_allowance_due' => 0.0,
        ];

        foreach ($attendance_summary_rows as $row) {
            $tiles['total_hours_worked'] += (float) ($row['total_hours_worked'] ?? 0);
            $tiles['regular_hours'] += (float) ($row['regular_hours'] ?? 0);
            $tiles['overtime_hours'] += (float) ($row['overtime_hours'] ?? 0);
            $tiles['total_allowance_due'] += (float) ($row['total_allowance_due'] ?? 0);
        }

        foreach ($tiles as $key => $value) {
            $tiles[$key] = round((float) $value, 2);
        }

        return $tiles;
    }

    /**
     * Emit a JSON response and terminate the request.
     *
     * @param array $payload
     */
    private function respond_json(array $payload)
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}
