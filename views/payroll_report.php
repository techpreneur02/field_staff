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

$form_action = function_exists('current_url') ? current_url() : '';
if ($form_action === '' && isset($_SERVER['REQUEST_URI'])) {
    $form_action = (string) $_SERVER['REQUEST_URI'];
}

$title_text = isset($title) ? html_escape($title) : 'HR Master Payroll Summary';
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
                            <div class="col-md-4 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label for="start_date" class="control-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label for="end_date" class="control-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12 col-xs-12">
                                <div class="form-group mtop25">
                                    <button type="submit" class="btn btn-primary">Apply Period</button>
                                    <a href="<?php echo html_escape($form_action); ?>" class="btn btn-default">Reset</a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive mtop15">
                            <table class="table table-striped table-hover dt-table">
                                <thead>
                                    <tr>
                                        <th>Worker Name</th>
                                        <th class="text-right">Regular Hours Worked (Capped at 44)</th>
                                        <th class="text-right">Overtime Hours Worked</th>
                                        <th class="text-right">Hourly Rate ($ USD)</th>
                                        <th class="text-right">Gross Earnings Pay</th>
                                        <th class="text-right">Employee NIB Deduction (5.5%)</th>
                                        <th class="text-right">Employer NIB Contribution (6.5%)</th>
                                        <th class="text-right">Employee NHIP Deduction (3.0%)</th>
                                        <th class="text-right">Employer NHIP Contribution (3.0%)</th>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>