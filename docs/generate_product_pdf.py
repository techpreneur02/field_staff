from fpdf import FPDF


class ProductPDF(FPDF):
    def header(self):
        self.set_fill_color(234, 244, 255)
        self.set_draw_color(184, 210, 240)
        self.rect(10, 10, 190, 24, style="DF")
        self.set_xy(14, 14)
        self.set_font("Helvetica", "B", 19)
        self.set_text_color(13, 92, 143)
        self.cell(0, 7, "Field Staff Product Dossier", ln=1)
        self.set_x(14)
        self.set_font("Helvetica", "", 10)
        self.set_text_color(29, 36, 51)
        self.cell(0, 6, "For Marketing, Sales, and Support Teams", ln=1)
        self.ln(4)

    def section(self, title):
        self.set_text_color(13, 92, 143)
        self.set_font("Helvetica", "B", 12)
        self.cell(0, 7, title, ln=1)
        self.set_draw_color(217, 223, 235)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(2)

    def paragraph(self, text):
        self.set_text_color(29, 36, 51)
        self.set_font("Helvetica", "", 10)
        self.set_x(10)
        self.multi_cell(190, 5, text)

    def bullet(self, text):
        self.set_text_color(29, 36, 51)
        self.set_font("Helvetica", "", 10)
        self.set_x(10)
        self.cell(4, 5, "-", ln=0)
        self.multi_cell(186, 5, text)


def add_table(pdf, headers, rows, widths):
    pdf.set_font("Helvetica", "B", 9)
    pdf.set_fill_color(246, 249, 255)
    pdf.set_draw_color(217, 223, 235)
    for i, h in enumerate(headers):
        pdf.cell(widths[i], 7, h, border=1, fill=True)
    pdf.ln()

    pdf.set_font("Helvetica", "", 8.6)
    for row in rows:
        y = pdf.get_y()
        heights = []
        for i, cell in enumerate(row):
            lines = max(1, len(str(cell)) // max(8, int(widths[i] / 2.2)) + 1)
            heights.append(lines * 4.2)
        rh = max(heights)

        if y + rh > 282:
            pdf.add_page()
            pdf.set_font("Helvetica", "B", 9)
            for i, h in enumerate(headers):
                pdf.cell(widths[i], 7, h, border=1, fill=True)
            pdf.ln()
            pdf.set_font("Helvetica", "", 8.6)
            y = pdf.get_y()

        x = pdf.get_x()
        for i, cell in enumerate(row):
            pdf.rect(x, y, widths[i], rh)
            pdf.set_xy(x + 1, y + 1)
            pdf.multi_cell(widths[i] - 2, 3.8, str(cell), border=0)
            x += widths[i]
        pdf.set_xy(10, y + rh)


pdf = ProductPDF("P", "mm", "A4")
pdf.set_auto_page_break(auto=True, margin=12)
pdf.add_page()

pdf.section("1. Product Overview")
pdf.paragraph("Field Staff is a white-labeled workforce operations module connecting field attendance, HR controls, and payroll preparation in a single workflow.")
pdf.bullet("Capture verifiable attendance with geolocation.")
pdf.bullet("Standardize HR operations through one workspace.")
pdf.bullet("Move from attendance to payrun with reporting clarity.")

pdf.section("2. Feature Set")
headers = ["Area", "What It Does", "Business Impact"]
rows = [
    ["Attendance", "Clock in and clock out with GPS coordinates, notes, and ledger history.", "Supports attendance accountability."],
    ["Employee Pay Setup", "Stores payroll profile values including rates and statutory fields.", "Improves payroll consistency."],
    ["Shift Scheduling", "Creates templates and distributes shifts by staff/department and date.", "Improves workforce planning."],
    ["Manual Attendance", "Allows controlled HR attendance corrections.", "Handles field exceptions safely."],
    ["Leave Tracking", "Records leave requests and status changes.", "Aligns operations and payroll context."],
    ["Reporting and Payrun", "Generates reporting exports and payrun statements.", "Accelerates payroll prep workflows."],
    ["Project Assignment", "Assigns projects to one or many staff with supervision traceability.", "Improves resource allocation control."],
]
add_table(pdf, headers, rows, [34, 96, 60])

pdf.section("3. Access and Governance")
pdf.bullet("Server-side tab-level authorization in HR workspace.")
pdf.bullet("Strict role-ID allowlist for Project Assignment access.")
pdf.bullet("Admin-managed allowlist settings and role context visibility.")

pdf.section("4. Team Workflow Summary")
pdf.bullet("Marketing: position end-to-end attendance-to-payrun value.")
pdf.bullet("Sales: qualify by distributed staff, payroll prep, and governance needs.")
pdf.bullet("Support: verify install, permissions, role allowlist, and reporting pipeline.")

pdf.section("5. Payroll Rules Built In")
pdf.bullet("Weekly regular hours cap: 44.00")
pdf.bullet("Overtime multiplier: 1.5x")
pdf.bullet("NIB: 5.5% employee and 6.5% employer")
pdf.bullet("NHIP: 3.0% employee and 3.0% employer")
pdf.bullet("NHIP monthly ceiling reference: 7800.00")

pdf.section("6. Reporting Outputs")
pdf.bullet("Attendance record export")
pdf.bullet("Attendance summary export")
pdf.bullet("Daily attendance export")
pdf.bullet("Monthly attendance export")
pdf.bullet("Department-wise export")

pdf.set_y(-16)
pdf.set_font("Helvetica", "", 8)
pdf.set_text_color(77, 90, 115)
pdf.cell(0, 5, "Author: Sherwin Armas | Agency: scaenterprise.com", align="L")
pdf.set_y(-16)
pdf.cell(0, 5, "Field Staff Product Dossier", align="R")

pdf.output("Field_Staff_Product_Dossier.pdf")
print("Generated Field_Staff_Product_Dossier.pdf")
