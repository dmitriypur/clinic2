export function doctorExternalUuid(doctor) {
  const raw = doctor?.external_id || doctor?.uuid || null;
  if (!raw) {
    return null;
  }

  const normalized = String(raw).trim().toLowerCase();
  return normalized || null;
}

export function normalizeUuidList(uuids) {
  return Array.from(
    new Set(
      (uuids || [])
        .map((uuid) => String(uuid || "").trim().toLowerCase())
        .filter(Boolean)
    )
  ).sort();
}

export function uniqueDoctors(doctors) {
  const map = new Map();

  (doctors || []).forEach((doctor) => {
    const key = doctorExternalUuid(doctor) || String(doctor?.id || "");
    if (!key || map.has(key)) {
      return;
    }
    map.set(key, doctor);
  });

  return Array.from(map.values());
}

export function normalizeSiteDoctorsPayload(payload) {
  return Array.isArray(payload?.data)
    ? payload.data
    : Array.isArray(payload)
    ? payload
    : [];
}

export function buildSiteDoctorsByUuid(siteDoctors) {
  return (siteDoctors || []).reduce((acc, doctor) => {
    const uuid = String(doctor?.uuid || "").toLowerCase().trim();
    if (uuid) {
      acc[uuid] = doctor;
    }
    return acc;
  }, {});
}

export function mergeDoctorWithSiteData(doctor, siteDoctor) {
  if (!doctor || !siteDoctor) {
    return doctor || null;
  }

  return {
    ...doctor,
    local_uuid: siteDoctor.uuid,
    ulid: siteDoctor.ulid || doctor.ulid,
    name: siteDoctor.full_name || doctor.name,
    full_name: siteDoctor.full_name || doctor.full_name || doctor.name,
    speciality: siteDoctor.speciality || doctor.speciality,
    specialization: siteDoctor.speciality || doctor.specialization,
    job_title: siteDoctor.job_title || doctor.job_title,
    excerpt: siteDoctor.excerpt || doctor.excerpt,
    video_url: siteDoctor.video_url || doctor.video_url,
    avatar_url: siteDoctor.avatar_url || doctor.avatar_url,
    avatar_image: siteDoctor.avatar_image || doctor.avatar_image,
    extra: siteDoctor.extra || doctor.extra || {},
    seniority:
      siteDoctor.extra?.seniority ||
      doctor.seniority ||
      doctor.extra?.seniority,
    receives:
      siteDoctor.extra?.receives ||
      doctor.receives ||
      doctor.extra?.receives,
  };
}
