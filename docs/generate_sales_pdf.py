from fpdf import FPDF


class SalesPDF(FPDF):
    def header(self):
        self.set_fill_color(234, 244, 255)
        self.set_draw_color(184, 210, 240)
        self.rect(10, 10, 190, 26, style="DF")
        self.set_xy(14, 14)
        self.set_font("Helvetica", "B", 20)
        self.set_text_color(19, 74, 158)
        self.cell(0, 8, "Field Staff Module", ln=1)
        self.set_x(14)
        self.set_font("Helvetica", "", 11)
        self.set_text_color(27, 31, 42)
        self.cell(0, 6, "Sales One-Pager for Marketing, Sales, and Support", ln=1)
        self.set_x(14)
        self.set_font("Helvetica", "", 9)
        self.set_text_color(10, 106, 140)
        self.cell(0, 5, "GPS Attendance | HR Operations | Payroll Readiness | Reporting", ln=1)
        self.ln(4)

    def section_title(self, title):
        self.set_text_color(10, 106, 140)
        self.set_font("Helvetica", "B", 13)
        self.cell(0, 8, title, ln=1)
        self.set_draw_color(216, 223, 236)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(2)

    def bullet(self, text):
        self.set_text_color(27, 31, 42)
        self.set_font("Helvetica", "", 10)
        self.set_x(10)
        self.cell(4, 5, "-", ln=0)
        self.multi_cell(186, 5, text)

    def line_text(self, text, h=5, style="", size=10, color=(27, 31, 42)):
        self.set_text_color(*color)
        self.set_font("Helvetica", style, size)
        self.set_x(10)
        self.multi_cell(190, h, text)


def add_table(pdf, headers, rows, col_widths):
    pdf.set_font("Helvetica", "B", 9)
    pdf.set_fill_color(244, 248, 255)
    pdf.set_draw_color(216, 223, 236)

    for i, header in enumerate(headers):
        pdf.cell(col_widths[i], 7, header, border=1, fill=True)
    pdf.ln()

    pdf.set_font("Helvetica", "", 8.5)
    for row in rows:
        x_start = pdf.get_x()
        y_start = pdf.get_y()

        heights = []
        for i, cell in enumerate(row):
            lines = max(1, len(str(cell)) // max(8, int(col_widths[i] / 2.2)) + 1)
            heights.append(lines * 4.2)
        row_h = max(heights)

        if y_start + row_h > 282:
            pdf.add_page()
            pdf.set_font("Helvetica", "B", 9)
            for i, header in enumerate(headers):
                pdf.cell(col_widths[i], 7, header, border=1, fill=True)
            pdf.ln()
            pdf.set_font("Helvetica", "", 8.5)
            y_start = pdf.get_y()

        x = x_start
        for i, cell in enumerate(row):
            pdf.rect(x, y_start, col_widths[i], row_h)
            pdf.set_xy(x + 1, y_start + 1)
            pdf.multi_cell(col_widths[i] - 2, 3.8, str(cell), border=0)
            x += col_widths[i]
        pdf.set_xy(x_start, y_start + row_h)


pdf = SalesPDF("P", "mm", "A4")
pdf.set_auto_page_break(auto=True, margin=12)
pdf.add_page()

pdf.section_title("1. Brand Positioning (Editable)")
pdf.line_text("Brand Name: [Your Brand Name]")
pdf.line_text("Tagline: [One-line promise, e.g., From field attendance to payroll confidence.]")
pdf.line_text("Primary Buyer: Operations-led businesses with distributed staff and payroll compliance needs.")
pdf.line_text("Differentiator: End-to-end workflow from geolocation attendance capture to payrun reporting, with strict role-ID governance.")

pdf.section_title("2. Sales Value Narrative")
pdf.bullet("Business pain solved: disputed attendance, disconnected HR/payroll prep, weak access governance, delayed reporting handoff.")
pdf.bullet("Commercial outcomes: faster verification cycles, improved payroll readiness, clear role accountability, cleaner finance handoff.")

pdf.section_title("3. Feature Highlights for Demos")
headers = ["Feature", "Demo Talking Point", "Why Buyers Care"]
rows = [
    ["GPS Clock In/Out", "Field users submit attendance with location proof and notes.", "Creates auditable attendance context."],
    ["HR Workspace Tabs", "Pay setup, operations, reporting, and assignments in one control center.", "Reduces tool switching and admin overhead."],
    ["Project Assignment Control", "Assign one project to multiple staff with supervisor traceability.", "Supports real scheduling and accountability."],
    ["Strict Role-ID Allowlist", "Project access controlled by explicit role IDs, not title guessing.", "Stronger operational governance."],
    ["Reporting and Payrun", "Generate exports and payrun-ready statements in workflow.", "Speeds payroll prep and decision visibility."],
]
add_table(pdf, headers, rows, [34, 88, 68])

pdf.section_title("4. Pricing Model (Editable Template)")
headers = ["Plan", "Best For", "Includes", "Suggested Price"]
rows = [
    ["Starter", "Small field teams launching core attendance and reports", "Attendance, basic HR tabs, reporting exports, docs", "$[XX] one-time"],
    ["Professional", "Growing teams needing full operations and payroll setup", "All starter + shifts, leave, pay setup, project assignment", "$[XX] one-time"],
    ["Business", "Larger teams requiring implementation support", "All professional + onboarding package + priority support window", "$[XX] one-time"],
]
add_table(pdf, headers, rows, [26, 52, 78, 34])
pdf.set_font("Helvetica", "B", 9)
pdf.set_text_color(145, 95, 0)
pdf.set_x(10)
pdf.multi_cell(190, 5, "Important: finalize license scope, support terms, and update policy before publication.")

pdf.section_title("5. Objection Handling Cheatsheet")
pdf.bullet("Will this fit our workflow? Yes. It mirrors attendance, scheduling, leave, and payroll-prep flow in one interface.")
pdf.bullet("How secure is supervisor access? Project Assignment uses strict explicit role-ID allowlist with server-side checks.")
pdf.bullet("Can we export for finance? Yes. Attendance and operations reports export to CSV.")
pdf.bullet("What if field exceptions happen? Manual attendance and leave workflows handle exceptions with controlled updates.")

pdf.section_title("6. Support Handoff Summary")
pdf.bullet("Full setup, permissions, API, and workflow docs are included.")
pdf.bullet("Support policy and UAT checklist are available for consistent issue handling.")
pdf.bullet("Versioned changelog supports release communication and customer updates.")

pdf.set_y(-14)
pdf.set_font("Helvetica", "", 8)
pdf.set_text_color(90, 101, 123)
pdf.cell(0, 5, "Author: Sherwin Armas | Agency: scaenterprise.com", align="L")
pdf.set_y(-14)
pdf.cell(0, 5, "Field Staff Sales One-Pager | Ready for presentation", align="R")

pdf.output("Field_Staff_Sales_One_Pager.pdf")
print("Generated Field_Staff_Sales_One_Pager.pdf")
