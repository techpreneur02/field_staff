<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
if (!function_exists('html_escape')) {
    function html_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$start_date = isset($start_date) ? html_escape($start_date) : date('Y-m-d', strtotime('monday this week'));
$end_date = isset($end_date) ? html_escape($end_date) : date('Y-m-d', strtotime('sunday this week'));
$records = isset($payroll_records) ? $payroll_records : (isset($payroll_rows) ? $payroll_rows : []);
$legacy_rows = isset($legacy_rows) && is_array($legacy_rows) ? $legacy_rows : [];
$cutoff_options = isset($cutoff_options) && is_array($cutoff_options) ? $cutoff_options : [];
$selected_cutoff_id = isset($selected_cutoff_id) ? (int) $selected_cutoff_id : 0;
$selected_cutoff = isset($selected_cutoff) && is_array($selected_cutoff) ? $selected_cutoff : [];

$form_action = function_exists('current_url') ? current_url() : '';
if ($form_action === '' && isset($_SERVER['REQUEST_URI'])) {
    $form_action = (string) $_SERVER['REQUEST_URI'];
}

$title_text = isset($title) ? html_escape($title) : 'HR Master Payroll Summary';
$summary_error = isset($summary_error) && trim((string) $summary_error) !== '' ? trim((string) $summary_error) : '';
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo $title_text; ?></h4>
                        <p class="text-muted mtop5">Weekly payroll register and statutory reporting summary.</p>

                        <form method="get" action="<?php echo html_escape($form_action); ?>" class="row mtop10">
                            <div class="col-md-3 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label for="cutoff_id" class="control-label">Payroll Cutoff</label>
                                    <select id="cutoff_id" name="cutoff_id" class="form-control" style="width: 100%;">
                                        <option value="0" <?php echo $selected_cutoff_id <= 0 ? 'selected' : ''; ?>>Select payroll cutoff</option>
                                        <?php foreach ($cutoff_options as $cutoff_option) { ?>
                                            <?php $cutoff_id_value = (int) ($cutoff_option['id'] ?? 0); ?>
                                            <?php if ($cutoff_id_value <= 0) continue; ?>
                                            <option value="<?php echo $cutoff_id_value; ?>" <?php echo $selected_cutoff_id === $cutoff_id_value ? 'selected' : ''; ?>>
                                                <?php echo html_escape((string) ($cutoff_option['cutoff_code'] ?? '')) . ' — ' . html_escape((string) ($cutoff_option['start_date'] ?? '')) . ' to ' . html_escape((string) ($cutoff_option['end_date'] ?? '')); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label for="start_date" class="control-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label for="end_date" class="control-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-12 col-xs-12">
                                <div class="form-group mtop25">
                                    <button type="submit" class="btn btn-primary">Apply Period</button>
                                    <a href="<?php echo html_escape($form_action); ?>" class="btn btn-default">Reset</a>
                                </div>
                            </div>
                        </form>

                        <?php if ($selected_cutoff_id > 0 && !empty($selected_cutoff)) { ?>
                            <div class="alert alert-info mtop10">
                                <strong>Cutoff in view:</strong> <?php echo html_escape((string) ($selected_cutoff['cutoff_code'] ?? '')); ?>
                                &nbsp;|&nbsp; <?php echo html_escape((string) ($selected_cutoff['start_date'] ?? '')); ?> to <?php echo html_escape((string) ($selected_cutoff['end_date'] ?? '')); ?>
                                &nbsp;|&nbsp; Payment: <?php echo html_escape((string) ($selected_cutoff['payment_date'] ?? '')); ?>
                            </div>
                            <div class="mtop10">
                                <a class="btn btn-default" href="<?php echo html_escape($export_csv_url . '?cutoff_id=' . $selected_cutoff_id); ?>">Export saved CSV</a>
                                <a class="btn btn-default" target="_blank" rel="noopener" href="<?php echo html_escape($print_url . '?cutoff_id=' . $selected_cutoff_id); ?>">Print saved register</a>
                                <a class="btn btn-default" href="<?php echo html_escape($pdf_url . '?cutoff_id=' . $selected_cutoff_id); ?>">Download saved PDF</a>
                            </div>
                        <?php } ?>

                        <?php if ($summary_error !== '') { ?>
                            <div class="alert alert-danger mtop15">
                                <strong>Payroll summary unavailable.</strong>
                                <?php echo html_escape($summary_error); ?>
                            </div>
                        <?php } ?>

                        <div class="table-responsive mtop15">
                            <table class="table table-striped table-hover<?php echo empty($records) ? '' : ' dt-table'; ?>">
                                <thead>
                                    <tr>
                                        <th>Worker Name</th>
                                        <th class="text-right">Regular Hours (Configured Workweek)</th>
                                        <th class="text-right">Overtime Hours Worked</th>
                                        <th class="text-right">Hourly Rate ($ USD)</th>
                                        <th class="text-right">Gross Earnings Pay</th>
                                        <th class="text-right">Employee NIB Deduction</th>
                                        <th class="text-right">Employer NIB Contribution</th>
                                        <th class="text-right">Employee NHIP Deduction</th>
                                        <th class="text-right">Employer NHIP Contribution</th>
                                        <th class="text-right">Net Salary Pay</th>
                                        <th class="text-center">Payment Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($records) && (is_array($records) || $records instanceof Traversable)) { ?>
                                        <?php foreach ($records as $row) { ?>
                                            <?php
                                            $worker_name = is_array($row) ? ($row['worker_name'] ?? 'Unknown') : ($row->worker_name ?? 'Unknown');
                                            $reg_hours = is_array($row) ? ($row['regular_hours'] ?? 0) : ($row->regular_hours ?? 0);
                                            $ot_hours = is_array($row) ? ($row['overtime_hours'] ?? 0) : ($row->overtime_hours ?? 0);
                                            $rate = is_array($row) ? ($row['hourly_rate'] ?? 0) : ($row->hourly_rate ?? 0);
                                            $gross = is_array($row) ? ($row['gross_earnings'] ?? 0) : ($row->gross_earnings ?? 0);
                                            $ee_nib = is_array($row) ? ($row['employee_nib'] ?? 0) : ($row->employee_nib ?? 0);
                                            $er_nib = is_array($row) ? ($row['employer_nib'] ?? 0) : ($row->employer_nib ?? 0);
                                            $ee_nhip = is_array($row) ? ($row['employee_nhip'] ?? 0) : ($row->employee_nhip ?? 0);
                                            $er_nhip = is_array($row) ? ($row['employer_nhip'] ?? 0) : ($row->employer_nhip ?? 0);
                                            $net = is_array($row) ? ($row['net_salary'] ?? 0) : ($row->net_salary ?? 0);
                                            $status = is_array($row) ? ($row['status'] ?? 'draft') : ($row->status ?? 'draft');
                                            $payroll_id = (int) (is_array($row) ? ($row['id'] ?? 0) : ($row->id ?? 0));

                                            if ($ot_hours == 0) {
                                                $ot_hours = is_array($row) ? ($row['ot_hours'] ?? 0) : ($row->ot_hours ?? 0);
                                            }
                                            if ($gross == 0) {
                                                $gross = is_array($row) ? ($row['gross_salary'] ?? 0) : ($row->gross_salary ?? 0);
                                            }
                                            if ($ee_nib == 0) {
                                                $ee_nib = is_array($row) ? ($row['nib_ee'] ?? 0) : ($row->nib_ee ?? 0);
                                            }
                                            if ($er_nib == 0) {
                                                $er_nib = is_array($row) ? ($row['nib_er'] ?? 0) : ($row->nib_er ?? 0);
                                            }
                                            if ($ee_nhip == 0) {
                                                $ee_nhip = is_array($row) ? ($row['nhip_ee'] ?? 0) : ($row->nhip_ee ?? 0);
                                            }
                                            if ($er_nhip == 0) {
                                                $er_nhip = is_array($row) ? ($row['nhip_er'] ?? 0) : ($row->nhip_er ?? 0);
                                            }

                                            $status = strtolower(trim((string) $status));
                                            $status_class = 'bg-secondary';
                                            if ($status === 'approved') {
                                                $status_class = 'bg-primary';
                                            } elseif ($status === 'paid') {
                                                $status_class = 'bg-success';
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo html_escape((string) $worker_name); ?></td>
                                                <td class="text-right"><?php echo number_format((float) $reg_hours, 2); ?></td>
                                                <td class="text-right"><?php echo number_format((float) $ot_hours, 2); ?></td>
                                                <td class="text-right"><?php echo '$' . number_format((float) $rate, 2); ?></td>
                                                <td class="text-right"><?php echo '$' . number_format((float) $gross, 2); ?></td>
                                                <td class="text-right"><?php echo '$' . number_format((float) $ee_nib, 2); ?></td>
                                                <td class="text-right"><?php echo '$' . number_format((float) $er_nib, 2); ?></td>
                                                <td class="text-right"><?php echo '$' . number_format((float) $ee_nhip, 2); ?></td>
                                                <td class="text-right"><?php echo '$' . number_format((float) $er_nhip, 2); ?></td>
                                                <td class="text-right bold"><?php echo '$' . number_format((float) $net, 2); ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?php echo $status_class; ?> text-uppercase"><?php echo html_escape($status); ?></span>
                                                    <?php if ($payroll_id > 0 && in_array($status, ['draft', 'under_review', 'approved'], true)) { ?>
                                                        <form class="payroll-workflow-form mtop10">
                                                            <input type="hidden" name="payroll_id" value="<?php echo $payroll_id; ?>">
                                                            <select name="action" class="form-control" aria-label="Payroll workflow action">
                                                                <?php if ($status === 'draft') { ?>
                                                                    <option value="submit_for_review">Submit for review</option>
                                                                <?php } elseif ($status === 'under_review') { ?>
                                                                    <option value="approve">Approve reviewed payroll</option>
                                                                    <option value="return_to_draft">Return to draft</option>
                                                                <?php } else { ?>
                                                                    <option value="record_payment">Record payment</option>
                                                                <?php } ?>
                                                            </select>
                                                            <input name="notes" class="form-control mtop5" placeholder="Audit notes" aria-label="Payroll audit notes" required>
                                                            <?php if ($status === 'approved') { ?>
                                                                <input name="payment_reference" class="form-control mtop5" placeholder="Payment reference" aria-label="Payment reference" required>
                                                                <input type="date" name="payment_date" class="form-control mtop5" aria-label="Payment date" required>
                                                                <input type="number" name="payment_amount" min="0" step="0.01" value="<?php echo number_format((float) $net, 2, '.', ''); ?>" class="form-control mtop5" aria-label="Payment amount" required>
                                                                <select name="payment_method" class="form-control mtop5" aria-label="Payment method">
                                                                    <option value="online_transfer">Online transfer</option><option value="cash">Cash</option><option value="check">Check</option>
                                                                </select>
                                                            <?php } ?>
                                                            <button type="submit" class="btn btn-primary btn-sm mtop5">Apply workflow action</button>
                                                        </form>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="11" class="text-center text-muted">No payroll records found for the selected weekly period.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!empty($legacy_rows)) { ?>
                            <div class="mtop25">
                                <h4 class="no-margin">Legacy Payroll History</h4>
                                <p class="text-muted">Unmapped payroll rows remain here until explicitly assigned to a cutoff.</p>
                                <div class="table-responsive mtop10">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Worker Name</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th class="text-right">Gross</th>
                                                <th class="text-right">Net</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($legacy_rows as $legacy_row) { ?>
                                                <?php
                                                $legacy_name = is_array($legacy_row) ? ($legacy_row['worker_name'] ?? 'Unknown') : ($legacy_row->worker_name ?? 'Unknown');
                                                $legacy_start = is_array($legacy_row) ? ($legacy_row['start_date'] ?? '') : ($legacy_row->start_date ?? '');
                                                $legacy_end = is_array($legacy_row) ? ($legacy_row['end_date'] ?? '') : ($legacy_row->end_date ?? '');
                                                $legacy_gross = is_array($legacy_row) ? ($legacy_row['gross_salary'] ?? 0) : ($legacy_row->gross_salary ?? 0);
                                                $legacy_net = is_array($legacy_row) ? ($legacy_row['net_salary'] ?? 0) : ($legacy_row->net_salary ?? 0);
                                                $legacy_status = is_array($legacy_row) ? ($legacy_row['status'] ?? 'draft') : ($legacy_row->status ?? 'draft');
                                                ?>
                                                <tr>
                                                    <td><?php echo html_escape((string) $legacy_name); ?></td>
                                                    <td><?php echo html_escape((string) $legacy_start); ?></td>
                                                    <td><?php echo html_escape((string) $legacy_end); ?></td>
                                                    <td class="text-right">$<?php echo number_format((float) $legacy_gross, 2); ?></td>
                                                    <td class="text-right">$<?php echo number_format((float) $legacy_net, 2); ?></td>
                                                    <td class="text-center"><span class="badge bg-secondary text-uppercase"><?php echo html_escape((string) $legacy_status); ?></span></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    $(document).on('submit', '.payroll-workflow-form', function(event) {
        event.preventDefault();
        var $form = $(this);
        var $button = $form.find('button');
        $button.prop('disabled', true);
        $.post('<?php echo html_escape(field_staff_admin_url('field_staff/payroll_workflow_action')); ?>', $form.serialize())
            .done(function(response) {
                if (response && response.success) {
                    window.location.reload();
                    return;
                }
                alert_float('danger', response && response.message ? response.message : 'Payroll workflow action failed.');
            })
            .fail(function() { alert_float('danger', 'Payroll workflow action failed.'); })
            .always(function() { $button.prop('disabled', false); });
    });
});
</script>