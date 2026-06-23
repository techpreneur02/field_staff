<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    .hr-dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .hr-dashboard-header h1 {
        margin: 0 0 5px 0;
        font-size: 28px;
        font-weight: 600;
    }

    .hr-dashboard-header p {
        margin: 0;
        opacity: 0.95;
        font-size: 14px;
    }

    .stat-card {
        background: white;
        border-left: 4px solid #667eea;
        padding: 20px;
        border-radius: 6px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        text-align: center;
        transition: transform 0.3s, box-shadow 0.3s;
        margin-bottom: 20px;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    }

    .stat-card.warning {
        border-left-color: #f59e0b;
    }

    .stat-card.success {
        border-left-color: #10b981;
    }

    .stat-card.danger {
        border-left-color: #ef4444;
    }

    .stat-label {
        color: #6b7280;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .stat-value {
        color: #1f2937;
        font-size: 32px;
        font-weight: 700;
        line-height: 1;
    }

    .filter-panel {
        background: #f9fafb;
        padding: 20px;
        border-radius: 6px;
        margin-bottom: 25px;
        border: 1px solid #e5e7eb;
    }

    .filter-title {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-section-title {
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
        margin-top: 25px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }

    .nav-tabs {
        border-bottom: 2px solid #e5e7eb;
        display: flex;
        flex-wrap: wrap;
        gap: 0;
    }

    .nav-tabs>li>a {
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 16px;
        color: #6b7280;
        font-weight: 500;
        font-size: 13px;
        transition: all 0.3s;
    }

    .nav-tabs>li.active>a,
    .nav-tabs>li>a:hover {
        background-color: transparent;
        border-bottom-color: #667eea;
        color: #667eea;
    }

    .panel_s {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .form-group label {
        font-weight: 600;
        color: #374151;
        font-size: 13px;
        margin-bottom: 6px;
    }

    .btn-primary {
        background-color: #667eea;
        border-color: #667eea;
    }

    .btn-primary:hover {
        background-color: #5568d3;
        border-color: #5568d3;
    }

    .btn-success {
        background-color: #10b981;
        border-color: #10b981;
    }

    .btn-success:hover {
        background-color: #059669;
        border-color: #059669;
    }

    .table {
        font-size: 13px;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .admin-panel {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .admin-panel .panel-heading {
        background-color: #f9fafb;
        border-bottom: 2px solid #e5e7eb;
        margin: -20px -20px 15px -20px;
        padding: 15px 20px;
        border-radius: 6px 6px 0 0;
    }

    @media (max-width: 768px) {
        .hr-dashboard-header {
            padding: 20px 15px;
        }

        .hr-dashboard-header h1 {
            font-size: 22px;
        }

        .stat-value {
            font-size: 24px;
        }

        .nav-tabs>li {
            flex: 1 1 50%;
        }

        .nav-tabs>li>a {
            padding: 10px 8px;
            font-size: 11px;
            text-align: center;
        }

        .form-section-title {
            font-size: 14px;
        }

        .table {
            font-size: 12px;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

    @media (max-width: 576px) {
        .stat-card {
            margin-bottom: 15px;
        }

        .filter-panel {
            padding: 15px;
        }

        .nav-tabs>li {
            flex: 1 1 100%;
        }
    }
</style>
<?php
if (!function_exists('field_staff_hr_escape')) {
    function field_staff_hr_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$title_text = isset($title) ? field_staff_hr_escape($title) : 'HR Management Workspace';
$start_date = isset($start_date) ? field_staff_hr_escape($start_date) : date('Y-m-d', strtotime('monday this week'));
$end_date = isset($end_date) ? field_staff_hr_escape($end_date) : date('Y-m-d', strtotime('sunday this week'));
$report_date = isset($report_date) ? field_staff_hr_escape($report_date) : date('Y-m-d');
$report_month = isset($report_month) ? field_staff_hr_escape($report_month) : date('Y-m');
$staff_directory = isset($staff_directory) && is_array($staff_directory) ? $staff_directory : [];
$departments = isset($departments) && is_array($departments) ? $departments : [];
$shifts = isset($shifts) && is_array($shifts) ? $shifts : [];
$selected_staff_id = isset($selected_staff_id) ? (int) $selected_staff_id : 0;
$selected_department_id = isset($selected_department_id) ? (int) $selected_department_id : 0;
$attendance_summary = isset($attendance_summary) && is_array($attendance_summary) ? $attendance_summary : [];
$attendance_record_rows = isset($attendance_record_rows) && is_array($attendance_record_rows) ? $attendance_record_rows : [];
$attendance_summary_rows = isset($attendance_summary_rows) && is_array($attendance_summary_rows) ? $attendance_summary_rows : [];
$daily_attendance_rows = isset($daily_attendance_rows) && is_array($daily_attendance_rows) ? $daily_attendance_rows : [];
$monthly_attendance = isset($monthly_attendance) && is_array($monthly_attendance) ? $monthly_attendance : ['days' => [], 'rows' => []];
$department_wise_rows = isset($department_wise_rows) && is_array($department_wise_rows) ? $department_wise_rows : [];
$issued_payslip_rows = isset($issued_payslip_rows) && is_array($issued_payslip_rows) ? $issued_payslip_rows : [];
$leave_rows = isset($leave_rows) && is_array($leave_rows) ? $leave_rows : [];
$profile_map = isset($profile_map) && is_array($profile_map) ? $profile_map : [];
$selected_profile = isset($selected_profile) && is_array($selected_profile) ? $selected_profile : [];
$save_profile_url = isset($save_profile_url) ? (string) $save_profile_url : '';
$save_department_url = isset($save_department_url) ? (string) $save_department_url : '';
$save_shift_url = isset($save_shift_url) ? (string) $save_shift_url : '';
$distribute_shift_url = isset($distribute_shift_url) ? (string) $distribute_shift_url : '';
$save_manual_attendance_url = isset($save_manual_attendance_url) ? (string) $save_manual_attendance_url : '';
$save_leave_url = isset($save_leave_url) ? (string) $save_leave_url : '';
$update_leave_status_url = isset($update_leave_status_url) ? (string) $update_leave_status_url : '';
$generate_payrun_url = isset($generate_payrun_url) ? (string) $generate_payrun_url : '';
$save_project_assignment_url = isset($save_project_assignment_url) ? (string) $save_project_assignment_url : '';
$save_hr_payroll_staff_allowlist_url = isset($save_hr_payroll_staff_allowlist_url) ? (string) $save_hr_payroll_staff_allowlist_url : '';
$download_payslip_url = isset($download_payslip_url) ? (string) $download_payslip_url : field_staff_admin_url('field_staff/download_payslip_statement');
$export_urls = isset($export_urls) && is_array($export_urls) ? $export_urls : [];
$current_url = function_exists('current_url') ? current_url() : '';
$summary_total_hours = isset($attendance_summary['total_hours_worked']) ? (float) $attendance_summary['total_hours_worked'] : 0;
$summary_regular_hours = isset($attendance_summary['regular_hours']) ? (float) $attendance_summary['regular_hours'] : 0;
$summary_overtime_hours = isset($attendance_summary['overtime_hours']) ? (float) $attendance_summary['overtime_hours'] : 0;
$summary_allowance_due = isset($attendance_summary['total_allowance_due']) ? (float) $attendance_summary['total_allowance_due'] : 0;
$project_assignment_rows = isset($project_assignment_rows) && is_array($project_assignment_rows) ? $project_assignment_rows : [];
$can_manage_pay_setup = isset($can_manage_pay_setup) ? (bool) $can_manage_pay_setup : false;
$can_manage_operations = isset($can_manage_operations) ? (bool) $can_manage_operations : false;
$can_manage_reporting = isset($can_manage_reporting) ? (bool) $can_manage_reporting : false;
$can_manage_project_assignment = isset($can_manage_project_assignment) ? (bool) $can_manage_project_assignment : false;
$can_manage_hr_payroll_staff_allowlist = isset($can_manage_hr_payroll_staff_allowlist) ? (bool) $can_manage_hr_payroll_staff_allowlist : false;
$can_access_hr_workspace = isset($can_access_hr_workspace) ? (bool) $can_access_hr_workspace : false;
$hr_payroll_staff_ids = isset($hr_payroll_staff_ids) && is_array($hr_payroll_staff_ids) ? $hr_payroll_staff_ids : [];
$hr_payroll_staff_ids_lookup = array_flip(array_map('intval', $hr_payroll_staff_ids));
$current_user_staff_id = isset($current_user_staff_id) ? (int) $current_user_staff_id : 0;
$default_hr_tab = isset($default_hr_tab) ? (string) $default_hr_tab : 'pay_setup';
$tab_map = [
    'shift_setup' => 'tab-shift-department',
    'pay_setup' => 'tab-employee-pay-setup',
    'manual_attendance' => 'tab-manual-attendance',
    'leave_tracking' => 'tab-leave-tracking',
    'reporting_payrun' => 'tab-reporting-payrun',
    'project_assignment' => 'tab-project-assignment',
];
$active_tab_id = isset($tab_map[$default_hr_tab]) ? $tab_map[$default_hr_tab] : 'tab-employee-pay-setup';
?>
<div id="wrapper">
    <div class="content">
        <!-- Dashboard Header -->
        <div class="hr-dashboard-header">
            <h1><?php echo $title_text; ?></h1>
            <p>Complete workforce management, payroll, and reporting control center</p>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="stat-card">
                    <div class="stat-label">Total Hours</div>
                    <div class="stat-value"><?php echo number_format($summary_total_hours, 1); ?></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="stat-card warning">
                    <div class="stat-label">Regular Hours</div>
                    <div class="stat-value"><?php echo number_format($summary_regular_hours, 1); ?></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="stat-card success">
                    <div class="stat-label">Overtime Hours</div>
                    <div class="stat-value"><?php echo number_format($summary_overtime_hours, 1); ?></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="stat-card danger">
                    <div class="stat-label">Allowance Due</div>
                    <div class="stat-value">$<?php echo number_format($summary_allowance_due, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="filter-panel mtop25">
            <div class="filter-title">Workspace Filters</div>
            <form method="get" action="<?php echo field_staff_hr_escape($current_url); ?>">
                <div class="row">
                    <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" id="filter_start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" id="filter_end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label>Daily Report</label>
                            <input type="date" id="filter_report_date" name="report_date" class="form-control" value="<?php echo $report_date; ?>">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label>Monthly</label>
                            <input type="month" id="filter_report_month" name="report_month" class="form-control" value="<?php echo $report_month; ?>">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label>Department</label>
                            <select id="filter_department_id" name="department_id" class="form-control selectpicker" data-width="100%" data-live-search="true">
                                <option value="0" <?php echo $selected_department_id <= 0 ? 'selected' : ''; ?>>All</option>
                                <?php foreach ($departments as $dept) { ?>
                                    <option value="<?php echo (int) ($dept['id'] ?? 0); ?>" <?php echo $selected_department_id === (int) ($dept['id'] ?? 0) ? 'selected' : ''; ?>>
                                        <?php echo field_staff_hr_escape($dept['name'] ?? ''); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label>Staff</label>
                            <select id="filter_staff_id" name="staff_id" class="form-control selectpicker" data-width="100%" data-live-search="true">
                                <option value="0" <?php echo $selected_staff_id <= 0 ? 'selected' : ''; ?>>All</option>
                                <?php foreach ($staff_directory as $staff) { ?>
                                    <option value="<?php echo (int) ($staff['staff_id'] ?? 0); ?>" <?php echo $selected_staff_id === (int) ($staff['staff_id'] ?? 0) ? 'selected' : ''; ?>>
                                        <?php echo field_staff_hr_escape($staff['worker_name'] ?? ''); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-default">Apply Filters</button>
            </form>
        </div>

        <!-- Main Panel with Tabs -->
        <div class="panel_s">
            <div class="panel-body">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs" role="tablist">
                    <?php if ($can_manage_hr_payroll_staff_allowlist) { ?>
                        <li role="presentation" class="<?php echo $active_tab_id === 'tab-payroll-admin' ? 'active' : ''; ?>">
                            <a href="#tab-payroll-admin" role="tab" data-toggle="tab"><i class="fa fa-lock"></i> Payroll Admin</a>
                        </li>
                    <?php } ?>
                    <?php if ($can_manage_operations) { ?>
                        <li role="presentation" class="<?php echo $active_tab_id === 'tab-shift-department' ? 'active' : ''; ?>">
                            <a href="#tab-shift-department" role="tab" data-toggle="tab">Shifts & Depts</a>
                        </li>
                    <?php } ?>
                    <?php if ($can_manage_pay_setup) { ?>
                        <li role="presentation" class="<?php echo $active_tab_id === 'tab-employee-pay-setup' ? 'active' : ''; ?>">
                            <a href="#tab-employee-pay-setup" role="tab" data-toggle="tab">Pay Setup</a>
                        </li>
                    <?php } ?>
                    <?php if ($can_manage_operations) { ?>
                        <li role="presentation" class="<?php echo $active_tab_id === 'tab-manual-attendance' ? 'active' : ''; ?>">
                            <a href="#tab-manual-attendance" role="tab" data-toggle="tab">Attendance</a>
                        </li>
                        <li role="presentation" class="<?php echo $active_tab_id === 'tab-leave-tracking' ? 'active' : ''; ?>">
                            <a href="#tab-leave-tracking" role="tab" data-toggle="tab">Leave</a>
                        </li>
                    <?php } ?>
                    <?php if ($can_manage_reporting) { ?>
                        <li role="presentation" class="<?php echo $active_tab_id === 'tab-reporting-payrun' ? 'active' : ''; ?>">
                            <a href="#tab-reporting-payrun" role="tab" data-toggle="tab">Payrun</a>
                        </li>
                    <?php } ?>
                    <?php if ($can_manage_pay_setup) { ?>
                        <li role="presentation" class="<?php echo $active_tab_id === 'tab-holidays' ? 'active' : ''; ?>">
                            <a href="#tab-holidays" role="tab" data-toggle="tab">Holidays</a>
                        </li>
                    <?php } ?>
                    <?php if ($can_manage_project_assignment) { ?>
                        <li role="presentation" class="<?php echo $active_tab_id === 'tab-project-assignment' ? 'active' : ''; ?>">
                            <a href="#tab-project-assignment" role="tab" data-toggle="tab">Projects</a>
                        </li>
                    <?php } ?>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content mtop15">
                    <!-- Payroll Admin Tab (NEW) -->
                    <?php if ($can_manage_hr_payroll_staff_allowlist) { ?>
                        <div role="tabpanel" class="tab-pane <?php echo $active_tab_id === 'tab-payroll-admin' ? 'active' : ''; ?>" id="tab-payroll-admin">
                            <div class="row mtop10">
                                <div class="col-md-12">
                                    <h4><i class="fa fa-lock"></i> Payroll Administration Control</h4>
                                    <p class="text-muted">Manage access permissions for HR payroll managers and workspace administrators</p>
                                </div>
                            </div>

                            <div class="row mtop20">
                                <div class="col-md-8">
                                    <div class="admin-panel">
                                        <div class="panel-heading">
                                            <h5 class="panel-title no-margin"><i class="fa fa-users"></i> Allowed Staff Members for Payroll Admin</h5>
                                        </div>
                                        <div class="panel-body">
                                            <div class="alert alert-warning">
                                                <strong><i class="fa fa-lock"></i> Master PIN Required:</strong> Enter the master code to unlock editing for Payroll Admin users.
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 col-sm-8 col-xs-12">
                                                    <div class="input-group">
                                                        <input type="password" id="payroll-admin-master-pin" class="form-control" placeholder="Enter master PIN">
                                                        <span class="input-group-btn">
                                                            <button type="button" id="unlock-payroll-admin-btn" class="btn btn-warning">Unlock Edit</button>
                                                        </span>
                                                    </div>
                                                    <p class="text-muted mtop5 mbot0">Editing is locked until valid PIN is entered.</p>
                                                </div>
                                                <div class="col-md-6 col-sm-4 col-xs-12 text-right">
                                                    <span id="payroll-admin-pin-status" class="label label-default">Locked</span>
                                                </div>
                                            </div>

                                            <p class="text-muted"><i class="fa fa-info-circle"></i> Select which staff members can access payroll management, reporting, and HR workspace. Leave empty to restrict access to super admin only.</p>
                                            <p class="text-info mtop10"><strong><i class="fa fa-user-circle"></i> Your Staff ID:</strong> <?php echo $current_user_staff_id; ?></p>

                                            <div class="form-group mtop15">
                                                <label for="hr-payroll-staff-ids" class="control-label"><i class="fa fa-check-circle"></i> Payroll Admin Users</label>
                                                <select id="hr-payroll-staff-ids" class="form-control selectpicker" data-live-search="true" data-width="100%" multiple data-actions-box="true" disabled>
                                                    <?php foreach ($staff_directory as $staff) { ?>
                                                        <?php $sid = (int) ($staff['staff_id'] ?? 0);
                                                        if ($sid <= 0) continue; ?>
                                                        <option value="<?php echo $sid; ?>" <?php echo isset($hr_payroll_staff_ids_lookup[$sid]) ? 'selected' : ''; ?>>
                                                            <?php echo field_staff_hr_escape($staff['worker_name'] ?? ''); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>

                                            <div class="mtop25">
                                                <button type="button" id="save-hr-payroll-staff-allowlist-btn" class="btn btn-primary btn-lg" disabled>
                                                    <i class="fa fa-save"></i> Save Allowed Staff Members
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="panel panel-default">
                                        <div class="panel-heading">
                                            <h5 class="panel-title"><i class="fa fa-shield"></i> Access Summary</h5>
                                        </div>
                                        <div class="panel-body">
                                            <p>
                                                <strong>Total Allowed Staff:</strong><br>
                                                <span class="badge badge-primary" style="background-color: #667eea; color: white; display: inline-block;"><?php echo count($hr_payroll_staff_ids); ?> members</span>
                                            </p>
                                            <p class="mtop15">
                                                <strong>Status:</strong><br>
                                                <span class="label label-info">Active</span>
                                            </p>
                                            <hr>
                                            <p class="text-muted text-sm">
                                                <strong><i class="fa fa-key"></i> Payroll Admins Can Access:</strong><br>
                                                <i class="fa fa-check text-success"></i> Employee Pay Setup<br>
                                                <i class="fa fa-check text-success"></i> Payrun Operations<br>
                                                <i class="fa fa-check text-success"></i> HR Reports & Analytics<br>
                                                <i class="fa fa-check text-success"></i> Attendance Management<br>
                                                <i class="fa fa-check text-success"></i> Leave Tracking<br>
                                                <i class="fa fa-check text-success"></i> Department Setup
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if ($can_manage_pay_setup) { ?>
                        <div role="tabpanel" class="tab-pane <?php echo $active_tab_id === 'tab-employee-pay-setup' ? 'active' : ''; ?>" id="tab-employee-pay-setup">
                            <div class="row">
                                <div class="col-md-4 col-sm-6 col-xs-12">
                                    <div class="form-group">
                                        <label for="profile_staff_id" class="control-label">Employee Selector</label>
                                        <select id="profile_staff_id" class="form-control selectpicker" data-live-search="true" data-width="100%" title="Select employee profile">
                                            <option value=""></option>
                                            <?php foreach ($staff_directory as $staff_row) { ?>
                                                <?php $staff_id = isset($staff_row['staff_id']) ? (int) $staff_row['staff_id'] : 0; ?>
                                                <option value="<?php echo $staff_id; ?>" <?php echo $selected_staff_id === $staff_id ? 'selected="selected"' : ''; ?>><?php echo field_staff_hr_escape(isset($staff_row['worker_name']) ? $staff_row['worker_name'] : ''); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6 col-xs-12">
                                    <div class="form-group">
                                        <label for="department_id" class="control-label">Department Selector</label>
                                        <div class="input-group">
                                            <select id="department_id" class="form-control selectpicker" data-width="100%" data-live-search="true" title="Select department">
                                                <option value="0">Unassigned</option>
                                                <?php foreach ($departments as $department_row) { ?>
                                                    <?php $department_id = isset($department_row['id']) ? (int) $department_row['id'] : 0; ?>
                                                    <option value="<?php echo $department_id; ?>"><?php echo field_staff_hr_escape(isset($department_row['name']) ? $department_row['name'] : ''); ?></option>
                                                <?php } ?>
                                            </select>
                                            <span class="input-group-btn">
                                                <button type="button" id="save-department-btn" class="btn btn-default">Add Department</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label for="default_shift_id" class="control-label">Shift Assignment</label>
                                        <select id="default_shift_id" class="form-control selectpicker" data-width="100%" data-live-search="true" title="Assign default shift">
                                            <option value="0">No Default Shift</option>
                                            <?php foreach ($shifts as $shift_row) { ?>
                                                <?php $shift_id = isset($shift_row['id']) ? (int) $shift_row['id'] : 0; ?>
                                                <option value="<?php echo $shift_id; ?>"><?php echo field_staff_hr_escape(isset($shift_row['shift_name']) ? $shift_row['shift_name'] : ''); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="base_hourly_rate" class="control-label">Base Hourly Rate</label><input type="number" min="0" step="0.01" id="base_hourly_rate" class="form-control"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="overtime_multiplier" class="control-label">Overtime Multiplier</label><input type="number" min="0" step="0.01" id="overtime_multiplier" class="form-control"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="daily_field_allowance" class="control-label">Daily Field Allowance</label><input type="number" min="0" step="0.01" id="daily_field_allowance" class="form-control"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="employee_nib_rate" class="control-label">Employee NIB %</label><input type="number" min="0" step="0.01" id="employee_nib_rate" class="form-control"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="employer_nib_rate" class="control-label">Employer NIB %</label><input type="number" min="0" step="0.01" id="employer_nib_rate" class="form-control"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="employee_nhip_rate" class="control-label">Employee NHIP %</label><input type="number" min="0" step="0.01" id="employee_nhip_rate" class="form-control"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="vacation_pay" class="control-label">Vacation Pay</label><input type="number" min="0" step="0.01" id="vacation_pay" class="form-control"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="outstanding_loan" class="control-label">Outstanding Loan</label><input type="number" min="0" step="0.01" id="outstanding_loan" class="form-control"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="loan_repayment" class="control-label">Loan Repayment</label><input type="number" min="0" step="0.01" id="loan_repayment" class="form-control"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="payment_method" class="control-label">Payment Method</label><select id="payment_method" class="form-control">
                                            <option value="online_transfer">Online Transfer</option>
                                            <option value="cash">Cash</option>
                                            <option value="check">Check</option>
                                        </select></div>
                                </div>
                                <div class="col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group"><label for="bank_account_info" class="control-label">Bank Account Info</label><textarea id="bank_account_info" class="form-control" rows="3"></textarea></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12"><button type="button" id="save-profile-btn" class="btn btn-primary btn-lg">Save Profile</button></div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($can_manage_operations) { ?>
                        <div role="tabpanel" class="tab-pane <?php echo $active_tab_id === 'tab-shift-department' ? 'active' : ''; ?>" id="tab-shift-department">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="setup_department_name" class="control-label">Department Name</label><input type="text" id="setup_department_name" class="form-control" placeholder="Operations"></div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="form-group mtop25"><button type="button" id="setup-save-department-btn" class="btn btn-default">Save Department</button></div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="shift_name" class="control-label">Shift Name</label><input type="text" id="shift_name" class="form-control" placeholder="Day Shift"></div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="form-group mtop25"><button type="button" id="save-shift-btn" class="btn btn-default btn-block">Save Shift Template</button></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="shift_start_time" class="control-label">Start Time</label><input type="time" id="shift_start_time" class="form-control"></div>
                                </div>
                                <div class="col-md-4 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="shift_end_time" class="control-label">End Time</label><input type="time" id="shift_end_time" class="form-control"></div>
                                </div>
                                <div class="col-md-4 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="grace_period_mins" class="control-label">Grace Minutes</label><input type="number" min="0" step="1" id="grace_period_mins" class="form-control" value="0"></div>
                                </div>
                            </div>
                            <div class="row mtop15">
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="distribution_shift_id" class="control-label">Shift Template</label><select id="distribution_shift_id" class="form-control selectpicker" data-live-search="true" data-width="100%">
                                            <option value="0">Select Shift</option><?php foreach ($shifts as $shift_row) { ?><option value="<?php echo (int) ($shift_row['id'] ?? 0); ?>"><?php echo field_staff_hr_escape($shift_row['shift_name'] ?? ''); ?></option><?php } ?>
                                        </select></div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="distribution_department_id" class="control-label">Department Target</label><select id="distribution_department_id" class="form-control selectpicker" data-live-search="true" data-width="100%">
                                            <option value="0">No Department Filter</option><?php foreach ($departments as $department_row) { ?><option value="<?php echo (int) ($department_row['id'] ?? 0); ?>"><?php echo field_staff_hr_escape($department_row['name'] ?? ''); ?></option><?php } ?>
                                        </select></div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="distribution_start_date" class="control-label">Start Date</label><input type="date" id="distribution_start_date" class="form-control" value="<?php echo $start_date; ?>"></div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="distribution_end_date" class="control-label">End Date</label><input type="date" id="distribution_end_date" class="form-control" value="<?php echo $end_date; ?>"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8 col-sm-12 col-xs-12">
                                    <div class="form-group"><label for="distribution_staff_ids" class="control-label">Individual Workers</label><select id="distribution_staff_ids" class="form-control selectpicker" data-live-search="true" data-width="100%" multiple><?php foreach ($staff_directory as $staff_row) { ?><option value="<?php echo (int) ($staff_row['staff_id'] ?? 0); ?>"><?php echo field_staff_hr_escape($staff_row['worker_name'] ?? ''); ?></option><?php } ?></select></div>
                                </div>
                                <div class="col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group mtop25"><button type="button" id="distribute-shift-btn" class="btn btn-primary">Distribute Shift</button></div>
                                </div>
                            </div>
                        </div>

                        <div role="tabpanel" class="tab-pane <?php echo $active_tab_id === 'tab-manual-attendance' ? 'active' : ''; ?>" id="tab-manual-attendance">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="manual_staff_id" class="control-label">Employee</label><select id="manual_staff_id" class="form-control selectpicker" data-live-search="true" data-width="100%">
                                            <option value="0">Select Employee</option><?php foreach ($staff_directory as $staff_row) { ?><option value="<?php echo (int) ($staff_row['staff_id'] ?? 0); ?>"><?php echo field_staff_hr_escape($staff_row['worker_name'] ?? ''); ?></option><?php } ?>
                                        </select></div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="manual_date" class="control-label">Attendance Date</label><input type="date" id="manual_date" class="form-control" value="<?php echo $report_date; ?>"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="manual_clock_in" class="control-label">Clock In</label><input type="time" id="manual_clock_in" class="form-control"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="manual_clock_out" class="control-label">Clock Out</label><input type="time" id="manual_clock_out" class="form-control"></div>
                                </div>
                                <div class="col-md-2 col-sm-12 col-xs-12">
                                    <div class="form-group mtop25"><button type="button" id="save-manual-attendance-btn" class="btn btn-primary">Post Manual Log</button></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group"><label for="manual_notes" class="control-label">Adjustment Notes</label><textarea id="manual_notes" class="form-control" rows="3"></textarea></div>
                                </div>
                            </div>
                        </div>

                        <div role="tabpanel" class="tab-pane <?php echo $active_tab_id === 'tab-leave-tracking' ? 'active' : ''; ?>" id="tab-leave-tracking">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="leave_staff_id" class="control-label">Employee</label><select id="leave_staff_id" class="form-control selectpicker" data-live-search="true" data-width="100%">
                                            <option value="0">Select Employee</option><?php foreach ($staff_directory as $staff_row) { ?><option value="<?php echo (int) ($staff_row['staff_id'] ?? 0); ?>"><?php echo field_staff_hr_escape($staff_row['worker_name'] ?? ''); ?></option><?php } ?>
                                        </select></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="leave_type" class="control-label">Leave Type</label><select id="leave_type" class="form-control">
                                            <option value="Vacation">Vacation</option>
                                            <option value="Sick">Sick</option>
                                            <option value="Maternity">Maternity</option>
                                            <option value="Unpaid">Unpaid</option>
                                        </select></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="leave_start_date" class="control-label">Start Date</label><input type="date" id="leave_start_date" class="form-control" value="<?php echo $start_date; ?>"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="leave_end_date" class="control-label">End Date</label><input type="date" id="leave_end_date" class="form-control" value="<?php echo $end_date; ?>"></div>
                                </div>
                                <div class="col-md-3 col-sm-12 col-xs-12">
                                    <div class="form-group"><label for="leave_status" class="control-label">Status</label><select id="leave_status" class="form-control">
                                            <option value="Pending">Pending</option>
                                            <option value="Approved">Approved</option>
                                            <option value="Rejected">Rejected</option>
                                        </select></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-9 col-sm-12 col-xs-12">
                                    <div class="form-group"><label for="leave_reason" class="control-label">Reason</label><textarea id="leave_reason" class="form-control" rows="3"></textarea></div>
                                </div>
                                <div class="col-md-3 col-sm-12 col-xs-12">
                                    <div class="form-group mtop25"><button type="button" id="save-leave-btn" class="btn btn-primary">Save Leave Request</button></div>
                                </div>
                            </div>
                            <div class="table-responsive mtop15">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Type</th>
                                            <th>Start</th>
                                            <th>End</th>
                                            <th>Status</th>
                                            <th>Reason</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody><?php if (!empty($leave_rows)) {
                                                foreach ($leave_rows as $leave_row) { ?><tr data-leave-id="<?php echo (int) ($leave_row['id'] ?? 0); ?>">
                                                    <td><?php echo field_staff_hr_escape($leave_row['worker_name'] ?? ''); ?></td>
                                                    <td><?php echo field_staff_hr_escape($leave_row['leave_type'] ?? ''); ?></td>
                                                    <td><?php echo field_staff_hr_escape($leave_row['start_date'] ?? ''); ?></td>
                                                    <td><?php echo field_staff_hr_escape($leave_row['end_date'] ?? ''); ?></td>
                                                    <td class="js-leave-status"><?php echo field_staff_hr_escape($leave_row['status'] ?? ''); ?></td>
                                                    <td><?php echo field_staff_hr_escape($leave_row['reason'] ?? ''); ?></td>
                                                    <td><button type="button" class="btn btn-success btn-xs js-leave-status-btn" data-status="Approved">Approve</button> <button type="button" class="btn btn-danger btn-xs js-leave-status-btn" data-status="Rejected">Reject</button></td>
                                                </tr><?php }
                                                } else { ?><tr>
                                                <td colspan="7" class="text-center text-muted">No leave records found for the selected filter period.</td>
                                            </tr><?php } ?></tbody>
                                </table>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($can_manage_reporting) { ?>
                        <div role="tabpanel" class="tab-pane <?php echo $active_tab_id === 'tab-reporting-payrun' ? 'active' : ''; ?>" id="tab-reporting-payrun">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5 class="bold">Reporting and Payrun Workspace</h5>
                                    <p class="text-muted">Export each ledger instantly in CSV/Excel-compatible format and generate an immutable payrun statement from the active filter state.</p>
                                </div>
                            </div>

                            <div class="row mtop10">
                                <div class="col-md-12">
                                    <a href="<?php echo field_staff_hr_escape($export_urls['attendance_record'] ?? '#'); ?>" class="btn btn-default">Export Attendance Record</a>
                                    <a href="<?php echo field_staff_hr_escape($export_urls['attendance_summary'] ?? '#'); ?>" class="btn btn-default">Export Attendance Summary</a>
                                    <a href="<?php echo field_staff_hr_escape($export_urls['daily_attendance'] ?? '#'); ?>" class="btn btn-default">Export Daily Attendance</a>
                                    <a href="<?php echo field_staff_hr_escape($export_urls['monthly_attendance'] ?? '#'); ?>" class="btn btn-default">Export Monthly Attendance</a>
                                    <a href="<?php echo field_staff_hr_escape($export_urls['department_wise'] ?? '#'); ?>" class="btn btn-default">Export Department Wise</a>
                                    <button type="button" id="generate-payrun-btn" class="btn btn-primary">Generate Payrun Ledger</button>
                                </div>
                            </div>

                            <div class="panel panel-default mtop20">
                                <div class="panel-heading"><strong>Attendance Record</strong></div>
                                <div class="panel-body table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Department</th>
                                                <th>Date</th>
                                                <th>Clock In</th>
                                                <th>Clock Out</th>
                                                <th>Total Hours</th>
                                                <th>Late In</th>
                                                <th>Early Out</th>
                                                <th>Over Work</th>
                                                <th>GPS</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody><?php if (!empty($attendance_record_rows)) {
                                                    foreach ($attendance_record_rows as $row) {
                                                        $inCoords = trim(($row['in_latitude'] ?? '') . ', ' . ($row['in_longitude'] ?? ''));
                                                        $outCoords = trim(($row['out_latitude'] ?? '') . ', ' . ($row['out_longitude'] ?? '')); ?><tr>
                                                        <td><?php echo field_staff_hr_escape($row['worker_name'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($row['department_name'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($row['date'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($row['clock_in'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($row['clock_out'] ?? ''); ?></td>
                                                        <td><?php echo number_format((float) ($row['total_hours'] ?? 0), 2); ?></td>
                                                        <td><?php echo (int) ($row['late_in_minutes'] ?? 0); ?> mins</td>
                                                        <td><?php echo (int) ($row['early_out_minutes'] ?? 0); ?> mins</td>
                                                        <td><?php echo (int) ($row['over_work_minutes'] ?? 0); ?> mins</td>
                                                        <td>
                                                            <div><?php echo field_staff_hr_escape($inCoords); ?></div>
                                                            <div><?php echo field_staff_hr_escape($outCoords); ?></div>
                                                        </td>
                                                        <td><?php echo field_staff_hr_escape($row['notes'] ?? ''); ?></td>
                                                    </tr><?php }
                                                    } else { ?><tr>
                                                    <td colspan="11" class="text-center text-muted">No attendance record rows available for the selected period.</td>
                                                </tr><?php } ?></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="panel panel-default mtop15">
                                <div class="panel-heading"><strong>Attendance Summary</strong></div>
                                <div class="panel-body table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Department</th>
                                                <th>Total Days Worked</th>
                                                <th>Total Late Ins</th>
                                                <th>Total Early Outs</th>
                                                <th>Total Hours Worked</th>
                                                <th>Regular Hours</th>
                                                <th>Overtime Hours</th>
                                            </tr>
                                        </thead>
                                        <tbody><?php if (!empty($attendance_summary_rows)) {
                                                    foreach ($attendance_summary_rows as $row) { ?><tr>
                                                        <td><?php echo field_staff_hr_escape($row['worker_name'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($row['department_name'] ?? ''); ?></td>
                                                        <td><?php echo (int) ($row['total_days_worked'] ?? 0); ?></td>
                                                        <td><?php echo (int) ($row['total_late_ins'] ?? 0); ?></td>
                                                        <td><?php echo (int) ($row['total_early_outs'] ?? 0); ?></td>
                                                        <td><?php echo number_format((float) ($row['total_hours_worked'] ?? 0), 2); ?></td>
                                                        <td><?php echo number_format((float) ($row['regular_hours'] ?? 0), 2); ?></td>
                                                        <td><?php echo number_format((float) ($row['overtime_hours'] ?? 0), 2); ?></td>
                                                    </tr><?php }
                                                    } else { ?><tr>
                                                    <td colspan="8" class="text-center text-muted">No attendance summary rows available for the selected period.</td>
                                                </tr><?php } ?></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="panel panel-default mtop15">
                                <div class="panel-heading"><strong>Daily Attendance Report</strong></div>
                                <div class="panel-body table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Department</th>
                                                <th>Status</th>
                                                <th>Clock In</th>
                                                <th>Clock Out</th>
                                                <th>Late In Minutes</th>
                                            </tr>
                                        </thead>
                                        <tbody><?php if (!empty($daily_attendance_rows)) {
                                                    foreach ($daily_attendance_rows as $row) { ?><tr>
                                                        <td><?php echo field_staff_hr_escape($row['worker_name'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($row['department_name'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($row['status'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($row['clock_in'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($row['clock_out'] ?? ''); ?></td>
                                                        <td><?php echo (int) ($row['late_in_minutes'] ?? 0); ?></td>
                                                    </tr><?php }
                                                    } else { ?><tr>
                                                    <td colspan="6" class="text-center text-muted">No daily attendance rows available for the selected report date.</td>
                                                </tr><?php } ?></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="panel panel-default mtop15">
                                <div class="panel-heading"><strong>Monthly Attendance Report</strong></div>
                                <div class="panel-body table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Department</th><?php foreach (($monthly_attendance['days'] ?? []) as $day_value) { ?><th><?php echo field_staff_hr_escape(date('d', strtotime($day_value))); ?></th><?php } ?>
                                            </tr>
                                        </thead>
                                        <tbody><?php if (!empty($monthly_attendance['rows'])) {
                                                    foreach ($monthly_attendance['rows'] as $row) { ?><tr>
                                                        <td><?php echo field_staff_hr_escape($row['worker_name'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($row['department_name'] ?? ''); ?></td><?php foreach (($monthly_attendance['days'] ?? []) as $day_value) { ?><td class="text-center"><?php echo field_staff_hr_escape($row['days'][$day_value] ?? 'A'); ?></td><?php } ?>
                                                    </tr><?php }
                                                    } else { ?><tr>
                                                    <td colspan="<?php echo 2 + count($monthly_attendance['days'] ?? []); ?>" class="text-center text-muted">No monthly attendance matrix available for the selected month.</td>
                                                </tr><?php } ?></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="panel panel-default mtop15">
                                <div class="panel-heading"><strong>Department Wise Report</strong></div>
                                <div class="panel-body table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Department</th>
                                                <th>Headcount</th>
                                                <th>Total Regular Hours</th>
                                                <th>Total Overtime Hours</th>
                                                <th>Total Hours Worked</th>
                                            </tr>
                                        </thead>
                                        <tbody><?php if (!empty($department_wise_rows)) {
                                                    foreach ($department_wise_rows as $row) { ?><tr>
                                                        <td><?php echo field_staff_hr_escape($row['department_name'] ?? ''); ?></td>
                                                        <td><?php echo (int) ($row['headcount'] ?? 0); ?></td>
                                                        <td><?php echo number_format((float) ($row['total_regular_hours'] ?? 0), 2); ?></td>
                                                        <td><?php echo number_format((float) ($row['total_overtime_hours'] ?? 0), 2); ?></td>
                                                        <td><?php echo number_format((float) ($row['total_hours_worked'] ?? 0), 2); ?></td>
                                                    </tr><?php }
                                                    } else { ?><tr>
                                                    <td colspan="5" class="text-center text-muted">No department wise analytics available for the selected period.</td>
                                                </tr><?php } ?></tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="payrun-results-panel" class="panel panel-default mtop20 hide">
                                <div class="panel-body">
                                    <div class="clearfix">
                                        <h5 class="pull-left no-margin">Immutable Payroll Breakdown Statement</h5><span id="payrun-generated-at" class="pull-right text-muted"></span>
                                    </div>
                                    <p id="payrun-period-label" class="text-muted mtop10 mbot15"></p>
                                    <p class="text-muted mtop5 mbot15"><small><i class="fa fa-info-circle"></i> Check or uncheck rows to select employees for payslip issuance. Use header checkbox to select/deselect all.</small></p>
                                    <div id="payrun-results-table" class="table-responsive"></div>
                                </div>
                                <div class="panel-footer">
                                    <button type="button" id="apply-payrun-btn" class="btn btn-success">Apply & Issue Payslips</button>
                                    <span id="payrun-selection-status" class="text-muted mleft15"></span>
                                    <span id="payrun-action-status" class="text-muted mleft15"></span>
                                </div>
                            </div>

                            <div class="panel panel-default mtop20">
                                <div class="panel-heading"><strong>Issued Payslips</strong></div>
                                <div class="panel-body table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Issued</th>
                                                <th>Employee</th>
                                                <th>Department</th>
                                                <th>Period</th>
                                                <th>Net Salary</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($issued_payslip_rows)) { ?>
                                                <?php foreach ($issued_payslip_rows as $payslip_row) { ?>
                                                    <tr>
                                                        <td><?php echo field_staff_hr_escape($payslip_row['created_at'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($payslip_row['worker_name'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape($payslip_row['department_name'] ?? ''); ?></td>
                                                        <td><?php echo field_staff_hr_escape(($payslip_row['start_date'] ?? '') . ' to ' . ($payslip_row['end_date'] ?? '')); ?></td>
                                                        <td>$<?php echo number_format((float) ($payslip_row['net_salary'] ?? 0), 2); ?></td>
                                                        <td><?php echo field_staff_hr_escape($payslip_row['status'] ?? 'issued'); ?></td>
                                                        <td>
                                                            <a class="btn btn-default btn-sm" href="<?php echo field_staff_hr_escape($download_payslip_url); ?>?payroll_id=<?php echo (int) ($payslip_row['id'] ?? 0); ?>" target="_blank" rel="noopener">Download</a>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">No issued payslips found for the selected filters.</td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($can_manage_pay_setup) { ?>
                        <div role="tabpanel" class="tab-pane <?php echo $active_tab_id === 'tab-holidays' ? 'active' : ''; ?>" id="tab-holidays">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5 class="bold">Holiday Management</h5>
                                    <p class="text-muted">Configure public holidays and special dates. Employees working on holidays receive 2x base pay.</p>
                                </div>
                            </div>
                            <div class="row mtop20">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="holiday_date">Holiday Date</label>
                                        <input type="date" id="holiday_date" class="form-control" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="holiday_name">Holiday Name</label>
                                        <input type="text" id="holiday_name" class="form-control" placeholder="e.g., New Year's Day" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="button" id="add-holiday-btn" class="btn btn-primary">Add Holiday</button>
                                    <span id="holiday-action-status" class="text-muted mleft15"></span>
                                </div>
                            </div>
                            <div class="row mtop25">
                                <div class="col-md-12">
                                    <h5 class="bold">Registered Holidays</h5>
                                    <div id="holidays-list" class="table-responsive mtop15"></div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($can_manage_project_assignment) { ?>
                        <div role="tabpanel" class="tab-pane <?php echo $active_tab_id === 'tab-project-assignment' ? 'active' : ''; ?>" id="tab-project-assignment">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5 class="bold">Project Assignment Control</h5>
                                    <p class="text-muted">Supervisor panel for site job and task resource mapping.</p>
                                </div>
                            </div>
                            <div class="row mtop15">
                                <div class="col-md-4 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="project_name" class="control-label">Project Name</label><input type="text" id="project_name" class="form-control" placeholder="Enter project or work package"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="project_start_date" class="control-label">Start Date</label><input type="date" id="project_start_date" class="form-control" value="<?php echo $start_date; ?>"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="project_end_date" class="control-label">End Date</label><input type="date" id="project_end_date" class="form-control" value="<?php echo $end_date; ?>"></div>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12">
                                    <div class="form-group"><label for="project_status" class="control-label">Status</label><select id="project_status" class="form-control">
                                            <option value="Planned">Planned</option>
                                            <option value="Active">Active</option>
                                            <option value="Completed">Completed</option>
                                            <option value="On Hold">On Hold</option>
                                        </select></div>
                                </div>
                                <div class="col-md-2 col-sm-12 col-xs-12">
                                    <div class="form-group mtop25"><button type="button" id="save-project-assignment-btn" class="btn btn-primary btn-block">Assign Staff</button></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8 col-sm-12 col-xs-12">
                                    <div class="form-group"><label for="project_staff_ids" class="control-label">Assign To Staff</label><select id="project_staff_ids" class="form-control selectpicker" data-live-search="true" data-width="100%" multiple><?php foreach ($staff_directory as $staff_row) { ?><option value="<?php echo (int) ($staff_row['staff_id'] ?? 0); ?>"><?php echo field_staff_hr_escape($staff_row['worker_name'] ?? ''); ?></option><?php } ?></select></div>
                                </div>
                                <div class="col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group"><label for="project_notes" class="control-label">Assignment Notes</label><textarea id="project_notes" class="form-control" rows="3"></textarea></div>
                                </div>
                            </div>
                            <div class="table-responsive mtop15">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Staff</th>
                                            <th>Supervisor/Manager</th>
                                            <th>Start</th>
                                            <th>End</th>
                                            <th>Status</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($project_assignment_rows)) { ?>
                                            <?php foreach ($project_assignment_rows as $project_row) { ?>
                                                <tr>
                                                    <td><?php echo field_staff_hr_escape($project_row['project_name'] ?? ''); ?></td>
                                                    <td><?php echo field_staff_hr_escape($project_row['worker_name'] ?? ''); ?></td>
                                                    <td><?php echo field_staff_hr_escape($project_row['supervisor_name'] ?? ''); ?></td>
                                                    <td><?php echo field_staff_hr_escape($project_row['start_date'] ?? ''); ?></td>
                                                    <td><?php echo field_staff_hr_escape($project_row['end_date'] ?? '-'); ?></td>
                                                    <td><?php echo field_staff_hr_escape($project_row['status'] ?? ''); ?></td>
                                                    <td><?php echo field_staff_hr_escape($project_row['notes'] ?? ''); ?></td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No project assignments found for the selected filter period.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <?php if (isset($this->security) && method_exists($this->security, 'get_csrf_token_name') && method_exists($this->security, 'get_csrf_hash')) { ?>
                    <input type="hidden" id="hr-csrf-name" value="<?php echo field_staff_hr_escape($this->security->get_csrf_token_name()); ?>">
                    <input type="hidden" id="hr-csrf-hash" value="<?php echo field_staff_hr_escape($this->security->get_csrf_hash()); ?>">
                <?php } ?>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<?php init_tail(); ?>
<script>
    (function() {
        'use strict';

        function getJq() {
            if (window.jQuery && window.jQuery.fn) {
                return window.jQuery;
            }

            if (window.$ && window.$.fn) {
                return window.$;
            }

            return null;
        }

        function bootHrManagement() {
            var jq = getJq();
            if (!jq) {
                return;
            }

            var $ = jq;
            var profileMap = <?php echo json_encode($profile_map); ?>;
            var departments = <?php echo json_encode($departments); ?>;
            var shifts = <?php echo json_encode($shifts); ?>;
            var saveProfileUrl = <?php echo json_encode($save_profile_url); ?>;
            var saveDepartmentUrl = <?php echo json_encode($save_department_url); ?>;
            var saveShiftUrl = <?php echo json_encode($save_shift_url); ?>;
            var distributeShiftUrl = <?php echo json_encode($distribute_shift_url); ?>;
            var saveManualAttendanceUrl = <?php echo json_encode($save_manual_attendance_url); ?>;
            var saveLeaveUrl = <?php echo json_encode($save_leave_url); ?>;
            var updateLeaveStatusUrl = <?php echo json_encode($update_leave_status_url); ?>;
            var generatePayrunUrl = <?php echo json_encode($generate_payrun_url); ?>;
            var applyPayrunUrl = <?php echo json_encode($apply_payrun_url); ?>;
            var saveProjectAssignmentUrl = <?php echo json_encode($save_project_assignment_url); ?>;
            var saveHrPayrollStaffAllowlistUrl = <?php echo json_encode($save_hr_payroll_staff_allowlist_url); ?>;
            var saveHolidayUrl = <?php echo json_encode(field_staff_admin_url('field_staff/save_holiday')); ?>;
            var deleteHolidayUrl = <?php echo json_encode(field_staff_admin_url('field_staff/delete_holiday')); ?>;
            var getHolidaysUrl = <?php echo json_encode(field_staff_admin_url('field_staff/get_holidays')); ?>;
            var payrollAdminMasterPin = '0212';
            var payrollAdminUnlocked = false;
            var currentPayrunStatement = null;;

            function notify(type, message) {
                if (typeof alert_float === 'function') {
                    alert_float(type, message);
                } else {
                    window.alert(message);
                }
            }

            function appendCsrf(payload) {
                var csrfName = ($('#hr-csrf-name').val() || '').trim();
                var csrfHash = ($('#hr-csrf-hash').val() || '').trim();
                if (csrfName && csrfHash) {
                    payload[csrfName] = csrfHash;
                }
                return payload;
            }

            function escapeHtml(value) {
                return $('<div>').text(value === null || typeof value === 'undefined' ? '' : value).html();
            }

            function refreshPickers() {
                if ($.fn.selectpicker) {
                    $('.selectpicker').selectpicker('refresh');
                }
            }

            function getSelectedProfile() {
                var staffId = $('#profile_staff_id').val();
                if (!staffId || !profileMap[staffId]) {
                    return {
                        department_id: 0,
                        default_shift_id: 0,
                        base_hourly_rate: '0.00',
                        overtime_multiplier: '1.50',
                        daily_field_allowance: '0.00',
                        employee_nib_rate: '5.50',
                        employer_nib_rate: '6.50',
                        employee_nhip_rate: '3.00',
                        vacation_pay: '0.00',
                        outstanding_loan: '0.00',
                        loan_repayment: '0.00',
                        payment_method: 'online_transfer',
                        bank_account_info: ''
                    };
                }
                return profileMap[staffId];
            }

            function populateProfileForm() {
                var profile = getSelectedProfile();
                $('#department_id').val(String(profile.department_id || 0));
                $('#default_shift_id').val(String(profile.default_shift_id || 0));
                $('#base_hourly_rate').val(profile.base_hourly_rate || '0.00');
                $('#overtime_multiplier').val(profile.overtime_multiplier || '1.50');
                $('#daily_field_allowance').val(profile.daily_field_allowance || '0.00');
                $('#employee_nib_rate').val(profile.employee_nib_rate || '5.50');
                $('#employer_nib_rate').val(profile.employer_nib_rate || '6.50');
                $('#employee_nhip_rate').val(profile.employee_nhip_rate || '3.00');
                $('#vacation_pay').val(profile.vacation_pay || '0.00');
                $('#outstanding_loan').val(profile.outstanding_loan || '0.00');
                $('#loan_repayment').val(profile.loan_repayment || '0.00');
                $('#payment_method').val(profile.payment_method || 'online_transfer');
                $('#bank_account_info').val(profile.bank_account_info || '');
                refreshPickers();
            }

            function renderPayrunStatement(statement) {
                if (!statement || !statement.rows || !statement.rows.length) {
                    $('#payrun-results-panel').addClass('hide');
                    return;
                }

                currentPayrunStatement = statement;

                var rowsHtml = '';
                for (var index = 0; index < statement.rows.length; index++) {
                    var row = statement.rows[index];
                    rowsHtml += '<tr data-staff-index="' + index + '">' +
                        '<td><input type="checkbox" class="payrun-row-select" data-index="' + index + '" checked></td>' +
                        '<td>' + escapeHtml(row.worker_name) + '</td>' +
                        '<td>' + escapeHtml(row.department_name || '') + '</td>' +
                        '<td class="text-right">' + escapeHtml(row.regular_hours) + '</td>' +
                        '<td class="text-right">' + escapeHtml(row.overtime_hours) + '</td>' +
                        '<td class="text-right">' + escapeHtml(row.holiday_hours || 0) + '</td>' +
                        '<td class="text-right">$' + escapeHtml(row.base_hourly_rate) + '</td>' +
                        '<td class="text-right">$' + escapeHtml(row.allowance_due) + '</td>' +
                        '<td class="text-right">$' + escapeHtml(row.vacation_pay || 0) + '</td>' +
                        '<td class="text-right">$' + escapeHtml(row.regular_pay || 0) + '</td>' +
                        '<td class="text-right">$' + escapeHtml(row.overtime_pay || 0) + '</td>' +
                        '<td class="text-right">$' + escapeHtml(row.holiday_pay || 0) + '</td>' +
                        '<td class="text-right">$' + escapeHtml(row.gross_pay) + '</td>' +
                        '<td class="text-right">$' + escapeHtml(row.total_deductions || 0) + '</td>' +
                        '<td class="text-right">$' + escapeHtml(row.net_pay) + '</td>' +
                        '</tr>';
                }

                rowsHtml += '<tr class="bold">' +
                    '<td colspan="2">Totals</td><td></td><td></td><td></td><td></td><td></td><td class="text-right">$' + escapeHtml(statement.totals.allowance_pay || 0) + '</td><td></td>' +
                    '<td class="text-right">$' + escapeHtml(statement.totals.regular_pay || 0) + '</td>' +
                    '<td class="text-right">$' + escapeHtml(statement.totals.overtime_pay || 0) + '</td>' +
                    '<td class="text-right">$' + escapeHtml(statement.totals.holiday_pay || 0) + '</td>' +
                    '<td class="text-right">$' + escapeHtml(statement.totals.gross_pay || 0) + '</td>' +
                    '<td class="text-right">$' + escapeHtml((statement.totals.nib_ee || 0) + (statement.totals.nhip_ee || 0)) + '</td>' +
                    '<td class="text-right">$' + escapeHtml(statement.totals.net_pay || 0) + '</td>' +
                    '</tr>';

                $('#payrun-results-table').html(
                    '<table class="table table-striped table-bordered">' +
                    '<thead><tr><th style="width: 30px;"><input type="checkbox" id="payrun-select-all" title="Select/deselect all"></th><th>Staff Name</th><th>Department</th><th class="text-right">Regular Hours</th><th class="text-right">Overtime Hours</th><th class="text-right">Holiday Hours</th><th class="text-right">Base Rate</th><th class="text-right">Allowance</th><th class="text-right">Vacation Pay</th><th class="text-right">Regular Pay</th><th class="text-right">Overtime Pay</th><th class="text-right">Holiday Pay (2x)</th><th class="text-right">Gross Pay</th><th class="text-right">Deductions</th><th class="text-right">Net Pay</th></tr></thead>' +
                    '<tbody>' + rowsHtml + '</tbody>' +
                    '</table>'
                );

                $('#payrun-generated-at').text(statement.generated_at || '');
                $('#payrun-period-label').text('Period: ' + (statement.start_date || '') + ' to ' + (statement.end_date || ''));
                $('#payrun-results-panel').removeClass('hide');

                // Setup select all/none checkbox
                $('#payrun-select-all').on('change', function() {
                    var isChecked = $(this).is(':checked');
                    $('.payrun-row-select').prop('checked', isChecked);
                    updatePayrunSelectionStatus();
                });

                // Setup individual row checkboxes
                $('.payrun-row-select').on('change', function() {
                    var totalChecks = $('.payrun-row-select').length;
                    var checkedCount = $('.payrun-row-select:checked').length;
                    $('#payrun-select-all').prop('indeterminate', checkedCount > 0 && checkedCount < totalChecks);
                    updatePayrunSelectionStatus();
                });

                updatePayrunSelectionStatus();
            }

            function updatePayrunSelectionStatus() {
                var checkedCount = $('.payrun-row-select:checked').length;
                var totalCount = $('.payrun-row-select').length;
                $('#payrun-selection-status').text('Selected: ' + checkedCount + ' of ' + totalCount);
            }

            function getSelectedPayrunRows() {
                var selected = [];
                if (!currentPayrunStatement || !currentPayrunStatement.rows) {
                    return selected;
                }
                $('.payrun-row-select:checked').each(function() {
                    var index = parseInt($(this).data('index'), 10);
                    if (!isNaN(index) && currentPayrunStatement.rows[index]) {
                        selected.push(currentPayrunStatement.rows[index]);
                    }
                });
                return selected;
            }

            $('#profile_staff_id').change(function() {
                populateProfileForm();
            });

            $('#save-profile-btn').click(function() {
                var staffId = $('#profile_staff_id').val();
                if (!staffId) {
                    notify('danger', 'Select an employee before saving the payroll profile.');
                    return;
                }

                var payload = appendCsrf({
                    staff_id: staffId,
                    department_id: $('#department_id').val(),
                    default_shift_id: $('#default_shift_id').val(),
                    base_hourly_rate: $('#base_hourly_rate').val(),
                    overtime_multiplier: $('#overtime_multiplier').val(),
                    daily_field_allowance: $('#daily_field_allowance').val(),
                    employee_nib_rate: $('#employee_nib_rate').val(),
                    employer_nib_rate: $('#employer_nib_rate').val(),
                    employee_nhip_rate: $('#employee_nhip_rate').val(),
                    vacation_pay: $('#vacation_pay').val(),
                    outstanding_loan: $('#outstanding_loan').val(),
                    loan_repayment: $('#loan_repayment').val(),
                    payment_method: $('#payment_method').val(),
                    bank_account_info: $('#bank_account_info').val()
                });

                $.post(saveProfileUrl, payload, function(response) {
                    if (response && response.success) {
                        profileMap[String(staffId)] = response.profile || {};
                        populateProfileForm();
                        notify('success', response.message || 'Payroll profile saved successfully.');
                    } else {
                        notify('danger', response && response.message ? response.message : 'Payroll profile save failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'Payroll profile save failed.');
                });
            });

            $('#save-department-btn').click(function() {
                var departmentName = window.prompt('Enter department name');
                if (!departmentName) {
                    return;
                }

                $.post(saveDepartmentUrl, appendCsrf({
                    name: departmentName
                }), function(response) {
                    if (response && response.success) {
                        notify('success', response.message || 'Department saved successfully.');
                        window.location.reload();
                    } else {
                        notify('danger', response && response.message ? response.message : 'Department save failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'Department save failed.');
                });
            });

            $('#setup-save-department-btn').click(function() {
                var departmentName = ($('#setup_department_name').val() || '').trim();
                if (!departmentName) {
                    notify('danger', 'Department name is required.');
                    return;
                }

                $.post(saveDepartmentUrl, appendCsrf({
                    name: departmentName
                }), function(response) {
                    if (response && response.success) {
                        notify('success', response.message || 'Department saved successfully.');
                        $('#setup_department_name').val('');
                        window.location.reload();
                    } else {
                        notify('danger', response && response.message ? response.message : 'Department save failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'Department save failed.');
                });
            });

            $('#save-shift-btn').click(function() {
                var payload = appendCsrf({
                    shift_name: $('#shift_name').val(),
                    start_time: $('#shift_start_time').val(),
                    end_time: $('#shift_end_time').val(),
                    grace_period_mins: $('#grace_period_mins').val()
                });

                $.post(saveShiftUrl, payload, function(response) {
                    if (response && response.success) {
                        notify('success', response.message || 'Shift template saved successfully.');
                        window.location.reload();
                    } else {
                        notify('danger', response && response.message ? response.message : 'Shift template save failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'Shift template save failed.');
                });
            });

            $('#distribute-shift-btn').click(function() {
                var selectedStaff = $('#distribution_staff_ids').val() || [];
                var payload = appendCsrf({
                    shift_id: $('#distribution_shift_id').val(),
                    department_id: $('#distribution_department_id').val(),
                    start_date: $('#distribution_start_date').val(),
                    end_date: $('#distribution_end_date').val(),
                    staff_ids: selectedStaff
                });

                $.post(distributeShiftUrl, payload, function(response) {
                    if (response && response.success) {
                        notify('success', response.message || 'Shift distribution completed successfully.');
                    } else {
                        notify('danger', response && response.message ? response.message : 'Shift distribution failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'Shift distribution failed.');
                });
            });

            $('#save-manual-attendance-btn').click(function() {
                var payload = appendCsrf({
                    staff_id: $('#manual_staff_id').val(),
                    date: $('#manual_date').val(),
                    clock_in: $('#manual_clock_in').val(),
                    clock_out: $('#manual_clock_out').val(),
                    notes: $('#manual_notes').val()
                });

                $.post(saveManualAttendanceUrl, payload, function(response) {
                    if (response && response.success) {
                        notify('success', response.message || 'Manual attendance saved successfully.');
                        window.location.reload();
                    } else {
                        notify('danger', response && response.message ? response.message : 'Manual attendance save failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'Manual attendance save failed.');
                });
            });

            $('#save-leave-btn').click(function() {
                var payload = appendCsrf({
                    staff_id: $('#leave_staff_id').val(),
                    leave_type: $('#leave_type').val(),
                    start_date: $('#leave_start_date').val(),
                    end_date: $('#leave_end_date').val(),
                    status: $('#leave_status').val(),
                    reason: $('#leave_reason').val()
                });

                $.post(saveLeaveUrl, payload, function(response) {
                    if (response && response.success) {
                        notify('success', response.message || 'Leave record saved successfully.');
                        window.location.reload();
                    } else {
                        notify('danger', response && response.message ? response.message : 'Leave record save failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'Leave record save failed.');
                });
            });

            $('.js-leave-status-btn').click(function() {
                var leaveId = $(this).closest('tr').attr('data-leave-id');
                var status = $(this).attr('data-status');
                var $row = $(this).closest('tr');
                $.post(updateLeaveStatusUrl, appendCsrf({
                    leave_id: leaveId,
                    status: status
                }), function(response) {
                    if (response && response.success) {
                        $row.find('.js-leave-status').text(status);
                        notify('success', response.message || 'Leave status updated successfully.');
                    } else {
                        notify('danger', response && response.message ? response.message : 'Leave status update failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'Leave status update failed.');
                });
            });

            $('#generate-payrun-btn').click(function() {
                var payload = appendCsrf({
                    start_date: $('#filter_start_date').val(),
                    end_date: $('#filter_end_date').val(),
                    staff_id: $('#filter_staff_id').val(),
                    department_id: $('#filter_department_id').val()
                });

                $.post(generatePayrunUrl, payload, function(response) {
                    if (response && response.success && response.statement) {
                        renderPayrunStatement(response.statement);
                        notify('success', response.message || 'Payrun ledger generated successfully.');
                    } else {
                        notify('danger', response && response.message ? response.message : 'Payrun generation failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'Payrun generation failed.');
                });
            });

            $('#apply-payrun-btn').click(function() {
                if (!currentPayrunStatement || !currentPayrunStatement.rows || !currentPayrunStatement.rows.length) {
                    notify('danger', 'No payrun data available. Generate a payrun first.');
                    return;
                }

                var selectedRows = getSelectedPayrunRows();
                if (!selectedRows || !selectedRows.length) {
                    notify('danger', 'Select at least one employee to issue payslips for.');
                    return;
                }

                var msgContent = 'Issue ' + selectedRows.length + ' payslip(s)?';
                if (selectedRows.length < currentPayrunStatement.rows.length) {
                    msgContent = 'Issue payslips for ' + selectedRows.length + ' selected of ' + currentPayrunStatement.rows.length + ' employees? Unselected staff will not receive payslips.';
                }
                msgContent += '\n\nThis action cannot be undone.';

                if (!confirm(msgContent)) {
                    return;
                }

                $('#payrun-action-status').text('Processing...');
                $('#apply-payrun-btn').prop('disabled', true);

                var payload = appendCsrf({
                    start_date: currentPayrunStatement.start_date,
                    end_date: currentPayrunStatement.end_date,
                    rows: selectedRows
                });

                $.post(applyPayrunUrl, payload, function(response) {
                    $('#apply-payrun-btn').prop('disabled', false);
                    if (response && response.success) {
                        $('#payrun-action-status').text('✓ Applied');
                        notify('success', response.message || 'Payrun applied successfully.');
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $('#payrun-action-status').text('');
                        notify('danger', response && response.message ? response.message : 'Payrun apply failed.');
                    }
                }, 'json').fail(function() {
                    $('#apply-payrun-btn').prop('disabled', false);
                    $('#payrun-action-status').text('');
                    notify('danger', 'Payrun apply failed.');
                });
            });

            $('#save-project-assignment-btn').click(function() {
                var staffIds = $('#project_staff_ids').val() || [];
                if (!staffIds.length) {
                    notify('danger', 'Select at least one staff member for assignment.');
                    return;
                }

                var payload = appendCsrf({
                    project_name: $('#project_name').val(),
                    start_date: $('#project_start_date').val(),
                    end_date: $('#project_end_date').val(),
                    status: $('#project_status').val(),
                    notes: $('#project_notes').val(),
                    staff_ids: staffIds
                });

                $.post(saveProjectAssignmentUrl, payload, function(response) {
                    if (response && response.success) {
                        notify('success', response.message || 'Project assignment saved successfully.');
                        window.location.reload();
                    } else {
                        notify('danger', response && response.message ? response.message : 'Project assignment save failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'Project assignment save failed.');
                });
            });

            $('#unlock-payroll-admin-btn').click(function() {
                var enteredPin = ($('#payroll-admin-master-pin').val() || '').trim();
                if (enteredPin !== payrollAdminMasterPin) {
                    payrollAdminUnlocked = false;
                    $('#payroll-admin-pin-status').removeClass('label-success').addClass('label-danger').text('Invalid PIN');
                    notify('danger', 'Invalid master PIN.');
                    return;
                }

                payrollAdminUnlocked = true;
                $('#payroll-admin-pin-status').removeClass('label-default label-danger').addClass('label-success').text('Unlocked');
                $('#hr-payroll-staff-ids').prop('disabled', false);
                $('#save-hr-payroll-staff-allowlist-btn').prop('disabled', false);
                if ($.fn.selectpicker) {
                    $('#hr-payroll-staff-ids').selectpicker('refresh');
                }
                notify('success', 'Payroll Admin editing unlocked.');
            });

            $('#save-hr-payroll-staff-allowlist-btn').click(function() {
                if (!payrollAdminUnlocked) {
                    notify('danger', 'Enter the master PIN to unlock Payroll Admin editing.');
                    return;
                }

                var staffIds = $('#hr-payroll-staff-ids').val() || [];

                $.post(saveHrPayrollStaffAllowlistUrl, appendCsrf({
                    master_pin: ($('#payroll-admin-master-pin').val() || '').trim(),
                    staff_ids: staffIds
                }), function(response) {
                    if (response && response.success) {
                        var savedIds = (response.staff_ids || []).map(function(item) {
                            return String(item);
                        });
                        $('#hr-payroll-staff-ids').val(savedIds);
                        if ($.fn.selectpicker) {
                            $('#hr-payroll-staff-ids').selectpicker('refresh');
                        }
                        notify('success', response.message || 'HR/payroll staff allowlist updated successfully.');
                    } else {
                        notify('danger', response && response.message ? response.message : 'HR/payroll staff allowlist update failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'HR/payroll staff allowlist update failed.');
                });
            });

            // Load and display holidays on initialization
            function loadAndDisplayHolidays() {
                $.getJSON(getHolidaysUrl, function(response) {
                    if (response && response.holidays && Array.isArray(response.holidays)) {
                        var holidaysHtml = '';
                        if (response.holidays.length === 0) {
                            holidaysHtml = '<p class="text-muted">No holidays configured yet.</p>';
                        } else {
                            holidaysHtml = '<table class="table table-striped table-bordered"><thead><tr><th>Date</th><th>Name</th><th>Action</th></tr></thead><tbody>';
                            response.holidays.forEach(function(holiday) {
                                holidaysHtml += '<tr><td>' + escapeHtml(holiday.date) + '</td><td>' + escapeHtml(holiday.name) + '</td><td><button type="button" class="btn btn-xs btn-danger delete-holiday-btn" data-holiday-date="' + escapeHtml(holiday.date) + '">Delete</button></td></tr>';
                            });
                            holidaysHtml += '</tbody></table>';
                        }

                        $('#holidays-list').html(holidaysHtml);

                        // Attach delete handlers
                        $('.delete-holiday-btn').click(function() {
                            var holidayDate = $(this).data('holiday-date');
                            if (!confirm('Delete holiday on ' + holidayDate + '?')) {
                                return;
                            }

                            $.post(deleteHolidayUrl, appendCsrf({
                                date: holidayDate
                            }), function(deleteResponse) {
                                if (deleteResponse && deleteResponse.success) {
                                    notify('success', deleteResponse.message || 'Holiday deleted.');
                                    loadAndDisplayHolidays();
                                } else {
                                    notify('danger', deleteResponse && deleteResponse.message ? deleteResponse.message : 'Delete failed.');
                                }
                            }, 'json').fail(function() {
                                notify('danger', 'Delete failed.');
                            });
                        });
                    } else {
                        $('#holidays-list').html('<p class="text-danger">Unexpected holiday response format.</p>');
                    }
                }).fail(function() {
                    $('#holidays-list').html('<p class="text-danger">Failed to load holidays.</p>');
                });
            }

            $('#add-holiday-btn').click(function() {
                var holidayDate = $('#holiday_date').val();
                var holidayName = ($('#holiday_name').val() || '').trim();

                if (!holidayDate) {
                    notify('danger', 'Please select a holiday date.');
                    return;
                }
                if (!holidayName) {
                    notify('danger', 'Please enter a holiday name.');
                    return;
                }

                $.post(saveHolidayUrl, appendCsrf({
                    date: holidayDate,
                    name: holidayName
                }), function(response) {
                    if (response && response.success) {
                        notify('success', response.message || 'Holiday added successfully.');
                        $('#holiday_date').val('');
                        $('#holiday_name').val('');
                        loadAndDisplayHolidays();
                    } else {
                        notify('danger', response && response.message ? response.message : 'Add holiday failed.');
                    }
                }, 'json').fail(function() {
                    notify('danger', 'Add holiday failed.');
                });
            });

            // Initialize holidays on tab shown
            $('a[href="#tab-holidays"]').on('shown.bs.tab', function() {
                loadAndDisplayHolidays();
            });

            // Load holidays once on init if on that tab
            if ($('#tab-holidays').length && $('#tab-holidays').hasClass('active')) {
                loadAndDisplayHolidays();
            }

            if ($.fn.selectpicker) {
                $('.selectpicker').selectpicker();
                $('.selectpicker').selectpicker('refresh');
            }

            populateProfileForm();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootHrManagement);
        } else {
            bootHrManagement();
        }
    })();
</script>