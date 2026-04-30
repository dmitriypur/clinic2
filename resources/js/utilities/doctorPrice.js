import { calculateAgeMonthsFromBirthDate } from "./doctorAge";

const CHILD_MAX_AGE_MONTHS = 18 * 12;

function normalizePriceValue(value) {
  if (value === null || value === undefined) {
    return null;
  }

  const normalized = String(value).trim();

  return normalized !== "" ? normalized : null;
}

export function getDoctorBasePrice(doctor) {
  return normalizePriceValue(doctor?.extra?.price);
}

export function getDoctorChildPrice(doctor) {
  return normalizePriceValue(doctor?.extra?.price_child);
}

export function getBranchPromoPrice(branch) {
  return normalizePriceValue(branch?.price);
}

export function isChildPatient(patientBirthDate) {
  const ageMonths = calculateAgeMonthsFromBirthDate(patientBirthDate);

  return Number.isFinite(ageMonths) && ageMonths < CHILD_MAX_AGE_MONTHS;
}

export function getDoctorAgeAwarePrice(doctor, patientBirthDate = null) {
  const adultPrice = getDoctorBasePrice(doctor);
  const childPrice = getDoctorChildPrice(doctor);

  if (adultPrice && childPrice) {
    return isChildPatient(patientBirthDate) ? childPrice : adultPrice;
  }

  return adultPrice || childPrice || null;
}

export function getDoctorDisplayPrice(
  doctor,
  branch = null,
  patientBirthDate = null
) {
  return (
    getDoctorAgeAwarePrice(doctor, patientBirthDate) ||
    getBranchPromoPrice(branch)
  );
}

export function getDoctorDisplayPriceSource(
  doctor,
  branch = null,
  patientBirthDate = null
) {
  if (getDoctorAgeAwarePrice(doctor, patientBirthDate)) {
    return "doctor";
  }

  if (getBranchPromoPrice(branch)) {
    return "branch";
  }

  return null;
}
