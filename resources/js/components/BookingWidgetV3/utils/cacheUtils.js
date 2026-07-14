export function buildCompositeCacheKey(parts = []) {
  return (Array.isArray(parts) ? parts : [parts])
    .map((part) => String(part ?? "none"))
    .join("|");
}

export function buildVersionedCacheKey(version, parts = []) {
  return buildCompositeCacheKey([
    version,
    ...(Array.isArray(parts) ? parts : [parts]),
  ]);
}

export function isCurrentCacheVersion(requestVersion, currentVersion) {
  return requestVersion === currentVersion;
}

export function getTimestampedCacheEntry(cacheObject, key, ttlMs) {
  const cached = cacheObject?.[key];
  if (!cached) {
    return null;
  }

  if (Date.now() - cached.ts > ttlMs) {
    delete cacheObject[key];
    return null;
  }

  return cached;
}

export function setTimestampedCacheEntry(cacheObject, key, value) {
  cacheObject[key] = {
    ts: Date.now(),
    ...value,
  };
}

export function trimTimestampedCache(cacheObject, maxEntries = 100) {
  const keys = Object.keys(cacheObject || {});
  if (keys.length <= maxEntries) {
    return;
  }

  keys
    .sort((a, b) => (cacheObject[a]?.ts || 0) - (cacheObject[b]?.ts || 0))
    .slice(0, keys.length - maxEntries)
    .forEach((key) => {
      delete cacheObject[key];
    });
}

export function clearObjectCaches(...cacheObjects) {
  cacheObjects
    .filter((cacheObject) => cacheObject && typeof cacheObject === "object")
    .forEach((cacheObject) => {
      Object.keys(cacheObject).forEach((key) => {
        delete cacheObject[key];
      });
    });
}

export function resetBookingLoadingFlags(state) {
  [
    "loadingClinics",
    "loadingCityBranches",
    "loadingDoctors",
    "loadingDateFlowDoctors",
    "loadingSlots",
    "loadingDoctorFlowBranches",
  ].forEach((key) => {
    state[key] = false;
  });
}
