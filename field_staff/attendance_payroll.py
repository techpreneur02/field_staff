from __future__ import annotations

from dataclasses import dataclass
from datetime import date, datetime
from typing import Dict, List, Union


DateLike = Union[date, str]


@dataclass(frozen=True)
class StaffMember:
    staff_id: str
    name: str
    hourly_rate: float


@dataclass(frozen=True)
class AttendanceEntry:
    staff_id: str
    work_date: date
    hours_worked: float


class FieldStaffAttendancePayrollModule:
    def __init__(self, standard_hours_per_day: float = 8.0, overtime_multiplier: float = 1.5) -> None:
        if standard_hours_per_day <= 0:
            raise ValueError("standard_hours_per_day must be greater than 0")
        if overtime_multiplier < 1:
            raise ValueError("overtime_multiplier must be at least 1")
        self.standard_hours_per_day = standard_hours_per_day
        self.overtime_multiplier = overtime_multiplier
        self._staff: Dict[str, StaffMember] = {}
        self._attendance: List[AttendanceEntry] = []

    def add_staff_member(self, staff_id: str, name: str, hourly_rate: float) -> StaffMember:
        if not staff_id:
            raise ValueError("staff_id is required")
        if hourly_rate < 0:
            raise ValueError("hourly_rate must be non-negative")
        member = StaffMember(staff_id=staff_id, name=name, hourly_rate=float(hourly_rate))
        self._staff[staff_id] = member
        return member

    def mark_attendance(self, staff_id: str, work_date: DateLike, hours_worked: float) -> AttendanceEntry:
        if staff_id not in self._staff:
            raise KeyError(f"Unknown staff_id: {staff_id}")
        if hours_worked < 0:
            raise ValueError("hours_worked must be non-negative")
        entry = AttendanceEntry(
            staff_id=staff_id,
            work_date=self._to_date(work_date),
            hours_worked=float(hours_worked),
        )
        self._attendance.append(entry)
        return entry

    def get_attendance(self, staff_id: str, start_date: DateLike, end_date: DateLike) -> List[AttendanceEntry]:
        if staff_id not in self._staff:
            raise KeyError(f"Unknown staff_id: {staff_id}")
        start = self._to_date(start_date)
        end = self._to_date(end_date)
        if start > end:
            raise ValueError("start_date must be before or equal to end_date")
        return [
            entry
            for entry in self._attendance
            if entry.staff_id == staff_id and start <= entry.work_date <= end
        ]

    def calculate_payroll(self, staff_id: str, start_date: DateLike, end_date: DateLike) -> Dict[str, float]:
        member = self._staff.get(staff_id)
        if member is None:
            raise KeyError(f"Unknown staff_id: {staff_id}")
        records = self.get_attendance(staff_id, start_date, end_date)

        regular_hours = 0.0
        overtime_hours = 0.0
        for record in records:
            regular_hours += min(record.hours_worked, self.standard_hours_per_day)
            overtime_hours += max(record.hours_worked - self.standard_hours_per_day, 0.0)

        regular_pay = regular_hours * member.hourly_rate
        overtime_pay = overtime_hours * member.hourly_rate * self.overtime_multiplier
        total_pay = regular_pay + overtime_pay

        return {
            "staff_id": member.staff_id,
            "period_start": self._to_date(start_date).isoformat(),
            "period_end": self._to_date(end_date).isoformat(),
            "days_present": float(len(records)),
            "regular_hours": regular_hours,
            "overtime_hours": overtime_hours,
            "regular_pay": regular_pay,
            "overtime_pay": overtime_pay,
            "total_pay": total_pay,
        }

    @staticmethod
    def _to_date(value: DateLike) -> date:
        if isinstance(value, date):
            return value
        if isinstance(value, str):
            return datetime.strptime(value, "%Y-%m-%d").date()
        raise TypeError("Date value must be a date object or YYYY-MM-DD string")
