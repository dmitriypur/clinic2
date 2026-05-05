import {
  calculateAgeMonthsFromBirthDate,
  formatAgeMonths,
  getDoctorAgeRange,
} from "@/utilities/doctorAge";

export function isDoctorAllowedForBirthDate(doctor, birthDateIso) {
  const patientAgeMonths = calculateAgeMonthsFromBirthDate(birthDateIso);

  if (!Number.isFinite(patientAgeMonths)) {
    return true;
  }

  const { minAgeMonths, maxAgeMonths } = getDoctorAgeRange(doctor);
  const isBelowMinimum =
    Number.isFinite(minAgeMonths) && patientAgeMonths < minAgeMonths;
  const isAboveMaximum =
    Number.isFinite(maxAgeMonths) && patientAgeMonths > maxAgeMonths;

  return !isBelowMinimum && !isAboveMaximum;
}

export function buildDoctorAgeBlockedMessage(doctor) {
  const { minAgeMonths, maxAgeMonths } = getDoctorAgeRange(doctor);
  const minText = Number.isFinite(minAgeMonths)
    ? formatAgeMonths(minAgeMonths)
    : null;
  const maxText = Number.isFinite(maxAgeMonths)
    ? formatAgeMonths(maxAgeMonths)
    : null;

  if (minText && maxText) {
    return `Данный врач принимает пациентов с ${minText} до ${maxText}`;
  }

  if (minText) {
    return `Данный врач принимает пациентов с ${minText}`;
  }

  if (maxText) {
    return `Данный врач принимает пациентов до ${maxText}`;
  }

  return "Данный врач принимает пациентов другого возраста";
}
