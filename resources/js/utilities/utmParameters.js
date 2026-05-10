const UTM_KEYS = [
  "utm_source",
  "utm_medium",
  "utm_campaign",
  "utm_content",
  "utm_term",
];

export function getUtmParameters() {
  const params = new URLSearchParams(window.location.search);
  const configuredUtm =
    window.config?.utm && typeof window.config.utm === "object"
      ? window.config.utm
      : {};

  const utm = UTM_KEYS.reduce((utm, key) => {
    const value = params.get(key) || configuredUtm[key];

    if (value) {
      utm[key] = value;
    }

    return utm;
  }, {});

  return Object.keys(utm).length > 0 ? utm : { default_site: "organic" };
}
