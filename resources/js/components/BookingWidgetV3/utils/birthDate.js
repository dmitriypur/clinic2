export function parseBirthDateDisplay(value) {
  const match = String(value || "")
    .trim()
    .match(/^(\d{2})\.(\d{2})\.(\d{4})$/);

  if (!match) {
    return null;
  }

  const day = Number(match[1]);
  const month = Number(match[2]);
  const year = Number(match[3]);
  const date = new Date(year, month - 1, day);

  if (
    !Number.isFinite(day) ||
    !Number.isFinite(month) ||
    !Number.isFinite(year) ||
    date.getFullYear() !== year ||
    date.getMonth() !== month - 1 ||
    date.getDate() !== day
  ) {
    return null;
  }

  return date;
}

export function isFutureBirthDate(value, now = new Date()) {
  const birthDate =
    value instanceof Date && !Number.isNaN(value.getTime())
      ? value
      : parseBirthDateDisplay(value);

  if (!birthDate) {
    return false;
  }

  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const normalizedBirthDate = new Date(
    birthDate.getFullYear(),
    birthDate.getMonth(),
    birthDate.getDate()
  );

  return normalizedBirthDate.getTime() > today.getTime();
}

export function birthDateDisplayToIso(value) {
  const parsed = parseBirthDateDisplay(value);

  if (!parsed || isFutureBirthDate(parsed)) {
    return null;
  }

  const year = parsed.getFullYear();
  const month = String(parsed.getMonth() + 1).padStart(2, "0");
  const day = String(parsed.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

export function birthDateIsoToDisplay(value) {
  const match = String(value || "")
    .trim()
    .match(/^(\d{4})-(\d{2})-(\d{2})$/);

  if (!match) {
    return "";
  }

  return `${match[3]}.${match[2]}.${match[1]}`;
}

export function validateBirthDateDisplay(value) {
  const normalized = String(value || "").trim();

  if (!normalized) {
    return "Укажите дату рождения";
  }

  const parsed = parseBirthDateDisplay(normalized);

  if (!parsed) {
    return "Укажите корректную дату рождения";
  }

  if (isFutureBirthDate(parsed)) {
    return "Дата рождения не может быть в будущем";
  }

  return "";
}
