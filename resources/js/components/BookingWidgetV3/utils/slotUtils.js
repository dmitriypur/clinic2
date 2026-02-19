const OCCUPIED_STATUSES = ["occupied", "booked", "busy"];
const UNAVAILABLE_STATUSES = ["occupied", "booked", "busy", "closed", "disabled"];
const AVAILABLE_STATUSES = ["free", "available", "open"];

export function isTrueLike(value) {
  return (
    value === true ||
    value === 1 ||
    value === "1" ||
    String(value).toLowerCase() === "true"
  );
}

export function parseSlotStatus(value) {
  return String(value || "").toLowerCase();
}

export function isSlotAvailable(slot, nowTs = Date.now()) {
  if (!slot) {
    return false;
  }

  const status = parseSlotStatus(slot.status);
  const hasIsAvailable = Object.prototype.hasOwnProperty.call(slot, "is_available");
  const hasFree = Object.prototype.hasOwnProperty.call(slot, "free");
  const hasIsOccupied = Object.prototype.hasOwnProperty.call(slot, "is_occupied");
  const hasIsPast = Object.prototype.hasOwnProperty.call(slot, "is_past");

  const availabilitySignals = [];

  if (hasIsAvailable) {
    availabilitySignals.push(isTrueLike(slot.is_available));
  }

  if (hasFree) {
    availabilitySignals.push(isTrueLike(slot.free));
  }

  if (status) {
    if (AVAILABLE_STATUSES.includes(status)) {
      availabilitySignals.push(true);
    }

    if (UNAVAILABLE_STATUSES.includes(status)) {
      availabilitySignals.push(false);
    }
  }

  const available = availabilitySignals.length
    ? availabilitySignals.some(Boolean)
    : true;

  const occupied = hasIsOccupied
    ? isTrueLike(slot.is_occupied)
    : status
    ? OCCUPIED_STATUSES.includes(status)
    : false;

  const past = hasIsPast
    ? isTrueLike(slot.is_past)
    : slot.datetime
    ? new Date(slot.datetime).getTime() < nowTs
    : false;

  return available && !occupied && !past;
}

export function areSameSlot(leftSlot, rightSlot) {
  if (!leftSlot || !rightSlot) {
    return false;
  }

  if (leftSlot.id != null && rightSlot.id != null) {
    return leftSlot.id === rightSlot.id;
  }

  if (leftSlot.datetime && rightSlot.datetime) {
    return leftSlot.datetime === rightSlot.datetime;
  }

  return leftSlot.time === rightSlot.time;
}

export function slotComparableValue(slot) {
  if (!slot) {
    return Number.MAX_SAFE_INTEGER;
  }

  if (slot.datetime) {
    const timestamp = new Date(slot.datetime).getTime();
    if (!Number.isNaN(timestamp)) {
      return timestamp;
    }
  }

  if (slot.time) {
    const [hours, minutes] = String(slot.time).split(":").map(Number);
    if (!Number.isNaN(hours) && !Number.isNaN(minutes)) {
      return hours * 60 + minutes;
    }
  }

  return Number.MAX_SAFE_INTEGER;
}
