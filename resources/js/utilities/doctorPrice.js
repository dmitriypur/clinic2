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
  patientBirthDate = null,
  options = {}
) {
  const priority = options?.priority || "branch-first";
  const doctorPrice = getDoctorAgeAwarePrice(doctor, patientBirthDate);
  const branchPrice = getBranchPromoPrice(branch);

  if (priority === "doctor-first") {
    return doctorPrice || branchPrice;
  }

  if (priority === "doctor-only") {
    return doctorPrice;
  }

  return branchPrice || doctorPrice;
}

export function getDoctorDisplayPriceSource(
  doctor,
  branch = null,
  patientBirthDate = null,
  options = {}
) {
  const priority = options?.priority || "branch-first";
  const doctorPrice = getDoctorAgeAwarePrice(doctor, patientBirthDate);
  const branchPrice = getBranchPromoPrice(branch);

  if (priority === "doctor-first") {
    if (doctorPrice) {
      return "doctor";
    }

    if (branchPrice) {
      return "branch";
    }
  } else if (priority === "doctor-only") {
    if (doctorPrice) {
      return "doctor";
    }
  } else {
    if (branchPrice) {
      return "branch";
    }

    if (doctorPrice) {
      return "doctor";
    }
  }

  return null;
}
