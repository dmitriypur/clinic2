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

  return normalized === "doctor" || normalized === "clinic"
    ? normalized
    : null;
}

function normalizeBoolean(value) {
  if (value === true) {
    return true;
  }

  const normalized = normalizeString(value)?.toLowerCase();

  return normalized === "true" || normalized === "1" || normalized === "yes";
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
    skipBirthDate: entry === "doctor" && normalizeBoolean(input.skipBirthDate),
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
    skipBirthDate: element.dataset.skipBirthDate,
  });
}
