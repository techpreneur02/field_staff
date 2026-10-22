<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
if (!function_exists('fs_portal_escape')) {
    function fs_portal_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$title_text   = isset($title) ? fs_portal_escape($title) : 'Employee Workforce Portal';
$attendance_records = isset($attendance_records) && is_array($attendance_records) ? $attendance_records : [];
$leave_rows   = isset($leave_rows) && is_array($leave_rows) ? $leave_rows : [];
$payslip_rows = isset($payslip_rows) && is_array($payslip_rows) ? $payslip_rows : [];
$start_date   = isset($start_date) ? fs_portal_escape($start_date) : date('Y-m-d', strtotime('-14 days'));
$end_date     = isset($end_date) ? fs_portal_escape($end_date) : date('Y-m-d');
$is_clocked_in = isset($is_clocked_in) ? (bool) $is_clocked_in : false;
$save_leave_request_url    = isset($save_leave_request_url) ? (string) $save_leave_request_url : field_staff_admin_url('field_staff/save_leave_request');
$get_payslip_statement_url = isset($get_payslip_statement_url) ? (string) $get_payslip_statement_url : field_staff_admin_url('field_staff/get_employee_payslip_statement');
$download_payslip_url = isset($download_payslip_url) ? (string) $download_payslip_url : field_staff_admin_url('field_staff/download_payslip_statement');
$clock_action_url = isset($clock_action_url) ? (string) $clock_action_url : field_staff_admin_url('field_staff/clock_action');
$team_monitor_url = field_staff_admin_url('field_staff/get_team_monitor');
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<style>
    .fs-portal-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%);
        color: #fff;
        border-radius: 18px;
        padding: 24px;
        margin-bottom: 18px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
    }

    .fs-portal-hero p {
        color: rgba(255, 255, 255, 0.82);
    }

    .fs-portal-badge {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .fs-portal-badge.on {
        background: #16a34a;
        color: #fff;
    }

    .fs-portal-badge.off {
        background: #475569;
        color: #fff;
    }

    .fs-portal-panel {
        border-radius: 16px;
        overflow: hidden;
    }

    .fs-portal-tabs {
        display: flex;
        gap: 8px;
        border-bottom: 0;
        margin-bottom: 4px;
    }

    .fs-portal-tabs>li {
        float: none;
        margin-bottom: 0;
    }

    .fs-portal-tabs>li>a {
        border-radius: 999px;
        border: 1px solid #dbe3ee;
        background: #f8fafc;
        color: #334155;
        padding: 10px 14px;
        font-weight: 600;
        margin-right: 0;
        line-height: 1.2;
        transition: all 0.18s ease;
    }

    .fs-portal-tabs>li.active>a,
    .fs-portal-tabs>li.active>a:focus,
    .fs-portal-tabs>li.active>a:hover {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
    }

    .fs-portal-tabs>li>a:focus,
    .fs-portal-tabs>li>a:hover {
        background: #eef2ff;
        border-color: #c7d2fe;
    }

    .fs-portal-subtle {
        color: #64748b;
    }

    .fs-table-wrap {
        overflow-x: auto;
    }

    .fs-statement-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .fs-statement-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px;
        background: #fff;
    }

    .fs-statement-card h5 {
        margin-top: 0;
    }

    .fs-attendance-map-wrap {
        height: 100%;
        min-height: 320px;
    }

    .fs-attendance-map {
        width: 100%;
        height: 260px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }

    .fs-attendance-meta {
        margin-top: 10px;
        font-size: 12px;
        color: #64748b;
    }

    .tab-content {
        border-top: 1px solid #e2e8f0;
        padding-top: 12px;
    }

    @media (max-width: 767px) {
        .panel_s.fs-portal-panel .panel-body {
            padding: 12px;
        }

        .fs-portal-hero {
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 12px;
        }

        .fs-portal-hero h3 {
            font-size: 20px;
        }

        .fs-portal-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            position: sticky;
            top: 0;
            z-index: 4;
            background: #fff;
            padding: 4px 0 8px;
        }

        .fs-portal-tabs::-webkit-scrollbar {
            display: none;
        }

        .fs-portal-tabs>li {
            flex: 0 0 auto;
            scroll-snap-align: start;
        }

        .fs-portal-tabs>li>a {
            white-space: nowrap;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            padding: 10px 12px;
        }

        .tab-content {
            padding-top: 8px;
        }

        .fs-statement-grid {
            grid-template-columns: 1fr;
        }

        .fs-attendance-map-wrap {
            min-height: 0;
            margin-top: 12px;
        }

        .fs-attendance-map {
            height: 220px;
        }
    }
</style>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">

                <div class="fs-portal-hero">
                    <div class="row">
                        <div class="col-md-8">
                            <h3 class="no-margin"><?php echo $title_text; ?></h3>
                            <p class="mbot0 mtop5">Private self-service workspace for personal attendance history, leave requests, and issued pay statements.</p>
                        </div>
                        <div class="col-md-4 text-right">
                            <span class="fs-portal-badge off">
                                Checking status...
                            </span>
                        </div>
                    </div>
                </div>

                <div class="panel_s fs-portal-panel">
                    <div class="panel-body">

                        <ul class="nav nav-tabs fs-portal-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#tab-my-daily-logs" aria-controls="tab-my-daily-logs" role="tab" data-toggle="tab">My Daily Logs</a>
                            </li>
                            <li role="presentation">
                                <a href="#tab-request-time-off" aria-controls="tab-request-time-off" role="tab" data-toggle="tab">Request Time Off</a>
                            </li>
                            <li role="presentation">
                                <a href="#tab-my-payslips" aria-controls="tab-my-payslips" role="tab" data-toggle="tab">My Payslips</a>
                            </li>
                        </ul>

                        <div class="tab-content mtop20">

                            <!-- Tab A: My Daily Logs -->
                            <div role="tabpanel" class="tab-pane active" id="tab-my-daily-logs">

                                <!-- Clock in / Clock out panel -->
                                <div class="row mbot20">
                                    <div class="col-md-5">
                                        <div class="panel panel-default">
                                            <div class="panel-heading clearfix">
                                                <strong class="pull-left">My Work - Record Attendance</strong>
                                                <span id="clock-status-badge" class="badge pull-right bg-secondary">Checking status...</span>
                                            </div>
                                            <div class="panel-body">
                                                <div id="employee-status-strip" class="mbot10">
                                                    <span id="employee-status-badge" class="label label-default">Status not yet confirmed</span>
                                                    <small id="employee-status-detail" class="text-muted">Waiting for live status.</small>
                                                    <div>Clock In: <span id="employee-clock-in">--</span></div>
                                                    <div>Break started: <span id="employee-break-start">--</span></div>
                                                    <div>Break: <span id="employee-break-type">--</span> | Elapsed: <span id="employee-break-elapsed">--</span></div>
                                                </div>
                                                <?php if (isset($this->security) && method_exists($this->security, 'get_csrf_token_name') && method_exists($this->security, 'get_csrf_hash')) { ?>
                                                    <input type="hidden" id="portal-csrf-name" value="<?php echo fs_portal_escape($this->security->get_csrf_token_name()); ?>">
                                                    <input type="hidden" id="portal-csrf-hash" value="<?php echo fs_portal_escape($this->security->get_csrf_hash()); ?>">
                                                <?php } ?>
                                                <form id="attendance-form" autocomplete="off">
                                                    <div class="form-group">
                                                        <label for="attendance_notes" class="control-label">Field Notes (Optional)</label>
                                                        <textarea id="attendance_notes" name="notes" class="form-control" rows="3" placeholder="Add attendance or location context"></textarea>
                                                    </div>
                                                    <p id="gps-preview" class="text-muted mbot15">GPS: waiting for capture</p>
                                                    <div class="btn-group btn-group-justified" role="group">
                                                        <div class="btn-group" role="group">
                                                            <button type="button" id="clock-in-btn" class="btn btn-success" disabled>Clock In</button>
                                                        </div>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" id="clock-out-btn" class="btn btn-warning" disabled>Clock Out</button>
                                                        </div>
                                                    </div>
                                                    <div class="mtop15">
                                                        <label for="break-type-select" class="control-label">Break Type</label>
                                                        <select id="break-type-select" class="form-control">
                                                            <option value="paid">Paid break</option>
                                                            <option value="unpaid">Unpaid break</option>
                                                        </select>
                                                    </div>
                                                    <div class="btn-group btn-group-justified mtop15" role="group">
                                                        <div class="btn-group" role="group">
                                                            <button type="button" id="break-out-btn" class="btn btn-info" disabled>Break Out</button>
                                                        </div>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" id="break-in-btn" class="btn btn-default" disabled>Break In</button>
                                                        </div>
                                                    </div>
                                                    <div class="mtop10">
                                                        <button type="button" id="reconcile-attendance-btn" class="btn btn-link btn-xs hidden">Refresh latest status</button>
                                                        <div id="attendance-state-message" class="text-muted small hidden"></div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="panel panel-default fs-attendance-map-wrap">
                                            <div class="panel-heading clearfix">
                                                <strong class="pull-left">Attendance Map</strong>
                                                <button type="button" id="show-map-btn" class="btn btn-default btn-xs pull-right">Hide Map</button>
                                            </div>
                                            <div class="panel-body" id="attendance-map-panel">
                                                <div id="attendance-live-map" class="fs-attendance-map"></div>
                                                <p id="map-status" class="fs-attendance-meta mbot0">Map is tracking your latest GPS position and attendance event.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($can_view_team_monitor)) { ?>
                                <div class="row mtop20" id="live-team-operations">
                                    <div class="col-md-12">
                                        <div class="panel panel-default">
                                            <div class="panel-heading clearfix">
                                                <strong class="pull-left">Team Operations - Live Employee Status</strong>
                                                <button type="button" id="refresh-team-monitor-btn" class="btn btn-default btn-xs pull-right">Refresh</button>
                                            </div>
                                            <div class="panel-body">
                                                <p id="team-monitor-error" class="text-warning" role="alert"></p>
                                                <div id="team-monitor-list" class="fs-statement-grid"></div>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="well well-sm">
                                            <div class="row">
                                                <div class="col-sm-3 col-xs-12">
                                                    <label for="my-logs-start-date" class="control-label">Start Date</label>
                                                    <input type="date" id="my-logs-start-date" class="form-control" value="<?php echo $start_date; ?>">
                                                </div>
                                                <div class="col-sm-3 col-xs-12">
                                                    <label for="my-logs-end-date" class="control-label">End Date</label>
                                                    <input type="date" id="my-logs-end-date" class="form-control" value="<?php echo $end_date; ?>">
                                                </div>
                                                <div class="col-sm-3 col-xs-12">
                                                    <label class="control-label">&nbsp;</label>
                                                    <button type="button" id="js-my-logs-filter" class="btn btn-primary btn-block">Run History Report</button>
                                                </div>
                                                <div class="col-sm-3 col-xs-12">
                                                    <label class="control-label">&nbsp;</label>
                                                    <button type="button" id="js-my-logs-reset" class="btn btn-default btn-block">Reset Range</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs-table-wrap">
                                    <table class="table table-striped table-bordered" id="my-attendance-history">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Clock In</th>
                                                <th>Clock Out</th>
                                                <th>Breaks / Payable Time</th>
                                                <th>Field Notes</th>
                                                <th>GPS Tracking</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($attendance_records)) { ?>
                                                <?php foreach ($attendance_records as $row) { ?>
                                                    <?php
                                                    $in_lat  = (string) ($row['in_latitude']  ?? '');
                                                    $in_lng  = (string) ($row['in_longitude'] ?? '');
                                                    $out_lat = (string) ($row['out_latitude'] ?? '');
                                                    $out_lng = (string) ($row['out_longitude'] ?? '');
                                                    $in_coords  = ($in_lat  !== '' && $in_lng  !== '') ? trim($in_lat  . ', ' . $in_lng)  : '';
                                                    $out_coords = ($out_lat !== '' && $out_lng !== '') ? trim($out_lat . ', ' . $out_lng) : '';
                                                    ?>
                                                    <tr>
                                                        <td><?php echo fs_portal_escape($row['date'] ?? ''); ?></td>
                                                        <td>
                                                            <?php echo fs_portal_escape($row['clock_in'] ?? ''); ?>
                                                            <?php if (($row['summary']['effective_clock_in'] ?? '') !== ($row['clock_in'] ?? '')) { ?>
                                                                <div>Approved correction: <?php echo fs_portal_escape($row['summary']['effective_clock_in']); ?></div>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <?php echo fs_portal_escape($row['clock_out'] ?? ''); ?>
                                                            <?php if (($row['summary']['effective_clock_out'] ?? '') !== ($row['clock_out'] ?? '')) { ?>
                                                                <div>Approved correction: <?php echo fs_portal_escape($row['summary']['effective_clock_out']); ?></div>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <?php $summary = $row['summary'] ?? []; ?>
                                                            <?php foreach (($summary['break_rows'] ?? []) as $break_row) { ?>
                                                                <div><?php echo fs_portal_escape(($break_row['break_type'] ?? '') . ': ' . ($break_row['break_out_at'] ?? '') . ' to ' . ($break_row['break_in_at'] ?? 'Break In missing')); ?></div>
                                                            <?php } ?>
                                                            <div>Paid: <?php echo fs_portal_escape($summary['paid_break_seconds'] ?? 0); ?> s; Unpaid: <?php echo fs_portal_escape($summary['unpaid_break_seconds'] ?? 0); ?> s</div>
                                                            <div>Payable: <?php echo !empty($summary['has_unresolved_breaks']) || empty($summary['effective_clock_out']) ? 'Pending correction / completion' : fs_portal_escape(round(($summary['payable_seconds'] ?? 0) / 3600, 6)) . ' h'; ?></div>
                                                        </td>
                                                        <td><?php echo fs_portal_escape($row['notes'] ?? ''); ?></td>
                                                        <td>
                                                            <div><strong>In:</strong> <?php echo fs_portal_escape($in_coords !== '' ? $in_coords : 'Unavailable'); ?></div>
                                                            <div class="mtop5"><strong>Out:</strong> <?php echo fs_portal_escape($out_coords !== '' ? $out_coords : 'Unavailable'); ?></div>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">No personal attendance records were found for the selected period.</td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Tab B: Request Time Off -->
                            <div role="tabpanel" class="tab-pane" id="tab-request-time-off">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="panel panel-default">
                                            <div class="panel-heading"><strong>Submit Leave Request</strong></div>
                                            <div class="panel-body">
                                                <form id="leave-request-form" autocomplete="off">
                                                    <div class="form-group">
                                                        <label for="leave_type" class="control-label">Leave Type</label>
                                                        <select id="leave_type" class="form-control">
                                                            <option value="Vacation">Vacation</option>
                                                            <option value="Sick">Sick</option>
                                                            <option value="Maternity">Maternity</option>
                                                            <option value="Unpaid">Unpaid</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="leave_start_date" class="control-label">Start Date</label>
                                                        <input type="date" id="leave_start_date" class="form-control">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="leave_end_date" class="control-label">End Date</label>
                                                        <input type="date" id="leave_end_date" class="form-control">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="leave_reason" class="control-label">Reason</label>
                                                        <textarea id="leave_reason" class="form-control" rows="4" placeholder="Add a short request note"></textarea>
                                                    </div>
                                                    <button type="button" id="js-submit-leave-request" class="btn btn-primary btn-block">Submit Request</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="panel panel-default">
                                            <div class="panel-heading"><strong>Recent Leave Requests</strong></div>
                                            <div class="panel-body">
                                                <div class="fs-table-wrap">
                                                    <table class="table table-striped table-bordered mbot0">
                                                        <thead>
                                                            <tr>
                                                                <th>Type</th>
                                                                <th>Dates</th>
                                                                <th>Status</th>
                                                                <th>Reason</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($leave_rows)) { ?>
                                                                <?php foreach ($leave_rows as $leave_row) { ?>
                                                                    <tr>
                                                                        <td><?php echo fs_portal_escape($leave_row['leave_type'] ?? ''); ?></td>
                                                                        <td><?php echo fs_portal_escape(($leave_row['start_date'] ?? '') . ' to ' . ($leave_row['end_date'] ?? '')); ?></td>
                                                                        <td><?php echo fs_portal_escape($leave_row['status'] ?? ''); ?></td>
                                                                        <td><?php echo fs_portal_escape($leave_row['reason'] ?? ''); ?></td>
                                                                    </tr>
                                                                <?php } ?>
                                                            <?php } else { ?>
                                                                <tr>
                                                                    <td colspan="4" class="text-center text-muted">No leave requests have been submitted yet.</td>
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

                            <!-- Tab C: My Payslips -->
                            <div role="tabpanel" class="tab-pane" id="tab-my-payslips">
                                <div class="panel panel-default">
                                    <div class="panel-heading"><strong>Issued Pay Statements</strong></div>
                                    <div class="panel-body">
                                        <div class="fs-table-wrap">
                                            <table class="table table-hover table-bordered mbot0">
                                                <thead>
                                                    <tr>
                                                        <th>Pay Date</th>
                                                        <th>Period</th>
                                                        <th>Net Salary</th>
                                                        <th>Status</th>
                                                        <th class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($payslip_rows)) { ?>
                                                        <?php foreach ($payslip_rows as $row) { ?>
                                                            <tr>
                                                                <td><?php echo fs_portal_escape($row['created_at'] ?? ($row['end_date'] ?? '')); ?></td>
                                                                <td><?php echo fs_portal_escape(($row['start_date'] ?? '') . ' to ' . ($row['end_date'] ?? '')); ?></td>
                                                                <td>$<?php echo number_format((float) ($row['net_salary'] ?? 0), 2); ?></td>
                                                                <td><?php echo fs_portal_escape($row['status'] ?? 'draft'); ?></td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-primary btn-sm js-view-payslip" data-payroll-id="<?php echo (int) ($row['id'] ?? 0); ?>">View Statement</button>
                                                                    <a class="btn btn-default btn-sm" href="<?php echo fs_portal_escape($download_payslip_url); ?>?payroll_id=<?php echo (int) ($row['id'] ?? 0); ?>" target="_blank" rel="noopener">Download</a>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                    <?php } else { ?>
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted">No issued pay statements are available yet.</td>
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

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payslip-statement-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Compiled Pay Statement</h4>
            </div>
            <div class="modal-body" id="payslip-statement-body">
                <p class="text-muted">Select a pay statement to review the compiled details.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    (function() {
        function bootPortal() {
            var $ = window.jQuery;
            if (!$) {
                return;
            }

            var saveLeaveRequestUrl = <?php echo json_encode($save_leave_request_url); ?>;
            var getPayslipStatementUrl = <?php echo json_encode($get_payslip_statement_url); ?>;
            var clockActionUrl = <?php echo json_encode($clock_action_url); ?>;
            var attendanceStateUrl = <?php echo json_encode($attendance_state_url ?? field_staff_admin_url('field_staff/get_attendance_state')); ?>;
            var TAB_STORAGE_KEY = 'fs_portal_active_tab';
            var map = null;
            var mapMarker = null;
            var mapVisible = true;

            function activeTabHref() {
                return $('.fs-portal-tabs li.active a').attr('href') || '';
            }

            function restoreActiveTab() {
                var saved = '';
                try {
                    saved = window.sessionStorage.getItem(TAB_STORAGE_KEY) || '';
                } catch (e) {
                    saved = '';
                }
                if (!saved) {
                    return;
                }
                var $tab = $('.fs-portal-tabs a[href="' + saved + '"]');
                if ($tab.length) {
                    $tab.tab('show');
                }
            }

            function persistActiveTab(href) {
                try {
                    window.sessionStorage.setItem(TAB_STORAGE_KEY, href || activeTabHref());
                } catch (e) {
                    // Ignore storage availability errors.
                }
            }

            function refreshAttendanceView(delayMs) {
                var wait = typeof delayMs === 'number' ? delayMs : 0;
                window.setTimeout(function() {
                    window.location.reload();
                }, wait);
            }

            function showMessage(msg) {
                if (window.alert) {
                    alert(msg);
                }
            }

            function escapeHtml(v) {
                return String(v || '')
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            // Clock in / Clock out
            function getGeo(cb, errCb) {
                if (!navigator.geolocation) {
                    errCb('Location services are unavailable in this browser.');
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        cb(pos.coords.latitude, pos.coords.longitude);
                    },
                    function() {
                        errCb('Unable to capture GPS location.');
                    }, {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            }

            function appendPortalCsrf(payload) {
                var csrfName = ($('#portal-csrf-name').val() || '').trim();
                var csrfHash = ($('#portal-csrf-hash').val() || '').trim();
                if (!csrfName && window.csrfData) {
                    csrfName = window.csrfData.token_name;
                    csrfHash = window.csrfData.hash;
                }
                if (csrfName && csrfHash) {
                    payload[csrfName] = csrfHash;
                }
                return payload;
            }

            function ensureMap() {
                if (map || typeof window.L === 'undefined') {
                    return;
                }
                map = window.L.map('attendance-live-map', {
                    zoomControl: true
                }).setView([21.75, -72.27], 10);

                window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
            }

            function updateMapPosition(lat, lng, label) {
                ensureMap();
                if (!map) {
                    return;
                }

                var point = [lat, lng];
                if (!mapMarker) {
                    mapMarker = window.L.marker(point).addTo(map);
                } else {
                    mapMarker.setLatLng(point);
                }

                map.setView(point, 16);
                if (label) {
                    mapMarker.bindPopup(label).openPopup();
                    $('#map-status').text(label + ' at ' + lat.toFixed(6) + ', ' + lng.toFixed(6));
                }
            }

            var teamMonitorUrl = <?php echo json_encode($team_monitor_url); ?>;

            function formatElapsed(seconds) {
                seconds = Math.max(0, Math.floor(seconds));
                return Math.floor(seconds / 3600) + ':' + String(Math.floor(seconds % 3600 / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
            }

            function renderTeamMonitor(rows) {
                var $list = $('#team-monitor-list');
                if (!Array.isArray(rows) || !rows.length) {
                    $list.html('<div class="col-md-12 text-muted">No active team members are on shift right now.</div>');
                    return;
                }

                var html = '';
                rows.forEach(function(row) {
                    var status = (row && row.status) || 'working';
                    var isOnBreak = status === 'on_break';
                    var label = status === 'timestamp_issue' ? 'Correction required' : isOnBreak ? 'On break' : status === 'clocked_out' ? 'Clocked out' : 'Working';
                    var elapsedBreak = Number(row && row.elapsed_break_seconds || 0);
                    var elapsed = isOnBreak ? formatElapsed(elapsedBreak + Math.floor((Date.now() - teamSyncTime) / 1000)) : '--';
                    html += '<div class="col-md-4 mtop10">' +
                        '<div class="fs-statement-card">' +
                        '<h5>' + escapeHtml(row && row.name ? row.name : 'Employee') + '</h5>' +
                        '<p class="mbot5"><strong>Status:</strong> ' + escapeHtml(label) + '</p>' +
                        '<p class="mbot5"><strong>Clock In:</strong> ' + escapeHtml(row.clock_in || '--') + '</p>' +
                        '<p class="mbot5"><strong>Break started:</strong> ' + escapeHtml(row.break_started_at || '--') + '</p>' +
                        '<p class="mbot5"><strong>Break:</strong> ' + escapeHtml(row.current_break_type || '--') + '</p>' +
                        '<p class="mbot0"><strong>Elapsed:</strong> ' + escapeHtml(elapsed) + '</p>' +
                        (row.issue ? '<p class="text-warning mbot0">' + escapeHtml(row.issue) + '</p>' : '') +
                        '</div>' +
                        '</div>';
                });

                $list.html(html);
            }

            function refreshTeamMonitor() {
                if (!$('#team-monitor-list').length) { return; }
                $.get(teamMonitorUrl).done(function(response) {
                    if (response && response.success && Array.isArray(response.team)) {
                        teamSnapshot = response.team;
                        teamSyncTime = Date.now();
                        renderTeamMonitor(response.team);
                        $('#team-monitor-error').text('');
                    } else {
                        $('#team-monitor-error').text(response && response.message || 'Team status could not be refreshed.');
                    }
                }).fail(function(xhr) {
                    $('#team-monitor-error').text(xhr.responseJSON && xhr.responseJSON.message || 'Team monitor is temporarily unavailable. Last confirmed statuses are shown.');
                });
            }

            var attendanceBusy = false;
            var confirmedAttendanceState = null;
            var teamSnapshot = [];
            var employeeSyncTime = 0;
            var teamSyncTime = 0;
            var attendanceActionButtons = ['#clock-in-btn', '#clock-out-btn', '#break-out-btn', '#break-in-btn'];

            function disableAttendanceActions() {
                attendanceActionButtons.forEach(function(selector) {
                    var $button = $(selector);
                    if ($button.length) {
                        $button.prop('disabled', true).addClass('disabled');
                    }
                });
            }

            function updateEmployeeStatus(state) {
                var clockedIn = !!(state && state.clocked_in);
                var onBreak = !!(state && state.on_break);
                var statusText = 'Clocked out';
                var detailText = 'No active attendance record.';
                var badgeClass = 'label-default';

                if (clockedIn && onBreak) {
                    statusText = 'On break';
                    detailText = 'Employee is currently on a break.';
                    badgeClass = 'label-info';
                } else if (clockedIn) {
                    statusText = 'Working';
                    detailText = 'Employee is on shift and actively working.';
                    badgeClass = 'label-success';
                }

                $('#employee-status-badge')
                    .removeClass('label-default label-info label-success label-warning')
                    .addClass(badgeClass)
                    .text('Employee status: ' + statusText);
                $('#employee-status-detail').text(detailText);
                if (state && state.issue) {
                    $('#employee-status-badge').removeClass('label-success label-info').addClass('label-warning').text('Correction required');
                    $('#employee-status-detail').text(state.issue);
                }
                $('#employee-clock-in').text(state.clock_in || '--');
                $('#employee-break-start').text(state.break_started_at || '--');
                $('#employee-break-type').text(state.break_type || '--');
                $('#employee-break-elapsed').text(onBreak ? formatElapsed(Number(state.elapsed_break_seconds || 0) + Math.floor((Date.now() - employeeSyncTime) / 1000)) : '--');
                $('.fs-portal-badge, #clock-status-badge').text(state.issue ? 'Correction required' : statusText);
                $('.fs-portal-badge').toggleClass('on', clockedIn && !state.issue).toggleClass('off', !clockedIn || !!state.issue);
                $('#clock-status-badge').toggleClass('bg-success', clockedIn && !state.issue).toggleClass('bg-secondary', !clockedIn || !!state.issue);
            }

            function applyAttendanceActionState(state) {
                var allowed = state && Array.isArray(state.allowed_actions) ? state.allowed_actions : [];
                ['clock_in', 'clock_out', 'break_out', 'break_in'].forEach(function(action) {
                    var disabled = attendanceBusy || allowed.indexOf(action) === -1;
                    $('#' + action.replace('_', '-') + '-btn').prop('disabled', disabled).toggleClass('disabled', disabled);
                });
                $('#break-type-select').prop('disabled', attendanceBusy || !!state.on_break);
                updateEmployeeStatus(state);
            }

            function syncPortalCsrf(responseCsrf) {
                if (!responseCsrf || !responseCsrf.name || !responseCsrf.hash) {
                    return;
                }
                $('#portal-csrf-name').val(responseCsrf.name);
                $('#portal-csrf-hash').val(responseCsrf.hash);
                if (window.csrfData) {
                    window.csrfData.token_name = responseCsrf.name;
                    window.csrfData.hash = responseCsrf.hash;
                }
            }

            function setAttendanceStateMessage(message, isError) {
                var $msg = $('#attendance-state-message');
                if (!message) {
                    $msg.addClass('hidden').text('');
                    return;
                }
                $msg.removeClass('hidden');
                $msg.text(message);
                $msg.removeClass('text-success text-warning text-danger');
                $msg.addClass(isError ? 'text-warning' : 'text-success');
            }

            function refreshAttendanceState() {
                if (attendanceBusy) { return; }
                disableAttendanceActions();
                $('#reconcile-attendance-btn').addClass('hidden');
                setAttendanceStateMessage('Refreshing latest attendance state...', false);
                return $.ajax({
                    url: attendanceStateUrl,
                    method: 'GET',
                    dataType: 'json',
                    timeout: 15000,
                    cache: false
                }).done(function(response) {
                    if (response && response.csrf) {
                        syncPortalCsrf(response.csrf);
                    }
                    if (!response || !response.success || !response.state) {
                        setAttendanceStateMessage(response && response.message || 'Attendance status could not be refreshed.', true);
                        $('#reconcile-attendance-btn').removeClass('hidden');
                        return;
                    }
                    confirmedAttendanceState = response.state;
                    employeeSyncTime = Date.now();
                    applyAttendanceActionState(confirmedAttendanceState);
                    setAttendanceStateMessage('Attendance state refreshed.', false);
                }).fail(function(xhr) {
                    var response = xhr && xhr.responseJSON ? xhr.responseJSON : {};
                    var message = response.message || 'Attendance status could not be refreshed. Reconcile the latest status before continuing.';
                    if (response && response.reference_id) {
                        message += ' Reference ID: ' + response.reference_id;
                    }
                    setAttendanceStateMessage(message, true);
                    $('#reconcile-attendance-btn').removeClass('hidden');
                });
            }

            $('#reconcile-attendance-btn').on('click', function() {
                refreshAttendanceState();
            });

            function postClock(actionKey) {
                var $btn = actionKey === 'clock_in' ? $('#clock-in-btn') : actionKey === 'clock_out' ? $('#clock-out-btn') : actionKey === 'break_out' ? $('#break-out-btn') : $('#break-in-btn');
                if (!($btn && $btn.length)) {
                    return;
                }

                if (attendanceBusy || !confirmedAttendanceState || $btn.prop('disabled')) {
                    return;
                }

                attendanceBusy = true;
                disableAttendanceActions();
                var buttonLabel = $btn.text();
                $btn.text('Processing...');

                var releaseBtn = function(success) {
                    attendanceBusy = false;
                    $btn.text(buttonLabel);
                    if (success) {
                        refreshAttendanceState();
                        refreshTeamMonitor();
                        $.get(window.location.href).done(function(html) {
                            var $body = $('<div>').append($.parseHTML(html));
                            var $rows = $body.find('#my-attendance-history tbody');
                            if ($rows.length) { $('#my-attendance-history tbody').html($rows.html()); }
                        }).fail(function() {
                            setAttendanceStateMessage('Attendance saved. History could not be refreshed; reload to view it.', true);
                        });
                    } else {
                        applyAttendanceActionState(confirmedAttendanceState);
                        $('#reconcile-attendance-btn').removeClass('hidden');
                    }
                };

                var breakType = $('#break-type-select').val() || 'paid';
                getGeo(
                    function(lat, lng) {
                        $('#gps-preview').text('GPS: ' + lat.toFixed(6) + ', ' + lng.toFixed(6));
                        updateMapPosition(lat, lng, actionKey === 'clock_in' ? 'Clock In location captured' : actionKey === 'clock_out' ? 'Clock Out location captured' : actionKey === 'break_out' ? 'Break Out captured' : 'Break In captured');
                        var payload = appendPortalCsrf({
                            action: actionKey,
                            break_type: breakType,
                            latitude: lat,
                            longitude: lng,
                            notes: $('#attendance_notes').val() || ''
                        });
                        $.ajax({
                                url: clockActionUrl,
                                method: 'POST',
                                dataType: 'json',
                                timeout: 20000,
                                data: payload
                            })
                            .done(function(r) {
                                if (r && r.success) {
                                    confirmedAttendanceState = r.state;
                                    employeeSyncTime = Date.now();
                                    setAttendanceStateMessage(r.message || 'Attendance recorded.', false);
                                    releaseBtn(true);
                                    return;
                                }

                                var message = r && r.message ? r.message : 'Could not save attendance.';
                                if (r && r.reference_id) {
                                    message += ' Reference ID: ' + r.reference_id;
                                }
                                setAttendanceStateMessage(message, true);
                                releaseBtn(false);
                            })
                            .fail(function(xhr) {
                                var response = xhr && xhr.responseJSON ? xhr.responseJSON : {};
                                var message = 'Server error while saving attendance.';
                                if (response.message) {
                                    message = response.message;
                                }
                                if (response.reference_id) {
                                    message += ' Reference ID: ' + response.reference_id;
                                }
                                setAttendanceStateMessage(message, true);
                                releaseBtn(false);
                            });
                    },
                    function(errMsg) {
                        setAttendanceStateMessage(errMsg, true);
                        releaseBtn(false);
                    }
                );
            }

            $('#clock-in-btn').click(function(e) {
                e.preventDefault();
                postClock('clock_in');
            });
            $('#clock-out-btn').click(function(e) {
                e.preventDefault();
                postClock('clock_out');
            });
            $('#break-out-btn').click(function(e) {
                e.preventDefault();
                postClock('break_out');
            });
            $('#break-in-btn').click(function(e) {
                e.preventDefault();
                postClock('break_in');
            });

            restoreActiveTab();
            $('.fs-portal-tabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                var href = $(e.target).attr('href') || '';
                persistActiveTab(href);
                if (window.matchMedia && window.matchMedia('(max-width: 767px)').matches) {
                    try {
                        e.target.scrollIntoView({
                            behavior: 'smooth',
                            inline: 'center',
                            block: 'nearest'
                        });
                    } catch (err) {
                        // Fallback no-op for older browsers.
                    }
                }
            });
            persistActiveTab();
            refreshTeamMonitor();
            refreshAttendanceState();
            window.setInterval(function() {
                if (confirmedAttendanceState) { updateEmployeeStatus(confirmedAttendanceState); }
                if ($('#team-monitor-list').length && teamSyncTime) { renderTeamMonitor(teamSnapshot); }
            }, 1000);

            // Prefetch GPS to show coordinates early
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    $('#gps-preview').text('GPS: ' + pos.coords.latitude.toFixed(6) + ', ' + pos.coords.longitude.toFixed(6));
                    updateMapPosition(pos.coords.latitude, pos.coords.longitude, 'Current location ready');
                });
            }

            $('#show-map-btn').on('click', function() {
                mapVisible = !mapVisible;
                $('#attendance-map-panel').toggle(mapVisible);
                $(this).text(mapVisible ? 'Hide Map' : 'Show Map');
                if (mapVisible && map) {
                    window.setTimeout(function() {
                        map.invalidateSize();
                    }, 50);
                }
            });

            $('#refresh-team-monitor-btn').on('click', function() {
                refreshTeamMonitor();
            });

            // Keep attendance view fresh so clock updates appear quickly.
            window.setInterval(function() {
                if (document.visibilityState === 'visible' && activeTabHref() === '#tab-my-daily-logs') {
                    refreshTeamMonitor();
                    refreshAttendanceState();
                }
            }, 30000);

            $('#js-my-logs-filter').click(function(e) {
                e.preventDefault();
                var q = '?start_date=' + encodeURIComponent($('#my-logs-start-date').val() || '') +
                    '&end_date=' + encodeURIComponent($('#my-logs-end-date').val() || '');
                window.location.href = window.location.pathname + q;
            });

            $('#js-my-logs-reset').click(function(e) {
                e.preventDefault();
                window.location.href = window.location.pathname;
            });

            $('#js-submit-leave-request').click(function(e) {
                e.preventDefault();
                $.ajax({
                    url: saveLeaveRequestUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        leave_type: $('#leave_type').val() || '',
                        start_date: $('#leave_start_date').val() || '',
                        end_date: $('#leave_end_date').val() || '',
                        reason: $('#leave_reason').val() || ''
                    }
                }).done(function(r) {
                    showMessage(r && r.message ? r.message : 'Leave request submitted.');
                    if (r && r.success) {
                        $('#leave-request-form')[0].reset();
                    }
                }).fail(function() {
                    showMessage('Leave request could not be submitted at this time.');
                });
            });

            function renderStatement(s) {
                var adj = s.adjustments || {};
                var h = '<div class="fs-statement-grid">';
                h += '<div class="fs-statement-card"><h5>' + escapeHtml(s.worker_name || '') + '</h5>' +
                    '<p class="fs-portal-subtle mbot0">Period: ' + escapeHtml(s.start_date || '') + ' to ' + escapeHtml(s.end_date || '') + '</p>' +
                    '<p class="mbot0">Status: ' + escapeHtml(s.status || '') + '</p>' +
                    '<p class="mbot0">Issued: ' + escapeHtml(s.created_at || '') + '</p></div>';
                h += '<div class="fs-statement-card"><h5>Earnings Summary</h5>' +
                    '<p class="mbot5">Regular Hours: ' + escapeHtml(s.regular_hours || 0) + '</p>' +
                    '<p class="mbot5">Overtime Hours: ' + escapeHtml(s.overtime_hours || 0) + '</p>' +
                    '<p class="mbot5">Gross Salary: $' + escapeHtml(Number(s.gross_salary || 0).toFixed(2)) + '</p>' +
                    '<p class="mbot0">Net Salary: <strong>$' + escapeHtml(Number(s.net_salary || 0).toFixed(2)) + '</strong></p></div>';
                h += '</div><div class="fs-statement-grid mtop15">';
                h += '<div class="fs-statement-card"><h5>Statutory Deductions</h5>' +
                    '<p class="mbot5">Employee NIB (5.5%): $' + escapeHtml(Number(s.nib_ee || 0).toFixed(2)) + '</p>' +
                    '<p class="mbot5">Employer NIB (6.5%): $' + escapeHtml(Number(s.nib_er || 0).toFixed(2)) + '</p>' +
                    '<p class="mbot5">Employee NHIP (3.0%): $' + escapeHtml(Number(s.nhip_ee || 0).toFixed(2)) + '</p>' +
                    '<p class="mbot0">Employer NHIP (3.0%): $' + escapeHtml(Number(s.nhip_er || 0).toFixed(2)) + '</p></div>';
                h += '<div class="fs-statement-card"><h5>EAV Adjustments</h5>' +
                    '<p class="mbot5">Commission: $' + escapeHtml(Number(adj.commission || 0).toFixed(2)) + '</p>' +
                    '<p class="mbot5">Loan Adjustment: $' + escapeHtml(Number(adj.loan_adjustment || 0).toFixed(2)) + '</p>' +
                    '<p class="mbot5">Advance: $' + escapeHtml(Number(adj.advance || 0).toFixed(2)) + '</p>' +
                    '<p class="mbot0">Vacation Pay: $' + escapeHtml(Number(adj.vacation_pay || 0).toFixed(2)) + '</p></div>';
                h += '</div><div class="fs-statement-card mtop15"><h5 class="mtop0">Payment Details</h5>' +
                    '<p class="mbot5">Method: ' + escapeHtml(s.payment_method || '') + '</p>' +
                    '<p class="mbot0">Staff ID: ' + escapeHtml(s.staff_id || '') + '</p></div>';
                $('#payslip-statement-body').html(h);
            }

            $(document).on('click', '.js-view-payslip', function(e) {
                e.preventDefault();
                var pid = $(this).data('payroll-id');
                if (!pid) {
                    showMessage('A valid payslip selection is required.');
                    return;
                }
                $.ajax({
                    url: getPayslipStatementUrl,
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        payroll_id: pid
                    }
                }).done(function(r) {
                    if (r && r.success && r.statement) {
                        renderStatement(r.statement);
                        $('#payslip-statement-modal').modal('show');
                        return;
                    }
                    showMessage(r && r.message ? r.message : 'The selected payslip could not be loaded.');
                }).fail(function() {
                    showMessage('The selected payslip could not be loaded.');
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootPortal);
        } else {
            bootPortal();
        }
    })();
</script>
<?php init_tail(); ?>