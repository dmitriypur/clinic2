import { extractDoctorMinimumAge } from "./doctorAgeUtils";

export function doctorExternalUuid(doctor) {
  const raw = doctor?.external_id || doctor?.uuid || null;
  if (!raw) {
    return null;
  }

  const normalized = String(raw).trim().toLowerCase();
  return normalized || null;
}

export function getDoctorExternalUuids(doctors) {
  return normalizeUuidList(
    uniqueDoctors(doctors).map((doctor) => doctorExternalUuid(doctor))
  );
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

export function mergeDoctorsWithSitePayload(doctors, payload) {
  const list = uniqueDoctors(doctors);
  if (!list.length) {
    return [];
  }

  const siteByUuid = buildSiteDoctorsByUuid(normalizeSiteDoctorsPayload(payload));

  return list
    .filter((doctor) => {
      const uuid = doctorExternalUuid(doctor);
      return Boolean(uuid && siteByUuid[uuid]);
    })
    .map((doctor) => {
      const uuid = doctorExternalUuid(doctor);
      return mergeDoctorWithSiteData(doctor, siteByUuid[uuid]);
    })
    .filter(Boolean);
}

export function sortDoctorsByOrders(
  doctors,
  { primaryOrders = {}, fallbackOrders = {}, resolveTieBreaker = null } = {}
) {
  return [...(doctors || [])]
    .map((doctor, index) => {
      const uuid = doctorExternalUuid(doctor);
      const primaryOrder = uuid ? primaryOrders[uuid] : null;
      const fallbackOrder = uuid ? fallbackOrders[uuid] : null;
      const hasPrimaryOrder = Number.isFinite(Number(primaryOrder));
      const hasFallbackOrder = Number.isFinite(Number(fallbackOrder));

      let priority = 2;
      let sortOrder = null;

      if (hasPrimaryOrder) {
        priority = 0;
        sortOrder = Number(primaryOrder);
      } else if (hasFallbackOrder) {
        priority = 1;
        sortOrder = Number(fallbackOrder);
      }

      const tieBreaker = typeof resolveTieBreaker === "function"
        ? resolveTieBreaker(doctor)
        : Number.MAX_SAFE_INTEGER;

      return {
        doctor,
        index,
        priority,
        sortOrder,
        tieBreaker: Number.isFinite(tieBreaker)
          ? tieBreaker
          : Number.MAX_SAFE_INTEGER,
      };
    })
    .sort((left, right) => {
      if (left.priority !== right.priority) {
        return left.priority - right.priority;
      }

      if (left.sortOrder === null && right.sortOrder === null) {
        if (left.tieBreaker !== right.tieBreaker) {
          return left.tieBreaker - right.tieBreaker;
        }

        return left.index - right.index;
      }

      if (left.sortOrder === null) {
        return 1;
      }

      if (right.sortOrder === null) {
        return -1;
      }

      if (left.sortOrder !== right.sortOrder) {
        return left.sortOrder - right.sortOrder;
      }

      if (left.tieBreaker !== right.tieBreaker) {
        return left.tieBreaker - right.tieBreaker;
      }

      return left.index - right.index;
    })
    .map(({ doctor }) => doctor);
}

export function sortDoctorsByMinimumAge(
  doctors,
  { primaryOrders = {}, fallbackOrders = {} } = {}
) {
  return sortDoctorsByOrders(doctors, {
    primaryOrders,
    fallbackOrders,
    resolveTieBreaker: (doctor) => extractDoctorMinimumAge(doctor),
  });
}
