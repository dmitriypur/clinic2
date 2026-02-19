import { parseApiDate } from "./dateUtils";

export function parseCalendarAvailabilityDateList(payload) {
  const items = Array.isArray(payload?.data)
    ? payload.data
    : Array.isArray(payload?.items)
    ? payload.items
    : Array.isArray(payload)
    ? payload
    : [];

  const dateSet = new Set();

  items.forEach((item) => {
    if (Number(item?.available_slots || 0) <= 0 || !item?.date) {
      return;
    }

    const date = parseApiDate(item.date);
    if (!date) {
      return;
    }

    const year = String(date.getFullYear()).padStart(4, "0");
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    dateSet.add(`${year}-${month}-${day}`);
  });

  return Array.from(dateSet).sort();
}
