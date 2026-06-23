# Payslips Management

Creates professional salary statements for employees. This system enables systematic payslip generation, secure employee distribution, and comprehensive salary documentation for transparent compensation communication and record maintenance.

## 13.5.1 Payslip Generation

Payslips are automatically created from processed payroll runs:

### 1. Automatic Generation

- **Generated automatically** from payroll entries in the payroll master table
- **One payslip per employee** per payroll run
- **Available after payroll completion** — payslip status set to `issued` after payrun processing
- **Staff-scoped access** — employees can only view their own payslips

### 2. Payslip Information

**Employee Details:**
- Name, Staff ID, designation, department

**Pay Period:**
- Payroll start date and end date
- Pay date (when payslip was issued)
- Pay status (draft, issued, archived)

**Earnings Section:**
- Regular salary (hours ≤ 44 per week @ base rate)
- Overtime hours (> 44 per week @ 1.5x multiplier)
- Gross Earnings (total before deductions)

**Deductions Section:**
- **Statutory Deductions:**
  - Employee NIB (5.5% of gross)
  - Employer NIB (6.5% — informational only, not deducted from employee pay)
  - Employee NHIP (3% of gross, capped at USD 7,800 monthly earnings ceiling)
  - Employer NHIP (3% — informational only)

- **EAV Adjustments** (Entity-Attribute-Value flexible additions/deductions):
  - Commission (positive adjustment)
  - Loan Adjustment (negative adjustment)
  - Advance (negative adjustment)
  - Vacation Pay (positive adjustment)
  - Other custom additions/deductions as configured

**Attendance Summary:**
- Working days vs present days
- Leave days (paid/unpaid breakdown)
- Overtime hours recorded
- Attendance percentage

**Net Pay:**
- Final take-home salary after all deductions

---

## 13.5.2 Payslip Features

- **Secure Generation** — Generated directly from validated payroll master records
- **PDF Download** — Generate professional PDF payslips on demand
- **Employee Self-Service** — Employees can download and view their own payslips (staff-scoped access only)
- **Payslip History** — Maintain complete payslip archive across all payroll runs
- **Access Control** — Staff members cannot view other employees' payslips; HR can view all
- **Statutory Accuracy** — Automatic calculation of TCI tax rules (44-hour OT cap, 1.5x multiplier, NIB/NHIP splits and ceilings)

---

## 13.5.3 Payslip Operations

### 1. Generate Payslips from Payroll Run (HR Only)

- Go to **HR Management → Tab 5: Payrun & Reporting**
- Select or execute a payroll run
- Click **"Generate Payslips"** button after payroll completion
- System generates payslips for all employees in that payroll run
- Generated payslips automatically appear with status `issued`
- Payslips stored in `field_staff_payroll_master` table with `start_date`, `end_date`, `created_at`, `status`, and statutory/adjustment columns

### 2. View Generated Payslips (HR)

- Go to **HR Management → Tab 5: Payrun & Reporting**
- View all generated payslips for the selected month/payroll run
- Filter by employee staff ID, date range, or payroll run
- Click individual payslip row to view detailed statement

### 3. Individual Payslip Operations (Employee Self-Service)

**Employee Portal → My Payslips tab:**

- View list of issued payslips with:
  - Pay date (created_at)
  - Period (start_date to end_date)
  - Net Salary (formatted currency)
  - Status (issued/draft)
  - Action: **View Statement** button

- Click **View Statement** to open modal displaying:
  - Employee name, period, status, issued date
  - Earnings summary (regular hours, overtime hours, gross salary, net salary)
  - Statutory deductions (NIB EE/ER, NHIP EE/ER with applicable percentages)
  - EAV adjustments (commission, loan adjustment, advance, vacation pay)
  - Payment details (payment method, staff ID)

- No download capability at employee level (payslips are view-only in self-service portal)

### 4. Bulk Payslip Operations (HR)

- Generate all payslips for a payroll run at once via **Tab 5: Payrun & Reporting**
- Bulk export payslips to HR records
- Mass payslip generation with single payroll completion

---

## 13.5.4 Payroll & Payslip Lifecycle

**Setup:**
- Configure salary components and employee salaries in **Tab 1: Master Data Setup**
- Define statutory rates (NIB 6.5% employer / 5.5% employee, NHIP 3% each, NHIP USD 7,800 ceiling)
- Configure EAV adjustment types (commission, loan, advance, vacation pay)

**Attendance:**
- Employees clock in/out daily via **Employee Portal → My Daily Logs**
- Hours tracked against 44-hour TCI weekly threshold

**Process:**
- Run payroll processing via **Tab 5: Payrun & Reporting**

**Calculate:**
- Automatic calculation of all salary components:
  - Regular pay (≤ 44 hrs @ base rate)
  - Overtime pay (> 44 hrs @ 1.5x base rate)
  - Gross salary
  - Statutory deductions (NIB/NHIP with caps and splits)
  - EAV adjustments
  - Net take-home pay

**Integrate:**
- Seamless integration with attendance (daily clock logs) and leave (requested/approved leave days) systems

**Track:**
- Monitor payroll runs and per-employee entries via **Tab 5: Payrun & Reporting**

**Generate:**
- One-click payslip generation from completed payroll runs

**Distribute:**
- Employees access their own payslips via self-service portal **My Payslips** tab
- HR can view all employee payslips in the HR workspace

**Report:**
- Generate comprehensive payroll reports from **Tab 5: Payrun & Reporting**

**Approve:**
- Payroll runs can be marked complete/issued, triggering payslip availability

**Archive:**
- Maintain complete payroll and payslip history — all payslips stored permanently in the payroll master table with full audit trail

---

## API Endpoints

### Employee Self-Service (Staff-Scoped)

**GET `/admin/field_staff/get_employee_payslip_statement`**
- Retrieve a single payslip statement for the logged-in employee
- Parameters: `payroll_id` (integer)
- Response: JSON with statement details (earnings, deductions, adjustments, payment method)
- Access: Current staff member only

### HR Management (Admin-Only)

**POST `/admin/field_staff/generate_payslips`**
- Generate all payslips for a completed payroll run
- Parameters: `payroll_run_id` (integer)
- Response: JSON success/failure with count of generated payslips
- Access: HR admin only

**GET `/admin/field_staff/get_payslip_list`**
- Retrieve paginated list of all payslips with optional filters
- Parameters: `staff_id`, `start_date`, `end_date`, `status`, `limit`, `offset`
- Response: JSON array of payslip records
- Access: HR admin only

---

## Security & Compliance

- **Staff-Scoped Access:** Employees can only retrieve their own payslips via unique staff ID verification
- **Statutory Compliance:**
  - TCI overtime: Weekly hours > 44 paid at 1.5× premium
  - NIB: 12% total (6.5% employer + 5.5% employee)
  - NHIP: 6% total (3% employer + 3% employee), capped at USD 7,800 monthly ceiling
- **Audit Trail:** All payslip generation, access, and modifications logged with timestamps
- **Data Validation:** Payroll calculations verified against attendance and leave records before payslip issuance
