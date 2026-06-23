<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Field_staff_model extends App_Model
{
    /**
     * Persist attendance by action keyword.
     *
     * @param int    $staff_id
     * @param string $action
     * @param mixed  $latitude
     * @param mixed  $longitude
     * @param string $notes
     *
     * @return bool
     */
    public function record_clock_action($staff_id, $action, $latitude, $longitude, $notes)
    {
        $action = strtolower(trim((string) $action));

        if (in_array($action, ['clock_in', 'in'], true)) {
            return $this->clock_in(
                $staff_id,
                date('Y-m-d'),
                date('Y-m-d H:i:s'),
                $latitude,
                $longitude,
                $notes
            );
        }

        if (in_array($action, ['clock_out', 'out'], true)) {
            return $this->clock_out(
                $staff_id,
                date('Y-m-d H:i:s'),
                $latitude,
                $longitude,
                $notes
            );
        }

        return false;
    }

    /**
     * Get recent attendance rows for dashboard history.
     *
     * @param int $staff_id
     * @param int $limit
     *
     * @return array
     */
    public function get_recent_attendance_by_staff($staff_id, $limit = 20)
    {
        $staff_id = (int) $staff_id;
        $limit = (int) $limit;

        if ($staff_id <= 0) {
            return [];
        }

        if ($limit <= 0) {
            $limit = 20;
        }

        $attendance_table = db_prefix() . 'fs_attendance';

        return $this->db
            ->select('id, date, clock_in, clock_out, in_latitude, in_longitude, out_latitude, out_longitude, notes')
            ->from($attendance_table)
            ->where('staff_id', $staff_id)
            ->order_by('date', 'DESC')
            ->order_by('clock_in', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Get a date-filtered personal attendance history for one staff member.
     *
     * @param int    $staff_id
     * @param string $start_date
     * @param string $end_date
     * @param int    $limit
     *
     * @return array
     */
    public function get_attendance_history_by_staff($staff_id, $start_date = '', $end_date = '', $limit = 50)
    {
        $staff_id = (int) $staff_id;
        $limit = (int) $limit;
        $start_date = trim((string) $start_date);
        $end_date = trim((string) $end_date);

        if ($staff_id <= 0) {
            return [];
        }

        if ($limit <= 0) {
            $limit = 50;
        }

        $attendance_table = db_prefix() . 'fs_attendance';
        $query = $this->db
            ->select('id, date, clock_in, clock_out, in_latitude, in_longitude, out_latitude, out_longitude, notes')
            ->from($attendance_table)
            ->where('staff_id', $staff_id);

        if ($start_date !== '') {
            $query->where('date >=', $start_date);
        }

        if ($end_date !== '') {
            $query->where('date <=', $end_date);
        }

        return $query
            ->order_by('date', 'DESC')
            ->order_by('clock_in', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Get recent attendance rows across the attendance table.
     *
     * @param int $limit
     *
     * @return array
     */
    public function get_recent_attendance($limit = 20)
    {
        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 20;
        }

        $attendance_table = db_prefix() . 'fs_attendance';

        return $this->db
            ->select('id, staff_id, date, clock_in, clock_out, in_latitude, in_longitude, out_latitude, out_longitude, notes')
            ->from($attendance_table)
            ->order_by('date', 'DESC')
            ->order_by('clock_in', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Get the latest attendance row for a staff member.
     *
     * @param int $staff_id
     *
     * @return array|null
     */
    public function get_latest_attendance_by_staff($staff_id)
    {
        $staff_id = (int) $staff_id;
        if ($staff_id <= 0) {
            return null;
        }

        $attendance_table = db_prefix() . 'fs_attendance';

        $row = $this->db
            ->select('id, staff_id, date, clock_in, clock_out, in_latitude, in_longitude, out_latitude, out_longitude, notes')
            ->from($attendance_table)
            ->where('staff_id', $staff_id)
            ->order_by('date', 'DESC')
            ->order_by('clock_in', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        return is_array($row) ? $row : null;
    }

    /**
     * Get active staff rows for HR workspace selectors.
     *
     * @return array
     */
    public function get_staff_directory($department_id = 0)
    {
        $staff_table = db_prefix() . 'staff';
        $department_id = (int) $department_id;

        $this->db->from($staff_table);
        if ($this->db->field_exists('active', $staff_table)) {
            $this->db->where('active', 1);
        }

        if ($this->db->field_exists('firstname', $staff_table)) {
            $this->db->order_by('firstname', 'ASC');
        }

        if ($this->db->field_exists('lastname', $staff_table)) {
            $this->db->order_by('lastname', 'ASC');
        }

        if (!$this->db->field_exists('firstname', $staff_table) && !$this->db->field_exists('lastname', $staff_table)) {
            $fallback_key = $this->db->field_exists('email', $staff_table) ? 'email' : ($this->db->field_exists('staffid', $staff_table) ? 'staffid' : 'id');
            $this->db->order_by($fallback_key, 'ASC');
        }

        $rows = $this->db->get()->result_array();

        $directory = [];
        foreach ($rows as $row) {
            $staff_id = $this->extract_staff_id($row);
            if ($staff_id <= 0) {
                continue;
            }

            $profile = $this->get_payroll_profile($staff_id);
            if ($department_id > 0 && (int) $profile['department_id'] !== $department_id) {
                continue;
            }

            $row['staff_id'] = $staff_id;
            $row['worker_name'] = $this->resolve_staff_name($row, $staff_id);
            $row['department_id'] = (int) $profile['department_id'];
            $row['department_name'] = $this->get_department_name((int) $profile['department_id']);
            $directory[] = $row;
        }

        return $directory;
    }

    /**
     * Retrieve department master rows.
     *
     * @return array
     */
    public function get_departments()
    {
        $table = db_prefix() . 'fs_departments';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        return $this->db
            ->from($table)
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();
    }

    /**
     * Create or update a department row.
     *
     * @param string $name
     *
     * @return bool
     */
    public function save_department($name)
    {
        $table = db_prefix() . 'fs_departments';
        $name = trim((string) $name);

        if ($name === '' || !$this->db->table_exists($table)) {
            return false;
        }

        $existing = $this->db
            ->select('id')
            ->from($table)
            ->where('name', $name)
            ->limit(1)
            ->get()
            ->row_array();

        if (!empty($existing)) {
            return true;
        }

        return (bool) $this->db->insert($table, ['name' => $name]);
    }

    /**
     * Retrieve shift definitions.
     *
     * @return array
     */
    public function get_shifts()
    {
        $table = db_prefix() . 'fs_shifts';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        return $this->db
            ->from($table)
            ->order_by('shift_name', 'ASC')
            ->get()
            ->result_array();
    }

    /**
     * Create or update a shift definition.
     *
     * @param array $data
     *
     * @return bool
     */
    public function save_shift(array $data)
    {
        $table = db_prefix() . 'fs_shifts';
        if (!$this->db->table_exists($table)) {
            return false;
        }

        $shift_id = isset($data['shift_id']) ? (int) $data['shift_id'] : 0;
        $payload = [
            'shift_name' => trim((string) ($data['shift_name'] ?? '')),
            'start_time' => trim((string) ($data['start_time'] ?? '')),
            'end_time' => trim((string) ($data['end_time'] ?? '')),
            'grace_period_mins' => max(0, (int) ($data['grace_period_mins'] ?? 0)),
        ];

        if ($payload['shift_name'] === '' || $payload['start_time'] === '' || $payload['end_time'] === '') {
            return false;
        }

        if ($shift_id > 0) {
            $this->db->where('id', $shift_id);
            return (bool) $this->db->update($table, $payload);
        }

        return (bool) $this->db->insert($table, $payload);
    }

    /**
     * Distribute one shift across a date range to staff members.
     *
     * @param int   $shift_id
     * @param array $staff_ids
     * @param string $start_date
     * @param string $end_date
     *
     * @return bool
     */
    public function distribute_shift($shift_id, array $staff_ids, $start_date, $end_date)
    {
        $table = db_prefix() . 'fs_shift_distributions';
        $shift_id = (int) $shift_id;

        if ($shift_id <= 0 || !$this->db->table_exists($table)) {
            return false;
        }

        $dates = $this->build_date_range($start_date, $end_date);
        if (empty($dates)) {
            return false;
        }

        foreach ($staff_ids as $staff_id) {
            $staff_id = (int) $staff_id;
            if ($staff_id <= 0) {
                continue;
            }

            foreach ($dates as $date_value) {
                $existing = $this->db
                    ->select('id')
                    ->from($table)
                    ->where('staff_id', $staff_id)
                    ->where('date', $date_value)
                    ->limit(1)
                    ->get()
                    ->row_array();

                $payload = [
                    'staff_id' => $staff_id,
                    'shift_id' => $shift_id,
                    'date' => $date_value,
                ];

                if (!empty($existing) && isset($existing['id'])) {
                    $this->db->where('id', (int) $existing['id']);
                    $this->db->update($table, $payload);
                } else {
                    $this->db->insert($table, $payload);
                }
            }
        }

        return true;
    }

    /**
     * Retrieve leave register rows.
     *
     * @param array $filters
     *
     * @return array
     */
    public function get_leave_records(array $filters = [])
    {
        $table = db_prefix() . 'fs_leaves';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        $query = $this->db->from($table)->order_by('start_date', 'DESC');

        if (!empty($filters['staff_id'])) {
            $query->where('staff_id', (int) $filters['staff_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', trim((string) $filters['status']));
        }

        if (!empty($filters['start_date'])) {
            $query->where('end_date >=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('start_date <=', $filters['end_date']);
        }

        $rows = $query->get()->result_array();

        foreach ($rows as &$row) {
            $staff_id = isset($row['staff_id']) ? (int) $row['staff_id'] : 0;
            $staff_row = $this->get_staff_row($staff_id);
            $row['worker_name'] = $this->resolve_staff_name($staff_row, $staff_id);
        }

        unset($row);

        return $rows;
    }

    /**
     * Create or update a leave request.
     *
     * @param array $data
     *
     * @return bool
     */
    public function save_leave_record(array $data)
    {
        $table = db_prefix() . 'fs_leaves';
        if (!$this->db->table_exists($table)) {
            return false;
        }

        $leave_id = isset($data['leave_id']) ? (int) $data['leave_id'] : 0;
        $payload = [
            'staff_id' => (int) ($data['staff_id'] ?? 0),
            'leave_type' => $this->normalize_leave_type($data['leave_type'] ?? 'Vacation'),
            'start_date' => trim((string) ($data['start_date'] ?? '')),
            'end_date' => trim((string) ($data['end_date'] ?? '')),
            'status' => $this->normalize_leave_status($data['status'] ?? 'Pending'),
            'reason' => trim((string) ($data['reason'] ?? '')),
        ];

        if ($payload['staff_id'] <= 0 || $payload['start_date'] === '' || $payload['end_date'] === '') {
            return false;
        }

        if ($leave_id > 0) {
            $this->db->where('id', $leave_id);
            return (bool) $this->db->update($table, $payload);
        }

        return (bool) $this->db->insert($table, $payload);
    }

    /**
     * Update leave request approval status.
     *
     * @param int    $leave_id
     * @param string $status
     *
     * @return bool
     */
    public function update_leave_status($leave_id, $status)
    {
        $table = db_prefix() . 'fs_leaves';
        if (!$this->db->table_exists($table)) {
            return false;
        }

        $this->db->where('id', (int) $leave_id);

        return (bool) $this->db->update($table, [
            'status' => $this->normalize_leave_status($status),
        ]);
    }

    /**
     * Retrieve project assignment records.
     *
     * @param array $filters
     *
     * @return array
     */
    public function get_project_assignments(array $filters = [])
    {
        $table = db_prefix() . 'fs_project_assignments';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        $query = $this->db->from($table)->order_by('start_date', 'DESC')->order_by('id', 'DESC');

        if (!empty($filters['staff_id'])) {
            $query->where('staff_id', (int) $filters['staff_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('(end_date IS NULL OR end_date >= ' . $this->db->escape(trim((string) $filters['start_date'])) . ')', null, false);
        }

        if (!empty($filters['end_date'])) {
            $query->where('start_date <=', trim((string) $filters['end_date']));
        }

        $rows = $query->get()->result_array();

        foreach ($rows as &$row) {
            $staff_id = isset($row['staff_id']) ? (int) $row['staff_id'] : 0;
            $supervisor_id = isset($row['supervisor_id']) ? (int) $row['supervisor_id'] : 0;
            $row['worker_name'] = $this->resolve_staff_name($this->get_staff_row($staff_id), $staff_id);
            $row['supervisor_name'] = $supervisor_id > 0 ? $this->resolve_staff_name($this->get_staff_row($supervisor_id), $supervisor_id) : 'Unassigned';
        }

        unset($row);

        return $rows;
    }

    /**
     * Retrieve payroll rows for one staff member.
     *
     * @param int $staff_id
     * @param int $limit
     *
     * @return array
     */
    public function get_employee_payslips($staff_id, $limit = 12)
    {
        $staff_id = (int) $staff_id;
        $limit = (int) $limit;

        if ($staff_id <= 0) {
            return [];
        }

        if ($limit <= 0) {
            $limit = 12;
        }

        $table = db_prefix() . 'fs_payroll_master';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        return $this->db
            ->select('id, staff_id, start_date, end_date, regular_hours, ot_hours, hourly_rate, gross_salary, nib_ee, nib_er, nhip_ee, nhip_er, net_salary, payment_method, status, created_at')
            ->from($table)
            ->where('staff_id', $staff_id)
            ->order_by('end_date', 'DESC')
            ->order_by('id', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Retrieve a compiled payroll statement for one master row.
     *
     * @param int  $payroll_id
     * @param bool $force_staff_scope
     * @param int  $viewer_staff_id
     *
     * @return array
     */
    public function get_employee_payslip_statement($payroll_id, $force_staff_scope = true, $viewer_staff_id = 0)
    {
        $payroll_id = (int) $payroll_id;
        if ($payroll_id <= 0) {
            return [];
        }

        $table = db_prefix() . 'fs_payroll_master';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        $row = $this->db
            ->from($table)
            ->where('id', $payroll_id)
            ->limit(1)
            ->get()
            ->row_array();

        if (!is_array($row) || empty($row)) {
            return [];
        }

        $row_staff_id = isset($row['staff_id']) ? (int) $row['staff_id'] : 0;
        if ($force_staff_scope && $viewer_staff_id > 0 && $row_staff_id !== (int) $viewer_staff_id) {
            return [];
        }

        $staff_row = $this->get_staff_row($row_staff_id);

        return [
            'payroll_id' => $payroll_id,
            'staff_id' => $row_staff_id,
            'worker_name' => $this->resolve_staff_name($staff_row, $row_staff_id),
            'start_date' => isset($row['start_date']) ? (string) $row['start_date'] : '',
            'end_date' => isset($row['end_date']) ? (string) $row['end_date'] : '',
            'created_at' => isset($row['created_at']) ? (string) $row['created_at'] : '',
            'payment_method' => isset($row['payment_method']) ? (string) $row['payment_method'] : 'online_transfer',
            'status' => isset($row['status']) ? (string) $row['status'] : 'draft',
            'regular_hours' => isset($row['regular_hours']) ? (float) $row['regular_hours'] : 0.0,
            'overtime_hours' => isset($row['ot_hours']) ? (float) $row['ot_hours'] : 0.0,
            'hourly_rate' => isset($row['hourly_rate']) ? (float) $row['hourly_rate'] : 0.0,
            'gross_salary' => isset($row['gross_salary']) ? (float) $row['gross_salary'] : 0.0,
            'nib_ee' => isset($row['nib_ee']) ? (float) $row['nib_ee'] : 0.0,
            'nib_er' => isset($row['nib_er']) ? (float) $row['nib_er'] : 0.0,
            'nhip_ee' => isset($row['nhip_ee']) ? (float) $row['nhip_ee'] : 0.0,
            'nhip_er' => isset($row['nhip_er']) ? (float) $row['nhip_er'] : 0.0,
            'net_salary' => isset($row['net_salary']) ? (float) $row['net_salary'] : 0.0,
            'adjustments' => $this->get_payroll_adjustments($payroll_id),
        ];
    }

    /**
     * Return payroll adjustment totals from the seeded EAV tables.
     *
     * @param int $payroll_id
     *
     * @return array
     */
    public function get_payroll_adjustments($payroll_id)
    {
        $payroll_id = (int) $payroll_id;
        $defaults = [
            'commission' => 0.0,
            'loan_adjustment' => 0.0,
            'advance' => 0.0,
            'vacation_pay' => 0.0,
        ];

        if ($payroll_id <= 0) {
            return $defaults;
        }

        $attributes_table = db_prefix() . 'fs_payroll_attributes';
        $values_table = db_prefix() . 'fs_payroll_values';
        if (!$this->db->table_exists($attributes_table) || !$this->db->table_exists($values_table)) {
            return $defaults;
        }

        $rows = $this->db
            ->select('a.code, COALESCE(SUM(v.value), 0) AS total_value', false)
            ->from($values_table . ' v')
            ->join($attributes_table . ' a', 'a.id = v.attribute_id', 'left')
            ->where('v.payroll_id', $payroll_id)
            ->where_in('a.code', array_keys($defaults))
            ->group_by('a.code')
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            $code = isset($row['code']) ? (string) $row['code'] : '';
            if ($code !== '' && array_key_exists($code, $defaults)) {
                $defaults[$code] = round((float) ($row['total_value'] ?? 0), 2);
            }
        }

        return $defaults;
    }

    /**
     * Get one payroll master row by ID.
     *
     * @param int $payroll_id
     *
     * @return array
     */
    public function get_payroll_master_row($payroll_id)
    {
        $payroll_id = (int) $payroll_id;
        if ($payroll_id <= 0) {
            return [];
        }

        $table = db_prefix() . 'fs_payroll_master';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        $row = $this->db
            ->from($table)
            ->where('id', $payroll_id)
            ->limit(1)
            ->get()
            ->row_array();

        return is_array($row) ? $row : [];
    }

    /**
     * Save one project assignment row.
     *
     * @param array $data
     *
     * @return bool
     */
    public function save_project_assignment(array $data)
    {
        $table = db_prefix() . 'fs_project_assignments';
        if (!$this->db->table_exists($table)) {
            return false;
        }

        $project_name = trim((string) ($data['project_name'] ?? ''));
        $staff_id = (int) ($data['staff_id'] ?? 0);
        $supervisor_id = (int) ($data['supervisor_id'] ?? 0);
        $start_date = trim((string) ($data['start_date'] ?? ''));
        $end_date = trim((string) ($data['end_date'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));
        $status = $this->normalize_project_status($data['status'] ?? 'Planned');

        if ($project_name === '' || $staff_id <= 0 || $start_date === '') {
            return false;
        }

        $timestamp = date('Y-m-d H:i:s');

        return (bool) $this->db->insert($table, [
            'project_name' => $project_name,
            'staff_id' => $staff_id,
            'supervisor_id' => $supervisor_id > 0 ? $supervisor_id : null,
            'start_date' => $start_date,
            'end_date' => $end_date !== '' ? $end_date : null,
            'status' => $status,
            'notes' => $notes,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    /**
     * Get one module setting value.
     *
     * @param string $setting_key
     * @param string $default_value
     *
     * @return string
     */
    public function get_module_setting($setting_key, $default_value = '')
    {
        $table = db_prefix() . 'fs_settings';
        if (!$this->db->table_exists($table)) {
            return (string) $default_value;
        }

        $row = $this->db
            ->select('setting_value')
            ->from($table)
            ->where('setting_key', trim((string) $setting_key))
            ->limit(1)
            ->get()
            ->row_array();

        if (!is_array($row) || !array_key_exists('setting_value', $row)) {
            return (string) $default_value;
        }

        return (string) $row['setting_value'];
    }

    /**
     * Save one module setting value.
     *
     * @param string $setting_key
     * @param string $setting_value
     *
     * @return bool
     */
    public function save_module_setting($setting_key, $setting_value)
    {
        $table = db_prefix() . 'fs_settings';
        if (!$this->db->table_exists($table)) {
            return false;
        }

        $setting_key = trim((string) $setting_key);
        if ($setting_key === '') {
            return false;
        }

        $setting_value = (string) $setting_value;
        $timestamp = date('Y-m-d H:i:s');
        $existing = $this->db
            ->select('id')
            ->from($table)
            ->where('setting_key', $setting_key)
            ->limit(1)
            ->get()
            ->row_array();

        if (!empty($existing) && isset($existing['id'])) {
            $this->db->where('id', (int) $existing['id']);
            return (bool) $this->db->update($table, [
                'setting_value' => $setting_value,
                'updated_at' => $timestamp,
            ]);
        }

        return (bool) $this->db->insert($table, [
            'setting_key' => $setting_key,
            'setting_value' => $setting_value,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    /**
     * Get configured staff IDs allowed into supervisor-level workspaces.
     *
     * @return array
     */
    public function get_manager_supervisor_role_ids()
    {
        return $this->get_hr_payroll_staff_ids();
    }

    /**
     * Save configured staff IDs allowed into supervisor-level workspaces.
     *
     * @param array $role_ids
     *
     * @return bool
     */
    public function save_manager_supervisor_role_ids(array $role_ids)
    {
        return $this->save_hr_payroll_staff_ids($role_ids);
    }

    /**
     * Get configured staff IDs allowed to access Master Payroll HR and HR Management Workspace.
     *
     * @return array
     */
    public function get_hr_payroll_staff_ids()
    {
        $raw = $this->get_module_setting('hr_payroll_staff_ids', '');
        $staff_ids = [];

        if ($raw !== '') {
            foreach (preg_split('/[^0-9]+/', $raw) as $value) {
                if ($value !== '') {
                    $staff_ids[] = (int) $value;
                }
            }
        }

        if (empty($staff_ids) && defined('FS_HR_PAYROLL_STAFF_IDS') && is_array(FS_HR_PAYROLL_STAFF_IDS)) {
            $staff_ids = array_map('intval', FS_HR_PAYROLL_STAFF_IDS);
        }

        $staff_ids = array_values(array_unique(array_filter($staff_ids, function ($value) {
            return (int) $value > 0;
        })));

        return $staff_ids;
    }

    /**
     * Save configured staff IDs allowed to access Master Payroll HR and HR Management Workspace.
     *
     * @param array $staff_ids
     *
     * @return bool
     */
    public function save_hr_payroll_staff_ids(array $staff_ids)
    {
        $normalized = array_values(array_unique(array_filter(array_map('intval', $staff_ids), function ($value) {
            return (int) $value > 0;
        })));

        return $this->save_module_setting('hr_payroll_staff_ids', implode(',', $normalized));
    }

    /**
     * Insert or correct a manual attendance record.
     *
     * @param array $data
     *
     * @return bool
     */
    public function save_manual_attendance(array $data)
    {
        $attendance_table = db_prefix() . 'fs_attendance';
        $staff_id = (int) ($data['staff_id'] ?? 0);
        $date = trim((string) ($data['date'] ?? ''));
        $clock_in = trim((string) ($data['clock_in'] ?? ''));
        $clock_out = trim((string) ($data['clock_out'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));

        if ($staff_id <= 0 || $date === '' || $clock_in === '' || $clock_out === '') {
            return false;
        }

        $payload = [
            'staff_id' => $staff_id,
            'date' => $date,
            'clock_in' => $this->compose_datetime($date, $clock_in),
            'clock_out' => $this->compose_datetime($date, $clock_out),
            'tracking_mode' => 'free-form',
            'notes' => $notes,
        ];

        $existing = $this->db
            ->select('id')
            ->from($attendance_table)
            ->where('staff_id', $staff_id)
            ->where('date', $date)
            ->order_by('clock_in', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!empty($existing) && isset($existing['id'])) {
            $this->db->where('id', (int) $existing['id']);
            return (bool) $this->db->update($attendance_table, $payload);
        }

        return (bool) $this->db->insert($attendance_table, $payload);
    }

    /**
     * Get detailed attendance rows with shift metrics.
     *
     * @param string $start_date
     * @param string $end_date
     * @param int    $staff_id
     * @param int    $department_id
     *
     * @return array
     */
    public function get_attendance_record_report($start_date, $end_date, $staff_id = 0, $department_id = 0)
    {
        $attendance_table = db_prefix() . 'fs_attendance';
        $staff_directory = $this->get_staff_directory($department_id);
        $staff_lookup = [];
        foreach ($staff_directory as $staff_row) {
            $staff_lookup[(int) $staff_row['staff_id']] = $staff_row;
        }

        if ($staff_id > 0 && !isset($staff_lookup[(int) $staff_id])) {
            return [];
        }

        $query = $this->db
            ->from($attendance_table)
            ->where('date >=', $start_date)
            ->where('date <=', $end_date)
            ->order_by('date', 'DESC')
            ->order_by('clock_in', 'DESC');

        if ($staff_id > 0) {
            $query->where('staff_id', (int) $staff_id);
        } elseif (!empty($staff_lookup)) {
            $query->where_in('staff_id', array_keys($staff_lookup));
        }

        $rows = $query->get()->result_array();
        $shift_map = $this->get_shift_map();
        $distribution_map = $this->get_shift_distribution_map(array_keys($staff_lookup), $start_date, $end_date);

        foreach ($rows as &$row) {
            $current_staff_id = isset($row['staff_id']) ? (int) $row['staff_id'] : 0;
            $staff_row = isset($staff_lookup[$current_staff_id]) ? $staff_lookup[$current_staff_id] : $this->get_staff_row($current_staff_id);
            $profile = $this->get_payroll_profile($current_staff_id);
            $metrics = $this->calculate_shift_metrics(
                $current_staff_id,
                isset($row['date']) ? $row['date'] : '',
                isset($row['clock_in']) ? $row['clock_in'] : '',
                isset($row['clock_out']) ? $row['clock_out'] : '',
                $profile,
                $shift_map,
                $distribution_map
            );

            $row['worker_name'] = $this->resolve_staff_name($staff_row, $current_staff_id);
            $row['department_id'] = (int) $profile['department_id'];
            $row['department_name'] = $this->get_department_name((int) $profile['department_id']);
            $row['total_hours'] = round($this->calculate_duration_seconds($row['clock_in'] ?? '', $row['clock_out'] ?? '') / 3600, 2);
            $row['late_in_minutes'] = $metrics['late_in_minutes'];
            $row['early_out_minutes'] = $metrics['early_out_minutes'];
            $row['over_work_minutes'] = $metrics['over_work_minutes'];
            $row['shift_name'] = $metrics['shift_name'];
            $row['scheduled_start'] = $metrics['scheduled_start'];
            $row['scheduled_end'] = $metrics['scheduled_end'];
        }

        unset($row);

        return $rows;
    }

    /**
     * Get attendance summary rows and tiles.
     *
     * @param string $start_date
     * @param string $end_date
     * @param int    $staff_id
     * @param int    $department_id
     *
     * @return array
     */
    public function get_attendance_summary_report($start_date, $end_date, $staff_id = 0, $department_id = 0)
    {
        $records = $this->get_attendance_record_report($start_date, $end_date, $staff_id, $department_id);
        $summary_rows = [];

        foreach ($records as $record) {
            $current_staff_id = isset($record['staff_id']) ? (int) $record['staff_id'] : 0;
            if ($current_staff_id <= 0) {
                continue;
            }

            if (!isset($summary_rows[$current_staff_id])) {
                $summary_rows[$current_staff_id] = [
                    'staff_id' => $current_staff_id,
                    'worker_name' => $record['worker_name'],
                    'department_name' => $record['department_name'],
                    'total_days_worked' => 0,
                    'total_late_ins' => 0,
                    'total_early_outs' => 0,
                    'total_hours_worked' => 0.0,
                    'regular_hours' => 0.0,
                    'overtime_hours' => 0.0,
                    'total_allowance_due' => 0.0,
                    '_days' => [],
                ];
            }

            $work_date = isset($record['date']) ? (string) $record['date'] : '';
            if ($work_date !== '' && !isset($summary_rows[$current_staff_id]['_days'][$work_date]) && (float) $record['total_hours'] > 0) {
                $summary_rows[$current_staff_id]['_days'][$work_date] = true;
                $summary_rows[$current_staff_id]['total_days_worked']++;
            }

            if ((int) $record['late_in_minutes'] > 0) {
                $summary_rows[$current_staff_id]['total_late_ins']++;
            }

            if ((int) $record['early_out_minutes'] > 0) {
                $summary_rows[$current_staff_id]['total_early_outs']++;
            }

            $summary_rows[$current_staff_id]['total_hours_worked'] += (float) $record['total_hours'];
        }

        foreach ($summary_rows as $key => $row) {
            $regular_hours = min((float) $row['total_hours_worked'], $this->get_regular_hours_cap());
            $overtime_hours = max((float) $row['total_hours_worked'] - $this->get_regular_hours_cap(), 0);
            $profile = $this->get_payroll_profile((int) $row['staff_id']);

            $summary_rows[$key]['total_hours_worked'] = round((float) $row['total_hours_worked'], 2);
            $summary_rows[$key]['regular_hours'] = round($regular_hours, 2);
            $summary_rows[$key]['overtime_hours'] = round($overtime_hours, 2);
            $summary_rows[$key]['total_allowance_due'] = round((float) $row['total_days_worked'] * (float) $profile['daily_field_allowance'], 2);
            unset($summary_rows[$key]['_days']);
        }

        return array_values($summary_rows);
    }

    /**
     * Get daily workforce attendance statuses.
     *
     * @param string $report_date
     * @param int    $department_id
     *
     * @return array
     */
    public function get_daily_attendance_report($report_date, $department_id = 0)
    {
        $staff_directory = $this->get_staff_directory($department_id);
        $records = $this->get_attendance_record_report($report_date, $report_date, 0, $department_id);
        $leaves = $this->get_leave_records([
            'status' => 'Approved',
            'start_date' => $report_date,
            'end_date' => $report_date,
        ]);
        $records_by_staff = [];
        foreach ($records as $record) {
            $records_by_staff[(int) $record['staff_id']][] = $record;
        }

        $leave_by_staff = [];
        foreach ($leaves as $leave_row) {
            $leave_by_staff[(int) $leave_row['staff_id']] = $leave_row;
        }

        $daily_rows = [];
        foreach ($staff_directory as $staff_row) {
            $current_staff_id = (int) $staff_row['staff_id'];
            $status = 'Absent';
            $late_minutes = 0;
            $clock_in = '';
            $clock_out = '';

            if (isset($leave_by_staff[$current_staff_id])) {
                $status = 'On Leave';
            } elseif (!empty($records_by_staff[$current_staff_id])) {
                $latest = $records_by_staff[$current_staff_id][0];
                $clock_in = isset($latest['clock_in']) ? (string) $latest['clock_in'] : '';
                $clock_out = isset($latest['clock_out']) ? (string) $latest['clock_out'] : '';
                $late_minutes = isset($latest['late_in_minutes']) ? (int) $latest['late_in_minutes'] : 0;

                if ($clock_out === '') {
                    $status = 'Clocked In';
                } elseif ($late_minutes > 0) {
                    $status = 'Late';
                } else {
                    $status = 'Clocked Out';
                }
            }

            $daily_rows[] = [
                'staff_id' => $current_staff_id,
                'worker_name' => $staff_row['worker_name'],
                'department_name' => $staff_row['department_name'],
                'status' => $status,
                'clock_in' => $clock_in,
                'clock_out' => $clock_out,
                'late_in_minutes' => $late_minutes,
            ];
        }

        return $daily_rows;
    }

    /**
     * Get monthly attendance matrix.
     *
     * @param string $month_value Y-m
     * @param int    $department_id
     *
     * @return array
     */
    public function get_monthly_attendance_report($month_value, $department_id = 0)
    {
        $month_value = trim((string) $month_value);
        if ($month_value === '') {
            $month_value = date('Y-m');
        }

        $month_start = $month_value . '-01';
        $month_end = date('Y-m-t', strtotime($month_start));
        $days = $this->build_date_range($month_start, $month_end);
        $staff_directory = $this->get_staff_directory($department_id);
        $records = $this->get_attendance_record_report($month_start, $month_end, 0, $department_id);
        $leaves = $this->get_leave_records([
            'status' => 'Approved',
            'start_date' => $month_start,
            'end_date' => $month_end,
        ]);

        $present_map = [];
        foreach ($records as $record) {
            $present_map[(int) $record['staff_id'] . '|' . (string) $record['date']] = (float) $record['total_hours'] > 0 ? 'P' : 'A';
        }

        $leave_map = [];
        foreach ($leaves as $leave_row) {
            foreach ($this->build_date_range($leave_row['start_date'], $leave_row['end_date']) as $leave_date) {
                $leave_map[(int) $leave_row['staff_id'] . '|' . $leave_date] = 'L';
            }
        }

        $matrix_rows = [];
        foreach ($staff_directory as $staff_row) {
            $status_days = [];
            foreach ($days as $day_value) {
                $key = (int) $staff_row['staff_id'] . '|' . $day_value;
                if (isset($leave_map[$key])) {
                    $status_days[$day_value] = 'L';
                } elseif (isset($present_map[$key])) {
                    $status_days[$day_value] = 'P';
                } else {
                    $status_days[$day_value] = 'A';
                }
            }

            $matrix_rows[] = [
                'staff_id' => (int) $staff_row['staff_id'],
                'worker_name' => $staff_row['worker_name'],
                'department_name' => $staff_row['department_name'],
                'days' => $status_days,
            ];
        }

        return [
            'days' => $days,
            'rows' => $matrix_rows,
            'month' => $month_value,
        ];
    }

    /**
     * Get department-wise attendance aggregation.
     *
     * @param string $start_date
     * @param string $end_date
     * @param int    $department_id
     *
     * @return array
     */
    public function get_department_wise_report($start_date, $end_date, $department_id = 0)
    {
        $summary_rows = $this->get_attendance_summary_report($start_date, $end_date, 0, $department_id);
        $grouped = [];

        foreach ($summary_rows as $row) {
            $group_name = trim((string) ($row['department_name'] ?? 'Unassigned'));
            if ($group_name === '') {
                $group_name = 'Unassigned';
            }

            if (!isset($grouped[$group_name])) {
                $grouped[$group_name] = [
                    'department_name' => $group_name,
                    'headcount' => 0,
                    'total_regular_hours' => 0.0,
                    'total_overtime_hours' => 0.0,
                    'total_hours_worked' => 0.0,
                ];
            }

            $grouped[$group_name]['headcount']++;
            $grouped[$group_name]['total_regular_hours'] += (float) $row['regular_hours'];
            $grouped[$group_name]['total_overtime_hours'] += (float) $row['overtime_hours'];
            $grouped[$group_name]['total_hours_worked'] += (float) $row['total_hours_worked'];
        }

        foreach ($grouped as $key => $group_row) {
            $grouped[$key]['total_regular_hours'] = round((float) $group_row['total_regular_hours'], 2);
            $grouped[$key]['total_overtime_hours'] = round((float) $group_row['total_overtime_hours'], 2);
            $grouped[$key]['total_hours_worked'] = round((float) $group_row['total_hours_worked'], 2);
        }

        return array_values($grouped);
    }

    /**
     * Retrieve a payroll profile for one staff member.
     *
     * @param int $staff_id
     *
     * @return array
     */
    public function get_payroll_profile($staff_id)
    {
        $staff_id = (int) $staff_id;
        if ($staff_id <= 0) {
            return [];
        }

        $staff_row = $this->get_staff_row($staff_id);
        $profile_table = db_prefix() . 'fs_payroll_profiles';

        if (!$this->db->table_exists($profile_table)) {
            return $this->normalize_profile_row([], $staff_row);
        }

        $profile = $this->db
            ->from($profile_table)
            ->where('staff_id', $staff_id)
            ->limit(1)
            ->get()
            ->row_array();

        return $this->normalize_profile_row(is_array($profile) ? $profile : [], $staff_row);
    }

    /**
     * Build a keyed payroll profile map for selector hydration.
     *
     * @param array $staff_directory
     *
     * @return array
     */
    public function get_payroll_profile_map(array $staff_directory)
    {
        $profile_map = [];

        foreach ($staff_directory as $staff_row) {
            $staff_id = isset($staff_row['staff_id']) ? (int) $staff_row['staff_id'] : $this->extract_staff_id($staff_row);
            if ($staff_id <= 0) {
                continue;
            }

            $profile_map[$staff_id] = $this->get_payroll_profile($staff_id);
        }

        return $profile_map;
    }

    /**
     * Save or update a staff payroll profile.
     *
     * @param int   $staff_id
     * @param array $profile_data
     *
     * @return bool
     */
    public function save_payroll_profile($staff_id, array $profile_data)
    {
        $staff_id = (int) $staff_id;
        if ($staff_id <= 0) {
            return false;
        }

        $staff_row = $this->get_staff_row($staff_id);
        if (empty($staff_row)) {
            return false;
        }

        $normalized = $this->normalize_profile_row($profile_data, $staff_row);
        $profile_table = db_prefix() . 'fs_payroll_profiles';
        $timestamp = date('Y-m-d H:i:s');

        if (!$this->db->table_exists($profile_table)) {
            return false;
        }

        $payload = [
            'staff_id'             => $staff_id,
            'base_hourly_rate'     => round((float) $normalized['base_hourly_rate'], 2),
            'overtime_multiplier'  => round((float) $normalized['overtime_multiplier'], 2),
            'daily_field_allowance' => round((float) $normalized['daily_field_allowance'], 2),
            'payment_method'       => $normalized['payment_method'],
            'bank_account_info'    => $normalized['bank_account_info'],
            'updated_at'           => $timestamp,
        ];

        $existing = $this->db
            ->select('id')
            ->from($profile_table)
            ->where('staff_id', $staff_id)
            ->limit(1)
            ->get()
            ->row_array();

        if (!empty($existing) && isset($existing['id'])) {
            $this->db->where('id', (int) $existing['id']);
            return (bool) $this->db->update($profile_table, $payload);
        }

        $payload['created_at'] = $timestamp;

        return (bool) $this->db->insert($profile_table, $payload);
    }

    /**
     * Aggregate attendance analytics for filtered HR summaries and export output.
     *
     * @param string $start_date
     * @param string $end_date
     * @param int    $staff_id
     * @param int    $department_id
     *
     * @return array
     */
    public function get_attendance_analytics($start_date, $end_date, $staff_id = 0, $department_id = 0)
    {
        $attendance_table = db_prefix() . 'fs_attendance';
        $staff_table = db_prefix() . 'staff';
        $profiles_table = db_prefix() . 'fs_payroll_profiles';
        $staff_key = $this->db->field_exists('staffid', $staff_table) ? 'staffid' : 'id';
        $staff_id = (int) $staff_id;
        $department_id = (int) $department_id;

        $select = $this->db
            ->select('a.id, a.staff_id, a.date, a.clock_in, a.clock_out, a.notes, a.in_latitude, a.in_longitude, a.out_latitude, a.out_longitude')
            ->select('s.*')
            ->from($attendance_table . ' a')
            ->join($staff_table . ' s', 's.' . $staff_key . ' = a.staff_id', 'left');

        if ($this->db->table_exists($profiles_table)) {
            $select
                ->select('p.department_id, p.default_shift_id, p.base_hourly_rate, p.overtime_multiplier, p.daily_field_allowance, p.employee_nib_rate, p.employer_nib_rate, p.employee_nhip_rate, p.vacation_pay, p.outstanding_loan, p.loan_repayment, p.payment_method, p.bank_account_info')
                ->join($profiles_table . ' p', 'p.staff_id = a.staff_id', 'left');
        }

        $rows = $select
            ->where('a.date >=', $start_date)
            ->where('a.date <=', $end_date)
            ->order_by('a.date', 'DESC')
            ->order_by('a.clock_in', 'DESC');

        if ($staff_id > 0) {
            $rows->where('a.staff_id', $staff_id);
        }

        $records = $rows->get()->result_array();
        $grouped_rows = [];

        foreach ($records as $record) {
            $current_staff_id = isset($record['staff_id']) ? (int) $record['staff_id'] : 0;
            if ($current_staff_id <= 0) {
                continue;
            }

            $profile = $this->normalize_profile_row($record, $record);
            if ($department_id > 0 && (int) $profile['department_id'] !== $department_id) {
                continue;
            }

            if (!isset($grouped_rows[$current_staff_id])) {
                $grouped_rows[$current_staff_id] = [
                    'staff_id' => $current_staff_id,
                    'worker_name' => $this->resolve_staff_name($record, $current_staff_id),
                    'department_id' => (int) $profile['department_id'],
                    'department_name' => $this->get_department_name((int) $profile['department_id']),
                    'days_worked' => 0,
                    'total_hours' => 0.0,
                    'regular_hours' => 0.0,
                    'overtime_hours' => 0.0,
                    'total_allowance_due' => 0.0,
                    'latest_clock_in' => '',
                    'latest_clock_out' => '',
                    'latest_notes' => '',
                    'base_hourly_rate' => (float) $profile['base_hourly_rate'],
                    'overtime_multiplier' => (float) $profile['overtime_multiplier'],
                    'daily_field_allowance' => (float) $profile['daily_field_allowance'],
                    'default_shift_id' => (int) $profile['default_shift_id'],
                    'employee_nib_rate' => (float) $profile['employee_nib_rate'],
                    'employer_nib_rate' => (float) $profile['employer_nib_rate'],
                    'employee_nhip_rate' => (float) $profile['employee_nhip_rate'],
                    'vacation_pay' => (float) $profile['vacation_pay'],
                    'outstanding_loan' => (float) $profile['outstanding_loan'],
                    'loan_repayment' => (float) $profile['loan_repayment'],
                    'payment_method' => $profile['payment_method'],
                    'bank_account_info' => $profile['bank_account_info'],
                    '_worked_dates' => [],
                ];
            }

            $seconds = $this->calculate_duration_seconds(
                isset($record['clock_in']) ? $record['clock_in'] : null,
                isset($record['clock_out']) ? $record['clock_out'] : null
            );
            $hours = $seconds / 3600;

            $grouped_rows[$current_staff_id]['total_hours'] += $hours;

            $work_date = isset($record['date']) ? trim((string) $record['date']) : '';
            if ($hours > 0 && $work_date !== '' && !isset($grouped_rows[$current_staff_id]['_worked_dates'][$work_date])) {
                $grouped_rows[$current_staff_id]['_worked_dates'][$work_date] = true;
                $grouped_rows[$current_staff_id]['days_worked']++;
                $grouped_rows[$current_staff_id]['total_allowance_due'] += (float) $grouped_rows[$current_staff_id]['daily_field_allowance'];
            }

            $clock_in = isset($record['clock_in']) ? trim((string) $record['clock_in']) : '';
            $clock_out = isset($record['clock_out']) ? trim((string) $record['clock_out']) : '';
            if ($clock_in !== '' && ($grouped_rows[$current_staff_id]['latest_clock_in'] === '' || $clock_in > $grouped_rows[$current_staff_id]['latest_clock_in'])) {
                $grouped_rows[$current_staff_id]['latest_clock_in'] = $clock_in;
            }
            if ($clock_out !== '' && ($grouped_rows[$current_staff_id]['latest_clock_out'] === '' || $clock_out > $grouped_rows[$current_staff_id]['latest_clock_out'])) {
                $grouped_rows[$current_staff_id]['latest_clock_out'] = $clock_out;
            }

            if ($grouped_rows[$current_staff_id]['latest_notes'] === '' && isset($record['notes']) && trim((string) $record['notes']) !== '') {
                $grouped_rows[$current_staff_id]['latest_notes'] = trim((string) $record['notes']);
            }
        }

        $summary = [
            'total_hours_worked' => 0.0,
            'regular_hours' => 0.0,
            'overtime_hours' => 0.0,
            'total_allowance_due' => 0.0,
        ];

        foreach ($grouped_rows as $staff_key_value => $grouped_row) {
            $regular_hours = min((float) $grouped_row['total_hours'], $this->get_regular_hours_cap());
            $overtime_hours = max((float) $grouped_row['total_hours'] - $this->get_regular_hours_cap(), 0.0);

            $grouped_rows[$staff_key_value]['total_hours'] = round((float) $grouped_row['total_hours'], 2);
            $grouped_rows[$staff_key_value]['regular_hours'] = round($regular_hours, 2);
            $grouped_rows[$staff_key_value]['overtime_hours'] = round($overtime_hours, 2);
            $grouped_rows[$staff_key_value]['total_allowance_due'] = round((float) $grouped_row['total_allowance_due'], 2);

            unset($grouped_rows[$staff_key_value]['_worked_dates']);

            $summary['total_hours_worked'] += $grouped_rows[$staff_key_value]['total_hours'];
            $summary['regular_hours'] += $grouped_rows[$staff_key_value]['regular_hours'];
            $summary['overtime_hours'] += $grouped_rows[$staff_key_value]['overtime_hours'];
            $summary['total_allowance_due'] += $grouped_rows[$staff_key_value]['total_allowance_due'];
        }

        usort($grouped_rows, function ($left, $right) {
            return strcasecmp((string) $left['worker_name'], (string) $right['worker_name']);
        });

        return [
            'rows' => $grouped_rows,
            'summary' => [
                'total_hours_worked' => round((float) $summary['total_hours_worked'], 2),
                'regular_hours' => round((float) $summary['regular_hours'], 2),
                'overtime_hours' => round((float) $summary['overtime_hours'], 2),
                'total_allowance_due' => round((float) $summary['total_allowance_due'], 2),
            ],
        ];
    }

    /**
     * Compile an HR payrun statement for the selected period.
     *
     * @param string $start_date
     * @param string $end_date
     * @param int    $staff_id
     *
     * @return array
     */
    public function generate_payrun_statement($start_date, $end_date, $staff_id = 0, $department_id = 0)
    {
        $analytics = $this->get_attendance_analytics($start_date, $end_date, $staff_id, $department_id);
        $holidays = $this->get_holidays();
        $holiday_dates = array_column($holidays, 'date');
        $statement_rows = [];
        $totals = [
            'regular_pay' => 0.0,
            'overtime_pay' => 0.0,
            'holiday_pay' => 0.0,
            'allowance_pay' => 0.0,
            'gross_pay' => 0.0,
            'nib_ee' => 0.0,
            'nib_er' => 0.0,
            'nhip_ee' => 0.0,
            'nhip_er' => 0.0,
            'net_pay' => 0.0,
        ];

        foreach ($analytics['rows'] as $row) {
            $rate = round((float) $row['base_hourly_rate'], 2);
            $overtime_multiplier = (float) $row['overtime_multiplier'];
            if ($overtime_multiplier <= 0) {
                $overtime_multiplier = $this->get_default_overtime_multiplier();
            }

            $staff_id_current = (int) $row['staff_id'];
            $holiday_hours = 0.0;
            $non_holiday_regular_hours = (float) $row['regular_hours'];

            // Calculate holiday hours from actual attendance records
            if (!empty($holiday_dates)) {
                $attendance_records = $this->get_attendance_record_report($start_date, $end_date, $staff_id_current, 0);
                foreach ($attendance_records as $record) {
                    $record_date = isset($record['date']) ? (string) $record['date'] : '';
                    if ($record_date && in_array($record_date, $holiday_dates, true)) {
                        $holiday_hours += (float) $record['total_hours'] ?? 0;
                    }
                }

                // Subtract holiday hours from regular hours
                if ($holiday_hours > 0) {
                    $non_holiday_regular_hours = max((float) $row['regular_hours'] - $holiday_hours, 0);
                }
            }

            $regular_pay = round($non_holiday_regular_hours * $rate, 2);
            $overtime_pay = round((float) $row['overtime_hours'] * $rate * $overtime_multiplier, 2);
            $holiday_pay = round($holiday_hours * $rate * 2.0, 2); // 2x pay for holidays
            $allowance_pay = round((float) $row['total_allowance_due'], 2);
            $vacation_pay = round((float) ($row['vacation_pay'] ?? 0), 2);
            $commission_pay = round((float) ($row['commission_pay'] ?? 0), 2);
            $loan_repayment = round((float) ($row['loan_repayment'] ?? 0), 2);
            $advance_repayment = round((float) ($row['advance_repayment'] ?? 0), 2);
            $other_misc_deductions = round((float) ($row['other_misc_deductions'] ?? 0), 2);
            $gross_pay = round($regular_pay + $overtime_pay + $holiday_pay + $allowance_pay + $vacation_pay + $commission_pay, 2);
            $nib_ee = round($gross_pay * $this->get_nib_employee_rate(), 2);
            $nib_er = round($gross_pay * $this->get_nib_employer_rate(), 2);
            $nhip_base = min($gross_pay, $this->get_nhip_ceiling());
            $nhip_ee = round($nhip_base * $this->get_nhip_employee_rate(), 2);
            $nhip_er = round($nhip_base * $this->get_nhip_employer_rate(), 2);
            $total_deductions = round($nib_ee + $nhip_ee + $loan_repayment + $advance_repayment + $other_misc_deductions, 2);
            $net_pay = round($gross_pay - $total_deductions, 2);

            $statement_rows[] = [
                'staff_id' => (int) $row['staff_id'],
                'worker_name' => $row['worker_name'],
                'department_name' => $row['department_name'] ?? '',
                'days_worked' => (int) $row['days_worked'],
                'regular_hours' => (float) $row['regular_hours'],
                'overtime_hours' => (float) $row['overtime_hours'],
                'holiday_hours' => $holiday_hours,
                'base_hourly_rate' => $rate,
                'overtime_multiplier' => round($overtime_multiplier, 2),
                'daily_field_allowance' => round((float) $row['daily_field_allowance'], 2),
                'allowance_due' => $allowance_pay,
                'vacation_pay' => $vacation_pay,
                'commission_pay' => $commission_pay,
                'regular_pay' => $regular_pay,
                'overtime_pay' => $overtime_pay,
                'holiday_pay' => $holiday_pay,
                'gross_pay' => $gross_pay,
                'nib_ee' => $nib_ee,
                'nib_er' => $nib_er,
                'nhip_ee' => $nhip_ee,
                'nhip_er' => $nhip_er,
                'loan_repayment' => $loan_repayment,
                'advance_repayment' => $advance_repayment,
                'other_misc_deductions' => $other_misc_deductions,
                'total_deductions' => $total_deductions,
                'net_pay' => $net_pay,
                'payment_method' => $row['payment_method'],
                'bank_account_info' => $row['bank_account_info'],
            ];

            $totals['regular_pay'] += $regular_pay;
            $totals['overtime_pay'] += $overtime_pay;
            $totals['holiday_pay'] += $holiday_pay;
            $totals['allowance_pay'] += $allowance_pay;
            $totals['gross_pay'] += $gross_pay;
            $totals['nib_ee'] += $nib_ee;
            $totals['nib_er'] += $nib_er;
            $totals['nhip_ee'] += $nhip_ee;
            $totals['nhip_er'] += $nhip_er;
            $totals['net_pay'] += $net_pay;
        }

        foreach ($totals as $key => $value) {
            $totals[$key] = round((float) $value, 2);
        }

        return [
            'rows' => $statement_rows,
            'totals' => $totals,
            'generated_at' => date('Y-m-d H:i:s'),
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];
    }

    /**
     * Check if staff has an open attendance session.
     *
     * @param int $staff_id
     *
     * @return bool
     */
    public function has_open_clock($staff_id)
    {
        $staff_id = (int) $staff_id;
        if ($staff_id <= 0) {
            return false;
        }

        $attendance_table = db_prefix() . 'fs_attendance';

        $row = $this->db
            ->select('id')
            ->from($attendance_table)
            ->where('staff_id', $staff_id)
            ->where('clock_out IS NULL', null, false)
            ->order_by('clock_in', 'DESC')
            ->limit(1)
            ->get()
            ->row();

        return (bool) $row;
    }

    /**
     * Calculate weekly payroll totals for a staff member.
     *
     * @param int    $staff_id
     * @param string $start_date Y-m-d
     * @param string $end_date   Y-m-d
     * @param float  $hourly_rate
     *
     * @return array
     */
    public function calculate_weekly_payroll($staff_id, $start_date, $end_date, $hourly_rate)
    {
        $staff_id    = (int) $staff_id;
        $hourly_rate = (float) $hourly_rate;

        $attendance_table = db_prefix() . 'fs_attendance';

        $this->db->select(
            "COALESCE(SUM(CASE
                WHEN clock_in IS NOT NULL
                AND clock_out IS NOT NULL
                AND clock_out > clock_in
                THEN TIMESTAMPDIFF(SECOND, clock_in, clock_out)
                ELSE 0
            END), 0) AS total_seconds",
            false
        );
        $this->db->from($attendance_table);
        $this->db->where('staff_id', $staff_id);
        $this->db->where('date >=', $start_date);
        $this->db->where('date <=', $end_date);

        $result        = $this->db->get()->row();
        $total_seconds = $result ? (float) $result->total_seconds : 0.0;
        $total_hours   = $total_seconds / 3600;

        // Weekly statutory overtime threshold.
        $regular_hours_cap = $this->get_regular_hours_cap();
        $regular_hours = min($total_hours, $regular_hours_cap);
        $ot_hours      = max($total_hours - $regular_hours_cap, 0.0);

        $gross_salary = ($regular_hours * $hourly_rate) + ($ot_hours * $hourly_rate * 1.5);

        $nib_ee = $gross_salary * $this->get_nib_employee_rate();
        $nib_er = $gross_salary * $this->get_nib_employer_rate();

        // NHIP monthly cap checkpoint: apply 7,800 USD monthly ceiling when monthly aggregation is available.
        $nhip_base_gross = min($gross_salary, $this->get_nhip_ceiling());
        $nhip_ee         = $nhip_base_gross * $this->get_nhip_employee_rate();
        $nhip_er         = $nhip_base_gross * $this->get_nhip_employer_rate();

        $net_salary = $gross_salary - ($nib_ee + $nhip_ee);

        return [
            'regular_hours' => round($regular_hours, 2),
            'ot_hours'      => round($ot_hours, 2),
            'gross_salary'  => round($gross_salary, 2),
            'nhip_base_gross' => round($nhip_base_gross, 2),
            'nib_ee'        => round($nib_ee, 2),
            'nib_er'        => round($nib_er, 2),
            'nhip_ee'       => round($nhip_ee, 2),
            'nhip_er'       => round($nhip_er, 2),
            'net_salary'    => round($net_salary, 2),
        ];
    }

    /**
     * Insert an EAV payroll value by attribute code.
     *
     * @param int    $payroll_id
     * @param string $attribute_code
     * @param float  $value
     *
     * @return bool
     */
    public function add_eav_payroll_value($payroll_id, $attribute_code, $value)
    {
        $payroll_id     = (int) $payroll_id;
        $attribute_code = trim((string) $attribute_code);
        $value          = (float) $value;

        if ($payroll_id <= 0 || $attribute_code === '') {
            return false;
        }

        $attributes_table = db_prefix() . 'fs_payroll_attributes';
        $values_table     = db_prefix() . 'fs_payroll_values';

        $attribute = $this->db
            ->select('id')
            ->from($attributes_table)
            ->where('code', $attribute_code)
            ->get()
            ->row();

        if (!$attribute) {
            return false;
        }

        return (bool) $this->db->insert($values_table, [
            'payroll_id'   => $payroll_id,
            'attribute_id' => (int) $attribute->id,
            'value'        => round($value, 2),
        ]);
    }

    /**
     * Create a new attendance clock-in record.
     *
     * @param int    $staff_id
     * @param string $date
     * @param string $datetime
     * @param mixed  $latitude
     * @param mixed  $longitude
     * @param string $notes
     *
     * @return bool
     */
    public function clock_in($staff_id, $date, $datetime, $latitude, $longitude, $notes)
    {
        $staff_id  = (int) $staff_id;
        $date      = trim((string) $date);
        $datetime  = trim((string) $datetime);
        $notes     = trim((string) $notes);
        $latitude  = $this->normalize_coordinate($latitude, 8);
        $longitude = $this->normalize_coordinate($longitude, 8);

        if ($staff_id <= 0 || $date === '' || $datetime === '' || $latitude === null || $longitude === null) {
            return false;
        }

        $attendance_table = db_prefix() . 'fs_attendance';

        return (bool) $this->db->insert($attendance_table, [
            'staff_id'      => $staff_id,
            'date'          => $date,
            'clock_in'      => $datetime,
            'in_latitude'   => $latitude,
            'in_longitude'  => $longitude,
            'tracking_mode' => 'free-form',
            'notes'         => $notes,
        ]);
    }

    /**
     * Close the most recent open attendance log for a staff member.
     *
     * @param int    $staff_id
     * @param string $datetime
     * @param mixed  $latitude
     * @param mixed  $longitude
     * @param string $notes
     *
     * @return bool
     */
    public function clock_out($staff_id, $datetime, $latitude, $longitude, $notes)
    {
        $staff_id  = (int) $staff_id;
        $datetime  = trim((string) $datetime);
        $notes     = trim((string) $notes);
        $latitude  = $this->normalize_coordinate($latitude, 8);
        $longitude = $this->normalize_coordinate($longitude, 8);

        if ($staff_id <= 0 || $datetime === '' || $latitude === null || $longitude === null) {
            return false;
        }

        $attendance_table = db_prefix() . 'fs_attendance';

        $open_record = $this->db
            ->select('id, notes')
            ->from($attendance_table)
            ->where('staff_id', $staff_id)
            ->where('clock_out IS NULL', null, false)
            ->order_by('clock_in', 'DESC')
            ->limit(1)
            ->get()
            ->row();

        if (!$open_record) {
            return false;
        }

        $merged_notes = $this->merge_notes((string) $open_record->notes, $notes);

        $this->db->where('id', (int) $open_record->id);

        return (bool) $this->db->update($attendance_table, [
            'clock_out'     => $datetime,
            'out_latitude'  => $latitude,
            'out_longitude' => $longitude,
            'notes'         => $merged_notes,
        ]);
    }

    /**
     * Normalize coordinate values to fixed decimal precision.
     *
     * @param mixed $value
     * @param int   $precision
     *
     * @return string|null
     */
    private function normalize_coordinate($value, $precision = 8)
    {
        if (!is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, (int) $precision, '.', '');
    }

    /**
     * Get a staff row by ID.
     *
     * @param int $staff_id
     *
     * @return array
     */
    private function get_staff_row($staff_id)
    {
        $staff_table = db_prefix() . 'staff';
        $staff_key = $this->db->field_exists('staffid', $staff_table) ? 'staffid' : 'id';

        $row = $this->db
            ->from($staff_table)
            ->where($staff_key, (int) $staff_id)
            ->limit(1)
            ->get()
            ->row_array();

        return is_array($row) ? $row : [];
    }

    /**
     * Extract the canonical staff ID.
     *
     * @param array $row
     *
     * @return int
     */
    private function extract_staff_id(array $row)
    {
        if (isset($row['staff_id'])) {
            return (int) $row['staff_id'];
        }

        if (isset($row['staffid'])) {
            return (int) $row['staffid'];
        }

        if (isset($row['id'])) {
            return (int) $row['id'];
        }

        return 0;
    }

    /**
     * Resolve a white-labeled staff display name.
     *
     * @param array $row
     * @param int   $staff_id
     *
     * @return string
     */
    private function resolve_staff_name(array $row, $staff_id)
    {
        $first_name = isset($row['firstname']) ? trim((string) $row['firstname']) : '';
        $last_name = isset($row['lastname']) ? trim((string) $row['lastname']) : '';
        $full_name = trim($first_name . ' ' . $last_name);

        if ($full_name !== '') {
            return $full_name;
        }

        if (isset($row['name']) && trim((string) $row['name']) !== '') {
            return trim((string) $row['name']);
        }

        return 'Worker #' . (int) $staff_id;
    }

    /**
     * Normalize profile row values with safe defaults.
     *
     * @param array $profile_row
     * @param array $staff_row
     *
     * @return array
     */
    private function normalize_profile_row(array $profile_row, array $staff_row)
    {
        $payment_method = isset($profile_row['payment_method']) ? strtolower(trim((string) $profile_row['payment_method'])) : 'online_transfer';
        if (!in_array($payment_method, ['cash', 'online_transfer', 'check'], true)) {
            $payment_method = 'online_transfer';
        }

        $default_rate = 0.00;
        foreach (['base_hourly_rate', 'hourly_rate', 'default_hourly_rate', 'rate'] as $rate_key) {
            if (isset($profile_row[$rate_key]) && is_numeric($profile_row[$rate_key])) {
                $default_rate = (float) $profile_row[$rate_key];
                break;
            }

            if (isset($staff_row[$rate_key]) && is_numeric($staff_row[$rate_key])) {
                $default_rate = (float) $staff_row[$rate_key];
                break;
            }
        }

        $overtime_multiplier = isset($profile_row['overtime_multiplier']) && is_numeric($profile_row['overtime_multiplier'])
            ? (float) $profile_row['overtime_multiplier']
            : $this->get_default_overtime_multiplier();
        if ($overtime_multiplier <= 0) {
            $overtime_multiplier = $this->get_default_overtime_multiplier();
        }

        $daily_field_allowance = isset($profile_row['daily_field_allowance']) && is_numeric($profile_row['daily_field_allowance'])
            ? (float) $profile_row['daily_field_allowance']
            : 0.0;

        return [
            'base_hourly_rate' => round(max($default_rate, 0), 2),
            'department_id' => isset($profile_row['department_id']) ? (int) $profile_row['department_id'] : (isset($staff_row['department_id']) ? (int) $staff_row['department_id'] : 0),
            'default_shift_id' => isset($profile_row['default_shift_id']) ? (int) $profile_row['default_shift_id'] : 0,
            'overtime_multiplier' => round($overtime_multiplier, 2),
            'daily_field_allowance' => round(max($daily_field_allowance, 0), 2),
            'employee_nib_rate' => round($this->normalize_percentage_value($profile_row['employee_nib_rate'] ?? 5.5, 5.5), 2),
            'employer_nib_rate' => round($this->normalize_percentage_value($profile_row['employer_nib_rate'] ?? 6.5, 6.5), 2),
            'employee_nhip_rate' => round($this->normalize_percentage_value($profile_row['employee_nhip_rate'] ?? 3.0, 3.0), 2),
            'vacation_pay' => round(max((float) ($profile_row['vacation_pay'] ?? 0), 0), 2),
            'outstanding_loan' => round(max((float) ($profile_row['outstanding_loan'] ?? 0), 0), 2),
            'loan_repayment' => round(max((float) ($profile_row['loan_repayment'] ?? 0), 0), 2),
            'payment_method' => $payment_method,
            'bank_account_info' => isset($profile_row['bank_account_info']) ? trim((string) $profile_row['bank_account_info']) : '',
        ];
    }

    /**
     * Calculate worked seconds for one attendance record.
     *
     * @param string|null $clock_in
     * @param string|null $clock_out
     *
     * @return float
     */
    private function calculate_duration_seconds($clock_in, $clock_out)
    {
        $clock_in = trim((string) $clock_in);
        $clock_out = trim((string) $clock_out);

        if ($clock_in === '' || $clock_out === '') {
            return 0.0;
        }

        $clock_in_ts = strtotime($clock_in);
        $clock_out_ts = strtotime($clock_out);
        if ($clock_in_ts === false || $clock_out_ts === false || $clock_out_ts <= $clock_in_ts) {
            return 0.0;
        }

        return (float) ($clock_out_ts - $clock_in_ts);
    }

    private function get_regular_hours_cap()
    {
        return defined('FS_WEEKLY_REGULAR_HOURS_CAP') ? (float) FS_WEEKLY_REGULAR_HOURS_CAP : 44.0;
    }

    private function get_default_overtime_multiplier()
    {
        return defined('FS_OT_MULTIPLIER') ? (float) FS_OT_MULTIPLIER : 1.5;
    }

    private function get_nib_employee_rate()
    {
        return defined('FS_NIB_EMPLOYEE_RATE') ? (float) FS_NIB_EMPLOYEE_RATE : 0.055;
    }

    private function get_nib_employer_rate()
    {
        return defined('FS_NIB_EMPLOYER_RATE') ? (float) FS_NIB_EMPLOYER_RATE : 0.065;
    }

    private function get_nhip_employee_rate()
    {
        return defined('FS_NHIP_EMPLOYEE_RATE') ? (float) FS_NHIP_EMPLOYEE_RATE : 0.03;
    }

    private function get_nhip_employer_rate()
    {
        return defined('FS_NHIP_EMPLOYER_RATE') ? (float) FS_NHIP_EMPLOYER_RATE : 0.03;
    }

    private function get_nhip_ceiling()
    {
        return defined('FS_NHIP_MONTHLY_CEILING') ? (float) FS_NHIP_MONTHLY_CEILING : 7800.0;
    }

    public function save_holiday($holiday_date, $holiday_name)
    {
        $holiday_date = trim((string) $holiday_date);
        $holiday_name = trim((string) $holiday_name);

        if (!$holiday_date || !$holiday_name) {
            return false;
        }

        $holidays = $this->get_holidays();
        $exists = false;
        foreach ($holidays as $holiday) {
            if ($holiday['date'] === $holiday_date) {
                $exists = true;
                $holiday['name'] = $holiday_name;
                break;
            }
        }

        if (!$exists) {
            $holidays[] = ['date' => $holiday_date, 'name' => $holiday_name];
        }

        usort($holidays, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return $this->update_field_staff_setting('holidays', json_encode($holidays));
    }

    public function delete_holiday($holiday_date)
    {
        $holiday_date = trim((string) $holiday_date);
        if (!$holiday_date) {
            return false;
        }

        $holidays = $this->get_holidays();
        $updated = array_filter($holidays, function ($holiday) use ($holiday_date) {
            return $holiday['date'] !== $holiday_date;
        });

        return $this->update_field_staff_setting('holidays', json_encode(array_values($updated)));
    }

    public function get_holidays()
    {
        $json_holidays = $this->get_field_staff_setting('holidays', '[]');
        $holidays = json_decode($json_holidays, true);

        if (!is_array($holidays)) {
            $holidays = [];
        }

        usort($holidays, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return $holidays;
    }

    public function is_holiday($work_date)
    {
        $work_date = trim((string) $work_date);
        $holidays = $this->get_holidays();
        foreach ($holidays as $holiday) {
            if ($holiday['date'] === $work_date) {
                return true;
            }
        }
        return false;
    }

    private function get_department_name($department_id)
    {
        $department_id = (int) $department_id;
        if ($department_id <= 0) {
            return 'Unassigned';
        }

        $table = db_prefix() . 'fs_departments';
        if (!$this->db->table_exists($table)) {
            return 'Unassigned';
        }

        $row = $this->db->select('name')->from($table)->where('id', $department_id)->limit(1)->get()->row_array();

        return !empty($row['name']) ? (string) $row['name'] : 'Unassigned';
    }

    private function get_shift_map()
    {
        $map = [];
        foreach ($this->get_shifts() as $shift_row) {
            $map[(int) $shift_row['id']] = $shift_row;
        }

        return $map;
    }

    private function get_shift_distribution_map(array $staff_ids, $start_date, $end_date)
    {
        $table = db_prefix() . 'fs_shift_distributions';
        if (!$this->db->table_exists($table) || empty($staff_ids)) {
            return [];
        }

        $rows = $this->db
            ->from($table)
            ->where_in('staff_id', array_map('intval', $staff_ids))
            ->where('date >=', $start_date)
            ->where('date <=', $end_date)
            ->get()
            ->result_array();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['staff_id'] . '|' . (string) $row['date']] = (int) $row['shift_id'];
        }

        return $map;
    }

    private function calculate_shift_metrics($staff_id, $date, $clock_in, $clock_out, array $profile, array $shift_map, array $distribution_map)
    {
        $key = (int) $staff_id . '|' . (string) $date;
        $shift_id = isset($distribution_map[$key]) ? (int) $distribution_map[$key] : (int) ($profile['default_shift_id'] ?? 0);
        if ($shift_id <= 0 || !isset($shift_map[$shift_id])) {
            return [
                'shift_name' => 'Unassigned',
                'scheduled_start' => '',
                'scheduled_end' => '',
                'late_in_minutes' => 0,
                'early_out_minutes' => 0,
                'over_work_minutes' => 0,
            ];
        }

        $shift = $shift_map[$shift_id];
        $scheduled_start = $this->compose_datetime($date, $shift['start_time']);
        $scheduled_end = $this->compose_datetime($date, $shift['end_time']);
        if (strtotime($scheduled_end) <= strtotime($scheduled_start)) {
            $scheduled_end = date('Y-m-d H:i:s', strtotime($scheduled_end . ' +1 day'));
        }

        $grace_minutes = max(0, (int) ($shift['grace_period_mins'] ?? 0));
        $late_in_minutes = $clock_in !== '' ? max(0, (int) floor((strtotime($clock_in) - strtotime($scheduled_start . ' +' . $grace_minutes . ' minutes')) / 60)) : 0;
        $early_out_minutes = $clock_out !== '' ? max(0, (int) floor((strtotime($scheduled_end) - strtotime($clock_out)) / 60)) : 0;
        $over_work_minutes = $clock_out !== '' ? max(0, (int) floor((strtotime($clock_out) - strtotime($scheduled_end)) / 60)) : 0;

        return [
            'shift_name' => (string) $shift['shift_name'],
            'scheduled_start' => $scheduled_start,
            'scheduled_end' => $scheduled_end,
            'late_in_minutes' => $late_in_minutes,
            'early_out_minutes' => $early_out_minutes,
            'over_work_minutes' => $over_work_minutes,
        ];
    }

    private function normalize_leave_type($value)
    {
        $value = trim((string) $value);
        $allowed = ['Vacation', 'Sick', 'Maternity', 'Unpaid'];

        return in_array($value, $allowed, true) ? $value : 'Vacation';
    }

    private function normalize_leave_status($value)
    {
        $value = trim((string) $value);
        $allowed = ['Pending', 'Approved', 'Rejected'];

        return in_array($value, $allowed, true) ? $value : 'Pending';
    }

    private function normalize_project_status($value)
    {
        $value = trim((string) $value);
        $allowed = ['Planned', 'Active', 'Completed', 'On Hold'];

        return in_array($value, $allowed, true) ? $value : 'Planned';
    }

    private function build_date_range($start_date, $end_date)
    {
        $start = strtotime((string) $start_date);
        $end = strtotime((string) $end_date);
        if ($start === false || $end === false || $start > $end) {
            return [];
        }

        $dates = [];
        for ($current = $start; $current <= $end; $current = strtotime('+1 day', $current)) {
            $dates[] = date('Y-m-d', $current);
        }

        return $dates;
    }

    private function compose_datetime($date, $time)
    {
        $date = trim((string) $date);
        $time = trim((string) $time);
        if ($date === '' || $time === '') {
            return '';
        }

        if (strlen($time) === 5) {
            $time .= ':00';
        }

        return $date . ' ' . $time;
    }

    private function normalize_percentage_value($value, $default)
    {
        if (!is_numeric($value)) {
            return (float) $default;
        }

        return max((float) $value, 0);
    }

    /**
     * Combine saved notes with new attendance notes.
     *
     * @param string $existing_notes
     * @param string $new_notes
     *
     * @return string
     */
    private function merge_notes($existing_notes, $new_notes)
    {
        $existing_notes = trim($existing_notes);
        $new_notes      = trim($new_notes);

        if ($existing_notes === '') {
            return $new_notes;
        }

        if ($new_notes === '') {
            return $existing_notes;
        }

        return $existing_notes . PHP_EOL . $new_notes;
    }
}
