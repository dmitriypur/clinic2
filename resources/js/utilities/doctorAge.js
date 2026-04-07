function normalizeMonths(value) {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const number = Number(value);

  return Number.isFinite(number) && number >= 0 ? number : null;
}

function normalizeTemplate(value) {
  const template = String(value || "").trim();

  return template || null;
}

export function getDoctorAgeRange(doctor) {
  return {
    minAgeMonths:
      normalizeMonths(doctor?.age_min_months) ??
      normalizeMonths(doctor?.extra?.age_min_months),
    maxAgeMonths:
      normalizeMonths(doctor?.age_max_months) ??
      normalizeMonths(doctor?.extra?.age_max_months),
    receivesText:
      normalizeTemplate(doctor?.receives_text) ??
      normalizeTemplate(doctor?.extra?.receives_text),
  };
}

export function formatAgeMonths(months) {
  const normalized = normalizeMonths(months);

  if (normalized === null) {
    return "";
  }

  if (normalized === 0) {
    return "рождения";
  }

  const years = Math.floor(normalized / 12);
  const restMonths = normalized % 12;
  const parts = [];

  if (years > 0) {
    parts.push(`${years} ${declension(years, "года", "лет")}`);
  }

  if (restMonths > 0) {
    parts.push(`${restMonths} ${declension(restMonths, "месяца", "месяцев")}`);
  }

  if (!parts.length) {
    return `${normalized} ${declension(normalized, "месяца", "месяцев")}`;
  }

  return parts.join(" ");
}

export function renderDoctorReceivesTemplate(template, { minAgeMonths, maxAgeMonths } = {}) {
  const normalizedTemplate = normalizeTemplate(template);

  if (!normalizedTemplate) {
    return "";
  }

  const requiresMin = normalizedTemplate.includes("{min}");
  const requiresMax = normalizedTemplate.includes("{max}");

  if ((requiresMin && normalizeMonths(minAgeMonths) === null) || (requiresMax && normalizeMonths(maxAgeMonths) === null)) {
    return "";
  }

  return normalizedTemplate
    .replaceAll("{min}", formatAgeMonths(minAgeMonths))
    .replaceAll("{max}", formatAgeMonths(maxAgeMonths))
    .trim();
}

export function buildDoctorReceivesDisplay({ minAgeMonths = null, maxAgeMonths = null, receivesText = null } = {}) {
  const renderedTemplate = renderDoctorReceivesTemplate(receivesText, {
    minAgeMonths,
    maxAgeMonths,
  });

  if (renderedTemplate) {
    return renderedTemplate;
  }

  const min = normalizeMonths(minAgeMonths);
  const max = normalizeMonths(maxAgeMonths);

  if (min === null && max === null) {
    return "";
  }

  if (min === 0 && max !== null) {
    return `Ведет прием с 0 до ${formatAgeMonths(max)}`;
  }

  if (min !== null && max !== null) {
    return `Ведет прием с ${formatAgeMonths(min)} до ${formatAgeMonths(max)}`;
  }

  if (min === 0) {
    return "Ведет прием с 0";
  }

  if (min !== null) {
    return `Ведет прием с ${formatAgeMonths(min)}`;
  }

  return `Ведет прием до ${formatAgeMonths(max)}`;
}

export function getDoctorReceivesDisplay(doctor) {
  const directDisplay = String(
    doctor?.receives_display || doctor?.extra?.receives_display || ""
  ).trim();

  if (directDisplay) {
    return directDisplay;
  }

  return buildDoctorReceivesDisplay(getDoctorAgeRange(doctor));
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

export function calculateAgeMonthsFromBirthDate(value, now = new Date()) {
  const birthDate = parseBirthDateInput(value);

  if (!birthDate) {
    return null;
  }

  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

  if (birthDate.getTime() > today.getTime()) {
    return null;
  }

  let months =
    (today.getFullYear() - birthDate.getFullYear()) * 12 +
    (today.getMonth() - birthDate.getMonth());

  if (today.getDate() < birthDate.getDate()) {
    months -= 1;
  }

  return Math.max(months, 0);
}

export function calculateAgeYearsFromBirthDate(value, now = new Date()) {
  const months = calculateAgeMonthsFromBirthDate(value, now);

  return Number.isFinite(months) ? Math.floor(months / 12) : null;
}

export function formatDateForInput(date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

function declension(value, singular, plural) {
  const mod100 = value % 100;

  if (mod100 >= 11 && mod100 <= 14) {
    return plural;
  }

  return value % 10 === 1 ? singular : plural;
}
