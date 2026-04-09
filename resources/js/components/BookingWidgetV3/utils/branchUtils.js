function normalizeBranchText(value) {
  if (value === null || value === undefined) {
    return null;
  }

  const normalized = String(value).trim();

  return normalized || null;
}

export function getBranchMetro(branch) {
  return normalizeBranchText(branch?.metro);
}

export function getBranchAddressLine(branch) {
  const city = normalizeBranchText(branch?.city);
  const address = normalizeBranchText(branch?.address);
  const parts = [city, address].filter(Boolean);

  if (parts.length) {
    return parts.join(", ");
  }

  return (
    normalizeBranchText(branch?.name) ||
    normalizeBranchText(branch?.title) ||
    "Филиал"
  );
}

export function getBranchFullLocation(branch) {
  const city = normalizeBranchText(branch?.city);
  const metro = normalizeBranchText(branch?.metro);
  const address = normalizeBranchText(branch?.address);
  const parts = [city, metro, address].filter(Boolean);

  if (parts.length) {
    return parts.join(", ");
  }

  return (
    normalizeBranchText(branch?.name) ||
    normalizeBranchText(branch?.title) ||
    "Филиал"
  );
}
