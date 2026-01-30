const cache = new Map();

export function getCache(key) {
  const hit = cache.get(key);
  if (!hit) return null;
  if (hit.expiresAt && Date.now() > hit.expiresAt) {
    cache.delete(key);
    return null;
  }
  return hit.value;
}

export function setCache(key, value, ttlMs = 300000) {
  cache.set(key, {
    value,
    expiresAt: ttlMs ? Date.now() + ttlMs : null,
  });
}

export function clearCache() {
  cache.clear();
}
