export function sortBranchesByEnabled(branches) {
  return [...(branches || [])].sort((a, b) => {
    const aEnabled = a?.enabled !== false;
    const bEnabled = b?.enabled !== false;
    if (aEnabled === bEnabled) {
      return 0;
    }
    return aEnabled ? -1 : 1;
  });
}

export function pickBranchByCurrentOrFirst(branches, currentBranchId) {
  if (!Array.isArray(branches) || !branches.length) {
    return null;
  }

  return (
    branches.find(
      (branch) => Number(branch?.id) === Number(currentBranchId)
    ) || branches[0]
  );
}

export function getFirstAvailableSlot(
  slots,
  isSlotAvailable,
  slotComparableValue
) {
  return (
    (slots || [])
      .filter((slot) => isSlotAvailable(slot))
      .sort((a, b) => slotComparableValue(a) - slotComparableValue(b))[0] ||
    null
  );
}

export function sortResolvedByFirstAvailable(resolved, slotComparableValue) {
  return [...(resolved || [])]
    .filter((item) => item?.firstAvailable)
    .sort(
      (a, b) =>
        slotComparableValue(a.firstAvailable) -
        slotComparableValue(b.firstAvailable)
    );
}

export function buildDoctorShiftMap(resolved) {
  return (resolved || []).reduce((acc, item) => {
    const doctorId = item?.doctor?.id;
    if (doctorId != null) {
      acc[String(doctorId)] =
        Array.isArray(item.slots) && item.slots.length > 0;
    }
    return acc;
  }, {});
}

export function pickClinicFlowSelectedEntry({
  resolved,
  withAvailable,
  keepSelectedDoctor = false,
  selectedDoctorId = null,
  firstDoctorId = null,
}) {
  let selectedEntry = null;

  if (keepSelectedDoctor && selectedDoctorId != null) {
    selectedEntry =
      (resolved || []).find(
        (item) => item?.doctor?.id === selectedDoctorId
      ) || null;
  }

  if (!selectedEntry && firstDoctorId != null) {
    selectedEntry =
      (resolved || []).find((item) => item?.doctor?.id === firstDoctorId) ||
      null;
  }

  if (!selectedEntry) {
    selectedEntry = (withAvailable || [])[0] || (resolved || [])[0] || null;
  }

  return selectedEntry;
}
