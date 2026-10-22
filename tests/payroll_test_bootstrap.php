<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASEPATH', true);

function db_prefix() { return ''; }
function is_admin() { return $GLOBALS['payroll_test_admin'] ?? true; }
function get_staff_user_id() { return $GLOBALS['attendance_test_staff_id'] ?? 7; }
function is_staff_logged_in() { return get_staff_user_id() > 0; }
function init_head() {}
function init_tail() {}
function field_staff_admin_url($path) { return '/admin/' . $path; }
function log_message($level, $message) { fwrite(STDERR, $level . ': ' . $message . "\n"); }

class App_Model { public $db; }
class AdminController
{
    public $db;
    public $input;
    public $field_staff_model;
}

class PayrollTestResult
{
    private $rows;
    public function __construct(array $rows) { $this->rows = array_values($rows); }
    public function result_array() { return $this->rows; }
    public function row_array() { return $this->rows[0] ?? null; }
    public function row() { return isset($this->rows[0]) ? (object) $this->rows[0] : null; }
    public function num_rows() { return count($this->rows); }
}

class PayrollTestDb
{
    public $tables = [];
    public $commits = 0;
    public $rollbacks = 0;
    public $failTable = '';
    public $lockedStaff = [];
    private $table = '';
    private $conditions = [];
    private $lastId = 0;
    private $affected = 0;
    private $backup;
    private $joins = [];

    public function table_exists($table) { return array_key_exists($table, $this->tables); }
    public function field_exists($field, $table) { return array_key_exists($field, $this->tables[$table][0] ?? []); }
    public function select($fields, $escape = true) { return $this; }
    public function from($table) { $this->table = explode(' ', $table)[0]; $this->joins = []; return $this; }
    public function where($field, $value = null, $escape = null) { $this->conditions[] = [$field, $value]; return $this; }
    public function order_by($field, $direction = 'ASC') { return $this; }
    public function limit($count) { return $this; }
    public function count_all_results() { return $this->get()->num_rows(); }
    public function join($table, $on, $type = '')
    {
        $parts = explode(' ', $table);
        if (!preg_match('/^(\w+)\.(\w+) = (\w+)\.(\w+)$/', $on, $match) || count($parts) !== 2) {
            throw new RuntimeException('Unsupported fixture join: ' . $table . ' ON ' . $on);
        }
        $joined_on_left = $match[1] === $parts[1];
        $this->joins[] = [$parts[0], $joined_on_left ? $match[2] : $match[4], $joined_on_left ? $match[4] : $match[2]];
        return $this;
    }
    public function where_in($field, array $values) { return $this->where($field . ' IN', $values); }
    public function group_start() { return $this; }
    public function group_end() { return $this; }
    public function or_where($field, $value) { return $this->where($field, $value); }

    private function matches(array $row)
    {
        foreach ($this->conditions as [$field, $expected]) {
            $field = preg_replace('/^\w+\./', '', $field);
            if (preg_match('/^([a-z_]+) IN$/i', $field, $in)) {
                if (!in_array($row[$in[1]] ?? null, $expected)) { return false; }
                continue;
            }
            if (preg_match('/^([a-z_]+) IS (NOT )?NULL$/i', $field, $null)) {
                $isNull = !isset($row[$null[1]]);
                if ($isNull === !empty($null[2])) { return false; }
                continue;
            }
            if (!preg_match('/^([a-z_]+)(?: (>=|<=|!=|>|<))?$/i', $field, $match)) {
                throw new RuntimeException('Unsupported fixture condition: ' . $field);
            }
            $actual = $row[$match[1]] ?? null;
            switch ($match[2] ?? '=') {
                case '>=': $ok = $actual >= $expected; break;
                case '<=': $ok = $actual <= $expected; break;
                case '>': $ok = $actual > $expected; break;
                case '<': $ok = $actual < $expected; break;
                case '!=': $ok = $actual != $expected; break;
                default: $ok = $actual == $expected;
            }
            if (!$ok) { return false; }
        }
        return true;
    }

    public function get()
    {
        $rows = array_filter($this->tables[$this->table] ?? [], function ($row) { return $this->matches($row); });
        foreach ($rows as &$row) {
            $id = $row['id'] ?? null;
            foreach ($this->joins as [$table, $joined_field, $base_field]) {
                foreach ($this->tables[$table] ?? [] as $joined) {
                    if (($joined[$joined_field] ?? null) == ($row[$base_field] ?? null)) {
                        $row = array_merge($row, $joined);
                        break;
                    }
                }
            }
            if ($id !== null) { $row['id'] = $id; }
        }
        unset($row);
        $this->conditions = [];
        $this->joins = [];
        return new PayrollTestResult($rows);
    }

    public function query($sql)
    {
        if (preg_match('/^SELECT \* FROM staff WHERE staffid = (\d+) FOR UPDATE$/', $sql, $match)) {
            $this->lockedStaff[] = (int) $match[1];
            return $this->from('staff')->where('staffid', (int) $match[1])->get();
        }
        if (preg_match('/^SELECT \* FROM (\w+) WHERE id = (\d+) FOR UPDATE$/', $sql, $match)) {
            return $this->from($match[1])->where('id', (int) $match[2])->get();
        }
        throw new RuntimeException('Unsupported fixture SQL: ' . $sql);
    }

    public function insert($table, array $row)
    {
        if ($table === $this->failTable) { return false; }
        $ids = array_column($this->tables[$table] ?? [], 'id');
        $this->lastId = empty($ids) ? 1 : max($ids) + 1;
        $row['id'] = $row['id'] ?? $this->lastId;
        $this->tables[$table][] = $row;
        $this->conditions = [];
        return true;
    }

    public function update($table, array $values)
    {
        if ($table === $this->failTable) { return false; }
        $this->affected = 0;
        foreach ($this->tables[$table] as &$row) {
            if ($this->matches($row)) { $row = array_merge($row, $values); $this->affected++; }
        }
        unset($row);
        $this->conditions = [];
        return true;
    }

    public function insert_id() { return $this->lastId; }
    public function affected_rows() { return $this->affected; }
    public function trans_begin() { $this->backup = $this->tables; }
    public function trans_status() { return true; }
    public function trans_commit() { $this->commits++; $this->backup = null; }
    public function trans_rollback()
    {
        $this->rollbacks++;
        if ($this->backup !== null) { $this->tables = $this->backup; }
        $this->backup = null;
    }
}

class PayrollTestInput
{
    public $post = [];
    public function server($key) { return $key === 'REQUEST_METHOD' ? 'POST' : null; }
    public function post($key, $clean = true) { return $this->post[$key] ?? null; }
    public function get($key, $clean = true) { return null; }
}

require_once dirname(__DIR__) . '/models/Field_staff_model.php';
require_once dirname(__DIR__) . '/controllers/Field_staff.php';

function payroll_fixture()
{
    $db = new PayrollTestDb();
    $db->tables = array_fill_keys([
        'staff', 'fs_payroll_profiles', 'fs_payroll_cutoffs', 'fs_payroll_master', 'fs_payroll_values',
        'fs_payroll_snapshots', 'fs_payroll_workflow_history', 'fs_break_sessions', 'fs_attendance',
        'fs_policy_overrides', 'fs_attendance_corrections', 'fs_payroll_work_records', 'fs_settings',
        'fs_payroll_attributes', 'fs_departments',
    ], []);
    $db->tables['staff'][] = ['staffid' => 21, 'firstname' => 'Payroll', 'lastname' => 'Regression', 'active' => 1];
    $db->tables['fs_payroll_profiles'][] = [
        'staff_id' => 21, 'payment_basis' => 'hourly', 'base_hourly_rate' => 20,
        'overtime_multiplier' => 1.5, 'payroll_group' => 'field_ops', 'department_id' => 0,
        'daily_field_allowance' => 0, 'payment_method' => 'online_transfer',
    ];
    $db->tables['fs_payroll_cutoffs'][] = [
        'id' => 1, 'cutoff_code' => 'TEST-20261012', 'payroll_group' => 'field_ops',
        'start_date' => '2026-10-12', 'end_date' => '2026-10-16', 'status' => 'open',
    ];
    foreach ([
        'field_staff_workweek_start_day' => '1', 'field_staff_regular_hours_cap' => '44',
        'field_staff_overtime_enabled' => '1', 'field_staff_overtime_multiplier' => '1.5',
        'field_staff_nib_employee_rate' => '10', 'field_staff_nib_employer_rate' => '5',
        'field_staff_nhip_employee_rate' => '2', 'field_staff_nhip_employer_rate' => '3',
        'field_staff_statutory_verification_status' => 'verified',
    ] as $key => $value) {
        $db->tables['fs_settings'][] = ['setting_key' => $key, 'setting_value' => $value];
    }
    foreach (['loan_adjustment', 'advance', 'commission', 'vacation_pay'] as $index => $code) {
        $db->tables['fs_payroll_attributes'][] = ['id' => $index + 1, 'code' => $code];
    }
    for ($day = 12; $day <= 16; $day++) {
        $date = '2026-10-' . $day;
        $db->tables['fs_attendance'][] = [
            'id' => $day, 'staff_id' => 21, 'date' => $date,
            'clock_in' => $date . ' 08:00:00', 'clock_out' => $date . ' 18:00:00',
        ];
    }
    $model = new Field_staff_model();
    $model->db = $db;
    return $model;
}
