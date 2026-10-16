<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    :root {
        --fs-preview-bg: #f5f8fc;
        --fs-preview-card: #ffffff;
        --fs-preview-border: #dfeaf6;
        --fs-preview-soft: #eef5ff;
        --fs-preview-primary: #0f4cbd;
        --fs-preview-primary-strong: #0d3f9a;
        --fs-preview-accent: #eaf4ff;
        --fs-preview-success: #12a66a;
        --fs-preview-warning: #d97706;
        --fs-preview-text: #1f2937;
        --fs-preview-muted: #64748b;
        --fs-preview-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }

    body {
        background: var(--fs-preview-bg);
    }

    .fs-preview-shell {
        padding: 18px 0 40px;
    }

    .fs-preview-hero {
        background: linear-gradient(135deg, #ffffff 0%, #edf5ff 55%, #eaf3ff 100%);
        border: 1px solid var(--fs-preview-border);
        border-radius: 18px;
        box-shadow: var(--fs-preview-shadow);
        padding: 22px 24px;
        margin-bottom: 20px;
    }

    .fs-preview-hero .tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 8px 12px;
        background: #dfeeff;
        color: var(--fs-preview-primary-strong);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .fs-preview-hero h2 {
        margin: 16px 0 8px;
        color: var(--fs-preview-text);
        font-weight: 700;
    }

    .fs-preview-hero p {
        margin: 0;
        color: var(--fs-preview-muted);
    }

    .fs-preview-panel {
        background: var(--fs-preview-card);
        border: 1px solid var(--fs-preview-border);
        border-radius: 16px;
        box-shadow: var(--fs-preview-shadow);
        overflow: hidden;
    }

    .fs-preview-panel .panel-heading {
        background: #f8fbff;
        border-bottom: 1px solid var(--fs-preview-border);
        color: var(--fs-preview-text);
        font-weight: 700;
        padding: 16px 18px;
    }

    .fs-preview-panel .panel-body {
        padding: 18px;
    }

    .fs-preview-nav {
        background: var(--fs-preview-card);
        border: 1px solid var(--fs-preview-border);
        border-radius: 16px;
        box-shadow: var(--fs-preview-shadow);
        padding: 12px;
    }

    .fs-preview-nav a {
        display: block;
        border: 1px solid transparent;
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 8px;
        color: var(--fs-preview-text);
        text-decoration: none;
        background: #f9fbff;
    }

    .fs-preview-nav a.active,
    .fs-preview-nav a:hover {
        background: var(--fs-preview-soft);
        border-color: #cfe0ff;
        color: var(--fs-preview-primary-strong);
    }

    .fs-preview-nav strong {
        display: block;
        font-size: 14px;
    }

    .fs-preview-nav small {
        color: var(--fs-preview-muted);
        line-height: 1.4;
    }

    .fs-preview-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .fs-stat-card {
        background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
        border: 1px solid var(--fs-preview-border);
        border-radius: 14px;
        padding: 16px;
    }

    .fs-stat-card .label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #eaf3ff;
        color: var(--fs-preview-primary-strong);
        padding: 4px 8px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .fs-stat-card h4 {
        margin: 12px 0 0;
        font-size: 28px;
        font-weight: 700;
        color: var(--fs-preview-text);
    }

    .fs-stat-card p {
        margin: 6px 0 0;
        color: var(--fs-preview-muted);
        font-size: 12px;
    }

    .fs-preview-action-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .fs-preview-action-row .btn {
        min-height: 42px;
        border-radius: 10px;
        font-weight: 600;
    }

    .fs-preview-section {
        margin-bottom: 24px;
        scroll-margin-top: 100px;
    }

    .fs-preview-form-group {
        margin-bottom: 14px;
    }

    .fs-preview-form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 700;
        color: var(--fs-preview-text);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .fs-preview-form-group input,
    .fs-preview-form-group select,
    .fs-preview-form-group textarea {
        width: 100%;
        border: 1px solid #d8e3f0;
        border-radius: 10px;
        padding: 10px 12px;
        background: #fff;
        color: var(--fs-preview-text);
    }

    .fs-preview-inline-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border: 1px solid var(--fs-preview-border);
        border-radius: 999px;
        background: #f8fbff;
        color: var(--fs-preview-text);
        font-size: 12px;
        font-weight: 600;
    }

    .fs-preview-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }

    .fs-preview-table th,
    .fs-preview-table td {
        border-bottom: 1px solid var(--fs-preview-border);
        padding: 10px 12px;
        text-align: left;
        vertical-align: middle;
    }

    .fs-preview-table th {
        background: #f8fbff;
        color: var(--fs-preview-text);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .fs-preview-table td {
        color: var(--fs-preview-text);
        font-size: 13px;
    }

    .fs-pill {
        display: inline-block;
        border-radius: 999px;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .fs-pill.success {
        background: rgba(18, 166, 106, 0.12);
        color: var(--fs-preview-success);
    }

    .fs-pill.warning {
        background: rgba(217, 119, 6, 0.12);
        color: var(--fs-preview-warning);
    }

    .fs-pill.neutral {
        background: #edf2f7;
        color: var(--fs-preview-text);
    }

    .fs-empty-state {
        border: 1px dashed #d7e2f0;
        border-radius: 12px;
        background: #f9fbff;
        color: var(--fs-preview-muted);
        padding: 18px;
        text-align: center;
        font-weight: 600;
    }

    .fs-disabled-btn {
        opacity: 0.6;
        cursor: not-allowed;
    }

    @media (max-width: 991px) {
        .fs-preview-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div id="wrapper">
    <div class="content">
        <div class="fs-preview-shell">
            <div class="fs-preview-hero">
                <div class="tag"><i class="fa fa-eye"></i> UI preview</div>
                <h2><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
                <p>Preview-only corporate workspace for Field Staff. Sample data is intentionally separate from live payroll and is not connected to save or payment actions.</p>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="fs-preview-nav">
                        <?php foreach ($screen_map as $screen) { ?>
                            <a href="#<?php echo htmlspecialchars((string) ($screen['id'] ?? 'screen'), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo (($screen['id'] ?? '') === 'my-work') ? 'active' : ''; ?>">
                                <strong><?php echo htmlspecialchars((string) ($screen['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars((string) ($screen['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="fs-preview-section" id="my-work">
                        <div class="fs-preview-panel">
                            <div class="panel-heading">My Work</div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="fs-preview-inline-item">
                                            <i class="fa fa-user-circle"></i> <?php echo htmlspecialchars((string) ($employee['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <div class="mtop15 fs-preview-grid">
                                            <div class="fs-stat-card">
                                                <span class="label">Status</span>
                                                <h4>Clocked In</h4>
                                                <p>Last sync 09:42</p>
                                            </div>
                                            <div class="fs-stat-card">
                                                <span class="label">This week</span>
                                                <h4>39.75 hrs</h4>
                                                <p>Regular 38.00 | OT 1.75</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="fs-preview-form-group">
                                            <label>Quick actions</label>
                                            <div class="fs-preview-action-row">
                                                <button class="btn btn-primary" disabled>Clock Out</button>
                                                <button class="btn btn-default fs-disabled-btn" disabled>Break In</button>
                                            </div>
                                            <div class="mtop15 fs-preview-action-row">
                                                <button class="btn btn-outline-primary fs-disabled-btn" disabled>Request leave</button>
                                                <button class="btn btn-default fs-disabled-btn" disabled>View payslip</button>
                                            </div>
                                            <p class="mtop10 text-muted">Not connected yet</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mtop20">
                                    <div class="panel_s" style="border:1px solid var(--fs-preview-border); border-radius:12px; margin-bottom:0;">
                                        <div class="panel-heading" style="padding:12px 16px; font-size:14px;">Attendance log</div>
                                        <div class="panel-body" style="padding:0;">
                                            <table class="fs-preview-table">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Clock in</th>
                                                        <th>Clock out</th>
                                                        <th>Hours</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($attendance_rows as $row) { ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars((string) ($row['date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td><?php echo htmlspecialchars((string) ($row['clock_in'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td><?php echo htmlspecialchars((string) ($row['clock_out'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td><?php echo htmlspecialchars((string) ($row['hours'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td><span class="fs-pill <?php echo ($row['status'] === 'Approved') ? 'success' : (($row['status'] === 'Pending') ? 'warning' : 'neutral'); ?>"><?php echo htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
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

                    <div class="fs-preview-section" id="team-operations">
                        <div class="fs-preview-panel">
                            <div class="panel-heading">Team Operations</div>
                            <div class="panel-body">
                                <div class="row mbottom15">
                                    <div class="col-md-3">
                                        <div class="fs-preview-form-group">
                                            <label>Department</label>
                                            <select><option>Operations</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fs-preview-form-group">
                                            <label>Shift</label>
                                            <select><option>All shifts</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fs-preview-form-group">
                                            <label>Status</label>
                                            <select><option>Any status</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fs-preview-form-group">
                                            <label>Location</label>
                                            <select><option>All locations</option></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="fs-preview-action-row mbottom15">
                                    <button class="btn btn-primary fs-disabled-btn" disabled>Save schedule</button>
                                    <button class="btn btn-default fs-disabled-btn" disabled>Approve corrections</button>
                                    <button class="btn btn-default fs-disabled-btn" disabled>Manual time</button>
                                </div>
                                <table class="fs-preview-table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Status</th>
                                            <th>Hours</th>
                                            <th>Location</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($team_rows as $row) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><span class="fs-pill <?php echo ($row['status'] === 'On shift') ? 'success' : (($row['status'] === 'Late') ? 'warning' : 'neutral'); ?>"><?php echo htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                <td><?php echo htmlspecialchars((string) ($row['hours'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) ($row['location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><button class="btn btn-default btn-xs fs-disabled-btn" disabled>Review</button></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="fs-preview-section" id="payroll">
                        <div class="fs-preview-panel">
                            <div class="panel-heading">Payroll</div>
                            <div class="panel-body">
                                <div class="row mbottom15">
                                    <div class="col-md-3">
                                        <div class="fs-preview-form-group">
                                            <label>Cutoff</label>
                                            <select><option>Cutoff CW-41</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fs-preview-form-group">
                                            <label>Payroll group</label>
                                            <select><option>Field Ops</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fs-preview-form-group">
                                            <label>Dates</label>
                                            <input type="text" value="2026-10-05 to 2026-10-18" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fs-preview-form-group">
                                            <label>Review state</label>
                                            <select><option>Draft</option></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="fs-preview-action-row mbottom15">
                                    <button class="btn btn-primary fs-disabled-btn" disabled>Recalculate</button>
                                    <button class="btn btn-default fs-disabled-btn" disabled>Save payrun</button>
                                    <button class="btn btn-default fs-disabled-btn" disabled>Approve</button>
                                </div>
                                <table class="fs-preview-table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Base</th>
                                            <th>Allowance</th>
                                            <th>Deductions</th>
                                            <th>Net</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payroll_rows as $row) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string) ($row['employee'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) ($row['base'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) ($row['allowance'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) ($row['deductions'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) ($row['net'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><span class="fs-pill <?php echo ($row['status'] === 'Approved') ? 'success' : (($row['status'] === 'Draft') ? 'neutral' : 'warning'); ?>"><?php echo htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="fs-preview-section" id="reports">
                        <div class="fs-preview-panel">
                            <div class="panel-heading">Reports</div>
                            <div class="panel-body">
                                <div class="row mbottom15">
                                    <div class="col-md-4">
                                        <div class="fs-preview-form-group">
                                            <label>Cutoff</label>
                                            <select><option>Current cutoff</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="fs-preview-form-group">
                                            <label>Report type</label>
                                            <select><option>Payroll register</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="fs-preview-form-group">
                                            <label>Export</label>
                                            <div class="fs-preview-action-row">
                                                <button class="btn btn-default fs-disabled-btn" disabled>Print</button>
                                                <button class="btn btn-default fs-disabled-btn" disabled>PDF</button>
                                                <button class="btn btn-default fs-disabled-btn" disabled>CSV</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <table class="fs-preview-table">
                                    <thead>
                                        <tr>
                                            <th>Cutoff</th>
                                            <th>Gross</th>
                                            <th>Employee NIB</th>
                                            <th>Employer NIB</th>
                                            <th>Net</th>
                                            <th>Export</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_rows as $row) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string) ($row['cutoff'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) ($row['gross'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) ($row['employee_nib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) ($row['employer_nib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) ($row['net'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><span class="fs-pill success"><?php echo htmlspecialchars((string) ($row['exports'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="fs-preview-section" id="hr-settings">
                        <div class="fs-preview-panel">
                            <div class="panel-heading">HR Settings</div>
                            <div class="panel-body">
                                <div class="row mbottom15">
                                    <div class="col-md-4">
                                        <div class="fs-preview-form-group">
                                            <label>Employee</label>
                                            <select><option><?php echo htmlspecialchars((string) ($employee['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="fs-preview-form-group">
                                            <label>Department</label>
                                            <input type="text" value="Operations" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="fs-preview-form-group">
                                            <label>Payroll group</label>
                                            <input type="text" value="Field Ops" />
                                        </div>
                                    </div>
                                </div>
                                <div class="fs-preview-grid">
                                    <?php foreach ($settings_sections as $section) { ?>
                                        <div class="fs-stat-card">
                                            <span class="label">Settings</span>
                                            <h4 style="font-size:18px; margin-top:10px;"><?php echo htmlspecialchars((string) $section, ENT_QUOTES, 'UTF-8'); ?></h4>
                                            <p>Effective dates, policy inheritance, and access controls displayed here.</p>
                                            <div class="mtop10">
                                                <button class="btn btn-default btn-sm fs-disabled-btn" disabled>Open</button>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
