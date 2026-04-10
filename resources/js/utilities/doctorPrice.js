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

export function getBranchPromoPrice(branch) {
  return normalizePriceValue(branch?.price);
}

export function getDoctorDisplayPrice(doctor, branch = null) {
  return getBranchPromoPrice(branch) || getDoctorBasePrice(doctor);
}

export function getDoctorDisplayPriceSource(doctor, branch = null) {
  if (getBranchPromoPrice(branch)) {
    return "branch";
  }

  if (getDoctorBasePrice(doctor)) {
    return "doctor";
  }

  return null;
}
