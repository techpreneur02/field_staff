<?php

define('BASEPATH', true);

function db_prefix()
{
    return 'fs_';
}

class App_Model
{
    public $db;
}

class FakeDb
{
    private $openClock = false;

    public function table_exists($table)
    {
        return true;
    }

    public function field_exists($field, $table = null)
    {
        return false;
    }

    public function from($table)
    {
        return $this;
    }

    public function where($field, $value, $escape = null)
    {
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

    public function set_open_clock($enabled)
    {
        $this->openClock = (bool) $enabled;
    }
}

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

echo "runtime checks passed\n";
