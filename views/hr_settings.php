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
                    <div class="panel-heading">Pending Configuration Sections</div>
                    <div class="panel-body">
                        <div class="row">
                            <?php foreach (['Employees and Employment','Attendance Policies','Break Policies','Pay Policies','Statutory Rules'] as $pending_section) { ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="alert alert-light" style="border: 1px solid #dbeafe; background: #f8fafc;">
                                        <strong><?php echo field_staff_settings_escape($pending_section); ?></strong><br>
                                        <span class="pending-pill">Pending</span>
                                        <p class="mtop10 text-muted mbot0">This section is intentionally left for a future phase and does not save changes yet.</p>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
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
});
</script>
