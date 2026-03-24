export function getCurrentCityName() {
  const directName = String(window.currentCity?.name || "").trim();
  if (directName) {
    return directName;
  }

  const configuredCities = Array.isArray(window.config?.cities)
    ? window.config.cities
    : [];
  const currentConfiguredCity = configuredCities.find((city) => city?.is_current);
  const configuredName = String(currentConfiguredCity?.name || "").trim();

  return configuredName || "";
}
