import { calculateAgeMonthsFromBirthDate, formatDateForInput } from "./doctorAge";

const CHILD_MAX_AGE_MONTHS = 18 * 12;

function normalizePriceValue(value) {
  if (value === null || value === undefined) {
    return null;
  }

  const normalized = String(value).trim();

  return normalized !== "" ? normalized : null;
}

function hasManagedPrice(owner, field) {
  return Object.prototype.hasOwnProperty.call(owner?.price_periods || {}, field);
}

function priceForDate(owner, field, appointmentDate = null) {
  if (!hasManagedPrice(owner, field)) {
    return normalizePriceValue(owner?.[field]);
  }

  const date = appointmentDate instanceof Date
    ? formatDateForInput(appointmentDate)
    : appointmentDate || formatDateForInput();
  let selected = null;
  for (const period of owner.price_periods[field] || []) {
    const start = period.starts_on || "";
    if (start <= date && (!selected || start >= (selected.starts_on || ""))) {
      selected = period;
    }
  }
  if (!selected || (selected.ends_on && selected.ends_on < date)) return null;
  return normalizePriceValue(selected.price);
}

export function getDoctorBasePrice(doctor, appointmentDate = null) {
  return priceForDate(doctor?.extra, "price", appointmentDate);
}

export function getDoctorChildPrice(doctor, appointmentDate = null) {
  return priceForDate(doctor?.extra, "price_child", appointmentDate);
}

export function getBranchPromoPrice(branch, appointmentDate = null) {
  return priceForDate(branch, "price", appointmentDate);
}

export function getBranchChildPromoPrice(branch, appointmentDate = null) {
  return priceForDate(branch, "price_child", appointmentDate);
}

export function isChildPatient(patientBirthDate) {
  const ageMonths = calculateAgeMonthsFromBirthDate(patientBirthDate);

  return Number.isFinite(ageMonths) && ageMonths < CHILD_MAX_AGE_MONTHS;
}

export function getDoctorAgeAwarePrice(doctor, patientBirthDate = null, appointmentDate = null) {
  const adultPrice = getDoctorBasePrice(doctor, appointmentDate);
  const childPrice = getDoctorChildPrice(doctor, appointmentDate);
  const field = isChildPatient(patientBirthDate) ? "price_child" : "price";

  // Explicitly removed/expired prices must not reappear from another age category.
  if (hasManagedPrice(doctor?.extra, field)) {
    return field === "price_child" ? childPrice : adultPrice;
  }

  if (adultPrice && childPrice) {
    return isChildPatient(patientBirthDate) ? childPrice : adultPrice;
  }

  return adultPrice || childPrice || null;
}

export function getBranchAgeAwarePrice(branch, patientBirthDate = null, appointmentDate = null) {
  return isChildPatient(patientBirthDate)
    ? getBranchChildPromoPrice(branch, appointmentDate)
    : getBranchPromoPrice(branch, appointmentDate);
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
  const doctorPrice = getDoctorAgeAwarePrice(doctor, patientBirthDate, options?.appointmentDate);

  if (
    priority === "doctor-only" ||
    isDoctorExcludedFromBranchPromoPrice(doctor)
  ) {
    return priceResult(doctorPrice, "doctor");
  }

  const branchPrice = getBranchAgeAwarePrice(branch, patientBirthDate, options?.appointmentDate);

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
