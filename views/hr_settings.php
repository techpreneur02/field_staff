<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    .hr-settings-section {
        margin-top: 15px;
    }
    .section-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
    .section-card .panel-heading {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        padding: 15px 20px;
        font-size: 16px;
        font-weight: 700;
    }
    .section-card .panel-body {
        padding: 20px;
    }
    .pending-pill {
        display: inline-block;
        padding: 4px 8px;
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
</style>
<?php
if (!function_exists('field_staff_settings_escape')) {
    function field_staff_settings_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$staff_directory = isset($staff_directory) && is_array($staff_directory) ? $staff_directory : [];
$hr_payroll_staff_ids = isset($hr_payroll_staff_ids) && is_array($hr_payroll_staff_ids) ? $hr_payroll_staff_ids : [];
$cutoffs = isset($cutoffs) && is_array($cutoffs) ? $cutoffs : [];
$selected_cutoff = isset($selected_cutoff) && is_array($selected_cutoff) ? $selected_cutoff : [];
$selected_cutoff_id = isset($selected_cutoff_id) ? (int) $selected_cutoff_id : 0;
$save_hr_access_url = isset($save_hr_access_url) ? (string) $save_hr_access_url : '';
$save_cutoff_url = isset($save_cutoff_url) ? (string) $save_cutoff_url : '';
$current_user_staff_id = isset($current_user_staff_id) ? (int) $current_user_staff_id : 0;
$current_url = isset($current_url) ? (string) $current_url : '';
$allowlist_lookup = array_flip(array_map('intval', $hr_payroll_staff_ids));
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">HR Settings</h4>
                        <p class="text-muted mtop5">Configuration for employees, attendance, payroll control, and access. Only live controls are enabled in this phase.</p>
                        <a class="btn btn-default" href="<?php echo field_staff_settings_escape(field_staff_admin_url('field_staff/attendance') . '#live-team-operations'); ?>">Team Operations - Live Status</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="section-card">
                    <div class="panel-heading">Access Control</div>
                    <div class="panel-body">
                        <p class="text-muted">Manage which staff IDs are permitted to manage HR settings and payroll controls.</p>
                        <div class="form-group">
                            <label class="control-label" for="hr-settings-staff-ids">Allowed Staff</label>
                            <select id="hr-settings-staff-ids" class="form-control selectpicker" data-width="100%" data-live-search="true" multiple>
                                <?php foreach ($staff_directory as $staff_row) { ?>
                                    <?php $staff_id = (int) ($staff_row['staff_id'] ?? 0); if ($staff_id <= 0) continue; ?>
                                    <option value="<?php echo $staff_id; ?>" <?php echo isset($allowlist_lookup[$staff_id]) ? 'selected' : ''; ?>>
                                        <?php echo field_staff_settings_escape($staff_row['worker_name'] ?? ''); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <strong>Your staff ID:</strong> <?php echo $current_user_staff_id; ?>
                        </div>
                        <button type="button" id="save-hr-settings-access" class="btn btn-primary"><i class="fa fa-save"></i> Save Access</button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="section-card">
                    <div class="panel-heading">Payroll Calendar</div>
                    <div class="panel-body">
                        <div class="alert alert-warning">
                            <strong>Phase 1:</strong> payroll cutoff creation is enabled. Additional payroll calendar settings remain pending.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Group</th>
                                        <th>Dates</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($cutoffs)) { ?>
                                        <?php foreach ($cutoffs as $cutoff) { ?>
                                            <?php $cutoff_id = (int) ($cutoff['id'] ?? 0); ?>
                                            <tr>
                                                <td><a href="<?php echo field_staff_settings_escape($current_url . '?cutoff_id=' . $cutoff_id); ?>"><?php echo field_staff_settings_escape((string) ($cutoff['cutoff_code'] ?? '')); ?></a></td>
                                                <td><?php echo field_staff_settings_escape((string) ($cutoff['payroll_group'] ?? 'default')); ?></td>
                                                <td><?php echo field_staff_settings_escape((string) ($cutoff['start_date'] ?? '')); ?> to <?php echo field_staff_settings_escape((string) ($cutoff['end_date'] ?? '')); ?></td>
                                                <td><span class="label label-default"><?php echo field_staff_settings_escape((string) ($cutoff['status'] ?? 'open')); ?></span></td>
                                                <td><?php echo field_staff_settings_escape((string) ($cutoff['payment_date'] ?? '')); ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr><td colspan="5" class="text-muted text-center">No payroll cutoffs created yet.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="section-card">
                    <div class="panel-heading">Payroll Cutoff Setup</div>
                    <div class="panel-body">
                        <form id="payroll-cutoff-form">
                            <input type="hidden" name="id" value="<?php echo (int) ($selected_cutoff['id'] ?? 0); ?>">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Cutoff code</label>
                                        <input type="text" class="form-control" name="cutoff_code" value="<?php echo field_staff_settings_escape((string) ($selected_cutoff['cutoff_code'] ?? '')); ?>" placeholder="AUTO">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Payroll group</label>
                                        <input type="text" class="form-control" name="payroll_group" value="<?php echo field_staff_settings_escape((string) ($selected_cutoff['payroll_group'] ?? 'default')); ?>" placeholder="default">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Start date</label>
                                        <input type="date" class="form-control" name="start_date" value="<?php echo field_staff_settings_escape((string) ($selected_cutoff['start_date'] ?? '')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">End date</label>
                                        <input type="date" class="form-control" name="end_date" value="<?php echo field_staff_settings_escape((string) ($selected_cutoff['end_date'] ?? '')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Payment date</label>
                                        <input type="date" class="form-control" name="payment_date" value="<?php echo field_staff_settings_escape((string) ($selected_cutoff['payment_date'] ?? '')); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Status</label>
                                        <select class="form-control" name="status">
                                            <?php $status_value = strtolower((string) ($selected_cutoff['status'] ?? 'open')); ?>
                                            <?php foreach (['open','under_review','approved','closed'] as $status_option) { ?>
                                                <option value="<?php echo $status_option; ?>" <?php echo $status_value === $status_option ? 'selected' : ''; ?>><?php echo ucfirst($status_option); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="save-payroll-cutoff-btn" class="btn btn-primary"><i class="fa fa-save"></i> Save Cutoff</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row hr-settings-section">
            <div class="col-md-12">
                <div class="section-card">
                    <div class="panel-heading">Employee Policy Overrides</div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label" for="policy-staff-select">Employee</label>
                                    <select id="policy-staff-select" class="form-control">
                                        <?php foreach ($staff_directory as $staff_row) { $staff_id = (int) ($staff_row['staff_id'] ?? 0); if ($staff_id <= 0) continue; ?>
                                            <option value="<?php echo $staff_id; ?>"><?php echo field_staff_settings_escape((string) ($staff_row['worker_name'] ?? '')); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label" for="policy-field-select">Policy Field</label>
                                    <select id="policy-field-select" class="form-control">
                                        <option value="attendance_requirement">Attendance Requirement</option>
                                        <option value="break_policy">Break Policy</option>
                                        <option value="grace_period_mins">Grace Period Minutes</option>
                                        <option value="break_duration_limit_mins">Break Duration Limit</option>
                                        <option value="overtime_policy">Overtime Policy</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="control-label" for="policy-effective-from">Effective From</label>
                                    <input type="date" id="policy-effective-from" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label" for="policy-value">Value</label>
                                    <input type="text" id="policy-value" class="form-control" placeholder="optional or 15">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label" for="policy-source">Source</label>
                                    <select id="policy-source" class="form-control">
                                        <option value="manual">Manual</option>
                                        <option value="verified">Verified</option>
                                        <option value="system">System</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label" for="policy-verification-status">Verification</label>
                                    <select id="policy-verification-status" class="form-control">
                                        <option value="pending">Pending</option>
                                        <option value="verified">Verified</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label" for="policy-notes">Notes</label>
                                    <input type="text" id="policy-notes" class="form-control" placeholder="Reason or policy note">
                                </div>
                            </div>
                        </div>
                        <button type="button" id="save-policy-override-btn" class="btn btn-primary"><i class="fa fa-save"></i> Save Policy</button>
                        <div id="policy-override-history" class="mtop20"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row hr-settings-section">
            <div class="col-md-12">
                <div class="section-card">
                    <div class="panel-heading">Employee Classification & Pay</div>
                    <div class="panel-body">
                        <form id="employee-profile-settings-form" class="form-horizontal">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label" for="employee-profile-staff">Employee</label>
                                        <select id="employee-profile-staff" name="staff_id" class="form-control">
                                            <?php foreach ($staff_directory as $staff_row) { $staff_id = (int) ($staff_row['staff_id'] ?? 0); if ($staff_id <= 0) continue; ?>
                                                <option value="<?php echo $staff_id; ?>"><?php echo field_staff_settings_escape((string) ($staff_row['worker_name'] ?? '')); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="employee-profile-position">Position</label>
                                        <select id="employee-profile-position" name="position" class="form-control">
                                            <?php foreach (['worker', 'supervisor', 'manager', 'executive', 'owner'] as $option) { ?>
                                                <option value="<?php echo $option; ?>"><?php echo ucfirst($option); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="employee-profile-arrangement">Arrangement</label>
                                        <select id="employee-profile-arrangement" name="employment_arrangement" class="form-control">
                                            <?php foreach (['permanent', 'fixed_term', 'temporary', 'casual'] as $option) { ?>
                                                <option value="<?php echo $option; ?>"><?php echo str_replace('_', ' ', ucfirst($option)); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="employee-profile-payment-basis">Payment Basis</label>
                                        <select id="employee-profile-payment-basis" name="payment_basis" class="form-control">
                                            <?php foreach (['hourly', 'salary', 'daily', 'per_job', 'per_unit'] as $option) { ?>
                                                <option value="<?php echo $option; ?>"><?php echo ucfirst(str_replace('_', ' ', $option)); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label" for="employee-profile-attendance">Attendance</label>
                                        <select id="employee-profile-attendance" name="attendance_requirement" class="form-control">
                                            <?php foreach (['required', 'optional', 'not_required'] as $option) { ?>
                                                <option value="<?php echo $option; ?>"><?php echo ucfirst(str_replace('_', ' ', $option)); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label" for="employee-profile-department">Department</label>
                                        <select id="employee-profile-department" name="department_id" class="form-control">
                                            <option value="0">Unassigned</option>
                                            <?php foreach ($departments as $department_row) { $department_id = (int) ($department_row['id'] ?? 0); if ($department_id <= 0) continue; ?>
                                                <option value="<?php echo $department_id; ?>"><?php echo field_staff_settings_escape((string) ($department_row['name'] ?? '')); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="employee-profile-hourly-rate">Base Rate</label>
                                        <input type="number" min="0" step="0.01" id="employee-profile-hourly-rate" name="base_hourly_rate" class="form-control" value="0.00">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="employee-profile-group">Payroll Group</label>
                                        <input type="text" id="employee-profile-group" name="payroll_group" class="form-control" value="default">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="employee-profile-payment-method">Payment Method</label>
                                        <select id="employee-profile-payment-method" name="payment_method" class="form-control">
                                            <?php foreach (['online_transfer', 'cash', 'check'] as $option) { ?>
                                                <option value="<?php echo $option; ?>"><?php echo ucfirst(str_replace('_', ' ', $option)); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 text-right" style="padding-top: 25px;">
                                    <button type="button" id="save-employee-profile-settings-btn" class="btn btn-primary"><i class="fa fa-save"></i> Save Employee Profile</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row hr-settings-section">
            <div class="col-md-6">
                <div class="section-card">
                    <div class="panel-heading">Department Setup</div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label" for="settings-department-name">Department Name</label>
                            <input type="text" id="settings-department-name" class="form-control" placeholder="Operations">
                        </div>
                        <button type="button" id="save-department-settings-btn" class="btn btn-primary"><i class="fa fa-plus"></i> Add Department</button>
                        <div class="mtop20">
                            <ul class="list-group">
                                <?php foreach ($departments as $department_row) { $department_id = (int) ($department_row['id'] ?? 0); if ($department_id <= 0) continue; ?>
                                    <li class="list-group-item"><?php echo field_staff_settings_escape((string) ($department_row['name'] ?? '')); ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="section-card">
                    <div class="panel-heading">Attendance & Break Policy</div>
                    <div class="panel-body">
                        <form id="attendance-policy-form">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="attendance-grace-period">Grace Period (mins)</label>
                                        <input type="number" min="0" step="1" id="attendance-grace-period" class="form-control" value="<?php echo field_staff_settings_escape((string) ($policy_settings['grace_period_mins'] ?? '10')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="attendance-rounding">Rounding</label>
                                        <select id="attendance-rounding" class="form-control">
                                            <?php foreach (['nearest_15', 'nearest_30', 'no_rounding'] as $option) { ?>
                                                <option value="<?php echo $option; ?>" <?php echo ($policy_settings['attendance_rounding'] ?? 'nearest_15') === $option ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $option)); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="fixed-start-time-enabled">Fixed Start Time</label>
                                        <div class="checkbox checkbox-primary mt-10">
                                            <input type="checkbox" id="fixed-start-time-enabled" value="1" <?php echo !empty($policy_settings['fixed_start_time_enabled']) ? 'checked' : ''; ?>>
                                            <label for="fixed-start-time-enabled">Enable</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="fixed-start-time">Fixed Start Time</label>
                                        <input type="time" id="fixed-start-time" class="form-control" value="<?php echo field_staff_settings_escape((string) ($policy_settings['fixed_start_time'] ?? '07:00')); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="break-policy">Break Policy</label>
                                        <select id="break-policy" class="form-control">
                                            <?php foreach (['paid', 'unpaid', 'required'] as $option) { ?>
                                                <option value="<?php echo $option; ?>" <?php echo ($policy_settings['break_policy'] ?? 'paid') === $option ? 'selected' : ''; ?>><?php echo ucfirst($option); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="break-duration-limit">Break Limit (mins)</label>
                                        <input type="number" min="0" step="1" id="break-duration-limit" class="form-control" value="<?php echo field_staff_settings_escape((string) ($policy_settings['break_duration_limit_mins'] ?? '60')); ?>">
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="save-attendance-policy-btn" class="btn btn-primary"><i class="fa fa-save"></i> Save Policies</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row hr-settings-section">
            <div class="col-md-6">
                <div class="section-card">
                    <div class="panel-heading">Pay Policy</div>
                    <div class="panel-body">
                        <form id="pay-policy-form">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="pay-regular-hours-cap">Weekly Regular Hours Cap</label>
                                        <input type="number" min="0" step="0.5" id="pay-regular-hours-cap" class="form-control" value="<?php echo field_staff_settings_escape((string) ($policy_settings['regular_hours_cap'] ?? '44')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="pay-overtime-enabled">Overtime</label>
                                        <div class="checkbox checkbox-primary mt-10">
                                            <input type="checkbox" id="pay-overtime-enabled" value="1" <?php echo !empty($policy_settings['overtime_enabled']) ? 'checked' : ''; ?>>
                                            <label for="pay-overtime-enabled">Apply Overtime</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="pay-overtime-multiplier">Overtime Multiplier</label>
                                        <input type="number" min="0" step="0.1" id="pay-overtime-multiplier" class="form-control" value="<?php echo field_staff_settings_escape((string) ($policy_settings['overtime_multiplier'] ?? '1.5')); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="pay-workweek-start">Workweek starts on</label>
                                <select id="pay-workweek-start" class="form-control">
                                    <?php foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day => $label) { ?>
                                        <option value="<?php echo $day; ?>" <?php echo (string) ($policy_settings['workweek_start_day'] ?? '1') === (string) $day ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php } ?>
                                </select>
                                <p class="text-muted">A fixed seven-day workweek, independent of cutoff boundaries. Confirm this policy before payroll review.</p>
                            </div>
                            <div class="form-group">
                                <label for="pay-holiday-multiplier">Configured Holiday Multiplier</label>
                                <input type="number" min="1" step="0.01" id="pay-holiday-multiplier" class="form-control" value="<?php echo field_staff_settings_escape((string) ($policy_settings['holiday_multiplier'] ?? '')); ?>">
                                <p class="text-muted">Required when hourly work falls on a configured holiday. No legal multiplier is inferred.</p>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="pay-daily-field-allowance">Daily Field Allowance</label>
                                <input type="number" min="0" step="0.01" id="pay-daily-field-allowance" class="form-control" value="<?php echo field_staff_settings_escape((string) ($policy_settings['daily_field_allowance'] ?? '0')); ?>">
                            </div>
                            <button type="button" id="save-pay-policy-btn" class="btn btn-primary"><i class="fa fa-save"></i> Save Pay Policy</button>
                        </form>
                        <hr>
                        <h4>Approve non-hourly work inputs</h4>
                        <p class="text-muted">Salary: one complete-cutoff amount, quantity 1. Daily: one work date, quantity 1 and daily rate. Job/unit: approved quantity and explicit rate. Records are append-only; check the source reference and values before approval.</p>
                        <form id="payroll-work-record-form">
                            <div class="form-group"><label for="work-staff-id">Employee</label>
                                <select id="work-staff-id" name="staff_id" class="form-control">
                                    <option value="">Select employee</option>
                                    <?php foreach ($staff_directory as $employee) { ?>
                                        <option value="<?php echo (int) $employee['staff_id']; ?>"><?php echo field_staff_settings_escape($employee['worker_name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group"><label for="work-cutoff-id">Cutoff</label>
                                <select id="work-cutoff-id" name="cutoff_id" class="form-control">
                                    <option value="">Select cutoff</option>
                                    <?php foreach ($cutoffs as $work_cutoff) { ?>
                                        <option value="<?php echo (int) $work_cutoff['id']; ?>"><?php echo field_staff_settings_escape($work_cutoff['cutoff_code']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group"><label for="work-start-date">Work / salary period start</label><input id="work-start-date" type="date" name="start_date" class="form-control" required></div>
                            <div class="form-group"><label for="work-end-date">Work / salary period end</label><input id="work-end-date" type="date" name="end_date" class="form-control" required></div>
                            <div class="form-group"><label for="work-quantity">Approved quantity</label><input id="work-quantity" type="number" min="0.0001" step="0.0001" name="quantity" class="form-control" required></div>
                            <div class="form-group"><label for="work-rate">Rate / complete-cutoff salary amount</label><input id="work-rate" type="number" min="0.0001" step="0.0001" name="rate" class="form-control" required></div>
                            <div class="form-group"><label for="work-reference">Unique approved source reference</label><input id="work-reference" name="reference" maxlength="120" class="form-control" required></div>
                            <label><input type="checkbox" name="attest_approved" value="1" required> I am authorized and have verified these work inputs against the source record.</label>
                            <button type="submit" class="btn btn-primary">Save approved work inputs</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="section-card">
                    <div class="panel-heading">Statutory Configuration</div>
                    <div class="panel-body">
                        <form id="statutory-policy-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="statutory-nib-ee">Employee NIB %</label>
                                        <input type="number" min="0" step="0.1" id="statutory-nib-ee" class="form-control" value="<?php echo field_staff_settings_escape((string) ($policy_settings['employee_nib_rate'] ?? '5.5')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="statutory-nib-er">Employer NIB %</label>
                                        <input type="number" min="0" step="0.1" id="statutory-nib-er" class="form-control" value="<?php echo field_staff_settings_escape((string) ($policy_settings['employer_nib_rate'] ?? '6.5')); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="statutory-nhip-ee">Employee NHIP %</label>
                                        <input type="number" min="0" step="0.1" id="statutory-nhip-ee" class="form-control" value="<?php echo field_staff_settings_escape((string) ($policy_settings['employee_nhip_rate'] ?? '3.0')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="statutory-nhip-er">Employer NHIP %</label>
                                        <input type="number" min="0" step="0.1" id="statutory-nhip-er" class="form-control" value="<?php echo field_staff_settings_escape((string) ($policy_settings['employer_nhip_rate'] ?? '3.0')); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="statutory-verification-status">Verification</label>
                                        <select id="statutory-verification-status" class="form-control">
                                            <?php foreach (['pending', 'verified', 'rejected'] as $option) { ?>
                                                <option value="<?php echo $option; ?>" <?php echo ($policy_settings['verification_status'] ?? 'pending') === $option ? 'selected' : ''; ?>><?php echo ucfirst($option); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="statutory-verification-source">Source</label>
                                        <select id="statutory-verification-source" class="form-control">
                                            <?php foreach (['manual', 'verified', 'system'] as $option) { ?>
                                                <option value="<?php echo $option; ?>" <?php echo ($policy_settings['verification_source'] ?? 'manual') === $option ? 'selected' : ''; ?>><?php echo ucfirst($option); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="save-statutory-policy-btn" class="btn btn-primary"><i class="fa fa-shield"></i> Save Statutory Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    function notify(type, message) {
        if (typeof alert_float === 'function') {
            alert_float(type, message);
        } else {
            window.alert(message);
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    var employeeProfileRequestId = 0;

    function populateEmployeeProfileForm(profile) {
        var normalized = profile || {};
        $('#employee-profile-position').val(normalized.position || 'worker');
        $('#employee-profile-arrangement').val(normalized.employment_arrangement || 'permanent');
        $('#employee-profile-payment-basis').val(normalized.payment_basis || 'hourly');
        $('#employee-profile-attendance').val(normalized.attendance_requirement || 'required');
        $('#employee-profile-department').val(normalized.department_id != null ? String(normalized.department_id) : '0');
        $('#employee-profile-hourly-rate').val(normalized.base_hourly_rate != null ? normalized.base_hourly_rate : '');
        $('#employee-profile-group').val(normalized.payroll_group || 'default');
        $('#employee-profile-payment-method').val(normalized.payment_method || 'online_transfer');
        $('#save-employee-profile-settings-btn').prop('disabled', false);
    }

    function resetEmployeeProfileForm() {
        $('#employee-profile-position').val('worker');
        $('#employee-profile-arrangement').val('permanent');
        $('#employee-profile-payment-basis').val('hourly');
        $('#employee-profile-attendance').val('required');
        $('#employee-profile-department').val('0');
        $('#employee-profile-hourly-rate').val('');
        $('#employee-profile-group').val('default');
        $('#employee-profile-payment-method').val('online_transfer');
        $('#save-employee-profile-settings-btn').prop('disabled', true);
    }

    function loadEmployeeProfile(staffId) {
        var selectedStaffId = parseInt(staffId, 10);
        if (!selectedStaffId || selectedStaffId <= 0) {
            resetEmployeeProfileForm();
            return;
        }

        var requestId = ++employeeProfileRequestId;
        $('#save-employee-profile-settings-btn').prop('disabled', true);

        $.get('<?php echo field_staff_settings_escape(field_staff_admin_url('field_staff/get_employee_profile')); ?>', { staff_id: selectedStaffId })
            .done(function(response) {
                if (requestId !== employeeProfileRequestId) {
                    return;
                }

                if (!response || !response.success || !response.profile) {
                    resetEmployeeProfileForm();
                    notify('danger', (response && response.message) ? response.message : 'Unable to load the employee profile.');
                    return;
                }

                if (parseInt($('#employee-profile-staff').val(), 10) !== selectedStaffId) {
                    return;
                }

                populateEmployeeProfileForm(response.profile);
            })
            .fail(function() {
                if (requestId !== employeeProfileRequestId) {
                    return;
                }
                resetEmployeeProfileForm();
                notify('danger', 'Unable to load employee profile.');
            });
    }

    function renderPolicyHistory(rows) {
        var $target = $('#policy-override-history');
        if (!Array.isArray(rows) || !rows.length) {
            $target.html('<p class="text-muted">No employee policy overrides recorded yet.</p>');
            return;
        }

        var html = '<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Field</th><th>Value</th><th>From</th><th>To</th><th>Verification</th><th>Source</th></tr></thead><tbody>';
        rows.forEach(function(row) {
            html += '<tr>' +
                '<td>' + escapeHtml(row.field_name || '') + '</td>' +
                '<td>' + escapeHtml(row.value || '') + '</td>' +
                '<td>' + escapeHtml(row.effective_from || '') + '</td>' +
                '<td>' + escapeHtml(row.effective_to || 'Open') + '</td>' +
                '<td>' + escapeHtml(row.verification_status || 'pending') + '</td>' +
                '<td>' + escapeHtml(row.source || 'manual') + '</td>' +
                '</tr>';
        });
        html += '</tbody></table></div>';
        $target.html(html);
    }

    function loadPolicyHistory() {
        var staffId = $('#policy-staff-select').val();
        if (!staffId) {
            return;
        }
        $.get('<?php echo field_staff_settings_escape(field_staff_admin_url('field_staff/get_employee_policy_overrides')); ?>', { staff_id: staffId, as_of_date: $('#policy-effective-from').val() || '' })
            .done(function(response) {
                if (response && response.success) {
                    renderPolicyHistory(response.history || []);
                }
            });
    }

    $('#employee-profile-staff').on('change', function() {
        loadEmployeeProfile($(this).val());
    });

    $('#save-hr-settings-access').on('click', function() {
        var selectedStaff = $('#hr-settings-staff-ids').val() || [];
        $.post('<?php echo field_staff_settings_escape($save_hr_access_url); ?>', {
            staff_ids: selectedStaff
        }).done(function(response) {
            if (response && response.success) {
                notify('success', response.message || 'Access updated successfully.');
                setTimeout(function() { location.reload(); }, 300);
                return;
            }
            notify('danger', (response && response.message) ? response.message : 'Unable to update access.');
        }).fail(function() {
            notify('danger', 'Unable to update access.');
        });
    });

    $('#save-payroll-cutoff-btn').on('click', function() {
        var formData = $('#payroll-cutoff-form').serialize();
        $.post('<?php echo field_staff_settings_escape($save_cutoff_url); ?>', formData, function(response) {
            if (response && response.success) {
                notify('success', response.message || 'Payroll cutoff saved successfully.');
                setTimeout(function() { window.location.href = '<?php echo field_staff_settings_escape($current_url); ?>'; }, 300);
                return;
            }
            notify('danger', (response && response.message) ? response.message : 'Unable to save cutoff.');
        }).fail(function() {
            notify('danger', 'Unable to save payroll cutoff.');
        });
    });

    function submitSettingsSection(section, payload, $button) {
        payload = payload || {};
        payload.section = section;

        if ($button && $button.length) {
            $button.prop('disabled', true);
        }

        return $.post('<?php echo field_staff_settings_escape($save_hr_settings_section_url); ?>', payload)
            .done(function(response) {
                if (response && response.success) {
                    notify('success', response.message || 'Settings saved successfully.');
                    if (section === 'employee_profile' && response.profile) {
                        populateEmployeeProfileForm(response.profile);
                    }
                    return;
                }
                notify('danger', (response && response.message) ? response.message : 'Unable to save settings.');
            })
            .fail(function(xhr) {
                var message = 'Unable to save settings.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                notify('danger', message);
            })
            .always(function() {
                if ($button && $button.length) {
                    $button.prop('disabled', false);
                }
            });
    }

    $('#save-department-settings-btn').on('click', function() {
        var departmentName = $.trim($('#settings-department-name').val());
        if (!departmentName) {
            notify('danger', 'Department name is required.');
            return;
        }
        submitSettingsSection('department', { department_name: departmentName }, $('#save-department-settings-btn'));
    });

    $('#save-employee-profile-settings-btn').on('click', function() {
        var staffId = $('#employee-profile-staff').val();
        if (!staffId) {
            notify('danger', 'Choose an employee before saving profile settings.');
            return;
        }
        submitSettingsSection('employee_profile', {
            staff_id: staffId,
            position: $('#employee-profile-position').val(),
            employment_arrangement: $('#employee-profile-arrangement').val(),
            payment_basis: $('#employee-profile-payment-basis').val(),
            attendance_requirement: $('#employee-profile-attendance').val(),
            department_id: $('#employee-profile-department').val(),
            base_hourly_rate: $('#employee-profile-hourly-rate').val(),
            payroll_group: $('#employee-profile-group').val(),
            payment_method: $('#employee-profile-payment-method').val()
        }, $('#save-employee-profile-settings-btn'));
    });

    $('#save-attendance-policy-btn').on('click', function() {
        submitSettingsSection('attendance_policy', {
            grace_period_mins: $('#attendance-grace-period').val(),
            attendance_rounding: $('#attendance-rounding').val(),
            fixed_start_time_enabled: $('#fixed-start-time-enabled').is(':checked') ? '1' : '0',
            fixed_start_time: $('#fixed-start-time').val(),
            break_policy: $('#break-policy').val(),
            break_duration_limit_mins: $('#break-duration-limit').val(),
        }, $('#save-attendance-policy-btn'));
    });

    $('#save-pay-policy-btn').on('click', function() {
        submitSettingsSection('pay_policy', {
            workweek_start_day: $('#pay-workweek-start').val(),
            holiday_multiplier: $('#pay-holiday-multiplier').val(),
            regular_hours_cap: $('#pay-regular-hours-cap').val(),
            overtime_enabled: $('#pay-overtime-enabled').is(':checked') ? '1' : '0',
            overtime_multiplier: $('#pay-overtime-multiplier').val(),
            daily_field_allowance: $('#pay-daily-field-allowance').val(),
        }, $('#save-pay-policy-btn'));
    });

    $('#payroll-work-record-form').on('submit', function(event) {
        event.preventDefault();
        var $button = $(this).find('button');
        $button.prop('disabled', true);
        $.post('<?php echo field_staff_settings_escape(field_staff_admin_url('field_staff/save_payroll_work_record')); ?>', $(this).serialize())
            .done(function(response) {
                notify(response && response.success ? 'success' : 'danger', response && response.message ? response.message : 'Work record could not be saved.');
            })
            .fail(function() { notify('danger', 'Work record could not be saved.'); })
            .always(function() { $button.prop('disabled', false); });
    });

    $('#save-statutory-policy-btn').on('click', function() {
        submitSettingsSection('statutory_policy', {
            employee_nib_rate: $('#statutory-nib-ee').val(),
            employer_nib_rate: $('#statutory-nib-er').val(),
            employee_nhip_rate: $('#statutory-nhip-ee').val(),
            employer_nhip_rate: $('#statutory-nhip-er').val(),
            verification_status: $('#statutory-verification-status').val(),
            verification_source: $('#statutory-verification-source').val(),
        }, $('#save-statutory-policy-btn'));
    });

    $('#break-policy').on('change', function() {
        var value = $(this).val();
        if (value === 'required') {
            notify('info', 'Break policy set to required - the rule will enforce a break before overtime/waiting thresholds.');
        }
    });

    $('#policy-staff-select, #policy-effective-from').on('change', loadPolicyHistory);
    $('#save-policy-override-btn').on('click', function() {
        var staffId = $('#policy-staff-select').val();
        if (!staffId) {
            notify('danger', 'Please choose an employee before saving a policy override.');
            return;
        }
        $.post('<?php echo field_staff_settings_escape(field_staff_admin_url('field_staff/save_employee_policy_override')); ?>', {
            staff_id: staffId,
            field_name: $('#policy-field-select').val(),
            value: $('#policy-value').val(),
            effective_from: $('#policy-effective-from').val(),
            effective_to: '',
            source: $('#policy-source').val(),
            verification_status: $('#policy-verification-status').val(),
            notes: $('#policy-notes').val()
        }).done(function(response) {
            if (response && response.success) {
                notify('success', response.message || 'Policy override saved successfully.');
                loadPolicyHistory();
                $('#policy-notes').val('');
                return;
            }
            notify('danger', (response && response.message) ? response.message : 'Unable to save policy override.');
        }).fail(function() {
            notify('danger', 'Unable to save policy override.');
        });
    });

    loadPolicyHistory();
});
</script>
