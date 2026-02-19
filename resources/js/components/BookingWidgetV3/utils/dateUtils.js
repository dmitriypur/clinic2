export function formatDateForApi(date) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
    return null;
  }

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

export function parseApiDate(value) {
  const parts = String(value || "").split("-");
  if (parts.length !== 3) {
    return null;
  }

  const [year, month, day] = parts.map((part) => Number(part));
  if (!year || !month || !day) {
    return null;
  }

  const parsed = new Date(year, month - 1, day);

  if (
    parsed.getFullYear() !== year ||
    parsed.getMonth() !== month - 1 ||
    parsed.getDate() !== day
  ) {
    return null;
  }

  return parsed;
}

export function addMonthsSafe(value, delta) {
  const baseDate =
    value instanceof Date && !Number.isNaN(value.getTime())
      ? new Date(value.getTime())
      : new Date();

  const day = baseDate.getDate();

  baseDate.setDate(1);
  baseDate.setMonth(baseDate.getMonth() + Number(delta || 0));

  const lastDay = new Date(
    baseDate.getFullYear(),
    baseDate.getMonth() + 1,
    0
  ).getDate();

  baseDate.setDate(Math.min(day, lastDay));

  return baseDate;
}

export function getMonthRange(value) {
  const base =
    value instanceof Date && !Number.isNaN(value.getTime())
      ? value
      : new Date(value || Date.now());

  const start = new Date(base.getFullYear(), base.getMonth(), 1);
  const end = new Date(base.getFullYear(), base.getMonth() + 1, 0);

  return {
    start,
    end,
    dateFrom: formatDateForApi(start),
    dateTo: formatDateForApi(end),
  };
}
