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
    public function record_clock_action($staff_id, $action, $latitude, $longitude, $notes, $break_type = 'paid')
    {
        $action = strtolower(trim((string) $action));
        $break_type = strtolower(trim((string) $break_type));
        if (!in_array($break_type, ['paid', 'unpaid'], true)) {
            $break_type = 'paid';
        }

        $this->db->trans_begin();

        try {
            if (in_array($action, ['clock_in', 'in'], true)) {
                if (!$this->validate_attendance_transition($staff_id, 'clock_in')) {
                    $this->db->trans_rollback();
                    return false;
                }

                $saved = $this->clock_in(
                    $staff_id,
                    date('Y-m-d'),
                    date('Y-m-d H:i:s'),
                    $latitude,
                    $longitude,
                    $notes
                );

                if (!$saved) {
                    $this->db->trans_rollback();
                    return false;
                }

                $this->db->trans_commit();
                return true;
            }

            if (in_array($action, ['break_out', 'break-out', 'breakout'], true)) {
                if (!$this->validate_break_transition($staff_id, 'break_out')) {
                    $this->db->trans_rollback();
                    return false;
                }

                $attendance_id = $this->get_open_attendance_id($staff_id);
                if ($attendance_id <= 0) {
                    $this->db->trans_rollback();
                    return false;
                }

                $saved = $this->record_break_session($staff_id, $attendance_id, 'break_out', $break_type, $notes);
                if (!$saved) {
                    $this->db->trans_rollback();
                    return false;
                }

                $this->db->trans_commit();
                return true;
            }

            if (in_array($action, ['break_in', 'break-in', 'breakin'], true)) {
                if (!$this->validate_break_transition($staff_id, 'break_in')) {
                    $this->db->trans_rollback();
                    return false;
                }

                $attendance_id = $this->get_open_attendance_id($staff_id);
                if ($attendance_id <= 0) {
                    $this->db->trans_rollback();
                    return false;
                }

                $saved = $this->record_break_session($staff_id, $attendance_id, 'break_in', $break_type, $notes);
                if (!$saved) {
                    $this->db->trans_rollback();
                    return false;
                }

                $this->db->trans_commit();
                return true;
            }

            if (in_array($action, ['clock_out', 'out'], true)) {
                if (!$this->validate_attendance_transition($staff_id, 'clock_out')) {
                    $this->db->trans_rollback();
                    return false;
                }

                $saved = $this->clock_out(
                    $staff_id,
                    date('Y-m-d H:i:s'),
                    $latitude,
                    $longitude,
                    $notes
                );

                if (!$saved) {
                    $this->db->trans_rollback();
                    return false;
                }

                $this->db->trans_commit();
                return true;
            }

            $this->db->trans_rollback();
            return false;
        } catch (Throwable $e) {
            if ($this->db && method_exists($this->db, 'trans_rollback')) {
                $this->db->trans_rollback();
            }

            error_log('Field Staff attendance model exception: ' . $e->getMessage());
            if (method_exists($e, 'getTraceAsString')) {
                error_log($e->getTraceAsString());
            }

            throw $e;
        }
    }

    /**
     * Validate the next attendance state transition for a staff member.
     *
     * @param int    $staff_id
     * @param string $requested_action
     *
     * @return bool
     */
    public function validate_attendance_transition($staff_id, $requested_action)
    {
        $staff_id = (int) $staff_id;
        $requested_action = strtolower(trim((string) $requested_action));

        if ($staff_id <= 0 || $requested_action === '') {
            return false;
        }

        $attendance_table = db_prefix() . 'fs_attendance';
        if (!$this->db->table_exists($attendance_table)) {
            return false;
        }

        $has_open_record = (bool) $this->db
            ->from($attendance_table)
            ->where('staff_id', $staff_id)
            ->where('clock_out IS NULL', null, false)
            ->limit(1)
            ->count_all_results();

        $has_active_break = $this->has_active_break_session($staff_id);

        if ($requested_action === 'clock_in') {
            return !$has_open_record;
        }

        if ($requested_action === 'clock_out') {
            return $has_open_record && !$has_active_break;
        }

        return false;
    }

    /**
     * Validate the next break state transition for a staff member.
     *
     * @param int    $staff_id
     * @param string $requested_status
     *
     * @return bool
     */
    public function validate_break_transition($staff_id, $requested_status)
    {
        $staff_id = (int) $staff_id;
        $requested_status = strtolower(trim((string) $requested_status));

        if ($staff_id <= 0 || $requested_status === '') {
            return false;
        }

        $attendance_table = db_prefix() . 'fs_attendance';
        if (!$this->db || !method_exists($this->db, 'table_exists') || !$this->db->table_exists($attendance_table)) {
            return false;
        }

        $has_open_record = (bool) $this->db
            ->from($attendance_table)
            ->where('staff_id', $staff_id)
            ->where('clock_out IS NULL', null, false)
            ->limit(1)
            ->count_all_results();

        $has_active_break = $this->has_active_break_session($staff_id);

        if ($requested_status === 'clock_in') {
            return !$has_open_record;
        }

        if ($requested_status === 'break_out') {
            return $has_open_record && !$has_active_break;
        }

        if ($requested_status === 'break_in') {
            return $has_open_record && $has_active_break;
        }

        if ($requested_status === 'working') {
            return $has_open_record && !$has_active_break;
        }

        if ($requested_status === 'clock_out') {
            return $has_open_record && !$has_active_break;
        }

        return false;
    }

    /**
     * Return the active attendance record ID if a staff member is currently on shift.
     *
     * @param int $staff_id
     *
     * @return int
     */
    public function get_open_attendance_id($staff_id)
    {
        $staff_id = (int) $staff_id;
        if ($staff_id <= 0) {
            return 0;
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
            ->row_array();

        return is_array($row) && isset($row['id']) ? (int) $row['id'] : 0;
    }

    /**
     * Record a break entry or exit on the active attendance row.
     *
     * @param int    $staff_id
     * @param int    $attendance_id
     * @param string $direction
     * @param string $break_type
     * @param string $notes
     *
     * @return bool
     */
    public function record_break_session($staff_id, $attendance_id, $direction, $break_type = 'paid', $notes = '')
    {
        $staff_id = (int) $staff_id;
        $attendance_id = (int) $attendance_id;
        $direction = strtolower(trim((string) $direction));
        $break_type = strtolower(trim((string) $break_type));
        $notes = trim((string) $notes);

        if ($staff_id <= 0 || $attendance_id <= 0 || !in_array($direction, ['break_out', 'break_in'], true)) {
            return false;
        }

        if (!in_array($break_type, ['paid', 'unpaid'], true)) {
            $break_type = 'paid';
        }

        $break_table = db_prefix() . 'fs_break_sessions';
        $active_row = $this->db
            ->select('id, ended_at')
            ->from($break_table)
            ->where('staff_id', $staff_id)
            ->where('attendance_id', $attendance_id)
            ->where('ended_at IS NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array();

        if ($direction === 'break_out') {
            if (is_array($active_row) && !empty($active_row)) {
                return false;
            }

            return (bool) $this->db->insert($break_table, [
                'staff_id' => $staff_id,
                'attendance_id' => $attendance_id,
                'break_out_at' => date('Y-m-d H:i:s'),
                'break_type' => $break_type,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (!is_array($active_row) || !isset($active_row['id'])) {
            return false;
        }

        $this->db->where('id', (int) $active_row['id']);
        return (bool) $this->db->update($break_table, [
            'break_in_at' => date('Y-m-d H:i:s'),
            'ended_at' => date('Y-m-d H:i:s'),
            'break_type' => $break_type,
            'notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get current active break state for a staff member.
     *
     * @param int $staff_id
     *
     * @return array
     */
    public function get_active_break_session($staff_id)
    {
        $staff_id = (int) $staff_id;
        if ($staff_id <= 0) {
            return [];
        }

        $break_table = db_prefix() . 'fs_break_sessions';
        $row = $this->db
            ->from($break_table)
            ->where('staff_id', $staff_id)
            ->where('ended_at IS NULL', null, false)
            ->order_by('break_out_at', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!is_array($row) || empty($row)) {
            return [];
        }

        $attendance_id = (int) ($row['attendance_id'] ?? 0);
        if ($attendance_id > 0) {
            $open_attendance_id = $this->get_open_attendance_id($staff_id);
            if ($open_attendance_id !== $attendance_id) {
                return [];
            }
        }

        $break_out_at = trim((string) ($row['break_out_at'] ?? ''));
        if ($break_out_at !== '' && $this->parse_datetime_or_null($break_out_at) === null) {
            return [];
        }

        return $row;
    }

    private function has_active_break_session($staff_id)
    {
        $staff_id = (int) $staff_id;
        if ($staff_id <= 0 || !$this->db || !method_exists($this->db, 'table_exists')) {
            return false;
        }

        $break_table = db_prefix() . 'fs_break_sessions';
        if (!$this->db->table_exists($break_table)) {
            return false;
        }

        $row = $this->db
            ->from($break_table)
            ->where('staff_id', $staff_id)
            ->where('ended_at IS NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array();

        if (!is_array($row) || empty($row)) {
            return false;
        }

        $attendance_id = (int) ($row['attendance_id'] ?? 0);
        if ($attendance_id > 0) {
            $open_attendance_id = $this->get_open_attendance_id($staff_id);
            if ($open_attendance_id !== $attendance_id) {
                return false;
            }
        }

        $break_out_at = trim((string) ($row['break_out_at'] ?? ''));
        if ($break_out_at !== '' && $this->parse_datetime_or_null($break_out_at) === null) {
            return false;
        }

        return true;
    }

    /**
     * Return the break state for a single attendance row.
     *
     * @param int $staff_id
     * @param int $attendance_id
     *
     * @return array
     */
    public function get_attendance_break_state($staff_id, $attendance_id = 0)
    {
        $staff_id = (int) $staff_id;
        $attendance_id = (int) $attendance_id;
        if ($staff_id <= 0) {
            return [
                'status' => 'working',
                'active_break' => null,
                'missing_break_in' => false,
                'total_paid_break_seconds' => 0,
                'total_unpaid_break_seconds' => 0,
            ];
        }

        $break_table = db_prefix() . 'fs_break_sessions';
        $query = $this->db
            ->from($break_table)
            ->where('staff_id', $staff_id)
            ->order_by('break_out_at', 'ASC');

        if ($attendance_id > 0) {
            $query->where('attendance_id', $attendance_id);
        }

        $rows = $query->get()->result_array();
        $rows = is_array($rows) ? $rows : [];

        $active_break = null;
        $missing_break_in = false;
        $paid_seconds = 0.0;
        $unpaid_seconds = 0.0;
        $has_invalid_timestamp = false;
        $invalid_breaks = [];

        foreach ($rows as $row) {
            $break_out = trim((string) ($row['break_out_at'] ?? ''));
            $break_in = trim((string) ($row['break_in_at'] ?? ''));
            $ended_at = trim((string) ($row['ended_at'] ?? ''));
            $break_type = strtolower(trim((string) ($row['break_type'] ?? 'paid')));
            $duration = 0.0;
            $invalid_fields = [];

            foreach (['break_out_at' => $break_out, 'break_in_at' => $break_in, 'ended_at' => $ended_at] as $field_name => $field_value) {
                if ($field_value !== '' && $this->parse_datetime_or_null($field_value) === null) {
                    $invalid_fields[] = $field_name;
                }
            }

            if (!empty($invalid_fields)) {
                $has_invalid_timestamp = true;
                $invalid_breaks[] = [
                    'id' => isset($row['id']) ? (int) $row['id'] : 0,
                    'invalid_fields' => $invalid_fields,
                ];
                continue;
            }

            if ($break_out !== '' && $break_in !== '') {
                $duration = $this->calculate_duration_seconds($break_out, $break_in);
            } elseif ($break_out !== '' && $ended_at !== '') {
                $duration = $this->calculate_duration_seconds($break_out, $ended_at);
            }

            if ($break_type === 'paid') {
                $paid_seconds += $duration;
            } else {
                $unpaid_seconds += $duration;
            }

            if ($break_out !== '' && $break_in === '' && $ended_at === '') {
                $missing_break_in = true;
                $active_break = $row;
            }
        }

        $status = 'working';
        if ($has_invalid_timestamp) {
            $status = 'timestamp_issue';
        } elseif ($active_break !== null) {
            $status = 'on_break';
        }

        return [
            'status' => $status,
            'active_break' => $active_break,
            'missing_break_in' => $missing_break_in,
            'has_invalid_timestamp' => $has_invalid_timestamp,
            'invalid_breaks' => $invalid_breaks,
            'total_paid_break_seconds' => round((float) $paid_seconds, 2),
            'total_unpaid_break_seconds' => round((float) $unpaid_seconds, 2),
        ];
    }

    /**
     * Get a live team monitor snapshot with working, on-break, and elapsed break state.
     *
     * @param int    $department_id
     * @param string $date
     *
     * @return array
     */
    public function get_live_team_monitor($department_id = 0, $date = '')
    {
        $attendance_table = db_prefix() . 'fs_attendance';
        $staff_table = db_prefix() . 'staff';
        $break_table = db_prefix() . 'fs_break_sessions';
        $date = trim((string) $date);
        if ($date === '') {
            $date = date('Y-m-d');
        }

        $sessions = $this->db
            ->select('a.id, a.staff_id, a.date, a.clock_in, a.clock_out, a.notes, s.firstname, s.lastname, s.department_id')
            ->from($attendance_table . ' a')
            ->join($staff_table . ' s', 's.' . ($this->db->field_exists('staffid', $staff_table) ? 'staffid' : 'id') . ' = a.staff_id', 'left')
            ->where('a.date', $date)
            ->where('a.clock_out IS NULL', null, false)
            ->order_by('a.clock_in', 'DESC')
            ->get()
            ->result_array();

        $team = [];
        foreach ($sessions as $session) {
            $staff_id = (int) ($session['staff_id'] ?? 0);
            if ($staff_id <= 0) {
                continue;
            }

            if ($department_id > 0 && (int) ($session['department_id'] ?? 0) !== $department_id) {
                continue;
            }

            $break_row = $this->db
                ->select('id, break_out_at, break_in_at, break_type, ended_at')
                ->from($break_table)
                ->where('staff_id', $staff_id)
                ->where('attendance_id', (int) ($session['id'] ?? 0))
                ->where('ended_at IS NULL', null, false)
                ->order_by('break_out_at', 'DESC')
                ->limit(1)
                ->get()
                ->row_array();

            $has_invalid_break_timestamp = false;
            $invalid_break_fields = [];
            if (is_array($break_row) && !empty($break_row)) {
                $break_out_at = trim((string) ($break_row['break_out_at'] ?? ''));
                $break_in_at = trim((string) ($break_row['break_in_at'] ?? ''));
                $ended_at = trim((string) ($break_row['ended_at'] ?? ''));

                foreach (['break_out_at' => $break_out_at, 'break_in_at' => $break_in_at, 'ended_at' => $ended_at] as $field_name => $field_value) {
                    if ($field_value !== '' && $this->parse_datetime_or_null($field_value) === null) {
                        $has_invalid_break_timestamp = true;
                        $invalid_break_fields[] = $field_name;
                    }
                }

                if ($break_out_at === '' || $this->parse_datetime_or_null($break_out_at) === null) {
                    $break_row = [];
                } elseif ($break_in_at !== '' && $this->parse_datetime_or_null($break_in_at) === null) {
                    $break_row = [];
                } elseif ($ended_at !== '' && $this->parse_datetime_or_null($ended_at) === null) {
                    $break_row = [];
                }
            }

            $now = new DateTimeImmutable('now', $this->resolve_business_timezone());
            $elapsed_break_seconds = 0;
            if (is_array($break_row) && isset($break_row['break_out_at']) && trim((string) $break_row['break_out_at']) !== '') {
                $break_start = $this->parse_datetime_or_null((string) $break_row['break_out_at']);
                if ($break_start !== null) {
                    $elapsed_break_seconds = max(0, $now->getTimestamp() - $break_start->getTimestamp());
                }
            }

            $missing_break_return = is_array($break_row)
                && !empty($break_row)
                && trim((string) ($break_row['break_in_at'] ?? '')) === ''
                && trim((string) ($break_row['ended_at'] ?? '')) === '';

            $status = 'working';
            if ($has_invalid_break_timestamp) {
                $status = 'timestamp_issue';
            } elseif (is_array($break_row) && !empty($break_row)) {
                $status = 'on_break';
            }

            $team[] = [
                'staff_id' => $staff_id,
                'name' => trim((string) ($session['firstname'] ?? '') . ' ' . (string) ($session['lastname'] ?? '')) ?: 'Staff #' . $staff_id,
                'status' => $status,
                'missing_break_return' => $missing_break_return && !$has_invalid_break_timestamp,
                'unresolved_timestamp' => $has_invalid_break_timestamp,
                'invalid_break_fields' => $invalid_break_fields,
                'clock_in' => $session['clock_in'] ?? null,
                'current_break_type' => is_array($break_row) ? strtolower((string) ($break_row['break_type'] ?? 'paid')) : 'paid',
                'elapsed_break_seconds' => $elapsed_break_seconds,
                'is_active' => true,
            ];
        }

        return $team;
    }

    /**
     * Save one effective-dated employee policy override.
     *
     * @param int    $staff_id
     * @param array  $policy_data
     *
     * @return bool
     */
    public function save_employee_policy_override($staff_id, array $policy_data)
    {
        $staff_id = (int) $staff_id;
        if ($staff_id <= 0) {
            return false;
        }

        $field_name = strtolower(trim((string) ($policy_data['field_name'] ?? '')));
        $effective_from = trim((string) ($policy_data['effective_from'] ?? ''));
        if ($field_name === '' || $effective_from === '') {
            return false;
        }

        $table = db_prefix() . 'fs_policy_overrides';
        if (!$this->db || !method_exists($this->db, 'table_exists') || !$this->db->table_exists($table)) {
            return false;
        }

        $payload = [
            'staff_id' => $staff_id,
            'field_name' => $field_name,
            'value' => is_scalar($policy_data['value'] ?? null) ? trim((string) ($policy_data['value'] ?? '')) : json_encode($policy_data['value'] ?? null, JSON_UNESCAPED_SLASHES),
            'effective_from' => $effective_from,
            'effective_to' => trim((string) ($policy_data['effective_to'] ?? '')) !== '' ? trim((string) $policy_data['effective_to'] ?? '') : null,
            'source' => strtolower(trim((string) ($policy_data['source'] ?? 'manual'))),
            'verification_status' => strtolower(trim((string) ($policy_data['verification_status'] ?? 'pending'))),
            'notes' => trim((string) ($policy_data['notes'] ?? '')),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!in_array($payload['source'], ['manual', 'system', 'imported', 'verified'], true)) {
            $payload['source'] = 'manual';
        }

        if (!in_array($payload['verification_status'], ['pending', 'verified', 'rejected'], true)) {
            $payload['verification_status'] = 'pending';
        }

        $existing_rows = $this->db
            ->from($table)
            ->where('staff_id', $staff_id)
            ->where('field_name', $field_name)
            ->order_by('effective_from', 'DESC')
            ->get()
            ->result_array();
        $existing_rows = is_array($existing_rows) ? $existing_rows : [];
        foreach ($existing_rows as $existing_row) {
            $current_effective_from = trim((string) ($existing_row['effective_from'] ?? ''));
            if ($current_effective_from === '' || $current_effective_from >= $effective_from) {
                continue;
            }

            $current_effective_to = trim((string) ($existing_row['effective_to'] ?? ''));
            if ($current_effective_to !== '' && $current_effective_to < $effective_from) {
                continue;
            }

            $this->db->where('id', (int) ($existing_row['id'] ?? 0));
            $this->db->update($table, ['effective_to' => $effective_from]);
        }

        if (method_exists($this->db, 'where')) {
            $existing = $this->db
                ->select('id')
                ->from($table)
                ->where('staff_id', $staff_id)
                ->where('field_name', $field_name)
                ->where('effective_from', $effective_from)
                ->limit(1)
                ->get()
                ->row_array();
        } else {
            $existing = null;
        }

        if (is_array($existing) && !empty($existing)) {
            $this->db->where('id', (int) $existing['id']);
            return (bool) $this->db->update($table, $payload);
        }

        $payload['created_at'] = date('Y-m-d H:i:s');

        return (bool) $this->db->insert($table, $payload);
    }

    /**
     * Return employee policy overrides effective on or before a target date.
     *
     * @param int    $staff_id
     * @param string $as_of_date
     *
     * @return array
     */
    public function get_employee_policy_overrides($staff_id, $as_of_date = '')
    {
        $staff_id = (int) $staff_id;
        if ($staff_id <= 0) {
            return [];
        }

        $table = db_prefix() . 'fs_policy_overrides';
        if (!$this->db || !method_exists($this->db, 'table_exists') || !$this->db->table_exists($table)) {
            return [];
        }

        $query = $this->db
            ->from($table)
            ->where('staff_id', $staff_id)
            ->order_by('effective_from', 'DESC');

        if (trim((string) $as_of_date) !== '') {
            $query->where('effective_from <=', trim((string) $as_of_date));
            $query->group_start();
            $query->where('effective_to IS NULL', null, false);
            $query->or_where('effective_to >=', trim((string) $as_of_date));
            $query->group_end();
        }

        $rows = $query->get()->result_array();

        return is_array($rows) ? $rows : [];
    }

    /**
     * Return the effective employee policy snapshot keyed by field name.
     *
     * @param int    $staff_id
     * @param string $as_of_date
     *
     * @return array
     */
    public function get_employee_policy_snapshot($staff_id, $as_of_date = '')
    {
        $snapshot = [];
        foreach ($this->get_employee_policy_overrides($staff_id, $as_of_date) as $row) {
            $field_name = trim((string) ($row['field_name'] ?? ''));
            if ($field_name === '') {
                continue;
            }

            $snapshot[$field_name] = [
                'field_name' => $field_name,
                'value' => $row['value'] ?? '',
                'source' => strtolower(trim((string) ($row['source'] ?? 'manual'))),
                'verification_status' => strtolower(trim((string) ($row['verification_status'] ?? 'pending'))),
                'effective_from' => $row['effective_from'] ?? '',
                'effective_to' => $row['effective_to'] ?? null,
                'notes' => $row['notes'] ?? '',
            ];
        }

        return $snapshot;
    }

    /**
     * Create an attendance correction request for a supervisor review.
     *
     * @param array $payload
     *
     * @return bool
     */
    public function save_attendance_correction_request(array $payload)
    {
        $attendance_id = (int) ($payload['attendance_id'] ?? 0);
        $staff_id = (int) ($payload['staff_id'] ?? 0);
        $requested_by = (int) ($payload['requested_by'] ?? 0);
        $correction_type = trim((string) ($payload['correction_type'] ?? 'attendance'));
        $before_value = trim((string) ($payload['before_value'] ?? ''));
        $after_value = trim((string) ($payload['after_value'] ?? ''));
        $reason = trim((string) ($payload['reason'] ?? ''));

        if ($attendance_id <= 0 || $staff_id <= 0 || $requested_by <= 0 || $reason === '') {
            return false;
        }

        $table = db_prefix() . 'fs_attendance_corrections';
        if (!$this->db || !method_exists($this->db, 'table_exists') || !$this->db->table_exists($table)) {
            return false;
        }

        $created_at = date('Y-m-d H:i:s');
        return (bool) $this->db->insert($table, [
            'attendance_id' => $attendance_id,
            'staff_id' => $staff_id,
            'requested_by' => $requested_by,
            'correction_type' => $correction_type !== '' ? $correction_type : 'attendance',
            'before_value' => $before_value,
            'after_value' => $after_value,
            'reason' => $reason,
            'status' => 'pending',
            'created_at' => $created_at,
            'updated_at' => $created_at,
        ]);
    }

    /**
     * Approve or reject an attendance correction request.
     *
     * @param int    $request_id
     * @param int    $approved_by
     * @param string $status
     * @param string $notes
     *
     * @return bool
     */
    public function approve_attendance_correction($request_id, $approved_by, $status, $notes = '')
    {
        $request_id = (int) $request_id;
        $approved_by = (int) $approved_by;
        $status = strtolower(trim((string) $status));
        $notes = trim((string) $notes);

        if ($request_id <= 0 || $approved_by <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }

        $table = db_prefix() . 'fs_attendance_corrections';
        if (!$this->db || !method_exists($this->db, 'table_exists') || !$this->db->table_exists($table)) {
            return false;
        }

        $this->db->where('id', $request_id);
        return (bool) $this->db->update($table, [
            'status' => $status,
            'approved_by' => $approved_by,
            'approved_at' => date('Y-m-d H:i:s'),
            'notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Return correction requests for one attendance row or a staff member.
     *
     * @param int $staff_id
     *
     * @return array
     */
    public function get_attendance_correction_history($staff_id = 0)
    {
        $staff_id = (int) $staff_id;
        $table = db_prefix() . 'fs_attendance_corrections';
        if (!$this->db || !method_exists($this->db, 'table_exists') || !$this->db->table_exists($table)) {
            return [];
        }

        $query = $this->db
            ->from($table)
            ->order_by('created_at', 'DESC');

        if ($staff_id > 0) {
            $query->where('staff_id', $staff_id);
        }

        return $query->get()->result_array();
    }

    /**
     * Return a single attendance row with break totals and payable time.
     *
     * @param array $attendance_row
     *
     * @return array
     */
    public function summarize_attendance_record(array $attendance_row)
    {
        $attendance_id = (int) ($attendance_row['id'] ?? 0);
        $clock_in = trim((string) ($attendance_row['clock_in'] ?? ''));
        $clock_out = trim((string) ($attendance_row['clock_out'] ?? ''));
        $break_rows = [];
        if (isset($attendance_row['break_rows']) && is_array($attendance_row['break_rows'])) {
            $break_rows = $attendance_row['break_rows'];
        } elseif ($attendance_id > 0) {
            $break_table = db_prefix() . 'fs_break_sessions';
            $break_rows = $this->db
                ->from($break_table)
                ->where('attendance_id', $attendance_id)
                ->order_by('break_out_at', 'ASC')
                ->get()
                ->result_array();
        }

        $shift_seconds = 0.0;
        if ($clock_in !== '' && $clock_out !== '') {
            $shift_seconds = $this->calculate_duration_seconds($clock_in, $clock_out);
        }

        $paid_break_seconds = 0.0;
        $unpaid_break_seconds = 0.0;
        $missing_break_in = false;
        $has_invalid_timestamp = false;
        $invalid_breaks = [];
        foreach ($break_rows as $break_row) {
            $break_out = trim((string) ($break_row['break_out_at'] ?? ''));
            $break_in = trim((string) ($break_row['break_in_at'] ?? ''));
            $ended_at = trim((string) ($break_row['ended_at'] ?? ''));
            $break_type = strtolower(trim((string) ($break_row['break_type'] ?? 'paid')));
            $duration = 0.0;
            $invalid_fields = [];

            foreach (['break_out_at' => $break_out, 'break_in_at' => $break_in, 'ended_at' => $ended_at] as $field_name => $field_value) {
                if ($field_value !== '' && $this->parse_datetime_or_null($field_value) === null) {
                    $invalid_fields[] = $field_name;
                }
            }

            if (!empty($invalid_fields)) {
                $has_invalid_timestamp = true;
                $invalid_breaks[] = [
                    'id' => isset($break_row['id']) ? (int) $break_row['id'] : 0,
                    'invalid_fields' => $invalid_fields,
                ];
                continue;
            }

            if ($break_out !== '' && $break_in !== '') {
                $duration = $this->calculate_duration_seconds($break_out, $break_in);
            } elseif ($break_out !== '' && $ended_at !== '') {
                $duration = $this->calculate_duration_seconds($break_out, $ended_at);
            }
            if ($break_type === 'paid') {
                $paid_break_seconds += $duration;
            } else {
                $unpaid_break_seconds += $duration;
            }

            if ($break_out !== '' && $break_in === '' && $ended_at === '') {
                $missing_break_in = true;
            }
        }

        $payable_seconds = max(0.0, $shift_seconds - $unpaid_break_seconds);
        $status = 'closed';
        if ($has_invalid_timestamp) {
            $status = 'timestamp_issue';
        } elseif ($missing_break_in) {
            $status = 'break_return_pending';
        }

        return [
            'attendance_id' => $attendance_id,
            'shift_seconds' => round($shift_seconds, 2),
            'paid_break_seconds' => round($paid_break_seconds, 2),
            'unpaid_break_seconds' => round($unpaid_break_seconds, 2),
            'payable_seconds' => round($payable_seconds, 2),
            'missing_break_in' => $missing_break_in,
            'has_invalid_timestamp' => $has_invalid_timestamp,
            'invalid_breaks' => $invalid_breaks,
            'status' => $status,
            'break_rows' => $break_rows,
        ];
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
     * Retrieve issued payslips for admin reporting workspace filters.
     *
     * @param string $start_date
     * @param string $end_date
     * @param int    $staff_id
     * @param int    $department_id
     * @param int    $limit
     *
     * @return array
     */
    public function get_issued_payslips($start_date, $end_date, $staff_id = 0, $department_id = 0, $limit = 100)
    {
        $table = db_prefix() . 'fs_payroll_master';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        $staff_id = (int) $staff_id;
        $department_id = (int) $department_id;
        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 100;
        }

        $query = $this->db
            ->select('id, staff_id, start_date, end_date, gross_salary, net_salary, payment_method, status, created_at')
            ->from($table)
            ->where_in('status', ['paid', 'issued'])
            ->where('start_date >=', $start_date)
            ->where('end_date <=', $end_date)
            ->order_by('end_date', 'DESC')
            ->order_by('id', 'DESC')
            ->limit($limit);

        if ($staff_id > 0) {
            $query->where('staff_id', $staff_id);
        }

        $rows = $query->get()->result_array();
        if (empty($rows)) {
            return [];
        }

        if ($department_id > 0) {
            $allowed_staff = $this->get_staff_directory($department_id);
            $allowed_ids = [];
            foreach ($allowed_staff as $staff_row) {
                $allowed_ids[] = (int) ($staff_row['staff_id'] ?? 0);
            }
            $allowed_lookup = array_flip(array_filter($allowed_ids));
            $rows = array_values(array_filter($rows, function ($row) use ($allowed_lookup) {
                return isset($allowed_lookup[(int) ($row['staff_id'] ?? 0)]);
            }));
        }

        foreach ($rows as &$row) {
            $current_staff_id = (int) ($row['staff_id'] ?? 0);
            $staff_row = $this->get_staff_row($current_staff_id);
            $profile = $this->get_payroll_profile($current_staff_id);
            $snapshot = $this->get_payroll_snapshot((int) ($row['id'] ?? 0));
            if (!empty($snapshot)) {
                $row['gross_salary'] = isset($snapshot['totals']['gross_salary']) ? (float) $snapshot['totals']['gross_salary'] : (float) ($row['gross_salary'] ?? 0);
                $row['net_salary'] = isset($snapshot['totals']['net_salary']) ? (float) $snapshot['totals']['net_salary'] : (float) ($row['net_salary'] ?? 0);
                $row['payment_method'] = isset($snapshot['payment_method']) ? (string) $snapshot['payment_method'] : (string) ($row['payment_method'] ?? 'online_transfer');
            }
            $row['worker_name'] = $this->resolve_staff_name($staff_row, $current_staff_id);
            $row['department_name'] = $this->get_department_name((int) ($profile['department_id'] ?? 0));
        }
        unset($row);

        return $rows;
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
        $snapshot = $this->get_payroll_snapshot($payroll_id);
        $base = [
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

        if (!empty($snapshot)) {
            $base['payment_method'] = isset($snapshot['payment_method']) ? (string) $snapshot['payment_method'] : $base['payment_method'];
            $base['regular_hours'] = isset($snapshot['totals']['regular_hours']) ? (float) $snapshot['totals']['regular_hours'] : $base['regular_hours'];
            $base['overtime_hours'] = isset($snapshot['totals']['ot_hours']) ? (float) $snapshot['totals']['ot_hours'] : $base['overtime_hours'];
            $base['hourly_rate'] = isset($snapshot['rates']['hourly_rate']) ? (float) $snapshot['rates']['hourly_rate'] : $base['hourly_rate'];
            $base['gross_salary'] = isset($snapshot['totals']['gross_salary']) ? (float) $snapshot['totals']['gross_salary'] : $base['gross_salary'];
            $base['nib_ee'] = isset($snapshot['totals']['nib_ee']) ? (float) $snapshot['totals']['nib_ee'] : $base['nib_ee'];
            $base['nib_er'] = isset($snapshot['totals']['nib_er']) ? (float) $snapshot['totals']['nib_er'] : $base['nib_er'];
            $base['nhip_ee'] = isset($snapshot['totals']['nhip_ee']) ? (float) $snapshot['totals']['nhip_ee'] : $base['nhip_ee'];
            $base['nhip_er'] = isset($snapshot['totals']['nhip_er']) ? (float) $snapshot['totals']['nhip_er'] : $base['nhip_er'];
            $base['net_salary'] = isset($snapshot['totals']['net_salary']) ? (float) $snapshot['totals']['net_salary'] : $base['net_salary'];
            $base['adjustments'] = isset($snapshot['adjustments']) && is_array($snapshot['adjustments']) ? $snapshot['adjustments'] : $base['adjustments'];
        }

        return $base;
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
    /**
     * Build a versioned snapshot payload for a saved payroll row.
     *
     * @param int   $payroll_id
     * @param array $master_row
     * @param array $row
     * @param array $profile
     * @param array $cutoff
     *
     * @return array
     */
    public function build_payroll_snapshot_payload($payroll_id, array $master_row = [], array $row = [], array $profile = [], array $cutoff = [])
    {
        $payroll_id = (int) $payroll_id;
        $staff_id = isset($master_row['staff_id']) ? (int) $master_row['staff_id'] : (int) ($row['staff_id'] ?? 0);
        $cutoff_id = isset($master_row['cutoff_id']) ? (int) $master_row['cutoff_id'] : (int) ($row['cutoff_id'] ?? 0);
        $status = isset($master_row['status']) ? strtolower(trim((string) $master_row['status'])) : 'draft';
        $status = in_array($status, ['draft', 'under_review', 'approved', 'paid'], true) ? $status : 'draft';

        $effective_profile = is_array($profile) ? $profile : [];
        $effective_policy = [
            'payroll_group' => trim((string) ($effective_profile['payroll_group'] ?? $cutoff['payroll_group'] ?? 'default')),
            'payment_basis' => strtolower(trim((string) ($effective_profile['payment_basis'] ?? 'hourly'))),
            'position' => trim((string) ($effective_profile['position'] ?? 'worker')),
            'employment_arrangement' => trim((string) ($effective_profile['employment_arrangement'] ?? 'permanent')),
            'attendance_requirement' => trim((string) ($effective_profile['attendance_requirement'] ?? 'required')),
            'base_hourly_rate' => isset($effective_profile['base_hourly_rate']) && is_numeric($effective_profile['base_hourly_rate']) ? round((float) $effective_profile['base_hourly_rate'], 2) : 0.0,
            'overtime_multiplier' => isset($effective_profile['overtime_multiplier']) && is_numeric($effective_profile['overtime_multiplier']) ? round((float) $effective_profile['overtime_multiplier'], 2) : 1.5,
            'payment_method' => isset($effective_profile['payment_method']) ? (string) $effective_profile['payment_method'] : 'online_transfer',
        ];

        $rates = [
            'hourly_rate' => isset($master_row['hourly_rate']) && is_numeric($master_row['hourly_rate']) ? round((float) $master_row['hourly_rate'], 2) : 0.0,
            'regular_hours' => isset($master_row['regular_hours']) && is_numeric($master_row['regular_hours']) ? round((float) $master_row['regular_hours'], 2) : 0.0,
            'ot_hours' => isset($master_row['ot_hours']) && is_numeric($master_row['ot_hours']) ? round((float) $master_row['ot_hours'], 2) : 0.0,
            'hourly_policy_rate' => isset($effective_profile['base_hourly_rate']) && is_numeric($effective_profile['base_hourly_rate']) ? round((float) $effective_profile['base_hourly_rate'], 2) : 0.0,
            'payment_basis' => $effective_policy['payment_basis'],
            'overtime_multiplier' => $effective_policy['overtime_multiplier'],
        ];

        $earnings = [
            'regular_pay' => isset($row['regular_pay']) && is_numeric($row['regular_pay']) ? round((float) $row['regular_pay'], 2) : 0.0,
            'overtime_pay' => isset($row['overtime_pay']) && is_numeric($row['overtime_pay']) ? round((float) $row['overtime_pay'], 2) : 0.0,
            'holiday_pay' => isset($row['holiday_pay']) && is_numeric($row['holiday_pay']) ? round((float) $row['holiday_pay'], 2) : 0.0,
            'allowance_due' => isset($row['allowance_due']) && is_numeric($row['allowance_due']) ? round((float) $row['allowance_due'], 2) : 0.0,
            'vacation_pay' => isset($row['vacation_pay']) && is_numeric($row['vacation_pay']) ? round((float) $row['vacation_pay'], 2) : 0.0,
            'commission_pay' => isset($row['commission_pay']) && is_numeric($row['commission_pay']) ? round((float) $row['commission_pay'], 2) : 0.0,
        ];

        $deductions = [
            'loan_repayment' => isset($row['loan_repayment']) && is_numeric($row['loan_repayment']) ? round((float) $row['loan_repayment'], 2) : 0.0,
            'advance_repayment' => isset($row['advance_repayment']) && is_numeric($row['advance_repayment']) ? round((float) $row['advance_repayment'], 2) : 0.0,
            'other_misc_deductions' => isset($row['other_misc_deductions']) && is_numeric($row['other_misc_deductions']) ? round((float) $row['other_misc_deductions'], 2) : 0.0,
            'nib_ee' => isset($master_row['nib_ee']) && is_numeric($master_row['nib_ee']) ? round((float) $master_row['nib_ee'], 2) : 0.0,
            'nib_er' => isset($master_row['nib_er']) && is_numeric($master_row['nib_er']) ? round((float) $master_row['nib_er'], 2) : 0.0,
            'nhip_ee' => isset($master_row['nhip_ee']) && is_numeric($master_row['nhip_ee']) ? round((float) $master_row['nhip_ee'], 2) : 0.0,
            'nhip_er' => isset($master_row['nhip_er']) && is_numeric($master_row['nhip_er']) ? round((float) $master_row['nhip_er'], 2) : 0.0,
        ];

        $totals = [
            'regular_hours' => isset($master_row['regular_hours']) && is_numeric($master_row['regular_hours']) ? round((float) $master_row['regular_hours'], 2) : 0.0,
            'ot_hours' => isset($master_row['ot_hours']) && is_numeric($master_row['ot_hours']) ? round((float) $master_row['ot_hours'], 2) : 0.0,
            'gross_salary' => isset($master_row['gross_salary']) && is_numeric($master_row['gross_salary']) ? round((float) $master_row['gross_salary'], 2) : 0.0,
            'nib_ee' => isset($master_row['nib_ee']) && is_numeric($master_row['nib_ee']) ? round((float) $master_row['nib_ee'], 2) : 0.0,
            'nib_er' => isset($master_row['nib_er']) && is_numeric($master_row['nib_er']) ? round((float) $master_row['nib_er'], 2) : 0.0,
            'nhip_ee' => isset($master_row['nhip_ee']) && is_numeric($master_row['nhip_ee']) ? round((float) $master_row['nhip_ee'], 2) : 0.0,
            'nhip_er' => isset($master_row['nhip_er']) && is_numeric($master_row['nhip_er']) ? round((float) $master_row['nhip_er'], 2) : 0.0,
            'net_salary' => isset($master_row['net_salary']) && is_numeric($master_row['net_salary']) ? round((float) $master_row['net_salary'], 2) : 0.0,
        ];

        $cutoff_payload = is_array($cutoff) ? $cutoff : [];
        return [
            'payroll_id' => $payroll_id,
            'staff_id' => $staff_id,
            'cutoff_id' => $cutoff_id,
            'status' => $status,
            'calculation_version' => '2026.10.19.payroll.v1',
            'start_date' => isset($master_row['start_date']) ? (string) $master_row['start_date'] : '',
            'end_date' => isset($master_row['end_date']) ? (string) $master_row['end_date'] : '',
            'payment_method' => isset($master_row['payment_method']) ? (string) $master_row['payment_method'] : ($effective_policy['payment_method'] ?? 'online_transfer'),
            'cutoff' => [
                'cutoff_id' => $cutoff_id,
                'cutoff_code' => trim((string) ($cutoff_payload['cutoff_code'] ?? '')),
                'payroll_group' => trim((string) ($cutoff_payload['payroll_group'] ?? $effective_policy['payroll_group'] ?? 'default')),
                'status' => trim((string) ($cutoff_payload['status'] ?? 'open')),
            ],
            'policies' => $effective_policy,
            'rates' => $rates,
            'earnings' => $earnings,
            'deductions' => $deductions,
            'totals' => $totals,
            'generated_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function save_payroll_snapshot($payroll_id, $status = 'draft', array $snapshot_payload = [])
    {
        $payroll_id = (int) $payroll_id;
        $status = strtolower(trim((string) $status));
        if (!in_array($status, ['draft', 'under_review', 'approved', 'paid'], true) || $payroll_id <= 0) {
            return false;
        }

        $table = db_prefix() . 'fs_payroll_snapshots';
        if (!$this->db->table_exists($table)) {
            return false;
        }

        $payload = is_array($snapshot_payload) ? $snapshot_payload : [];
        $payload['status'] = $status;
        $payload['calculation_version'] = trim((string) ($payload['calculation_version'] ?? '2026.10.19.payroll.v1'));
        if ($payload['calculation_version'] === '') {
            $payload['calculation_version'] = '2026.10.19.payroll.v1';
        }

        $existing = $this->db
            ->from($table)
            ->where('payroll_id', $payroll_id)
            ->limit(1)
            ->get()
            ->row_array();

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $now = date('Y-m-d H:i:s');

        if (is_array($existing) && !empty($existing)) {
            $existing_status = strtolower(trim((string) ($existing['snapshot_status'] ?? 'draft')));
            if ($existing_status === 'paid') {
                return false;
            }
            if ($existing_status === 'approved' && !in_array($status, ['approved', 'paid'], true)) {
                return false;
            }

            $this->db->where('payroll_id', $payroll_id);
            return (bool) $this->db->update($table, [
                'snapshot_status' => $status,
                'calculation_version' => $payload['calculation_version'],
                'snapshot_data' => $encoded,
                'generated_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $master_row = $this->get_payroll_master_row($payroll_id);
        $staff_id = isset($master_row['staff_id']) ? (int) $master_row['staff_id'] : 0;
        $cutoff_id = isset($master_row['cutoff_id']) ? (int) $master_row['cutoff_id'] : 0;

        return (bool) $this->db->insert($table, [
            'payroll_id' => $payroll_id,
            'staff_id' => $staff_id,
            'cutoff_id' => $cutoff_id,
            'calculation_version' => $payload['calculation_version'],
            'snapshot_status' => $status,
            'snapshot_data' => $encoded,
            'generated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function get_payroll_snapshot($payroll_id)
    {
        $payroll_id = (int) $payroll_id;
        if ($payroll_id <= 0) {
            return [];
        }

        $table = db_prefix() . 'fs_payroll_snapshots';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        $row = $this->db
            ->from($table)
            ->where('payroll_id', $payroll_id)
            ->limit(1)
            ->get()
            ->row_array();

        if (!is_array($row) || empty($row)) {
            return [];
        }

        $payload = trim((string) ($row['snapshot_data'] ?? ''));
        if ($payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function get_payroll_workflow_history($payroll_id)
    {
        $payroll_id = (int) $payroll_id;
        if ($payroll_id <= 0) {
            return [];
        }

        $table = db_prefix() . 'fs_payroll_workflow_history';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        return $this->db
            ->from($table)
            ->where('payroll_id', $payroll_id)
            ->order_by('created_at', 'ASC')
            ->get()
            ->result_array();
    }

    public function record_payroll_workflow_event($payroll_id, $action, $actor_staff_id = 0, array $details = [])
    {
        $payroll_id = (int) $payroll_id;
        $action = strtolower(trim((string) $action));
        if ($payroll_id <= 0 || $action === '') {
            return false;
        }

        $table = db_prefix() . 'fs_payroll_workflow_history';
        if (!$this->db->table_exists($table)) {
            return false;
        }

        $payroll_row = $this->get_payroll_master_row($payroll_id);
        $staff_id = isset($payroll_row['staff_id']) ? (int) $payroll_row['staff_id'] : 0;
        $cutoff_id = isset($payroll_row['cutoff_id']) ? (int) $payroll_row['cutoff_id'] : 0;
        $from_status = strtolower(trim((string) ($details['from_status'] ?? (string) ($payroll_row['status'] ?? 'draft'))));
        $to_status = strtolower(trim((string) ($details['to_status'] ?? $from_status)));

        $payload = [
            'payroll_id' => $payroll_id,
            'staff_id' => $staff_id,
            'cutoff_id' => $cutoff_id,
            'action' => $action,
            'from_status' => $from_status,
            'to_status' => $to_status,
            'actor_staff_id' => (int) $actor_staff_id,
            'notes' => isset($details['notes']) ? trim((string) $details['notes']) : '',
            'payment_date' => isset($details['payment_date']) && trim((string) $details['payment_date']) !== '' ? trim((string) $details['payment_date']) : null,
            'payment_method' => isset($details['payment_method']) && trim((string) $details['payment_method']) !== '' ? trim((string) $details['payment_method']) : null,
            'payment_reference' => isset($details['payment_reference']) && trim((string) $details['payment_reference']) !== '' ? trim((string) $details['payment_reference']) : null,
            'payment_amount' => isset($details['payment_amount']) && is_numeric($details['payment_amount']) ? round((float) $details['payment_amount'], 2) : null,
            'snapshot_id' => isset($details['snapshot_id']) ? (int) $details['snapshot_id'] : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return (bool) $this->db->insert($table, $payload);
    }

    public function has_unverified_payroll_policy($staff_id)
    {
        $staff_id = (int) $staff_id;
        if ($staff_id <= 0) {
            return false;
        }

        $table = db_prefix() . 'fs_policy_overrides';
        if (!$this->db->table_exists($table)) {
            return false;
        }

        $rows = $this->db
            ->from($table)
            ->where('staff_id', $staff_id)
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            $status = strtolower(trim((string) ($row['verification_status'] ?? 'verified')));
            if (in_array($status, ['pending', 'unverified', 'required'], true)) {
                return true;
            }
        }

        return false;
    }

    public function apply_payroll_workflow_action($payroll_id, $action, $actor_staff_id = 0, array $details = [])
    {
        $payroll_id = (int) $payroll_id;
        $action = strtolower(trim((string) $action));
        if ($payroll_id <= 0 || $action === '') {
            return ['success' => false, 'message' => 'A valid payroll record and action are required.'];
        }

        $payroll_row = $this->get_payroll_master_row($payroll_id);
        if (!is_array($payroll_row) || empty($payroll_row)) {
            return ['success' => false, 'message' => 'The selected payroll record could not be found.'];
        }

        $current_status = strtolower(trim((string) ($payroll_row['status'] ?? 'draft')));
        $staff_id = (int) ($payroll_row['staff_id'] ?? 0);
        $snapshot = $this->get_payroll_snapshot($payroll_id);

        switch ($action) {
            case 'submit_for_review':
                if (!in_array($current_status, ['draft'], true)) {
                    return ['success' => false, 'message' => 'Only draft payroll can be submitted for review.'];
                }
                if (empty($snapshot)) {
                    return ['success' => false, 'message' => 'A saved snapshot is required before payroll can be submitted for review.'];
                }

                $snapshot_payload = $this->get_payroll_snapshot($payroll_id);
                if (!empty($snapshot_payload)) {
                    $snapshot_payload['status'] = 'under_review';
                    $snapshot_payload['updated_at'] = date('Y-m-d H:i:s');
                    $snapshot_payload['generated_at'] = $snapshot_payload['generated_at'] ?? $snapshot_payload['updated_at'];
                    $this->save_payroll_snapshot($payroll_id, 'under_review', $snapshot_payload);
                }

                $this->db->where('id', $payroll_id);
                $updated = (bool) $this->db->update(db_prefix() . 'fs_payroll_master', ['status' => 'under_review']);
                if (!$updated) {
                    return ['success' => false, 'message' => 'Payroll could not be moved to under review at this time.'];
                }

                $this->record_payroll_workflow_event($payroll_id, 'submit_for_review', $actor_staff_id, [
                    'from_status' => $current_status,
                    'to_status' => 'under_review',
                    'notes' => isset($details['notes']) ? (string) $details['notes'] : 'Submitted for review.',
                ]);
                return ['success' => true, 'message' => 'Payroll submitted for review.', 'status' => 'under_review'];

            case 'return_to_draft':
                if (!in_array($current_status, ['under_review', 'approved'], true)) {
                    return ['success' => false, 'message' => 'Only payroll under review or already approved can be returned to draft.'];
                }
                $this->db->where('id', $payroll_id);
                $updated = (bool) $this->db->update(db_prefix() . 'fs_payroll_master', ['status' => 'draft']);
                if (!$updated) {
                    return ['success' => false, 'message' => 'Payroll could not be returned to draft at this time.'];
                }
                $this->record_payroll_workflow_event($payroll_id, 'return_to_draft', $actor_staff_id, [
                    'from_status' => $current_status,
                    'to_status' => 'draft',
                    'notes' => isset($details['notes']) ? (string) $details['notes'] : 'Returned to draft.',
                ]);
                return ['success' => true, 'message' => 'Payroll returned to draft.', 'status' => 'draft'];

            case 'approve':
                if (in_array($current_status, ['paid'], true)) {
                    return ['success' => false, 'message' => 'Paid payroll cannot be re-approved.'];
                }
                if (!in_array($current_status, ['draft', 'under_review'], true)) {
                    return ['success' => false, 'message' => 'Only draft or under-review payroll can be approved.'];
                }
                if (empty($snapshot)) {
                    return ['success' => false, 'message' => 'Payroll approval requires a valid saved snapshot.'];
                }
                if ($this->has_unverified_payroll_policy($staff_id)) {
                    return ['success' => false, 'message' => 'Payroll approval is blocked until the staff policy verification status is cleared.'];
                }

                $snapshot_payload = $this->get_payroll_snapshot($payroll_id);
                if (!empty($snapshot_payload)) {
                    $snapshot_payload['status'] = 'approved';
                    $snapshot_payload['updated_at'] = date('Y-m-d H:i:s');
                    $this->save_payroll_snapshot($payroll_id, 'approved', $snapshot_payload);
                }

                $this->db->where('id', $payroll_id);
                $updated = (bool) $this->db->update(db_prefix() . 'fs_payroll_master', ['status' => 'approved']);
                if (!$updated) {
                    return ['success' => false, 'message' => 'Payroll could not be approved at this time.'];
                }
                $this->record_payroll_workflow_event($payroll_id, 'approve', $actor_staff_id, [
                    'from_status' => $current_status,
                    'to_status' => 'approved',
                    'notes' => isset($details['notes']) ? (string) $details['notes'] : 'Approved payroll.',
                    'snapshot_id' => (int) ($details['snapshot_id'] ?? 0),
                ]);
                return ['success' => true, 'message' => 'Payroll approved.', 'status' => 'approved'];

            case 'record_payment':
                $payment_reference = trim((string) ($details['payment_reference'] ?? ''));
                $payment_method = trim((string) ($details['payment_method'] ?? ''));
                $payment_date = trim((string) ($details['payment_date'] ?? ''));
                $payment_amount = isset($details['payment_amount']) && is_numeric($details['payment_amount']) ? round((float) $details['payment_amount'], 2) : 0.0;
                if (!in_array($current_status, ['approved'], true)) {
                    return ['success' => false, 'message' => 'Only approved payroll can have payment recorded.'];
                }
                if ($payment_reference === '') {
                    return ['success' => false, 'message' => 'A payment reference is required to record payment.'];
                }
                if (empty($snapshot)) {
                    return ['success' => false, 'message' => 'A saved snapshot is required before a payment can be recorded.'];
                }

                $existing = $this->db
                    ->from(db_prefix() . 'fs_payroll_workflow_history')
                    ->where('payroll_id', $payroll_id)
                    ->where('action', 'record_payment')
                    ->where('payment_reference', $payment_reference)
                    ->limit(1)
                    ->get()
                    ->row_array();
                if (is_array($existing) && !empty($existing)) {
                    return ['success' => false, 'message' => 'A duplicate payment record has already been recorded for this reference.'];
                }

                $snapshot_payload = $this->get_payroll_snapshot($payroll_id);
                if (!empty($snapshot_payload)) {
                    $snapshot_payload['status'] = 'paid';
                    $snapshot_payload['updated_at'] = date('Y-m-d H:i:s');
                    $snapshot_payload['payment_recorded_at'] = date('Y-m-d H:i:s');
                    $snapshot_payload['payment_reference'] = $payment_reference;
                    $snapshot_payload['payment_method'] = $payment_method;
                    $snapshot_payload['payment_amount'] = $payment_amount;
                    $snapshot_payload['payment_date'] = $payment_date;
                    $this->save_payroll_snapshot($payroll_id, 'paid', $snapshot_payload);
                }

                $this->db->where('id', $payroll_id);
                $updated = (bool) $this->db->update(db_prefix() . 'fs_payroll_master', ['status' => 'paid']);
                if (!$updated) {
                    return ['success' => false, 'message' => 'Payment could not be recorded at this time.'];
                }
                $this->record_payroll_workflow_event($payroll_id, 'record_payment', $actor_staff_id, [
                    'from_status' => $current_status,
                    'to_status' => 'paid',
                    'notes' => isset($details['notes']) ? (string) $details['notes'] : 'Payment recorded.',
                    'payment_date' => $payment_date,
                    'payment_method' => $payment_method,
                    'payment_reference' => $payment_reference,
                    'payment_amount' => $payment_amount,
                    'snapshot_id' => (int) ($details['snapshot_id'] ?? 0),
                ]);
                return ['success' => true, 'message' => 'Payment recorded.', 'status' => 'paid'];

            default:
                return ['success' => false, 'message' => 'Unsupported payroll workflow action.'];
        }
    }

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

    public function get_payroll_cutoff_by_id($cutoff_id)
    {
        $cutoff_id = (int) $cutoff_id;
        if ($cutoff_id <= 0) {
            return null;
        }

        $table = db_prefix() . 'fs_payroll_cutoffs';
        if (!$this->db->table_exists($table)) {
            return null;
        }

        $row = $this->db
            ->from($table)
            ->where('id', $cutoff_id)
            ->limit(1)
            ->get()
            ->row_array();

        if (!is_array($row)) {
            return null;
        }

        $row['payroll_count'] = (int) $this->count_payroll_records_for_cutoff($cutoff_id);
        return $row;
    }

    public function get_payroll_cutoffs(array $filters = [])
    {
        $table = db_prefix() . 'fs_payroll_cutoffs';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        $query = $this->db->from($table);

        if (!empty($filters['payroll_group'])) {
            $query->where('payroll_group', trim((string) $filters['payroll_group']));
        }

        if (!empty($filters['status'])) {
            $query->where('status', strtolower(trim((string) $filters['status'])));
        }

        $rows = $query
            ->order_by('start_date', 'DESC')
            ->order_by('id', 'DESC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $row['payroll_count'] = (int) $this->count_payroll_records_for_cutoff((int) ($row['id'] ?? 0));
        }
        unset($row);

        return $rows;
    }

    public function save_payroll_cutoff(array $data, $user_staff_id = 0)
    {
        $table = db_prefix() . 'fs_payroll_cutoffs';
        if (!$this->db->table_exists($table)) {
            return false;
        }

        $cutoff_id = (int) ($data['id'] ?? 0);
        $group = trim((string) ($data['payroll_group'] ?? 'default'));
        $group = $group !== '' ? $group : 'default';
        $start_date = trim((string) ($data['start_date'] ?? ''));
        $end_date = trim((string) ($data['end_date'] ?? ''));
        $payment_date = trim((string) ($data['payment_date'] ?? ''));
        $status = strtolower(trim((string) ($data['status'] ?? 'open')));
        $status = in_array($status, ['open', 'under_review', 'approved', 'closed'], true) ? $status : 'open';

        if ($start_date === '' || $end_date === '' || $payment_date === '') {
            return false;
        }

        if ($start_date > $end_date) {
            $tmp = $start_date;
            $start_date = $end_date;
            $end_date = $tmp;
        }

        $existing = [];
        if ($cutoff_id > 0) {
            $existing = $this->db
                ->from($table)
                ->where('id', $cutoff_id)
                ->limit(1)
                ->get()
                ->row_array();
        }

        if (!empty($existing)) {
            $old_status = strtolower((string) ($existing['status'] ?? 'open'));
            $allowed_transitions = [
                'open' => ['under_review', 'closed'],
                'under_review' => ['approved', 'closed'],
                'approved' => ['closed'],
                'closed' => [],
            ];

            if ($old_status !== $status && !in_array($status, $allowed_transitions[$old_status] ?? [], true)) {
                return false;
            }

            $payroll_count = (int) $this->count_payroll_records_for_cutoff($cutoff_id);
            if ($payroll_count > 0) {
                if ((string) ($existing['payroll_group'] ?? '') !== $group || (string) ($existing['start_date'] ?? '') !== $start_date || (string) ($existing['end_date'] ?? '') !== $end_date) {
                    return false;
                }
            }
        }

        $overlap_check = $this->db
            ->select('id')
            ->from($table)
            ->where('payroll_group', $group)
            ->where('id !=', $cutoff_id)
            ->group_start()
            ->where('start_date <=', $end_date)
            ->where('end_date >=', $start_date)
            ->group_end()
            ->limit(1)
            ->get()
            ->row_array();

        if (is_array($overlap_check) && !empty($overlap_check)) {
            return false;
        }

        $cutoff_code = trim((string) ($data['cutoff_code'] ?? ''));
        if ($cutoff_code === '') {
            $cutoff_code = $this->generate_cutoff_code($group, $start_date, $end_date);
        }

        $payload = [
            'cutoff_code' => $cutoff_code,
            'payroll_group' => $group,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'payment_date' => $payment_date,
            'status' => $status,
            'updated_by' => (int) ($user_staff_id > 0 ? $user_staff_id : (int) ($data['updated_by'] ?? 0)),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($cutoff_id > 0) {
            $this->db->where('id', $cutoff_id);
            return (bool) $this->db->update($table, $payload);
        }

        $payload['created_by'] = (int) ($user_staff_id > 0 ? $user_staff_id : (int) ($data['created_by'] ?? 0));
        $payload['created_at'] = date('Y-m-d H:i:s');
        return (bool) $this->db->insert($table, $payload);
    }

    private function apply_saved_snapshot_to_payroll_row(array &$row)
    {
        $payroll_id = (int) ($row['id'] ?? 0);
        if ($payroll_id <= 0) {
            return;
        }

        $snapshot = $this->get_payroll_snapshot($payroll_id);
        if (!is_array($snapshot) || empty($snapshot)) {
            return;
        }

        $totals = is_array($snapshot['totals'] ?? null) ? $snapshot['totals'] : [];
        $rates = is_array($snapshot['rates'] ?? null) ? $snapshot['rates'] : [];

        foreach (['regular_hours', 'ot_hours', 'gross_salary', 'nib_ee', 'nib_er', 'nhip_ee', 'nhip_er', 'net_salary'] as $field_name) {
            if (array_key_exists($field_name, $totals) && is_numeric($totals[$field_name])) {
                $row[$field_name] = round((float) $totals[$field_name], 2);
            }
        }

        if (array_key_exists('hourly_rate', $rates) && is_numeric($rates['hourly_rate'])) {
            $row['hourly_rate'] = round((float) $rates['hourly_rate'], 2);
        }

        if (isset($snapshot['payment_method']) && trim((string) $snapshot['payment_method']) !== '') {
            $row['payment_method'] = trim((string) $snapshot['payment_method']);
        }

        if (isset($snapshot['status']) && trim((string) $snapshot['status']) !== '') {
            $row['status'] = strtolower(trim((string) $snapshot['status']));
        }
    }

    public function get_cutoff_payroll_rows($cutoff_id, $start_date = '', $end_date = '')
    {
        $cutoff_id = (int) $cutoff_id;
        if ($cutoff_id <= 0) {
            return [];
        }

        $table = db_prefix() . 'fs_payroll_master';
        if (!$this->db->table_exists($table)) {
            return [];
        }

        $query = $this->db
            ->from($table)
            ->where('cutoff_id', $cutoff_id);

        if ($start_date !== '') {
            $query->where('start_date >=', $start_date);
        }

        if ($end_date !== '') {
            $query->where('end_date <=', $end_date);
        }

        $rows = $query
            ->order_by('end_date', 'DESC')
            ->order_by('id', 'DESC')
            ->get()
            ->result_array();

        $staff_lookup = [];
        foreach ($rows as $row) {
            $staff_id = (int) ($row['staff_id'] ?? 0);
            if ($staff_id > 0 && !isset($staff_lookup[$staff_id])) {
                $staff_lookup[$staff_id] = $this->get_staff_row($staff_id);
            }
        }

        foreach ($rows as &$row) {
            $staff_id = (int) ($row['staff_id'] ?? 0);
            $staff_row = $staff_id > 0 ? ($staff_lookup[$staff_id] ?? $this->get_staff_row($staff_id)) : [];
            $row['worker_name'] = $this->resolve_staff_name($staff_row, $staff_id);
            $this->apply_saved_snapshot_to_payroll_row($row);
        }
        unset($row);

        return is_array($rows) ? $rows : [];
    }

    public function get_legacy_payroll_rows($start_date = '', $end_date = '')
    {
        $table = db_prefix() . 'fs_payroll_master';
        if (!$this->db->table_exists($table) || !$this->db->field_exists('cutoff_id', $table)) {
            return [];
        }

        $query = $this->db->from($table)->where('cutoff_id IS NULL', null, false);

        if ($start_date !== '') {
            $query->where('start_date >=', $start_date);
        }

        if ($end_date !== '') {
            $query->where('end_date <=', $end_date);
        }

        $rows = $query
            ->order_by('end_date', 'DESC')
            ->order_by('id', 'DESC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $row['worker_name'] = $this->resolve_staff_name($this->get_staff_row((int) ($row['staff_id'] ?? 0)), (int) ($row['staff_id'] ?? 0));
            $this->apply_saved_snapshot_to_payroll_row($row);
            $row['legacy_history'] = true;
        }
        unset($row);

        return $rows;
    }

    public function has_legacy_payroll_for_window($staff_id, $start_date, $end_date)
    {
        $table = db_prefix() . 'fs_payroll_master';
        if (!$this->db->table_exists($table) || !$this->db->field_exists('cutoff_id', $table)) {
            return false;
        }

        $staff_id = (int) $staff_id;
        if ($staff_id <= 0) {
            return false;
        }

        return $this->db
            ->from($table)
            ->where('staff_id', $staff_id)
            ->where('cutoff_id IS NULL', null, false)
            ->where('start_date >=', $start_date)
            ->where('end_date <=', $end_date)
            ->limit(1)
            ->count_all_results() > 0;
    }

    public function count_payroll_records_for_cutoff($cutoff_id)
    {
        $cutoff_id = (int) $cutoff_id;
        if ($cutoff_id <= 0) {
            return 0;
        }

        $table = db_prefix() . 'fs_payroll_master';
        if (!$this->db->table_exists($table) || !$this->db->field_exists('cutoff_id', $table)) {
            return 0;
        }

        return (int) $this->db
            ->from($table)
            ->where('cutoff_id', $cutoff_id)
            ->count_all_results();
    }

    public function generate_cutoff_code($group, $start_date, $end_date)
    {
        $group = preg_replace('/[^A-Za-z0-9-]/', '-', trim((string) $group));
        $group = trim($group, '-');
        if ($group === '') {
            $group = 'DEFAULT';
        }

        $suffix = date('Ymd', strtotime($start_date)) . '-' . date('Ymd', strtotime($end_date));
        $unique = strtoupper(substr($group, 0, 16));

        return $unique . '-' . $suffix;
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
    public function get_payroll_profile($staff_id, $as_of_date = '')
    {
        $staff_id = (int) $staff_id;
        if ($staff_id <= 0) {
            return [];
        }

        $staff_row = $this->get_staff_row($staff_id);
        $profile_table = db_prefix() . 'fs_payroll_profiles';

        $profile = [];
        if ($this->db->table_exists($profile_table)) {
            $profile = $this->db
                ->from($profile_table)
                ->where('staff_id', $staff_id)
                ->limit(1)
                ->get()
                ->row_array();
            $profile = is_array($profile) ? $profile : [];
        }

        $effective_date = trim((string) $as_of_date);
        if ($effective_date === '') {
            $effective_date = date('Y-m-d');
        }

        $effective_profile = $this->merge_employee_policy_snapshot($profile, $staff_id, $effective_date);
        $normalized = $this->normalize_profile_row($effective_profile, $staff_row);

        return $normalized;
    }

    private function merge_employee_policy_snapshot(array $profile_row, $staff_id, $as_of_date = '')
    {
        $staff_id = (int) $staff_id;
        $merged = is_array($profile_row) ? $profile_row : [];
        if ($staff_id <= 0) {
            return $merged;
        }

        $effective_date = trim((string) $as_of_date);
        if ($effective_date === '') {
            $effective_date = date('Y-m-d');
        }

        $snapshot = $this->get_employee_policy_snapshot($staff_id, $effective_date);
        if (empty($snapshot)) {
            return $merged;
        }

        foreach ($snapshot as $field_name => $entry) {
            $value = $entry['value'] ?? '';
            if (!is_scalar($value) && !is_null($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            }

            $field_name = trim((string) $field_name);
            if ($field_name === '') {
                continue;
            }

            switch ($field_name) {
                case 'position':
                    $normalized = strtolower(trim((string) $value));
                    if (in_array($normalized, ['owner', 'executive', 'manager', 'supervisor', 'worker'], true)) {
                        $merged['position'] = $normalized;
                    }
                    break;
                case 'employment_arrangement':
                    $normalized = strtolower(trim((string) $value));
                    if (in_array($normalized, ['permanent', 'fixed_term', 'temporary', 'casual'], true)) {
                        $merged['employment_arrangement'] = $normalized;
                    }
                    break;
                case 'payment_basis':
                    $normalized = strtolower(trim((string) $value));
                    if (in_array($normalized, ['salary', 'hourly', 'daily', 'per_job', 'per_unit'], true)) {
                        $merged['payment_basis'] = $normalized;
                    }
                    break;
                case 'attendance_requirement':
                    $normalized = strtolower(trim((string) $value));
                    if (in_array($normalized, ['required', 'optional', 'not_required'], true)) {
                        $merged['attendance_requirement'] = $normalized;
                    }
                    break;
                case 'payroll_group':
                    $normalized = trim((string) $value);
                    if ($normalized !== '') {
                        $merged['payroll_group'] = $normalized;
                    }
                    break;
                case 'department_id':
                    if (is_numeric($value)) {
                        $merged['department_id'] = (int) $value;
                    }
                    break;
                case 'default_shift_id':
                    if (is_numeric($value)) {
                        $merged['default_shift_id'] = (int) $value;
                    }
                    break;
                case 'base_hourly_rate':
                    if (is_numeric($value)) {
                        $merged['base_hourly_rate'] = (float) $value;
                    }
                    break;
                case 'hourly_rate':
                    if (is_numeric($value)) {
                        $merged['hourly_rate'] = (float) $value;
                    }
                    break;
                case 'overtime_multiplier':
                    if (is_numeric($value)) {
                        $merged['overtime_multiplier'] = (float) $value;
                    }
                    break;
                case 'daily_field_allowance':
                    if (is_numeric($value)) {
                        $merged['daily_field_allowance'] = (float) $value;
                    }
                    break;
                case 'payment_method':
                    $normalized = strtolower(trim((string) $value));
                    if (in_array($normalized, ['cash', 'online_transfer', 'check'], true)) {
                        $merged['payment_method'] = $normalized;
                    }
                    break;
                case 'employee_nib_rate':
                    if (is_numeric($value)) {
                        $merged['employee_nib_rate'] = (float) $value;
                    }
                    break;
                case 'employer_nib_rate':
                    if (is_numeric($value)) {
                        $merged['employer_nib_rate'] = (float) $value;
                    }
                    break;
                case 'employee_nhip_rate':
                    if (is_numeric($value)) {
                        $merged['employee_nhip_rate'] = (float) $value;
                    }
                    break;
                case 'employer_nhip_rate':
                    if (is_numeric($value)) {
                        $merged['employer_nhip_rate'] = (float) $value;
                    }
                    break;
            }
        }

        return $merged;
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
            'staff_id'                 => $staff_id,
            'payroll_group'            => $normalized['payroll_group'],
            'position'                 => $normalized['position'],
            'employment_arrangement'   => $normalized['employment_arrangement'],
            'payment_basis'            => $normalized['payment_basis'],
            'attendance_requirement'   => $normalized['attendance_requirement'],
            'department_id'            => isset($normalized['department_id']) ? (int) $normalized['department_id'] : 0,
            'default_shift_id'         => isset($normalized['default_shift_id']) ? (int) $normalized['default_shift_id'] : 0,
            'base_hourly_rate'         => round((float) $normalized['base_hourly_rate'], 2),
            'overtime_multiplier'      => round((float) $normalized['overtime_multiplier'], 2),
            'daily_field_allowance'    => round((float) $normalized['daily_field_allowance'], 2),
            'employee_nib_rate'        => round((float) $normalized['employee_nib_rate'], 2),
            'employer_nib_rate'        => round((float) $normalized['employer_nib_rate'], 2),
            'employee_nhip_rate'       => round((float) $normalized['employee_nhip_rate'], 2),
            'vacation_pay'             => round((float) $normalized['vacation_pay'], 2),
            'outstanding_loan'         => round((float) $normalized['outstanding_loan'], 2),
            'loan_repayment'           => round((float) $normalized['loan_repayment'], 2),
            'payment_method'           => $normalized['payment_method'],
            'bank_account_info'        => $normalized['bank_account_info'],
            'updated_at'               => $timestamp,
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
    public function generate_payrun_statement($start_date, $end_date, $staff_id = 0, $department_id = 0, $cutoff = null)
    {
        $cutoff_id = 0;
        $cutoff_code = '';
        $payroll_group = '';

        if (is_array($cutoff)) {
            $cutoff_id = (int) ($cutoff['id'] ?? 0);
            $cutoff_code = trim((string) ($cutoff['cutoff_code'] ?? ''));
            $payroll_group = trim((string) ($cutoff['payroll_group'] ?? ''));
        }

        $analytics = $this->get_attendance_analytics($start_date, $end_date, $staff_id, $department_id);
        $holidays = $this->get_holidays();
        $holiday_dates = array_column($holidays, 'date');

        // Pre-fetch all attendance records once for efficiency
        $all_attendance_records = $this->get_attendance_record_report($start_date, $end_date, $staff_id, $department_id);
        $attendance_by_staff = [];
        foreach ($all_attendance_records as $record) {
            $record_staff_id = (int) ($record['staff_id'] ?? 0);
            if ($record_staff_id > 0) {
                if (!isset($attendance_by_staff[$record_staff_id])) {
                    $attendance_by_staff[$record_staff_id] = [];
                }
                $attendance_by_staff[$record_staff_id][] = $record;
            }
        }

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

            // Calculate holiday hours from pre-fetched attendance records
            if (!empty($holiday_dates) && isset($attendance_by_staff[$staff_id_current])) {
                foreach ($attendance_by_staff[$staff_id_current] as $record) {
                    $record_date = isset($record['date']) ? (string) $record['date'] : '';
                    if ($record_date && in_array($record_date, $holiday_dates, true)) {
                        $holiday_hours += (float) ($record['total_hours'] ?? 0);
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
            'cutoff_id' => $cutoff_id,
            'cutoff_code' => $cutoff_code,
            'payroll_group' => $payroll_group,
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
    public function calculate_payroll_for_staff($staff_id, $start_date, $end_date, array $context = [])
    {
        $staff_id = (int) $staff_id;
        $start_date = trim((string) $start_date);
        $end_date = trim((string) $end_date);

        $policy_reference_date = trim((string) ($context['policy_reference_date'] ?? $start_date));
        if ($policy_reference_date === '') {
            $policy_reference_date = date('Y-m-d');
        }

        $profile = $this->get_payroll_profile($staff_id, $policy_reference_date);
        $payment_basis = strtolower(trim((string) ($context['payment_basis'] ?? $profile['payment_basis'] ?? 'hourly')));
        if (!in_array($payment_basis, ['salary', 'hourly', 'daily', 'per_job', 'per_unit'], true)) {
            $payment_basis = 'hourly';
        }

        $hourly_rate = isset($context['hourly_rate']) && is_numeric($context['hourly_rate'])
            ? (float) $context['hourly_rate']
            : (float) ($profile['base_hourly_rate'] ?? 0);
        if ($hourly_rate < 0) {
            $hourly_rate = 0.0;
        }

        $attendance_table = db_prefix() . 'fs_attendance';
        $attendance_rows = [];
        if ($this->db && method_exists($this->db, 'select') && method_exists($this->db, 'from') && method_exists($this->db, 'where')) {
            $this->db->select('*');
            $this->db->from($attendance_table);
            $this->db->where('staff_id', $staff_id);
            if ($start_date !== '') {
                $this->db->where('date >=', $start_date);
            }
            if ($end_date !== '') {
                $this->db->where('date <=', $end_date);
            }
            $attendance_rows = $this->db->get()->result_array();
        }

        $attendance_rows = is_array($attendance_rows) ? $attendance_rows : [];
        $regular_hours = 0.0;
        $ot_hours = 0.0;
        $gross_salary = 0.0;
        $has_work_rows = false;

        if (!empty($attendance_rows)) {
            foreach ($attendance_rows as $attendance) {
                if (!is_array($attendance)) {
                    continue;
                }

                $work_date = trim((string) ($attendance['date'] ?? $start_date));
                $day_profile = $this->get_payroll_profile($staff_id, $work_date !== '' ? $work_date : $policy_reference_date);
                $effective_base_rate = isset($context['hourly_rate']) && is_numeric($context['hourly_rate'])
                    ? (float) $context['hourly_rate']
                    : (float) ($day_profile['base_hourly_rate'] ?? $profile['base_hourly_rate'] ?? $hourly_rate);
                if ($effective_base_rate < 0) {
                    $effective_base_rate = 0.0;
                }

                $effective_ot_multiplier = isset($day_profile['overtime_multiplier']) && is_numeric($day_profile['overtime_multiplier'])
                    ? (float) $day_profile['overtime_multiplier']
                    : (float) ($context['overtime_multiplier'] ?? 1.5);
                if ($effective_ot_multiplier < 1) {
                    $effective_ot_multiplier = 1.0;
                }

                $clock_in = isset($attendance['clock_in']) ? trim((string) $attendance['clock_in']) : '';
                $clock_out = isset($attendance['clock_out']) ? trim((string) $attendance['clock_out']) : '';
                $seconds = 0.0;
                if ($clock_in !== '' && $clock_out !== '' && $clock_out > $clock_in) {
                    $seconds = max((strtotime($clock_out) - strtotime($clock_in)), 0);
                }

                $hours = $seconds / 3600;
                if ($hours <= 0) {
                    continue;
                }

                $has_work_rows = true;
                $regular_hours += min($hours, (float) $this->get_regular_hours_cap());
                $ot_hours += max($hours - (float) $this->get_regular_hours_cap(), 0.0);
                $gross_salary += min($hours, (float) $this->get_regular_hours_cap()) * $effective_base_rate;
                $gross_salary += max($hours - (float) $this->get_regular_hours_cap(), 0.0) * $effective_base_rate * $effective_ot_multiplier;
            }
        }

        if (!$has_work_rows) {
            $total_hours = 0.0;
            $regular_hours_cap = $this->get_regular_hours_cap();
            if ($this->db && method_exists($this->db, 'select') && method_exists($this->db, 'from') && method_exists($this->db, 'where')) {
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
                $result = $this->db->get()->row();
                $total_seconds = $result ? (float) $result->total_seconds : 0.0;
                $total_hours = $total_seconds / 3600;
            }

            $regular_hours = min($total_hours, $regular_hours_cap);
            $ot_hours = max($total_hours - $regular_hours_cap, 0.0);
            $gross_salary = ($regular_hours * $hourly_rate) + ($ot_hours * $hourly_rate * 1.5);
        }

        if ($payment_basis === 'salary') {
            $gross_salary = max($gross_salary, (float) ($context['salary_amount'] ?? $gross_salary));
        } elseif ($payment_basis === 'daily') {
            $work_days = (float) ($context['work_days'] ?? max($regular_hours / 8.0, 0.0));
            $gross_salary = max($gross_salary, $work_days * max($hourly_rate * 8.0, 0.0));
        } elseif ($payment_basis === 'per_job') {
            $job_units = isset($context['job_units']) && is_numeric($context['job_units']) ? (float) $context['job_units'] : 0.0;
            $job_rate = isset($context['job_rate']) && is_numeric($context['job_rate']) ? (float) $context['job_rate'] : $hourly_rate;
            $gross_salary = $job_units * max($job_rate, 0.0);
        } elseif ($payment_basis === 'per_unit') {
            $unit_count = isset($context['unit_count']) && is_numeric($context['unit_count']) ? (float) $context['unit_count'] : 0.0;
            $unit_rate = isset($context['unit_rate']) && is_numeric($context['unit_rate']) ? (float) $context['unit_rate'] : $hourly_rate;
            $gross_salary = $unit_count * max($unit_rate, 0.0);
        }

        $nib_ee = $gross_salary * $this->get_nib_employee_rate();
        $nib_er = $gross_salary * $this->get_nib_employer_rate();
        $nhip_base_gross = min($gross_salary, $this->get_nhip_ceiling());
        $nhip_ee = $nhip_base_gross * $this->get_nhip_employee_rate();
        $nhip_er = $nhip_base_gross * $this->get_nhip_employer_rate();
        $net_salary = $gross_salary - ($nib_ee + $nhip_ee);

        return [
            'payment_basis' => $payment_basis,
            'regular_hours' => round($regular_hours, 2),
            'ot_hours' => round($ot_hours, 2),
            'gross_salary' => round($gross_salary, 2),
            'nhip_base_gross' => round($nhip_base_gross, 2),
            'nib_ee' => round($nib_ee, 2),
            'nib_er' => round($nib_er, 2),
            'nhip_ee' => round($nhip_ee, 2),
            'nhip_er' => round($nhip_er, 2),
            'net_salary' => round($net_salary, 2),
        ];
    }

    public function calculate_weekly_payroll($staff_id, $start_date, $end_date, $hourly_rate)
    {
        $staff_id = (int) $staff_id;
        $profile = $this->get_payroll_profile($staff_id, trim((string) $start_date));
        return $this->calculate_payroll_for_staff($staff_id, $start_date, $end_date, [
            'payment_basis' => $profile['payment_basis'] ?? 'hourly',
            'policy_reference_date' => trim((string) $start_date),
            'hourly_rate' => (float) $hourly_rate,
        ]);
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
        $value          = round((float) $value, 2);

        if ($payroll_id <= 0 || $attribute_code === '') {
            return false;
        }

        $attributes_table = db_prefix() . 'fs_payroll_attributes';
        $values_table     = db_prefix() . 'fs_payroll_values';

        $attribute = $this->db
            ->select('id')
            ->from($attributes_table)
            ->where('code', $attribute_code)
            ->limit(1)
            ->get()
            ->row_array();

        if (!is_array($attribute) || !isset($attribute['id'])) {
            return false;
        }

        $attribute_id = (int) $attribute['id'];
        $existing_row = $this->db
            ->select('id')
            ->from($values_table)
            ->where('payroll_id', $payroll_id)
            ->where('attribute_id', $attribute_id)
            ->limit(1)
            ->get()
            ->row_array();

        if (is_array($existing_row) && isset($existing_row['id'])) {
            $this->db->where('id', (int) $existing_row['id']);
            return (bool) $this->db->update($values_table, ['value' => $value]);
        }

        try {
            return (bool) $this->db->insert($values_table, [
                'payroll_id'   => $payroll_id,
                'attribute_id' => $attribute_id,
                'value'        => $value,
            ]);
        } catch (Throwable $e) {
            $message = strtolower((string) $e->getMessage());
            $is_duplicate = strpos($message, 'duplicate') !== false || strpos($message, 'already exists') !== false;
            if (!$is_duplicate) {
                return false;
            }

            $existing_row = $this->db
                ->select('id')
                ->from($values_table)
                ->where('payroll_id', $payroll_id)
                ->where('attribute_id', $attribute_id)
                ->limit(1)
                ->get()
                ->row_array();

            if (is_array($existing_row) && isset($existing_row['id'])) {
                $this->db->where('id', (int) $existing_row['id']);
                return (bool) $this->db->update($values_table, ['value' => $value]);
            }

            return false;
        }
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

        $payroll_group = trim((string) ($profile_row['payroll_group'] ?? $staff_row['payroll_group'] ?? 'default'));
        if ($payroll_group === '') {
            $payroll_group = 'default';
        }

        $allowed_positions = ['owner', 'executive', 'manager', 'supervisor', 'worker'];
        $allowed_arrangements = ['permanent', 'fixed_term', 'temporary', 'casual'];
        $allowed_payment_bases = ['salary', 'hourly', 'daily', 'per_job', 'per_unit'];
        $allowed_attendance_requirements = ['required', 'optional', 'not_required'];

        $position = strtolower(trim((string) ($profile_row['position'] ?? $staff_row['position'] ?? 'worker')));
        if (!in_array($position, $allowed_positions, true)) {
            $position = 'worker';
        }

        $employment_arrangement = strtolower(trim((string) ($profile_row['employment_arrangement'] ?? $staff_row['employment_arrangement'] ?? 'permanent')));
        if (!in_array($employment_arrangement, $allowed_arrangements, true)) {
            $employment_arrangement = 'permanent';
        }

        $payment_basis = strtolower(trim((string) ($profile_row['payment_basis'] ?? $staff_row['payment_basis'] ?? 'hourly')));
        if (!in_array($payment_basis, $allowed_payment_bases, true)) {
            $payment_basis = 'hourly';
        }

        $attendance_requirement = strtolower(trim((string) ($profile_row['attendance_requirement'] ?? $staff_row['attendance_requirement'] ?? 'required')));
        if (!in_array($attendance_requirement, $allowed_attendance_requirements, true)) {
            $attendance_requirement = 'required';
        }

        return [
            'base_hourly_rate' => round(max($default_rate, 0), 2),
            'payroll_group' => $payroll_group,
            'position' => $position,
            'employment_arrangement' => $employment_arrangement,
            'payment_basis' => $payment_basis,
            'attendance_requirement' => $attendance_requirement,
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
     * Parse a database datetime string into DateTimeImmutable without throwing on malformed values.
     *
     * @param string|null $value
     *
     * @return DateTimeImmutable|null
     */
    private function resolve_business_timezone()
    {
        $timezone = 'UTC';
        if (defined('FS_BUSINESS_TIMEZONE') && trim((string) FS_BUSINESS_TIMEZONE) !== '') {
            $timezone = trim((string) FS_BUSINESS_TIMEZONE);
        } elseif (function_exists('date_default_timezone_get') && trim((string) date_default_timezone_get()) !== '') {
            $timezone = trim((string) date_default_timezone_get());
        }

        try {
            return new DateTimeZone($timezone);
        } catch (Throwable $e) {
            return new DateTimeZone('UTC');
        }
    }

    private function parse_datetime_or_null($value)
    {
        if (is_array($value) || is_object($value) && !method_exists($value, '__toString')) {
            return null;
        }

        $value = trim((string) $value);
        $normalized = strtolower($value);
        if ($value === '' || in_array($normalized, ['null', 'none', 'n/a', 'na', '0000-00-00', '0000-00-00 00:00:00', '0000-00-00 00:00:00.000000'], true)) {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value, $this->resolve_business_timezone());
            return $date->getTimestamp() > 0 ? $date : null;
        } catch (Throwable $e) {
            return null;
        }
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

        $clock_in_dt = $this->parse_datetime_or_null($clock_in);
        $clock_out_dt = $this->parse_datetime_or_null($clock_out);
        if ($clock_in_dt === null || $clock_out_dt === null || $clock_out_dt <= $clock_in_dt) {
            return 0.0;
        }

        return (float) ($clock_out_dt->getTimestamp() - $clock_in_dt->getTimestamp());
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
        foreach ($holidays as &$holiday) {
            if ($holiday['date'] === $holiday_date) {
                $exists = true;
                $holiday['name'] = $holiday_name;
                break;
            }
        }
        unset($holiday);

        if (!$exists) {
            $holidays[] = ['date' => $holiday_date, 'name' => $holiday_name];
        }

        usort($holidays, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return (bool) $this->save_module_setting('field_staff_holidays', json_encode($holidays));
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

        return (bool) $this->save_module_setting('field_staff_holidays', json_encode(array_values($updated)));
    }

    public function get_holidays()
    {
        $json_holidays = $this->get_module_setting('field_staff_holidays', '[]');
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
