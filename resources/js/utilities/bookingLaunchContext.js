export const BOOKING_LAUNCH_SELECTOR =
  "[data-appointment-entry], [data-doctor-id], [data-branch-id], [data-clinic-id]";

function normalizeString(value) {
  if (value === null || value === undefined) {
    return null;
  }

  const normalized = String(value).trim();
  return normalized || null;
}

function normalizeEntry(value) {
  const normalized = normalizeString(value)?.toLowerCase() || null;

  if (normalized === "doctor" || normalized === "clinic") {
    return normalized;
  }

  return normalized === "branch" ? "clinic" : null;
}

function normalizeSearchParams(input) {
  if (input instanceof URLSearchParams) {
    return input;
  }

  if (typeof input === "string") {
    return new URLSearchParams(input.startsWith("?") ? input.slice(1) : input);
  }

  return new URLSearchParams();
}

function firstSearchParam(params, names = []) {
  for (const name of names) {
    const value = normalizeString(params.get(name));

    if (value) {
      return value;
    }
  }

  return null;
}

export function normalizeBookingLaunchContext(input = null) {
  if (!input || typeof input !== "object") {
    return null;
  }

  const doctorId = normalizeString(input.doctorId || input.doctor_id);
  const branchId = normalizeString(
    input.branchId || input.branch_id || input.clinicId || input.clinic_id
  );
  let entry = normalizeEntry(
    input.entry || input.appointmentEntry || input.appointment_entry
  );

  if (!entry) {
    entry = normalizeEntry(input.bookingStartMode);
  }

  if (!entry && doctorId && branchId) {
    return null;
  }

  if (!entry && doctorId) {
    entry = "doctor";
  }

  if (!entry && branchId) {
    entry = "clinic";
  }

  if (!entry) {
    return null;
  }

  return {
    entry,
    doctorId: entry === "doctor" ? doctorId : null,
    branchId: entry === "clinic" ? branchId : null,
  };
}

export function buildBookingLaunchContextFromElement(element) {
  if (!element?.dataset) {
    return null;
  }

  return normalizeBookingLaunchContext({
    appointmentEntry: element.dataset.appointmentEntry,
    doctorId: element.dataset.doctorId,
    branchId: element.dataset.branchId || element.dataset.clinicId,
  });
}

export function buildBookingLaunchContextFromSearchParams(input = "") {
  const params = normalizeSearchParams(input);
  const explicitEntry = firstSearchParam(params, [
    "appointment",
    "appointment_entry",
    "booking_entry",
    "booking_mode",
  ]);
  const prefixedDoctorId = firstSearchParam(params, [
    "booking_doctor",
    "booking_doctor_id",
  ]);
  const prefixedBranchId = firstSearchParam(params, [
    "booking_branch",
    "booking_branch_id",
    "booking_clinic",
    "booking_clinic_id",
  ]);
  const entry =
    normalizeEntry(explicitEntry) ||
    (prefixedDoctorId ? "doctor" : null) ||
    (prefixedBranchId ? "clinic" : null);

  if (!entry) {
    return null;
  }

  const doctorId =
    prefixedDoctorId ||
    (entry === "doctor"
      ? firstSearchParam(params, ["doctor_id", "doctor", "doctor_uuid"])
      : null);
  const branchId =
    prefixedBranchId ||
    (entry === "clinic"
      ? firstSearchParam(params, ["branch_id", "branch", "clinic_id", "clinic"])
      : null);

  return normalizeBookingLaunchContext({
    entry,
    doctorId,
    branchId,
  });
}
