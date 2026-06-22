import unittest

from field_staff import FieldStaffAttendancePayrollModule


class FieldStaffAttendancePayrollModuleTests(unittest.TestCase):
    def setUp(self) -> None:
        self.module = FieldStaffAttendancePayrollModule()
        self.module.add_staff_member("FS001", "Alex", 10.0)

    def test_records_attendance_and_calculates_regular_and_overtime_pay(self) -> None:
        self.module.mark_attendance("FS001", "2026-06-01", 8)
        self.module.mark_attendance("FS001", "2026-06-02", 10)

        payroll = self.module.calculate_payroll("FS001", "2026-06-01", "2026-06-30")

        self.assertEqual(payroll["regular_hours"], 16.0)
        self.assertEqual(payroll["overtime_hours"], 2.0)
        self.assertEqual(payroll["regular_pay"], 160.0)
        self.assertEqual(payroll["overtime_pay"], 30.0)
        self.assertEqual(payroll["total_pay"], 190.0)

    def test_unknown_staff_raises_key_error(self) -> None:
        with self.assertRaises(KeyError):
            self.module.mark_attendance("UNKNOWN", "2026-06-01", 8)

    def test_invalid_period_raises_value_error(self) -> None:
        with self.assertRaises(ValueError):
            self.module.calculate_payroll("FS001", "2026-06-30", "2026-06-01")


if __name__ == "__main__":
    unittest.main()
