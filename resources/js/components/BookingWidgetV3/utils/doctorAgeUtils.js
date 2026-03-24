export function getDoctorReceivesText(doctor) {
  const value = doctor?.extra?.receives || "";
  return String(value || "").trim();
}

export function extractMinimumAgeFromReceivesText(value) {
  const text = String(value || "").trim();
  if (!text) {
    return null;
  }

  const patterns = [
    /(?:с|от)\s*(\d+)\s*(?:года?|лет)/i,
    /дет(?:ей|и)\s*(?:с|от)?\s*(\d+)/i,
    /(\d+)\s*(?:года?|лет)/i,
    /(\d+)/,
  ];

  for (const pattern of patterns) {
    const match = text.match(pattern);
    if (!match) {
      continue;
    }

    const age = Number(match[1]);
    if (Number.isFinite(age) && age >= 0) {
      return age;
    }
  }

  return null;
}

export function extractDoctorMinimumAge(doctor) {
  return extractMinimumAgeFromReceivesText(getDoctorReceivesText(doctor));
}

export function parseBirthDateInput(value) {
  const match = String(value || "")
    .trim()
    .match(/^(\d{4})-(\d{2})-(\d{2})$/);

  if (!match) {
    return null;
  }

  const year = Number(match[1]);
  const month = Number(match[2]);
  const day = Number(match[3]);
  const date = new Date(year, month - 1, day);

  if (
    !Number.isFinite(year) ||
    !Number.isFinite(month) ||
    !Number.isFinite(day) ||
    date.getFullYear() !== year ||
    date.getMonth() !== month - 1 ||
    date.getDate() !== day
  ) {
    return null;
  }

  return date;
}

export function calculateAgeFromBirthDate(value, now = new Date()) {
  const birthDate = parseBirthDateInput(value);
  if (!birthDate) {
    return null;
  }

  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  if (birthDate.getTime() > today.getTime()) {
    return null;
  }

  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();
  const dayDiff = today.getDate() - birthDate.getDate();

  if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
    age -= 1;
  }

  return Math.max(age, 0);
}

export function formatDoctorMinimumAgeText(age) {
  if (!Number.isFinite(age) || age < 0) {
    return "";
  }

  if (age === 1) {
    return "с 1 года";
  }

  return `с ${age} лет`;
}

export function formatDateForInput(date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}
