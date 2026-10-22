<?php

require __DIR__ . '/payroll_test_bootstrap.php';

function check($condition, $message)
{
    if (!$condition) { throw new RuntimeException($message); }
}

function near($actual, $expected, $message)
{
    check(abs((float) $actual - $expected) < 0.00001, $message . ': expected ' . $expected . ', got ' . $actual);
}

function blocked(callable $operation, $message)
{
    try { $operation(); } catch (DomainException $e) { return; }
    throw new RuntimeException($message);
}

function calculate($model, $start = '2026-10-12', $end = '2026-10-16')
{
    return $model->calculate_payroll_for_staff(21, $start, $end);
}

function setting($model, $key, $value)
{
    $model->db->from('fs_settings')->where('setting_key', $key)->update('fs_settings', ['setting_value' => $value]);
}

if (($argv[1] ?? '') === '--endpoint') {
    $model = payroll_fixture();
    $scenario = $argv[3] ?? '';
    $GLOBALS['payroll_test_admin'] = $scenario !== 'unauthorized';
    if ($scenario === 'unresolved') { $model->db->tables['fs_attendance'][0]['clock_out'] = null; }
    if ($scenario === 'component-failure') { $model->db->failTable = 'fs_payroll_values'; }
    if ($scenario === 'snapshot-failure') { $model->db->failTable = 'fs_payroll_snapshots'; }
    if ($scenario === 'audit-failure') {
        $calculation = calculate($model);
        $master = array_merge($calculation, [
            'id' => 1, 'staff_id' => 21, 'cutoff_id' => 1, 'start_date' => '2026-10-12',
            'end_date' => '2026-10-16', 'status' => 'under_review', 'hourly_rate' => 20,
        ]);
        $model->db->tables['fs_payroll_master'][] = $master;
        $snapshot = $model->build_payroll_snapshot_payload(1, $master, $calculation, $model->get_payroll_profile(21), $model->get_payroll_cutoff_by_id(1));
        $model->save_payroll_snapshot(1, 'under_review', $snapshot);
        $model->db->failTable = 'fs_payroll_workflow_history';
    }
    if ($scenario === 'adjustments') {
        $model->db->tables['fs_payroll_profiles'][0]['loan_repayment'] = 10;
        $model->db->tables['fs_payroll_profiles'][0]['vacation_pay'] = 50;
    }
    if (in_array($scenario, ['work-input', 'work-input-failure'], true)) {
        $model->db->tables['fs_payroll_profiles'][0]['payment_basis'] = 'salary';
        if ($scenario === 'work-input-failure') { $model->db->failTable = 'fs_payroll_work_records'; }
    }
    if ($scenario === 'salary') {
        $model->db->tables['fs_payroll_profiles'][0]['payment_basis'] = 'salary';
        $model->save_approved_payroll_work_record([
            'staff_id' => 21, 'cutoff_id' => 1, 'start_date' => '2026-10-12', 'end_date' => '2026-10-16',
            'quantity' => 1, 'rate' => 700, 'reference' => 'TEST-SALARY',
        ], 7);
        $model->db->tables['fs_payroll_work_records'][0]['approved_at'] = '2026-10-17 09:00:00';
    }
    if ($scenario === 'protected') {
        $model->db->tables['fs_payroll_master'][] = ['id' => 1, 'staff_id' => 21, 'cutoff_id' => 1, 'status' => 'paid', 'net_salary' => 123];
    }
    $controller = (new ReflectionClass(Field_staff::class))->newInstanceWithoutConstructor();
    $controller->db = $model->db;
    $controller->field_staff_model = $model;
    $controller->input = new PayrollTestInput();
    $controller->input->post = ['cutoff_id' => 1, 'staff_id' => 21, 'employee_ids' => [21], 'payroll_id' => 1, 'action' => 'approve'];
    if ($scenario === 'explicit-adjustments') {
        $controller->input->post += ['commission' => 50, 'vacation_pay' => 30, 'loan_adjustment' => 5, 'advance' => 2];
    }
    if ($scenario === 'invalid-adjustment') { $controller->input->post['commission'] = 'invalid'; }
    if (in_array($scenario, ['work-input', 'work-input-failure'], true)) {
        $controller->input->post += ['attest_approved' => '1', 'start_date' => '2026-10-12', 'end_date' => '2026-10-16',
            'quantity' => 1, 'rate' => 700, 'reference' => 'TEST-WORK-INPUT'];
    }
    ob_start();
    register_shutdown_function(function () use ($model) {
        $output = ob_get_clean();
        echo json_encode(['response' => json_decode($output, true), 'raw' => $output,
            'tables' => $model->db->tables, 'commits' => $model->db->commits, 'rollbacks' => $model->db->rollbacks], JSON_THROW_ON_ERROR);
    });
    $route = $argv[2];
    $controller->$route();
    exit;
}

function endpoint($route, $scenario = '')
{
    $process = proc_open([PHP_BINARY, __FILE__, '--endpoint', $route, $scenario],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    check(is_resource($process), 'Could not start controller regression process');
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    check($status === 0, 'Controller process failed: ' . $error . $output);
    $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    check(is_array($result['response']), 'Controller did not return JSON: ' . $result['raw']);
    return $result;
}

$model = payroll_fixture();
$result = calculate($model);
near($result['regular_hours'], 44, 'Five ten-hour shifts: regular hours');
near($result['ot_hours'], 6, 'Five ten-hour shifts: overtime hours');
near($result['gross_salary'], 1060, 'Weekly gross at configured test rates');
near($result['net_salary'], 932.80, 'Configured test statutory deductions, not hardcoded rates');
echo "PASS five ten-hour shifts: 44 regular + 6 overtime; gross 1060; net 932.80\n";

$early = calculate($model, '2026-10-12', '2026-10-15');
$late = calculate($model, '2026-10-16', '2026-10-16');
near($early['regular_hours'], 40, 'Earlier cutoff regular hours');
near($late['regular_hours'], 4, 'Later cutoff must retain earlier weekly consumption');
near($late['ot_hours'], 6, 'Later cutoff overtime');
near($early['gross_salary'] + $late['gross_salary'], $result['gross_salary'], 'Split cutoff financial parity');
setting($model, 'field_staff_workweek_start_day', '3');
$wednesday = calculate($model);
near($wednesday['regular_hours'], 50, 'Configured Wednesday start creates two distinct workweeks');
near($wednesday['ot_hours'], 0, 'Configured Wednesday workweek overtime');
echo "PASS cutoff-crossing workweek and configured start day\n";

$model = payroll_fixture();
for ($day = 19; $day <= 23; $day++) {
    $date = '2026-10-' . $day;
    $model->db->tables['fs_attendance'][] = ['id' => $day, 'staff_id' => 21, 'date' => $date,
        'clock_in' => $date . ' 08:00:00', 'clock_out' => $date . ' 18:00:00'];
}
$two_weeks = calculate($model, '2026-10-12', '2026-10-23');
near($two_weeks['regular_hours'], 88, 'Two weeks reset regular cap');
near($two_weeks['ot_hours'], 12, 'Two weeks overtime');
near($two_weeks['gross_salary'], 2120, 'Two weeks gross');
setting($model, 'field_staff_overtime_enabled', '0');
near(calculate($model, '2026-10-12', '2026-10-23')['gross_salary'], 2000, 'Disabling overtime must retain all worked hours at base rate');
echo "PASS multiweek reset: 88 regular + 12 overtime; overtime-disabled hours are not dropped\n";

foreach (['paid' => [44, 6, 1060], 'unpaid' => [44, 1, 910]] as $type => $expected) {
    $model = payroll_fixture();
    foreach ($model->db->tables['fs_attendance'] as $attendance) {
        $model->db->tables['fs_break_sessions'][] = [
            'id' => $attendance['id'], 'attendance_id' => $attendance['id'],
            'break_out_at' => $attendance['date'] . ' 12:00:00',
            'break_in_at' => $attendance['date'] . ' 13:00:00', 'break_type' => $type,
        ];
    }
    $result = calculate($model);
    near($result['regular_hours'], $expected[0], $type . ' breaks regular hours');
    near($result['ot_hours'], $expected[1], $type . ' breaks overtime hours');
    near($result['gross_salary'], $expected[2], $type . ' breaks gross');
}
echo "PASS paid versus unpaid breaks: 50 versus 45 payable hours\n";

foreach (['salary', 'daily', 'per_job', 'per_unit'] as $basis) {
    $model = payroll_fixture();
    $model->db->tables['fs_payroll_profiles'][0]['payment_basis'] = $basis;
    blocked(function () use ($model) { calculate($model); }, 'Missing ' . $basis . ' inputs must not fall back to hourly pay');
    $input = ['staff_id' => 21, 'cutoff_id' => 1, 'start_date' => '2026-10-12',
        'end_date' => $basis === 'salary' ? '2026-10-16' : '2026-10-12',
        'quantity' => in_array($basis, ['salary', 'daily'], true) ? 1 : 3,
        'rate' => $basis === 'salary' ? 700 : 100, 'reference' => 'TEST-' . $basis];
    check($model->save_approved_payroll_work_record($input, 7), 'Approved work record save');
    if ($basis === 'daily') {
        blocked(function () use ($model) { calculate($model); }, 'A missing daily rate record must block payroll');
        for ($day = 13; $day <= 16; $day++) {
            $daily = array_merge($input, ['start_date' => '2026-10-' . $day, 'end_date' => '2026-10-' . $day, 'reference' => 'TEST-DAY-' . $day]);
            check($model->save_approved_payroll_work_record($daily, 7), 'Daily work input save');
        }
    }
    near(calculate($model)['gross_salary'], $basis === 'salary' ? 700 : ($basis === 'daily' ? 500 : 300), $basis . ' explicit input gross');
    blocked(function () use ($model, $input) { $model->save_approved_payroll_work_record($input, 7); }, 'Duplicate work input');
    $valid_record = $model->db->tables['fs_payroll_work_records'][0];
    foreach (['rate', 'quantity'] as $required) {
        foreach ([null, 0, -1, 'invalid'] as $invalid) {
            $model->db->tables['fs_payroll_work_records'][0][$required] = $invalid;
            blocked(function () use ($model) { calculate($model); }, $basis . ' missing/invalid ' . $required);
        }
        $model->db->tables['fs_payroll_work_records'][0] = $valid_record;
    }
    $model->db->tables['fs_payroll_work_records'][0]['status'] = 'pending';
    blocked(function () use ($model) { calculate($model); }, 'Unapproved work cannot be paid');
}
echo "PASS all four non-hourly bases require explicit approved inputs; duplicates and pending inputs rejected\n";

$model = payroll_fixture();
$raw = $model->db->tables['fs_attendance'];
$model->db->tables['fs_attendance_corrections'][] = [
    'id' => 1, 'attendance_id' => 16, 'staff_id' => 21, 'correction_type' => 'clock_out',
    'before_value' => '2026-10-16 18:00:00', 'after_value' => '2026-10-16 20:00:00',
    'status' => 'pending', 'created_at' => '2026-11-10 09:00:00', 'approved_by' => 7,
];
check($model->has_unresolved_attendance_for_staff(21, '2026-10-12', '2026-10-16'), 'Late correction must block affected earlier cutoff');
check(!$model->has_unresolved_attendance_for_staff(21, '2026-11-01', '2026-11-15'), 'Submission cutoff must not be blocked by earlier work');
blocked(function () use ($model) { calculate($model); }, 'Pending correction calculation blocker');
check($model->approve_attendance_correction(1, 7, 'approved', 'TEST approved late correction'), 'Approve valid pending correction');
check(!$model->approve_attendance_correction(1, 7, 'approved'), 'Resolved correction cannot be reapproved');
near(calculate($model)['ot_hours'], 8, 'Approved clock correction affects payable hours');
near(calculate($model)['gross_salary'], 1120, 'Approved clock correction gross');
check($model->db->tables['fs_attendance'] === $raw, 'Raw punches must be immutable');
echo "PASS late corrections follow work date; approved correction changes calculation without changing raw punches\n";

$model = payroll_fixture();
$model->db->tables['fs_break_sessions'][] = ['id' => 1, 'attendance_id' => 12, 'break_type' => 'unpaid',
    'break_out_at' => '2026-10-12 12:00:00', 'break_in_at' => '2026-10-12 13:00:00'];
check($model->save_attendance_correction_request([
    'attendance_id' => 12, 'staff_id' => 21, 'requested_by' => 7, 'correction_type' => 'break:1:break_in_at',
    'before_value' => '2026-10-12 13:00:00', 'after_value' => '2026-10-12 12:30:00', 'reason' => 'TEST break return correction',
]), 'Submit validated break correction');
check($model->approve_attendance_correction(1, 7, 'approved'), 'Approve validated break correction');
near(calculate($model)['ot_hours'], 5.5, 'Approved break correction changes unpaid duration');
near(calculate($model)['gross_salary'], 1045, 'Approved break correction gross');
check($model->db->tables['fs_break_sessions'][0]['break_in_at'] === '2026-10-12 13:00:00', 'Raw break punches must be immutable');
echo "PASS approved unpaid-break correction; raw break punch preserved\n";

foreach (['open', 'malformed', 'overflow', 'relative', 'reversed', 'overlap', 'outside'] as $defect) {
    $model = payroll_fixture();
    if ($defect === 'open') { $model->db->tables['fs_attendance'][0]['clock_out'] = null; }
    if ($defect === 'malformed') { $model->db->tables['fs_attendance'][0]['clock_in'] = 'not-a-date'; }
    if ($defect === 'overflow') { $model->db->tables['fs_attendance'][0]['clock_in'] = '2026-02-30 08:00:00'; }
    if ($defect === 'relative') { $model->db->tables['fs_attendance'][0]['clock_in'] = 'yesterday'; }
    if ($defect === 'reversed') { $model->db->tables['fs_attendance'][0]['clock_out'] = '2026-10-12 07:00:00'; }
    if (in_array($defect, ['overlap', 'outside'], true)) {
        $model->db->tables['fs_break_sessions'][] = ['id' => 1, 'attendance_id' => 12,
            'break_out_at' => '2026-10-12 12:00:00', 'break_in_at' => '2026-10-12 13:00:00', 'break_type' => 'unpaid'];
        $model->db->tables['fs_break_sessions'][] = ['id' => 2, 'attendance_id' => 12,
            'break_out_at' => '2026-10-12 ' . ($defect === 'outside' ? '06:00:00' : '12:30:00'),
            'break_in_at' => '2026-10-12 14:00:00', 'break_type' => 'unpaid'];
    }
    blocked(function () use ($model) { calculate($model); }, 'Unresolved attendance defect: ' . $defect);
}
echo "PASS open, malformed, reversed, overlapping and out-of-shift attendance blockers\n";

foreach (['', 'adjustments', 'explicit-adjustments', 'salary'] as $scenario) {
    $preview = endpoint('generate_payrun', $scenario);
    $individual = endpoint('save_payroll', $scenario);
    $bulk = endpoint('apply_payrun', $scenario);
    foreach ([$preview, $individual, $bulk] as $response) {
        check($response['response']['success'] === true, 'Endpoint failure: ' . json_encode($response['response']));
    }
    $row = $preview['response']['statement']['rows'][0];
    foreach ([$individual, $bulk] as $saved) {
        check($saved['commits'] === 1 && $saved['rollbacks'] === 0, 'Successful save must commit once');
        $master = $saved['tables']['fs_payroll_master'][0];
        $snapshot = json_decode($saved['tables']['fs_payroll_snapshots'][0]['snapshot_data'], true, 512, JSON_THROW_ON_ERROR);
        foreach (['regular_hours', 'ot_hours', 'gross_salary', 'net_salary', 'nib_ee', 'nib_er', 'nhip_ee', 'nhip_er'] as $key) {
            near($master[$key], $row[$key], 'Preview / master parity: ' . $key);
            near($snapshot['totals'][$key], $row[$key], 'Preview / snapshot parity: ' . $key);
        }
        check($snapshot['calculation'] == $row || $snapshot['calculation'] == array_diff_key($row, array_flip(['worker_name', 'department_name', 'payment_method', 'bank_account_info'])), 'Snapshot must retain the shared calculation');
    }
}
echo "PASS actual preview, individual-save and bulk-controller / snapshot numerical parity (hourly, adjustments, salary)\n";

foreach (['save_payroll', 'apply_payrun'] as $route) {
    foreach (['unresolved', 'component-failure', 'snapshot-failure', 'protected'] as $scenario) {
        $response = endpoint($route, $scenario);
        check($response['response']['success'] === false, $route . ' should reject ' . $scenario);
        check($response['commits'] === 0 && $response['rollbacks'] === 1, 'Failure must roll back: ' . $route . ' / ' . $scenario);
        check(count($response['tables']['fs_payroll_master']) === ($scenario === 'protected' ? 1 : 0), 'Rollback must preserve master state');
        if ($scenario === 'protected') { near($response['tables']['fs_payroll_master'][0]['net_salary'], 123, 'Paid history unchanged'); }
    }
}
foreach (['generate_payrun', 'save_payroll', 'apply_payrun', 'save_payroll_work_record'] as $route) {
    check(endpoint($route, 'unauthorized')['response']['success'] === false, 'Unauthorized route must reject: ' . $route);
}
check(endpoint('save_payroll', 'invalid-adjustment')['response']['success'] === false, 'Invalid adjustment must not be silently ignored');
$work_input = endpoint('save_payroll_work_record', 'work-input');
check($work_input['response']['success'] && $work_input['commits'] === 1, 'Authorized work-input save must commit');
check($work_input['tables']['fs_payroll_work_records'][0]['approved_by'] === 7, 'Work-input actor must be server-resolved');
$work_failure = endpoint('save_payroll_work_record', 'work-input-failure');
check(!$work_failure['response']['success'] && $work_failure['rollbacks'] === 1 && empty($work_failure['tables']['fs_payroll_work_records']), 'Work-input persistence failure rollback');
echo "PASS controller authorization, transaction rollback, invalid adjustments and paid-history protections\n";

$audit_failure = endpoint('payroll_workflow_action', 'audit-failure');
check(!$audit_failure['response']['success'] && $audit_failure['rollbacks'] === 1, 'Audit failure must roll back approval');
check($audit_failure['tables']['fs_payroll_master'][0]['status'] === 'under_review', 'Audit failure must preserve master status');
check($audit_failure['tables']['fs_payroll_snapshots'][0]['snapshot_status'] === 'under_review', 'Audit failure must preserve snapshot status');

$model = payroll_fixture();
$saved = endpoint('save_payroll');
$model->db->tables = $saved['tables'];
check(!$model->apply_payroll_workflow_action(1, 'approve', 7)['success'], 'Draft approval must be rejected');
check(!$model->apply_payroll_workflow_action(1, 'record_payment', 7, ['payment_reference' => 'TEST'])['success'], 'Draft payment must be rejected');
check($model->apply_payroll_workflow_action(1, 'submit_for_review', 7)['success'], 'Valid submission');
check($model->apply_payroll_workflow_action(1, 'return_to_draft', 7)['success'], 'Review can be returned to draft');
check($model->get_payroll_snapshot(1)['status'] === 'draft', 'Return to draft must update saved snapshot status');
check($model->apply_payroll_workflow_action(1, 'submit_for_review', 7)['success'], 'Returned draft can be resubmitted');
setting($model, 'field_staff_statutory_verification_status', 'pending');
check(!$model->apply_payroll_workflow_action(1, 'approve', 7)['success'], 'Statutory verification gate');
setting($model, 'field_staff_statutory_verification_status', 'verified');
$model->db->tables['fs_attendance_corrections'][] = [
    'id' => 2, 'attendance_id' => 16, 'staff_id' => 21, 'correction_type' => 'clock_out',
    'before_value' => '2026-10-16 18:00:00', 'after_value' => '2026-10-16 20:00:00',
    'status' => 'pending', 'created_at' => '2026-11-10 09:00:00', 'approved_by' => 7,
];
check(!$model->apply_payroll_workflow_action(1, 'approve', 7)['success'], 'Unresolved attendance must block approval');
$model->db->tables['fs_attendance_corrections'][0]['status'] = 'approved';
check(!$model->apply_payroll_workflow_action(1, 'approve', 7)['success'], 'Resolved late correction must require a fresh snapshot');
$model->db->tables['fs_attendance_corrections'] = [];
check($model->apply_payroll_workflow_action(1, 'approve', 7)['success'], 'Valid reviewed payroll can be approved');
check(!$model->apply_payroll_workflow_action(1, 'return_to_draft', 7)['success'], 'Approved payroll must not be reopened');
check(!$model->apply_payroll_workflow_action(1, 'approve', 7)['success'], 'Duplicate approval must reject');
check(!$model->apply_payroll_workflow_action(1, 'approve', 0)['success'], 'Missing actor must reject');
$approved = $model->get_payroll_snapshot(1);
$tampered = $approved;
$tampered['totals']['gross_salary'] = 99999;
check(!$model->save_payroll_snapshot(1, 'approved', $tampered), 'Approved financial snapshot cannot be overwritten');
check($model->get_payroll_snapshot(1) == $approved, 'Rejected snapshot overwrite must preserve history');
check(!$model->apply_payroll_workflow_action(1, 'record_payment', 7, [
    'payment_reference' => 'TEST-INVALID', 'payment_method' => 'cash', 'payment_date' => '2026-10-17', 'payment_amount' => 1,
])['success'], 'Payment amount mismatch must be rejected');
check($model->apply_payroll_workflow_action(1, 'record_payment', 7, [
    'payment_reference' => 'TEST-PAID', 'payment_method' => 'online_transfer', 'payment_date' => '2026-10-17', 'payment_amount' => 932.8,
])['success'], 'Valid payment preserves approved financial snapshot');
near($model->get_payroll_snapshot(1)['totals']['net_salary'], 932.8, 'Payment must preserve saved net salary');
echo "PASS review-only approval, statutory gate, unresolved/stale corrections and protected approved history\n";

function payroll_report_html($status)
{
    $payroll_records = [['id' => 1, 'worker_name' => 'TEST payroll', 'status' => $status, 'net_salary' => 932.8]];
    $selected_cutoff_id = 1;
    $selected_cutoff = ['id' => 1, 'cutoff_code' => 'TEST'];
    $export_csv_url = '/admin/field_staff/export_payroll_register';
    $print_url = '/admin/field_staff/print_payroll_register';
    $pdf_url = '/admin/field_staff/download_payroll_register_pdf';
    ob_start();
    require dirname(__DIR__) . '/views/payroll_report.php';
    return ob_get_clean();
}
$draft_html = payroll_report_html('draft');
check(strpos($draft_html, 'value="submit_for_review"') !== false && strpos($draft_html, 'value="approve"') === false, 'Draft UI must only offer review');
$review_html = payroll_report_html('under_review');
check(strpos($review_html, 'value="approve"') !== false && strpos($review_html, 'value="return_to_draft"') !== false, 'Review UI actions');
$approved_html = payroll_report_html('approved');
check(strpos($approved_html, 'value="record_payment"') !== false && strpos($approved_html, 'value="return_to_draft"') === false, 'Approved UI must not reopen history');
check(strpos($approved_html, 'export_payroll_register?cutoff_id=1') !== false && strpos($approved_html, 'download_payroll_register_pdf?cutoff_id=1') !== false, 'Saved register export links');
check(strpos(payroll_report_html('paid'), 'name="action"') === false, 'Paid UI offers no financial mutation');
echo "PASS rendered payroll UI workflow gating and saved CSV/print/PDF links\n";
echo "payroll regression checks passed\n";
