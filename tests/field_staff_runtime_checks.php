<?php

define('BASEPATH', true);

function db_prefix()
{
    return '';
}

class App_Model
{
    public $db;
}

class FakeDbResult
{
    private $rows;
    private $row;

    public function __construct(array $rows = [], array $row = null)
    {
        $this->rows = $rows;
        $this->row = $row;
    }

    public function result_array()
    {
        return $this->rows;
    }

    public function row_array()
    {
        return $this->row;
    }

    public function num_rows()
    {
        return count($this->rows);
    }
}

class FakeDb
{
    private $openClock = false;
    private $activeBreak = false;
    private $currentTable = '';
    private $queryConditions = [];
    private $snapshots = [];
    private $workflowHistory = [];
    private $attendanceRows = [];
    private $policyOverrides = [];
    private $schemaMigrations = [];
    private $createdTables = [];
    private $indexes = [
        'fs_payroll_profiles' => ['idx_fs_payroll_profiles_group'],
        'fs_payroll_master' => ['uq_fs_payroll_master_staff_cutoff'],
        'fs_payroll_values' => ['uq_fs_payroll_values_payroll_attribute'],
        'fs_payroll_snapshots' => ['uq_fs_payroll_snapshots_payroll_id'],
    ];
    private $tables = [
        'fs_schema_migrations' => false,
        'fs_attendance' => true,
        'fs_payroll_profiles' => true,
        'fs_payroll_master' => true,
        'fs_payroll_cutoffs' => true,
        'fs_payroll_values' => true,
        'fs_payroll_snapshots' => true,
        'fs_payroll_workflow_history' => true,
        'fs_break_sessions' => true,
        'fs_policy_overrides' => true,
        'fs_attendance_corrections' => true,
    ];
    private $columns = [
        'fs_payroll_master' => ['status' => "enum('draft','under_review','approved','paid')", 'cutoff_id' => 'int unsigned'],
        'fs_payroll_profiles' => ['payroll_group' => 'varchar(80)', 'position' => 'varchar(32)', 'employment_arrangement' => 'varchar(32)', 'payment_basis' => 'varchar(32)', 'attendance_requirement' => 'varchar(32)'],
        'fs_payroll_snapshots' => ['snapshot_status' => "enum('draft','under_review','approved','paid')"],
    ];

    public function query($sql)
    {
        $statement = trim((string) $sql);
        if (stripos($statement, 'SELECT') === 0) {
            return new FakeDbResult([], null);
        }

        if (stripos($statement, 'SHOW COLUMNS FROM') !== false) {
            preg_match('/SHOW COLUMNS FROM\s+`?([A-Za-z0-9_]+)`?\s+LIKE\s+\'?([A-Za-z0-9_]+)\'?/i', $statement, $matches);
            $table = $matches[1] ?? '';
            $column = $matches[2] ?? '';
            if ($table !== '' && $column !== '') {
                $columnType = $this->columns[$table][$column] ?? 'varchar(50)';
                return new FakeDbResult([], ['Type' => $columnType]);
            }
            return new FakeDbResult([], null);
        }

        if (stripos($statement, 'SHOW INDEX FROM') !== false) {
            preg_match('/SHOW INDEX FROM\s+`?([A-Za-z0-9_]+)`?/i', $statement, $matches);
            $table = $matches[1] ?? '';
            $rows = [];
            foreach (($this->indexes[$table] ?? []) as $indexName) {
                $rows[] = ['Key_name' => $indexName];
            }
            return new FakeDbResult($rows, $rows[0] ?? null);
        }

        if (stripos($statement, 'CREATE TABLE') !== false || stripos($statement, 'ALTER TABLE') !== false) {
            preg_match('/`?([A-Za-z0-9_]+)`?\s*\(/i', $statement, $tableMatch);
            $table = $tableMatch[1] ?? '';
            if ($table !== '') {
                $this->createdTables[$table] = true;
                $this->tables[$table] = true;
            }
            return true;
        }

        return true;
    }

    public function error()
    {
        return ['code' => 0, 'message' => ''];
    }

    private $defaultPolicyOverrides = [
        1 => ['id' => 1, 'staff_id' => 7, 'field_name' => 'attendance_requirement', 'value' => 'optional', 'effective_from' => '2026-01-01', 'effective_to' => null, 'source' => 'manual', 'verification_status' => 'pending', 'notes' => 'Trial override'],
        2 => ['id' => 2, 'staff_id' => 21, 'field_name' => 'payment_basis', 'value' => 'hourly', 'effective_from' => '2026-01-01', 'effective_to' => null, 'source' => 'manual', 'verification_status' => 'verified', 'notes' => 'Baseline'],
    ];
    private $cutoffRows = [
        1 => [
            'id' => 1,
            'cutoff_code' => 'OPS-20260101-20260115',
            'payroll_group' => 'field_ops',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-15',
            'payment_date' => '2026-01-20',
            'status' => 'open',
        ],
    ];
    private $payrollMasterRows = [
        88 => [
            'id' => 88,
            'staff_id' => 21,
            'cutoff_id' => 9,
            'status' => 'draft',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-14',
            'net_salary' => 1015.0,
            'payment_method' => 'online_transfer',
        ],
    ];

    public function table_exists($table)
    {
        if (isset($this->createdTables[$table])) {
            return true;
        }

        if (array_key_exists($table, $this->tables)) {
            return (bool) $this->tables[$table];
        }

        return false;
    }

    public function field_exists($field, $table = null)
    {
        if ($table === null || !isset($this->columns[$table])) {
            return false;
        }

        return array_key_exists($field, $this->columns[$table]);
    }

    public function select($field)
    {
        return $this;
    }

    public function from($table)
    {
        $this->currentTable = $table;
        $this->queryConditions[$table] = [];
        return $this;
    }

    public function order_by($field, $direction = 'ASC')
    {
        return $this;
    }

    public function group_start()
    {
        return $this;
    }

    public function group_end()
    {
        return $this;
    }

    public function where($field, $value, $escape = null)
    {
        $this->queryConditions[$this->currentTable][] = [$field, $value];
        return $this;
    }

    public function or_where($field, $value)
    {
        $this->queryConditions[$this->currentTable][] = [$field, $value];
        return $this;
    }

    public function limit($limit)
    {
        return $this;
    }

    public function count_all_results()
    {
        return $this->openClock ? 1 : 0;
    }

    private function matchesQueryConditions(array $row): bool
    {
        $conditions = $this->queryConditions[$this->currentTable] ?? [];
        $effectiveNullMatches = null;
        $effectiveGteMatches = null;
        $otherConditions = [];

        foreach ($conditions as $condition) {
            [$field, $value] = $condition;
            $fieldName = trim((string) $field);
            if ($fieldName === '') {
                continue;
            }

            $operator = '';
            foreach (['<=', '>=', '!=', '=', '>', '<'] as $candidate) {
                if (stripos($fieldName, $candidate) !== false) {
                    $operator = $candidate;
                    $fieldName = trim(str_replace($candidate, '', $fieldName));
                    break;
                }
            }

            if (stripos($fieldName, 'effective_to') !== false && stripos((string) $field, 'IS NULL') !== false) {
                $actual = array_key_exists('effective_to', $row) ? $row['effective_to'] : null;
                $effectiveNullMatches = ($actual === null || $actual === '');
                continue;
            }

            if ($fieldName === 'effective_to' && $operator === '>=') {
                $actual = array_key_exists('effective_to', $row) ? $row['effective_to'] : null;
                $effectiveGteMatches = ($actual !== null && $actual !== '' && strtotime((string) $actual) >= strtotime((string) $value));
                continue;
            }

            if ($fieldName === 'effective_from' && $operator === '<=') {
                $fieldName = 'effective_from';
            }

            $otherConditions[] = [$field, $value, $fieldName, $operator];
        }

        foreach ($otherConditions as [$field, $value, $fieldName, $operator]) {
            $actual = array_key_exists($fieldName, $row) ? $row[$fieldName] : null;
            if ($actual === null && !array_key_exists($fieldName, $row)) {
                return false;
            }

            if ($operator === '>=') {
                $expected = (string) $value;
                if (strtotime((string) $actual) < strtotime($expected)) {
                    return false;
                }
                continue;
            }

            if ($operator === '<=') {
                $expected = (string) $value;
                if (strtotime((string) $actual) > strtotime($expected)) {
                    return false;
                }
                continue;
            }

            if ($operator === '!=') {
                if ((string) $actual === (string) $value) {
                    return false;
                }
                continue;
            }

            if ((string) $actual !== (string) $value) {
                return false;
            }
        }

        if ($effectiveNullMatches !== null || $effectiveGteMatches !== null) {
            $nullOrGte = ($effectiveNullMatches === true) || ($effectiveGteMatches === true);
            if (!$nullOrGte) {
                return false;
            }
        }

        return true;
    }

    public function get()
    {
        if (str_ends_with($this->currentTable, 'fs_schema_migrations')) {
            $filtered = [];
            foreach ($this->schemaMigrations as $row) {
                if ($this->matchesQueryConditions($row)) {
                    $filtered[] = $row;
                }
            }
            $row = !empty($filtered) ? $filtered[0] : null;
            return new FakeDbResult($filtered, $row);
        }

        if (str_ends_with($this->currentTable, 'fs_policy_overrides')) {
            $allRows = $this->policyOverrides;
            if (empty($allRows)) {
                $allRows = $this->defaultPolicyOverrides;
                $this->policyOverrides = $allRows;
            }
            $filtered = [];
            foreach ($allRows as $row) {
                if ($this->matchesQueryConditions($row)) {
                    $filtered[] = $row;
                }
            }
            $row = !empty($filtered) ? $filtered[0] : null;
            return new FakeDbResult($filtered, $row);
        }

        if (str_ends_with($this->currentTable, 'fs_break_sessions')) {
            if ($this->activeBreak) {
                $rows = [['id' => 1, 'staff_id' => 7, 'break_out_at' => '2026-01-15 12:15:00', 'break_in_at' => '', 'ended_at' => null, 'break_type' => 'paid']];
                $row = $rows[0];
                return new FakeDbResult($rows, $row);
            }
            return new FakeDbResult([], null);
        }

        if (str_ends_with($this->currentTable, 'fs_attendance')) {
            $filtered = [];
            foreach ($this->attendanceRows as $row) {
                if ($this->matchesQueryConditions($row)) {
                    $filtered[] = $row;
                }
            }
            $row = !empty($filtered) ? $filtered[0] : null;
            return new FakeDbResult($filtered, $row);
        }

        if (str_ends_with($this->currentTable, 'fs_payroll_snapshots')) {
            $filtered = [];
            foreach ($this->snapshots as $row) {
                if ($this->matchesQueryConditions($row)) {
                    $filtered[] = ['payroll_id' => $row['payroll_id'], 'snapshot_data' => $row['snapshot_data'], 'snapshot_status' => $row['snapshot_status'] ?? null];
                }
            }
            $row = !empty($filtered) ? $filtered[0] : null;
            return new FakeDbResult($filtered, $row);
        }

        if (str_ends_with($this->currentTable, 'fs_payroll_master')) {
            $filtered = [];
            foreach ($this->payrollMasterRows as $row) {
                if ($this->matchesQueryConditions($row)) {
                    $filtered[] = $row;
                }
            }
            $row = !empty($filtered) ? $filtered[0] : null;
            return new FakeDbResult($filtered, $row);
        }

        if (str_ends_with($this->currentTable, 'fs_payroll_cutoffs')) {
            $filtered = [];
            foreach ($this->cutoffRows as $row) {
                if ($this->matchesQueryConditions($row)) {
                    $filtered[] = $row;
                }
            }
            $row = !empty($filtered) ? $filtered[0] : null;
            return new FakeDbResult($filtered, $row);
        }

        if (str_ends_with($this->currentTable, 'fs_payroll_workflow_history')) {
            $filtered = [];
            foreach ($this->workflowHistory as $row) {
                if ($this->matchesQueryConditions($row)) {
                    $filtered[] = $row;
                }
            }
            $row = !empty($filtered) ? $filtered[0] : null;
            return new FakeDbResult($filtered, $row);
        }

        $rows = $this->activeBreak ? [['id' => 1, 'staff_id' => 7, 'ended_at' => null]] : [];
        $row = $this->activeBreak ? ['id' => 1, 'staff_id' => 7, 'ended_at' => null] : null;
        return new FakeDbResult($rows, $row);
    }

    public function insert($table, $payload)
    {
        if (str_ends_with($table, 'fs_schema_migrations')) {
            $this->schemaMigrations[] = $payload;
            $this->createdTables[$table] = true;
            $this->tables[$table] = true;
            return true;
        }

        if (str_ends_with($table, 'fs_policy_overrides')) {
            $overrideId = (int) ($payload['id'] ?? 0);
            if ($overrideId > 0) {
                $this->policyOverrides[$overrideId] = $payload;
            } else {
                $overrideId = count($this->policyOverrides) + 10;
                $this->policyOverrides[$overrideId] = array_merge(['id' => $overrideId], $payload);
            }
        }

        if (str_ends_with($table, 'fs_payroll_snapshots')) {
            $this->snapshots[(int) $payload['payroll_id']] = $payload;
        }

        if (str_ends_with($table, 'fs_payroll_workflow_history')) {
            $this->workflowHistory[] = $payload;
        }

        if (str_ends_with($table, 'fs_payroll_cutoffs')) {
            $cutoffId = (int) ($payload['id'] ?? 0);
            if ($cutoffId > 0) {
                $this->cutoffRows[$cutoffId] = $payload;
            } else {
                $cutoffId = max(array_keys($this->cutoffRows)) + 1;
                $this->cutoffRows[$cutoffId] = array_merge(['id' => $cutoffId], $payload);
            }
        }
        return true;
    }

    public function update($table, $payload)
    {
        if (str_ends_with($table, 'fs_schema_migrations')) {
            foreach ($this->schemaMigrations as $index => $row) {
                if (($row['migration_name'] ?? '') === ($this->queryConditions[$table][0][1] ?? '')) {
                    $this->schemaMigrations[$index] = array_merge($row, $payload);
                    return true;
                }
            }
            $this->schemaMigrations[] = $payload;
            return true;
        }

        if (str_ends_with($table, 'fs_policy_overrides')) {
            $rows = $this->policyOverrides;
            if (empty($rows)) {
                $rows = $this->defaultPolicyOverrides;
            }
            $selectedIds = [];
            $overrideId = (int) ($payload['id'] ?? 0);
            if ($overrideId > 0) {
                $selectedIds[] = $overrideId;
            } else {
                foreach ($rows as $rowId => $row) {
                    if ($this->matchesQueryConditions($row)) {
                        $selectedIds[] = (int) $rowId;
                    }
                }
            }

            if (empty($selectedIds)) {
                $newId = max(array_keys($rows)) + 1;
                $selectedIds[] = $newId;
            }

            foreach ($selectedIds as $selectedId) {
                $current = $rows[$selectedId] ?? ['id' => $selectedId];
                $this->policyOverrides[$selectedId] = array_merge($current, $payload, ['id' => $selectedId]);
            }
        }

        if (str_ends_with($table, 'fs_payroll_snapshots')) {
            $targetPayrollId = isset($payload['payroll_id']) ? (int) $payload['payroll_id'] : 0;
            if ($targetPayrollId <= 0) {
                $conditions = $this->queryConditions[$table] ?? [];
                foreach ($conditions as [$field, $value]) {
                    if (trim((string) $field) === 'payroll_id') {
                        $targetPayrollId = (int) $value;
                        break;
                    }
                }
            }
            if ($targetPayrollId <= 0) {
                $targetPayrollId = (int) array_key_first($this->snapshots);
            }
            if (isset($this->snapshots[$targetPayrollId])) {
                $this->snapshots[$targetPayrollId] = array_merge($this->snapshots[$targetPayrollId], $payload);
            }
        }

        if (str_ends_with($table, 'fs_payroll_master')) {
            $targetPayrollId = isset($payload['id']) ? (int) $payload['id'] : 0;
            if ($targetPayrollId <= 0) {
                $conditions = $this->queryConditions[$table] ?? [];
                foreach ($conditions as [$field, $value]) {
                    if (trim((string) $field) === 'id') {
                        $targetPayrollId = (int) $value;
                        break;
                    }
                }
            }
            if ($targetPayrollId <= 0) {
                $targetPayrollId = 88;
            }
            if (isset($this->payrollMasterRows[$targetPayrollId])) {
                $this->payrollMasterRows[$targetPayrollId] = array_merge($this->payrollMasterRows[$targetPayrollId], $payload);
            } else {
                $this->payrollMasterRows[$targetPayrollId] = array_merge(['id' => $targetPayrollId], $payload);
            }
        }
        return true;
    }

    public function set_open_clock($enabled)
    {
        $this->openClock = (bool) $enabled;
    }

    public function set_active_break($enabled)
    {
        $this->activeBreak = (bool) $enabled;
    }

    public function set_attendance_rows(array $rows)
    {
        $this->attendanceRows = $rows;
    }
}

if (!function_exists('get_instance')) {
    function &get_instance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = (object) ['db' => new FakeDb()];
        }
        return $instance;
    }
}

if (!function_exists('db_prefix')) {
    function db_prefix()
    {
        return '';
    }
}

$test_ci = &get_instance();
require_once dirname(__DIR__) . '/field_staff.php';

field_staff_run_pending_migrations();

echo "migration checks passed\n";

require_once dirname(__DIR__) . '/models/Field_staff_model.php';

$model = new Field_staff_model();
$model->db = new FakeDb();

$reflect = new ReflectionClass($model);
$method = $reflect->getMethod('normalize_profile_row');
$method->setAccessible(true);
$normalized = $method->invoke($model, [
    'position' => 'manager',
    'employment_arrangement' => 'fixed_term',
    'payment_basis' => 'daily',
    'attendance_requirement' => 'optional',
    'base_hourly_rate' => '35.50',
    'payroll_group' => 'field_ops',
], []);

$asserts = [
    'position' => $normalized['position'] === 'manager',
    'employment_arrangement' => $normalized['employment_arrangement'] === 'fixed_term',
    'payment_basis' => $normalized['payment_basis'] === 'daily',
    'attendance_requirement' => $normalized['attendance_requirement'] === 'optional',
    'payroll_group' => $normalized['payroll_group'] === 'field_ops',
    'base_hourly_rate' => abs((float) $normalized['base_hourly_rate'] - 35.50) < 0.0001,
];

foreach ($asserts as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "ASSERTION FAILED: {$name}\n");
        exit(1);
    }
}

$model->db->set_open_clock(false);
$model->db->set_active_break(false);
if (!$model->validate_attendance_transition(7, 'clock_in')) {
    fwrite(STDERR, "Clock-in validation should allow a fresh start.\n");
    exit(1);
}

$model->db->set_open_clock(true);
if ($model->validate_attendance_transition(7, 'clock_in')) {
    fwrite(STDERR, "Clock-in validation should reject duplicate open sessions.\n");
    exit(1);
}

if (!$model->validate_attendance_transition(7, 'clock_out')) {
    fwrite(STDERR, "Clock-out validation should accept an open session.\n");
    exit(1);
}

$model->db->set_open_clock(true);
$model->db->set_active_break(false);
if (!$model->validate_break_transition(7, 'break_out')) {
    fwrite(STDERR, "Break-out validation should allow a working shift to break.\n");
    exit(1);
}

$model->db->set_active_break(true);
if (!$model->validate_break_transition(7, 'break_in')) {
    fwrite(STDERR, "Break-in validation should accept an active break.\n");
    exit(1);
}

if (!$model->save_employee_policy_override(7, [
    'field_name' => 'attendance_requirement',
    'value' => 'optional',
    'effective_from' => '2026-01-01',
    'source' => 'manual',
    'verification_status' => 'pending',
    'notes' => 'Trial override'
])) {
    fwrite(STDERR, "Policy override save should succeed for a valid employee policy record.\n");
    exit(1);
}

$snapshot = $model->get_employee_policy_snapshot(7, '2026-01-15');
if (($snapshot['attendance_requirement']['value'] ?? '') !== 'optional') {
    fwrite(STDERR, "Policy snapshot should reflect the effective override.\n");
    exit(1);
}

$model->db->set_active_break(true);
$summary = $model->summarize_attendance_record([
    'id' => 9,
    'clock_in' => '2026-01-15 08:00:00',
    'clock_out' => '2026-01-15 17:00:00',
]);
if (($summary['missing_break_in'] ?? false) !== true) {
    fwrite(STDERR, "Break summary should surface a missing break return before clock-out.\n");
    exit(1);
}
if ((float) $summary['payable_seconds'] <= 0) {
    fwrite(STDERR, "Payable time should not be zeroed out for a missing-break exception.\n");
    exit(1);
}

$original_rate = 25.0;
$original_policy = [
    'payroll_group' => 'field_ops',
    'payment_basis' => 'hourly',
    'base_hourly_rate' => $original_rate,
    'overtime_multiplier' => 1.5,
    'payment_method' => 'online_transfer',
];
$original_master = [
    'staff_id' => 21,
    'cutoff_id' => 9,
    'status' => 'draft',
    'start_date' => '2026-01-01',
    'end_date' => '2026-01-14',
    'regular_hours' => 40.0,
    'ot_hours' => 5.0,
    'hourly_rate' => $original_rate,
    'gross_salary' => 1125.0,
    'nib_ee' => 55.0,
    'nib_er' => 65.0,
    'nhip_ee' => 30.0,
    'nhip_er' => 30.0,
    'net_salary' => 1015.0,
    'payment_method' => 'online_transfer',
];
$original_row = [
    'regular_pay' => 1000.0,
    'overtime_pay' => 125.0,
    'vacation_pay' => 0.0,
    'commission_pay' => 0.0,
    'loan_repayment' => 0.0,
    'advance_repayment' => 0.0,
    'other_misc_deductions' => 0.0,
    'net_pay' => 1015.0,
];
$original_snapshot = $model->build_payroll_snapshot_payload(88, $original_master, $original_row, $original_policy, ['payroll_group' => 'field_ops']);
$updated_policy = $original_policy;
$updated_policy['base_hourly_rate'] = 40.0;
$updated_master = $original_master;
$updated_master['hourly_rate'] = 40.0;
$updated_master['gross_salary'] = 1800.0;
$updated_master['net_salary'] = 1640.0;
$updated_snapshot = $model->build_payroll_snapshot_payload(88, $updated_master, $original_row, $updated_policy, ['payroll_group' => 'field_ops']);
if ($original_snapshot['totals']['net_salary'] === $updated_snapshot['totals']['net_salary']) {
    fwrite(STDERR, "Saved payroll snapshot should reflect the original rate, not a later policy change.\n");
    exit(1);
}

if ($original_snapshot['totals']['net_salary'] !== 1015.0) {
    fwrite(STDERR, "Saved payroll snapshot should remain stable after a later policy change.\n");
    exit(1);
}

if (($updated_snapshot['totals']['net_salary'] ?? 0.0) === $original_snapshot['totals']['net_salary']) {
    fwrite(STDERR, "The live recalculation should differ from the stored historical snapshot.\n");
    exit(1);
}

$stored_snapshot = [
    'totals' => ['gross_salary' => 1125.0, 'net_salary' => 1015.0],
    'payment_method' => 'online_transfer',
];
$model->db->insert('fs_payroll_snapshots', ['payroll_id' => 88, 'snapshot_data' => json_encode($stored_snapshot)]);
$workflow_result = $model->apply_payroll_workflow_action(88, 'submit_for_review', 7, ['notes' => 'Ready for review']);
if ($workflow_result['success'] !== true) {
    fwrite(STDERR, "Payroll workflow should accept a valid draft submission.\n");
    exit(1);
}
if (($workflow_result['status'] ?? '') !== 'under_review') {
    fwrite(STDERR, "Submitting payroll for review should move the record to under_review.\n");
    exit(1);
}
if (($model->get_payroll_master_row(88)['status'] ?? '') !== 'under_review') {
    fwrite(STDERR, "The payroll master row should be updated when review is requested.\n");
    exit(1);
}
if (($model->get_payroll_snapshot(88)['status'] ?? '') !== 'under_review') {
    fwrite(STDERR, "Submitting payroll for review should update the saved snapshot status.\n");
    exit(1);
}

$approval_result = $model->apply_payroll_workflow_action(88, 'approve', 7, ['notes' => 'Approved']);
if ($approval_result['success'] !== true) {
    fwrite(STDERR, "Payroll workflow should approve a valid review record with a saved snapshot.\n");
    exit(1);
}

$duplicate_payment = $model->apply_payroll_workflow_action(88, 'record_payment', 7, [
    'payment_reference' => 'PMT-0001',
    'payment_method' => 'online_transfer',
    'payment_amount' => 1015.0,
    'payment_date' => '2026-01-17',
]);
if ($duplicate_payment['success'] !== true) {
    fwrite(STDERR, "Payroll workflow should record a valid payment once the payroll is approved.\n");
    exit(1);
}
if (($model->get_payroll_snapshot(88)['status'] ?? '') !== 'paid') {
    fwrite(STDERR, "Recording payment should freeze the saved payroll snapshot as paid.\n");
    exit(1);
}

$pending_duplicate = $model->apply_payroll_workflow_action(88, 'record_payment', 7, [
    'payment_reference' => 'PMT-0001',
    'payment_method' => 'online_transfer',
    'payment_amount' => 1015.0,
    'payment_date' => '2026-01-17',
]);
if ($pending_duplicate['success'] !== false) {
    fwrite(STDERR, "Duplicate payment records should be rejected.\n");
    exit(1);
}

$cutoff_1 = [
    'payroll_group' => 'field_ops',
    'start_date' => '2026-02-01',
    'end_date' => '2026-02-15',
    'payment_date' => '2026-02-20',
    'status' => 'open',
    'cutoff_code' => 'OPS-20260201-20260215',
];
$cutoff_2 = [
    'payroll_group' => 'field_ops',
    'start_date' => '2026-02-10',
    'end_date' => '2026-02-20',
    'payment_date' => '2026-02-25',
    'status' => 'open',
    'cutoff_code' => 'OPS-20260210-20260220',
];
if ($model->save_payroll_cutoff($cutoff_1, 7) !== true) {
    fwrite(STDERR, "Cutoff creation should accept a valid payroll cutoff.\n");
    exit(1);
}
if ($model->save_payroll_cutoff($cutoff_2, 7) !== false) {
    fwrite(STDERR, "Overlapping cutoff windows should be rejected to maintain cutoff isolation.\n");
    exit(1);
}

$model->db->set_attendance_rows([
    ['id' => 30, 'staff_id' => 21, 'date' => '2026-02-03', 'clock_in' => '2026-02-03 08:00:00', 'clock_out' => '2026-02-03 16:00:00'],
    ['id' => 31, 'staff_id' => 21, 'date' => '2026-02-10', 'clock_in' => '2026-02-10 08:00:00', 'clock_out' => '2026-02-10 16:00:00'],
]);
if (!$model->save_employee_policy_override(21, [
    'field_name' => 'base_hourly_rate',
    'value' => '20.00',
    'effective_from' => '2026-02-01',
    'effective_to' => '2026-02-08',
    'source' => 'manual',
    'verification_status' => 'verified',
    'notes' => 'Early cutoff rate',
])) {
    fwrite(STDERR, "Early cutoff hourly rate should be saved as an effective policy override.\n");
    exit(1);
}
if (!$model->save_employee_policy_override(21, [
    'field_name' => 'base_hourly_rate',
    'value' => '30.00',
    'effective_from' => '2026-02-09',
    'source' => 'manual',
    'verification_status' => 'verified',
    'notes' => 'Later cutoff rate',
])) {
    fwrite(STDERR, "Later cutoff hourly rate should be saved as an effective policy override.\n");
    exit(1);
}
$rate_split = $model->calculate_payroll_for_staff(21, '2026-02-01', '2026-02-15', ['payment_basis' => 'hourly']);
if (abs((float) $rate_split['gross_salary'] - 400.0) > 0.01) {
    fwrite(STDERR, "Rate changes within a single cutoff should apply by date and produce the correct split gross pay.\n");
    exit(1);
}
if (abs((float) $rate_split['regular_hours'] - 16.0) > 0.01) {
    fwrite(STDERR, "Rate-split calculations should total the worked hours without drifting to current policy.\n");
    exit(1);
}
$stored = [
    'totals' => ['gross_salary' => 400.0, 'net_salary' => 360.0],
    'status' => 'approved',
    'payment_method' => 'online_transfer',
];
$model->db->insert('fs_payroll_snapshots', ['payroll_id' => 90, 'snapshot_data' => json_encode($stored)]);
if (($model->get_payroll_snapshot(90)['status'] ?? '') !== 'approved') {
    fwrite(STDERR, "The saved payroll snapshot should retain the approved historical state.\n");
    exit(1);
}
$model->save_employee_policy_override(21, [
    'field_name' => 'base_hourly_rate',
    'value' => '40.00',
    'effective_from' => '2026-02-12',
    'source' => 'manual',
    'verification_status' => 'verified',
    'notes' => 'Later policy edit after approval',
]);
$approved_snapshot = $model->get_payroll_snapshot(90);
if ((float) ($approved_snapshot['totals']['gross_salary'] ?? 0.0) !== 400.0) {
    fwrite(STDERR, "Saved approved payroll should remain unchanged after later policy edits.\n");
    exit(1);
}

$historical_snapshot = [
    'totals' => ['regular_hours' => 40.0, 'overtime_hours' => 5.0, 'gross_salary' => 1125.0, 'net_salary' => 1015.0],
    'rates' => ['hourly_rate' => 25.0],
    'payment_method' => 'online_transfer',
    'status' => 'approved',
];
$model->db->insert('fs_payroll_snapshots', ['payroll_id' => 89, 'snapshot_data' => json_encode($historical_snapshot)]);
$model->db->update('fs_payroll_master', ['id' => 89, 'staff_id' => 21, 'cutoff_id' => 9, 'status' => 'draft', 'start_date' => '2026-01-01', 'end_date' => '2026-01-14', 'gross_salary' => 1800.0, 'net_salary' => 1640.0]);
$historical_rows = $model->get_cutoff_payroll_rows(9);
$historical_row = null;
foreach ($historical_rows as $candidate) {
    if ((int) ($candidate['id'] ?? 0) === 89) {
        $historical_row = $candidate;
        break;
    }
}
if (!is_array($historical_row) || (float) ($historical_row['gross_salary'] ?? 0) !== 1125.0 || (float) ($historical_row['net_salary'] ?? 0) !== 1015.0) {
    fwrite(STDERR, "Saved snapshot totals should be used instead of live payroll recomputation for historical cutoff rows.\n");
    exit(1);
}

if (($model->get_payroll_snapshot(89)['status'] ?? '') !== 'approved') {
    fwrite(STDERR, "Saved snapshot rows should retain the stored historical status.\n");
    exit(1);
}

echo "runtime checks passed\n";
