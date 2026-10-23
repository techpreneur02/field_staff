<?php

require __DIR__ . '/payroll_test_bootstrap.php';

if (($argv[1] ?? '') === '--endpoint') {
    $model = payroll_fixture();
    $model->db->tables['fs_attendance'] = [];
    $scenario = $argv[2];
    $GLOBALS['attendance_test_staff_id'] = $scenario === 'unauthorized' ? 0 : 21;
    $controller = (new ReflectionClass(Field_staff::class))->newInstanceWithoutConstructor();
    $controller->field_staff_model = $model;
    $controller->input = new PayrollTestInput();
    $controller->input->post = ['action' => 'clock_in', 'latitude' => 21.8, 'longitude' => -72.1, 'staff_id' => 99];
    if ($scenario === 'invalid-coordinates') { $controller->input->post['latitude'] = 91; }
    if ($scenario === 'invalid-break-type') {
        $controller->input->post['action'] = 'break_out';
        $controller->input->post['break_type'] = 'unknown';
    }
    if ($scenario === 'inactive') { $model->db->tables['staff'][0]['active'] = 0; }
    if ($scenario === 'persistence-failure') { $model->db->failTable = 'fs_attendance'; }
    ob_start();
    register_shutdown_function(function () use ($model) {
        $response = json_decode(ob_get_clean(), true);
        echo json_encode(['response' => $response, 'attendance' => $model->db->tables['fs_attendance'],
            'locks' => $model->db->lockedStaff, 'rollbacks' => $model->db->rollbacks], JSON_THROW_ON_ERROR);
    });
    $controller->clock_action();
    exit;
}

if (($argv[1] ?? '') === '--render') {
    $render = function () {
        $can_view_team_monitor = true;
        require dirname(__DIR__) . '/views/attendance_dashboard.php';
    };
    $render->call(new Field_staff_model());
    exit;
}

function attendance_check($condition, $message)
{
    if (!$condition) { throw new RuntimeException($message); }
}

function attendance_rejected(callable $action)
{
    try { $action(); } catch (DomainException $e) { return; }
    throw new RuntimeException('Invalid attendance action was accepted.');
}

$model = payroll_fixture();
$model->db->tables['fs_attendance'] = [];
$model->db->from('fs_settings')->where('setting_key', 'field_staff_attendance_rounding')->update('fs_settings', ['setting_value' => 'none']);
$model->db->tables['fs_settings'][] = ['setting_key' => 'field_staff_attendance_rounding', 'setting_value' => 'none'];
$action = function ($name, $type = 'paid') use (&$model) {
    return $model->record_clock_action(21, $name, 21.8, -72.1, 'Lifecycle regression', $type);
};
$state = function () use (&$model) { return $model->get_employee_attendance_state(21); };
attendance_check($state()['allowed_actions'] === ['clock_in'], 'Initial state must allow only Clock In.');
attendance_rejected(function () use ($action) { $action('break_in'); });
attendance_check($action('clock_in'), 'Clock In failed.');
attendance_check(abs(strtotime($model->db->tables['fs_attendance'][0]['clock_in']) - time()) <= 1, 'Clock In was rounded instead of preserving its raw timestamp.');
attendance_check($state()['status'] === 'working', 'Clock In must enter Working.');
attendance_check($state()['allowed_actions'] === ['break_out', 'clock_out'], 'Working state must not expose Break In before a break starts.');
attendance_check(count($model->db->lockedStaff) === 2, 'Every transition must lock the stable staff row.');
$rawIn = $model->db->tables['fs_attendance'][0]['clock_in'];
attendance_rejected(function () use ($action) { $action('clock_in'); });
attendance_rejected(function () use ($action) { $action('in'); });
attendance_rejected(function () use ($action) { $action('break_out', 'invalid'); });
attendance_check(count($model->db->tables['fs_attendance']) === 1, 'Duplicate open shift was inserted.');
attendance_check($action('break_out', 'unpaid'), 'First Break Out failed.');
attendance_check($state()['status'] === 'on_break' && $state()['allowed_actions'] === ['break_in'], 'On-break actions incorrect.');
attendance_check($state()['break_type'] === 'unpaid' && $state()['break_started_at'] !== null, 'Break details missing.');
attendance_check($state() === $state(), 'Reloaded state must be stable.');
attendance_rejected(function () use ($action) { $action('break_out'); });
attendance_rejected(function () use ($action) { $action('clock_out'); });
// Use deterministic historical times instead of sleeping or changing the server clock.
$model->db->tables['fs_attendance'][0]['clock_in'] = date('Y-m-d H:i:s', time() - 7200);
$model->db->tables['fs_break_sessions'][0]['break_out_at'] = date('Y-m-d H:i:s', time() - 600);
attendance_check($state()['elapsed_break_seconds'] >= 600, 'Elapsed timer must use the persisted start.');
attendance_check($action('break_in', 'paid'), 'First Break In failed.');
attendance_check($model->db->tables['fs_break_sessions'][0]['break_type'] === 'unpaid', 'Break In changed the original unpaid type.');
attendance_check($state()['status'] === 'working', 'Break In must restore Working.');
$model->db->tables['fs_break_sessions'][0]['break_in_at'] = date('Y-m-d H:i:s', time() - 300);
$model->db->tables['fs_break_sessions'][0]['ended_at'] = $model->db->tables['fs_break_sessions'][0]['break_in_at'];
attendance_check($action('break_out', 'paid'), 'Second Break Out failed.');
$model->db->tables['fs_break_sessions'][1]['break_out_at'] = date('Y-m-d H:i:s', time() - 2);
attendance_check($action('break_in', 'unpaid'), 'Second Break In failed.');
attendance_check($model->db->tables['fs_break_sessions'][1]['break_type'] === 'paid', 'Second type was changed.');
attendance_check($action('clock_out'), 'Clock Out failed.');
attendance_check($state()['status'] === 'clocked_out', 'Clock Out must restore Clocked out.');
$summary = $model->get_attendance_payable_summary($model->db->tables['fs_attendance'][0]);
attendance_check($summary['unpaid_break_seconds'] >= 300 && $summary['paid_break_seconds'] >= 2, 'Break durations were not saved.');
attendance_check($summary['payable_seconds'] === $summary['shift_seconds'] - $summary['unpaid_break_seconds'], 'Unpaid-only deduction incorrect.');
attendance_check($rawIn !== '' && count($model->db->tables['fs_break_sessions']) === 2, 'Multiple breaks not retained.');
attendance_rejected(function () use ($action) { $action('clock_out'); });

$overnight = ['id' => 100, 'staff_id' => 21, 'date' => '2026-10-12',
    'clock_in' => '2026-10-12 22:00:00', 'clock_out' => '2026-10-13 06:00:00',
    'break_rows' => [['id' => 10, 'break_out_at' => '2026-10-12 23:45:00',
        'break_in_at' => '2026-10-13 00:15:00', 'ended_at' => '2026-10-13 00:15:00', 'break_type' => 'unpaid']]];
$summary = $model->summarize_attendance_record($overnight);
attendance_check(!$summary['has_unresolved_breaks'] && $summary['payable_seconds'] === 27000.0, 'Overnight 8h minus 30m should be 7.5h.');
attendance_check($model->apply_working_time_policy('2026-10-12', '2026-10-12 23:59:00', 'out') === '2026-10-12 23:59:00', 'Disabled rounding changed raw time.');
$model->db->tables['fs_settings'] = [['setting_key' => 'field_staff_attendance_rounding', 'setting_value' => 'nearest_15']];
attendance_check($model->apply_working_time_policy('2026-10-12', '2026-10-12 23:59:00', 'out') === '2026-10-13 00:00:00', 'Midnight rounding lost its day carry.');
attendance_check($model->apply_working_time_policy('2026-10-12', '2026-10-13 06:00:00', 'out') === '2026-10-13 06:00:00', 'Overnight return was moved onto the start date.');
$rounded = ['id' => 100, 'staff_id' => 21, 'raw_punches' => 1, 'date' => '2026-10-12',
    'clock_in' => '2026-10-12 22:01:00', 'clock_out' => '2026-10-12 23:59:00', 'break_rows' => []];
attendance_check($model->summarize_attendance_record($rounded)['payable_seconds'] === 7200.0, 'Configured payroll rounding not applied to raw punches.');
attendance_check($rounded['clock_out'] === '2026-10-12 23:59:00', 'Summary mutated a raw punch.');

$live_shift = ['id' => 200, 'staff_id' => 21, 'date' => '2026-10-22',
    'clock_in' => '2026-10-22 00:00:00', 'clock_out' => '2026-10-22 22:04:14',
    'break_rows' => [
        ['id' => 301, 'staff_id' => 21, 'attendance_id' => 200, 'break_out_at' => '2026-10-22 00:12:24', 'break_in_at' => '2026-10-22 20:52:44', 'ended_at' => '2026-10-22 20:52:44', 'break_type' => 'paid'],
        ['id' => 302, 'staff_id' => 21, 'attendance_id' => 200, 'break_out_at' => '2026-10-22 21:16:01', 'break_in_at' => '2026-10-22 21:32:38', 'ended_at' => '2026-10-22 21:32:38', 'break_type' => 'paid'],
        ['id' => 303, 'staff_id' => 21, 'attendance_id' => 200, 'break_out_at' => '2026-10-22 21:33:13', 'break_in_at' => '2026-10-22 22:03:41', 'ended_at' => '2026-10-22 22:03:41', 'break_type' => 'paid'],
        ['id' => 304, 'staff_id' => 21, 'attendance_id' => 200, 'break_out_at' => '2026-10-22 22:04:06', 'break_in_at' => '2026-10-22 22:04:11', 'ended_at' => '2026-10-22 22:04:11', 'break_type' => 'unpaid'],
    ],
];
$live_summary = $model->summarize_attendance_record($live_shift);
attendance_check($live_summary['shift_seconds'] === 79454.0, 'Fresh shift elapsed time must be 79,454 seconds.');
attendance_check($live_summary['paid_break_seconds'] === 77245.0, 'Paid breaks must total 77,245 seconds.');
attendance_check($live_summary['unpaid_break_seconds'] === 5.0, 'Unpaid breaks must total 5 seconds.');
attendance_check($live_summary['payable_seconds'] === 79449.0, 'Payable must equal shift minus unpaid breaks only.');
attendance_check(abs($live_summary['payable_seconds'] - ($live_summary['shift_seconds'] - $live_summary['unpaid_break_seconds'])) < 0.00001, 'Payable calculation must exclude paid breaks without double-counting overlap.');
attendance_check(!$live_summary['has_unresolved_breaks'] && empty($live_summary['invalid_breaks']), 'Valid paid/unpaid break sequence must not be marked unresolved.');

foreach (['missing', 'malformed', 'ended-only', 'overlap'] as $defect) {
    $row = $overnight;
    if ($defect === 'missing') { $row['break_rows'][0]['break_in_at'] = $row['break_rows'][0]['ended_at'] = null; }
    if ($defect === 'malformed') { $row['break_rows'][0]['break_out_at'] = 'bad-date'; }
    if ($defect === 'ended-only') { $row['break_rows'][0]['break_in_at'] = null; }
    if ($defect === 'overlap') { $row['break_rows'][] = $row['break_rows'][0]; }
    $summary = $model->summarize_attendance_record($row);
    attendance_check($summary['has_unresolved_breaks'], 'Unresolved break not flagged: ' . $defect);
    attendance_check($summary['payable_seconds'] > 0, 'An unresolved break deducted the entire shift.');
}
$open = array_merge($overnight, ['clock_out' => null]);
unset($open['break_rows']);
$model->db->tables['fs_attendance'] = [$open];
$model->db->tables['fs_break_sessions'] = [
    ['id' => 10, 'staff_id' => 21, 'attendance_id' => 100, 'break_out_at' => 'bad-date', 'break_type' => 'unpaid'],
];
attendance_check($state()['status'] === 'timestamp_issue' && $state()['allowed_actions'] === [], 'Malformed break must fail closed.');
attendance_rejected(function () use ($action) { $action('clock_out'); });
$model->db->tables['fs_attendance'][] = $model->db->tables['fs_attendance'][0];
attendance_check($state()['allowed_actions'] === [], 'Duplicate existing shifts must require correction.');

$model = payroll_fixture();
$model->db->tables['fs_attendance'] = [['id' => 100, 'staff_id' => 21, 'date' => '2026-10-12',
    'clock_in' => '2026-10-12 22:00:00', 'clock_out' => null]];
$model->db->tables['fs_attendance_corrections'] = [['id' => 1, 'attendance_id' => 100, 'staff_id' => 21,
    'correction_type' => 'clock_out', 'before_value' => '', 'after_value' => '2026-10-13 06:00:00', 'status' => 'approved', 'approved_by' => 7]];
attendance_check($state()['status'] === 'clocked_out', 'An approved closing correction must restore Clocked out without modifying raw data.');
attendance_check($action('clock_in'), 'A new shift after an approved closing correction was rejected.');
attendance_check($model->db->tables['fs_attendance'][0]['clock_out'] === null, 'Approved closing correction mutated raw Clock Out.');

$model = payroll_fixture();
$model->db->tables['fs_attendance'] = [['id' => 100, 'staff_id' => 21, 'date' => '2026-10-12',
    'clock_in' => '2026-10-12 22:00:00', 'clock_out' => null]];
$model->db->tables['fs_break_sessions'] = [['id' => 5, 'attendance_id' => 100, 'staff_id' => 21,
    'break_out_at' => '2026-10-12 23:00:00', 'break_in_at' => null, 'ended_at' => null, 'break_type' => 'unpaid']];
$model->db->tables['fs_attendance_corrections'] = [['id' => 1, 'attendance_id' => 100, 'staff_id' => 21,
    'correction_type' => 'break:5:break_in_at', 'before_value' => '', 'after_value' => '2026-10-13 00:00:00',
    'status' => 'approved', 'approved_by' => 7]];
attendance_check($state()['status'] === 'working', 'An approved Break In correction must restore Working.');
attendance_check($action('break_out', 'paid'), 'A second break after an approved return was rejected.');
attendance_check($model->db->tables['fs_break_sessions'][0]['break_in_at'] === null, 'Approved return correction mutated the raw break.');

foreach (['self', 'unauthorized', 'invalid-coordinates', 'invalid-break-type', 'inactive', 'persistence-failure'] as $scenario) {
    $process = proc_open([PHP_BINARY, __FILE__, '--endpoint', $scenario], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    attendance_check(is_resource($process), 'Could not launch attendance endpoint test.');
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    attendance_check(proc_close($process) === 0, 'Attendance endpoint process failed: ' . $error);
    $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    attendance_check($result['response']['success'] === ($scenario === 'self'), 'Unexpected endpoint result: ' . $scenario);
    if ($scenario === 'self') {
        attendance_check($result['attendance'][0]['staff_id'] === 21 && $result['locks'] === [21], 'A posted staff ID changed the authenticated actor.');
    } else {
        attendance_check($result['attendance'] === [], 'Rejected attendance action persisted a row: ' . $scenario);
    }
    if ($scenario === 'persistence-failure') { attendance_check($result['rollbacks'] === 1, 'Failed persistence did not roll back.'); }
}

$model = payroll_fixture();
$manual = ['staff_id' => 21, 'date' => '2026-10-18',
    'clock_in' => '2026-10-18T22:00', 'clock_out' => '2026-10-19T06:00', 'notes' => 'Explicit overnight fixture'];
attendance_check($model->save_manual_attendance($manual), 'Explicit overnight manual attendance failed.');
$manual_row = end($model->db->tables['fs_attendance']);
attendance_check($manual_row['clock_out'] === '2026-10-19 06:00:00', 'Overnight manual Clock Out lost its date.');
attendance_rejected(function () use ($model, $manual) {
    $model->save_manual_attendance(array_merge($manual, ['clock_out' => '06:00']));
});
attendance_rejected(function () use ($model, $manual) {
    $model->save_manual_attendance(array_merge($manual, ['clock_in' => 'bad-date']));
});
$model->db->tables['fs_attendance'][0]['raw_punches'] = 1;
attendance_rejected(function () use ($model) {
    $model->save_manual_attendance(['staff_id' => 21, 'date' => '2026-10-12', 'clock_in' => '08:00', 'clock_out' => '18:00']);
});
$model->db->tables['fs_payroll_master'][] = ['staff_id' => 21, 'start_date' => '2026-10-18', 'end_date' => '2026-10-18', 'status' => 'paid'];
attendance_rejected(function () use ($model, $manual) { $model->save_manual_attendance($manual); });

echo "PASS locked lifecycle, duplicate/invalid actions, two immutable paid/unpaid breaks, persisted timer, overnight and unresolved breaks\n";
echo "PASS actual attendance controller actor scoping, permissions, validation and transaction rollback\n";
echo "PASS explicit overnight manual input, malformed input rejection and raw/paid-history protections\n";
echo "attendance lifecycle checks passed\n";
