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

export function getBranchChildPromoPrice(branch) {
  return normalizePriceValue(branch?.price_child);
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

export function getBranchAgeAwarePrice(branch, patientBirthDate = null) {
  return isChildPatient(patientBirthDate)
    ? getBranchChildPromoPrice(branch)
    : getBranchPromoPrice(branch);
}

export function isDoctorExcludedFromBranchPromoPrice(doctor) {
  return doctor?.extra?.exclude_from_branch_promo_price === true;
}

function priceResult(price, source) {
  return {
    price: price || null,
    source: price ? source : null,
  };
}

export function resolveDoctorDisplayPrice(
  doctor,
  branch = null,
  patientBirthDate = null,
  options = {}
) {
  const priority = options?.priority || "branch-first";
  const doctorPrice = getDoctorAgeAwarePrice(doctor, patientBirthDate);

  if (
    priority === "doctor-only" ||
    isDoctorExcludedFromBranchPromoPrice(doctor)
  ) {
    return priceResult(doctorPrice, "doctor");
  }

  const branchPrice = getBranchAgeAwarePrice(branch, patientBirthDate);

  if (priority === "doctor-first") {
    return doctorPrice
      ? priceResult(doctorPrice, "doctor")
      : priceResult(branchPrice, "branch");
  }

  return branchPrice
    ? priceResult(branchPrice, "branch")
    : priceResult(doctorPrice, "doctor");
}

export function getDoctorDisplayPrice(
  doctor,
  branch = null,
  patientBirthDate = null,
  options = {}
) {
  return resolveDoctorDisplayPrice(
    doctor,
    branch,
    patientBirthDate,
    options
  ).price;
}

export function getDoctorDisplayPriceSource(
  doctor,
  branch = null,
  patientBirthDate = null,
  options = {}
) {
  return resolveDoctorDisplayPrice(
    doctor,
    branch,
    patientBirthDate,
    options
  ).source;
}
