import {
  buildDoctorReceivesDisplay,
  calculateAgeMonthsFromBirthDate,
  calculateAgeYearsFromBirthDate,
  formatDateForInput,
  getDoctorAgeRange,
  getDoctorReceivesDisplay,
} from "@/utilities/doctorAge";

export function extractDoctorMinimumAge(doctor) {
  return getDoctorAgeRange(doctor).minAgeMonths;
}

export function extractDoctorAgeRange(doctor) {
  return getDoctorAgeRange(doctor);
}

export function calculateAgeFromBirthDate(value, now = new Date()) {
  return calculateAgeYearsFromBirthDate(value, now);
}

export function calculateAgeMonths(value, now = new Date()) {
  return calculateAgeMonthsFromBirthDate(value, now);
}

export function formatDoctorMinimumAgeText(age) {
  return buildDoctorReceivesDisplay({ minAgeMonths: age, maxAgeMonths: null });
}

export function formatDoctorAgeRangeText({ minAgeMonths = null, maxAgeMonths = null, receivesText = null } = {}) {
  return buildDoctorReceivesDisplay({ minAgeMonths, maxAgeMonths, receivesText });
}

export { formatDateForInput, getDoctorReceivesDisplay };
