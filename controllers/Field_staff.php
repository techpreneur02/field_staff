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

        $data['title'] = 'HR Settings';
        $data['staff_directory'] = $staff_directory;
        $data['hr_payroll_staff_ids'] = $staff_ids;
        $data['current_user_staff_id'] = $this->resolve_session_staff_id();
        $data['cutoffs'] = $cutoffs;
        $data['selected_cutoff'] = $selected_cutoff;
        $data['selected_cutoff_id'] = $selected_cutoff_id;
        $data['save_hr_access_url'] = field_staff_admin_url('field_staff/save_hr_payroll_staff_ids');
        $data['save_cutoff_url'] = field_staff_admin_url('field_staff/save_payroll_cutoff');
        $data['current_url'] = field_staff_admin_url('field_staff/hr_settings');

        $this->load->view('hr_settings', $data);
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

        $this->load->view('attendance_dashboard', $data);
    }

    /**
     * Unified HR management workspace for profile setup, attendance analytics, and payrun execution.
     */
    public function hr_management()
    {
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
        $this->enforce_payroll_access();

        $selected_cutoff_id = (int) $this->input->get_post('cutoff_id', true);
        $cutoff_options = $this->field_staff_model->get_payroll_cutoffs();
        $selected_cutoff = $selected_cutoff_id > 0 ? $this->field_staff_model->get_payroll_cutoff_by_id($selected_cutoff_id) : null;

        if ($selected_cutoff_id > 0 && empty($selected_cutoff)) {
            show_error('Invalid payroll cutoff selected.', 400);
        }

        $start_input = trim((string) $this->input->get_post('start_date', true));
        $end_input   = trim((string) $this->input->get_post('end_date', true));

        $today = new DateTimeImmutable('today');

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

        $staff_table = db_prefix() . 'staff';
        $master_table = db_prefix() . 'fs_payroll_master';

        $this->db->from($staff_table);
        if ($this->db->field_exists('active', $staff_table)) {
            $this->db->where('active', 1);
        }

        $staff_rows = $this->db->get()->result_array();
        $payroll_records = [];
        $legacy_rows = [];

        foreach ($staff_rows as $staff_row) {
            $worker_id = isset($staff_row['staffid']) ? (int) $staff_row['staffid'] : 0;
            if ($worker_id <= 0 && isset($staff_row['id'])) {
                $worker_id = (int) $staff_row['id'];
            }

            if ($worker_id <= 0) {
                continue;
            }

            $hourly_rate = $this->resolve_hourly_rate($staff_row);
            $calculation = $this->field_staff_model->calculate_weekly_payroll(
                $worker_id,
                $start_date,
                $end_date,
                $hourly_rate
            );

            $status = 'draft';
            $master_record = $this->db
                ->select('status, cutoff_id')
                ->from($master_table)
                ->where('staff_id', $worker_id)
                ->where('start_date', $start_date)
                ->where('end_date', $end_date)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()
                ->row_array();

            if (!empty($master_record) && isset($master_record['status'])) {
                $status = (string) $master_record['status'];
            }

            $calculation['worker_name'] = $this->resolve_worker_name($staff_row, $worker_id);
            $calculation['hourly_rate'] = round((float) $hourly_rate, 2);
            $calculation['status']      = in_array($status, ['draft', 'approved', 'paid'], true) ? $status : 'draft';

            if ($selected_cutoff_id > 0) {
                $calculation['cutoff_id'] = $selected_cutoff_id;
                $calculation['cutoff_code'] = (string) ($selected_cutoff['cutoff_code'] ?? '');
                $payroll_records[] = $calculation;
            } elseif ($this->field_staff_model->has_legacy_payroll_for_window($worker_id, $start_date, $end_date)) {
                $legacy_rows[] = $calculation;
            } else {
                $payroll_records[] = $calculation;
            }
        }

        $data['title'] = 'HR Master Payroll Summary';
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['selected_cutoff_id'] = $selected_cutoff_id;
        $data['selected_cutoff'] = $selected_cutoff;
        $data['cutoff_options'] = $cutoff_options;
        $data['legacy_rows'] = $legacy_rows;
        $data['payroll_records'] = $selected_cutoff_id > 0 ? $this->field_staff_model->get_cutoff_payroll_rows($selected_cutoff_id, $start_date, $end_date) : $payroll_records;
        $data['payroll_rows'] = $data['payroll_records'];

        $this->load->view('payroll_report', $data);
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

        $statement = $this->field_staff_model->generate_payrun_statement($start_date, $end_date, $selected_staff_id, $selected_department_id, $cutoff);
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

            $statement = $this->field_staff_model->generate_payrun_statement($start_date, $end_date, 0, 0, $cutoff);
            $statement_rows = [];
            foreach ($statement['rows'] as $row) {
                $row_staff_id = isset($row['staff_id']) ? (int) $row['staff_id'] : 0;
                if (in_array($row_staff_id, $selected_staff_ids, true)) {
                    $statement_rows[] = $row;
                }
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

                $profile = $this->field_staff_model->get_payroll_profile($staff_id);
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
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            $this->respond_json([
                'success' => false,
                'message' => 'Payrun could not be saved due to a duplicate or database conflict.',
            ]);
        }
    }

    /**
     * Save project assignment rows for one or multiple staff members.
     */
    public function save_project_assignment()
    {
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
        $status = in_array($status_input, ['draft', 'approved', 'paid'], true) ? $status_input : 'draft';

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

            $payroll_profile = $this->field_staff_model->get_payroll_profile($staff_id);
            $employee_group = trim((string) ($payroll_profile['payroll_group'] ?? ''));
            if ($employee_group !== '' && $employee_group !== $cutoff_group) {
                $this->db->trans_rollback();
                $this->respond_json([
                    'success' => false,
                    'message' => 'Employee does not belong to the selected payroll group.',
                ]);
            }

            $hourly_rate = $this->resolve_hourly_rate($staff_row);
            $baseline = $this->field_staff_model->calculate_weekly_payroll(
                $staff_id,
                $start_date,
                $end_date,
                $hourly_rate
            );

            $adjustment_map = [
                'loan_adjustment' => 0.00,
                'advance' => 0.00,
                'commission' => 0.00,
                'vacation_pay' => 0.00,
            ];
            $provided_adjustments = [];

            foreach ($adjustment_map as $code => $default_value) {
                $raw_value = $this->input->post($code, true);
                if ($raw_value === null || trim((string) $raw_value) === '') {
                    continue;
                }

                if (!is_numeric($raw_value)) {
                    continue;
                }

                $parsed_value = round((float) $raw_value, 2);
                $adjustment_map[$code] = $parsed_value;
                $provided_adjustments[$code] = $parsed_value;
            }

            $gross_salary = isset($baseline['gross_salary']) ? (float) $baseline['gross_salary'] : 0.00;
            $regular_hours = isset($baseline['regular_hours']) ? (float) $baseline['regular_hours'] : 0.00;
            $ot_hours = isset($baseline['ot_hours']) ? (float) $baseline['ot_hours'] : 0.00;

            $taxable_additions = $adjustment_map['commission'] + $adjustment_map['vacation_pay'];
            $employee_deductions = $adjustment_map['loan_adjustment'] + $adjustment_map['advance'];
            if (!empty($provided_adjustments)) {
                $gross_salary = round($gross_salary + $taxable_additions, 2);
            }

            $nib_ee = $gross_salary * 0.055;
            $nib_er = $gross_salary * 0.065;
            $nhip_base_gross = min($gross_salary, 7800.00);
            $nhip_ee = $nhip_base_gross * 0.03;
            $nhip_er = $nhip_base_gross * 0.03;
            $net_salary = $gross_salary - ($nib_ee + $nhip_ee) - $employee_deductions;

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

            foreach ($provided_adjustments as $attribute_code => $value) {
                $this->field_staff_model->add_eav_payroll_value($payroll_id, $attribute_code, $value);
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
        } catch (Throwable $e) {
            $this->db->trans_rollback();
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
    private function persist_payroll_components($payroll_id, array $row)
    {
        $payroll_id = (int) $payroll_id;
        if ($payroll_id <= 0) {
            return false;
        }

        $components = [
            'regular_pay' => (float) ($row['regular_pay'] ?? 0),
            'overtime_pay' => (float) ($row['overtime_pay'] ?? 0),
            'holiday_pay' => (float) ($row['holiday_pay'] ?? 0),
            'allowance_due' => (float) ($row['allowance_due'] ?? 0),
            'vacation_pay' => (float) ($row['vacation_pay'] ?? 0),
            'commission_pay' => (float) ($row['commission_pay'] ?? 0),
            'gross_pay' => (float) ($row['gross_pay'] ?? 0),
            'nib_ee' => (float) ($row['nib_ee'] ?? 0),
            'nib_er' => (float) ($row['nib_er'] ?? 0),
            'nhip_ee' => (float) ($row['nhip_ee'] ?? 0),
            'nhip_er' => (float) ($row['nhip_er'] ?? 0),
            'loan_repayment' => (float) ($row['loan_repayment'] ?? 0),
            'advance_repayment' => (float) ($row['advance_repayment'] ?? 0),
            'other_misc_deductions' => (float) ($row['other_misc_deductions'] ?? 0),
            'total_deductions' => (float) ($row['total_deductions'] ?? 0),
            'net_pay' => (float) ($row['net_pay'] ?? 0),
        ];

        foreach ($components as $attribute_code => $value) {
            $this->field_staff_model->add_eav_payroll_value($payroll_id, $attribute_code, $value);
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
        $is_post = strtoupper((string) $this->input->server('REQUEST_METHOD')) === 'POST';

        if (!$is_post) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Request validation failed.',
            ]);
            exit;
        }

        $action = strtolower(trim((string) $this->input->post('action', true)));

        $latitude  = trim((string) $this->input->post('latitude', true));
        $longitude = trim((string) $this->input->post('longitude', true));
        $notes     = trim((string) $this->input->post('notes', true));
        $staff_id  = $this->resolve_session_staff_id();

        if ($staff_id <= 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Session is not active.',
            ]);
            exit;
        }

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Location details are required to record attendance.',
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
            $notes
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
            'message' => 'Server routing communication error or action denied.',
        ]);
        exit;
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
        return $this->can_access_hr_payroll_workspace();
    }

    private function can_access_operations_tab()
    {
        return $this->can_access_hr_payroll_workspace();
    }

    private function can_access_reporting_tab()
    {
        return $this->can_access_hr_payroll_workspace();
    }

    private function can_access_project_assignment_tab()
    {
        return $this->can_access_hr_payroll_workspace();
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
