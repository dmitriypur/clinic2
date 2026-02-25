<template>
  <Modal
    :open="open"
    :zIndexOverride="49"
    closeButtonHiddenOnMobile
    @close="handleClose"
  >
    <div class="relative h-auto">
      <StartStep
        v-if="currentStep === 'start'"
        :mode="mode"
        @close="handleClose"
        @select-mode="handleModeSelect"
        @leave-request="handleLeaveRequest"
      />

      <DoctorSelectStep
        v-else-if="currentStep === 'doctor-select'"
        :doctors="doctors"
        :selectedDoctorId="selectedDoctor?.id"
        :loading="loadingDoctors"
        @close="handleClose"
        @select="handleDoctorSelect"
        @next="goToDoctorSchedule"
        @back="goToStart"
      />

      <ClinicSelectStep
        v-else-if="currentStep === 'clinic-select'"
        :branches="cityBranches"
        :selectedBranchId="selectedBranch?.id"
        :loading="loadingCityBranches"
        @close="handleClose"
        @select-branch="handleBranchSelect"
        @next="goToClinicSchedule"
        @back="goToStart"
      />

      <DoctorScheduleStep
        v-else-if="currentStep === 'doctor-schedule'"
        :doctor="selectedDoctor"
        :clinic="selectedClinic"
        :branches="doctorFlowBranches"
        :selectedBranchId="selectedBranch?.id"
        :selectedDate="selectedDate"
        :highlightedDates="doctorFlowHighlightedDates"
        :slots="slots"
        :selectedSlot="selectedSlot"
        :loading="loadingSlots"
        :loadingBranches="loadingDoctorFlowBranches"
        @select-branch="handleBranchSelect"
        @select-date="handleDateSelect"
        @select-slot="handleSlotSelect"
        @next="goToForm"
        @back="goToDoctorSelect"
      />

      <ClinicScheduleStep
        v-else-if="currentStep === 'clinic-schedule'"
        :selectedDoctor="selectedDoctor"
        :doctors="clinicDoctors"
        :doctorShiftMap="clinicDoctorShiftMap"
        :selectedDoctorId="selectedDoctor?.id"
        :selectedDate="selectedDate"
        :highlightedDates="clinicFlowHighlightedDates"
        :slots="slots"
        :selectedSlot="selectedSlot"
        :loadingDoctors="loadingDoctors"
        :loading="loadingSlots"
        @select-doctor="handleDoctorSelect"
        @select-date="handleDateSelect"
        @select-slot="handleSlotSelect"
        @next="goToForm"
        @back="goToClinicSelect"
      />

      <PatientFormStep
        v-else-if="currentStep === 'form'"
        :selectedDoctor="selectedDoctor"
        :selectedClinic="selectedClinic"
        :selectedBranch="selectedBranch"
        :selectedDate="selectedDate"
        :selectedSlot="selectedSlot"
        :isSubmitting="isSubmitting"
        ref="patientForm"
        @close="handleClose"
        @back="goBackFromForm"
        @submit="handleFormSubmit"
      />

      <CallbackFormNew
        v-else-if="currentStep === 'leave-request'"
        button-content="Отправить"
        :target="callbackFormTarget"
        :showOnlineLink="true"
        @open-online="goToStart"
      />

      <SuccessStep
        v-else-if="currentStep === 'success'"
        :doctorName="selectedDoctor?.name"
        :clinicName="selectedClinic?.name"
        :branchName="selectedBranch?.name"
        :appointmentDate="selectedDate"
        :appointmentTime="selectedSlot?.time"
        @close="handleClose"
      />

      <div
        v-if="transitionLoading"
        class="absolute inset-0 z-30 flex min-h-[260px] items-center justify-center bg-white/85 backdrop-blur-[1px]"
      >
        <div class="flex flex-col items-center gap-3 text-interactive">
          <span class="h-8 w-8 animate-spin rounded-full border-2 border-surface-subdued border-t-action-primary"></span>
          <p class="text-sm font-semibold">Подбираем доступные варианты...</p>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script>
import bookingApi from "../../services/bookingApi";

const Modal = () => import("../Modal");
const StartStep = () => import("./components/StartStep.vue");
const DoctorSelectStep = () => import("./components/DoctorSelectStep.vue");
const ClinicSelectStep = () => import("./components/ClinicSelectStep.vue");
const DoctorScheduleStep = () => import("./components/DoctorScheduleStep.vue");
const ClinicScheduleStep = () => import("./components/ClinicScheduleStep.vue");
const PatientFormStep = () => import("./components/PatientFormStep.vue");
const CallbackFormNew = () => import("../CallbackForm/CallbackFormNew.vue");
const SuccessStep = () => import("./components/SuccessStep.vue");

export default {
  name: "BookingWidgetV3",
  components: {
    Modal,
    StartStep,
    DoctorSelectStep,
    ClinicSelectStep,
    DoctorScheduleStep,
    ClinicScheduleStep,
    PatientFormStep,
    CallbackFormNew,
    SuccessStep,
  },
  props: {
    open: {
      type: Boolean,
      default: false,
    },
    mode: {
      type: String,
      default: null, // 'doctor' | 'clinic'
    },
    callbackTarget: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      currentStep: "start",
      selectedDoctor: null,
      selectedClinic: null,
      selectedBranch: null,
      selectedDate: new Date(),
      selectedSlot: null,
      clinics: [],
      branches: [],
      cityBranches: [],
      doctors: [],
      clinicDoctors: [],
      clinicDoctorShiftMap: {},
      slots: [],
      doctorFlowBranches: [],
      doctorFlowHighlightedDates: [],
      clinicFlowHighlightedDates: [],
      allCities: [],
      doctorsCacheByCity: {},
      siteDoctorsCacheByUuids: {},
      slotsCacheByQuery: {},
      doctorsCacheTtlMs: 60 * 1000,
      siteDoctorsCacheTtlMs: 60 * 1000,
      loadingClinics: false,
      loadingCityBranches: false,
      loadingDoctors: false,
      loadingSlots: false,
      loadingDoctorFlowBranches: false,
      isSubmitting: false,
      formSourceStep: null,
      transitionLoading: false,
    };
  },
  computed: {
    currentCityId() {
      if (!this.allCities || !this.allCities.length) {
        return null;
      }

      // Нормализация имени (убираем "г." и приводим к нижнему регистру)
      const normalize = (str) => str ? str.toLowerCase().replace(/^г\.\s*/, '').trim() : '';
      const configuredCities = Array.isArray(window.config?.cities)
        ? window.config.cities
        : [];
      const currentConfiguredCity =
        configuredCities.find((city) => city?.is_current) || null;

      const cityNameCandidates = [
        window.currentCity?.name,
        currentConfiguredCity?.name,
        window.config?.state?.currentCity?.name,
      ].filter(Boolean);

      for (const cityName of cityNameCandidates) {
        const targetName = normalize(cityName);
        if (!targetName) {
          continue;
        }

        // 1. Пытаемся найти точное совпадение имени города
        const matchedCity = this.allCities.find(
          (city) => normalize(city?.name) === targetName
        );
        if (matchedCity) {
          return matchedCity.id;
        }

        // 2. Если точного нет, пробуем мягкое совпадение
        const partialMatch = this.allCities.find((city) => {
          const sourceName = normalize(city?.name);
          return sourceName.includes(targetName) || targetName.includes(sourceName);
        });
        if (partialMatch) {
          return partialMatch.id;
        }
      }

      // 3. Если не нашли, пробуем найти Москву как дефолт
      const moscow = this.allCities.find((city) => normalize(city?.name).includes("москва"));
      if (moscow) {
        return moscow.id;
      }

      // 4. Если совсем ничего не нашли, берем первый город из API
      return this.allCities[0].id;
    },
    allowedClinicIds() {
      const list = window.config?.booking?.allowedClinicIds;
      if (!Array.isArray(list)) {
        return [];
      }

      return list
        .map((id) => Number(id))
        .filter((id) => Number.isFinite(id));
    },
    callbackFormTarget() {
      return this.callbackTarget || "otpravka-formy";
    },
  },
  watch: {
    open: {
      async handler(val) {
        if (val) {
          await this.initCities();
          await Promise.all([this.loadClinics(), this.loadDoctorsByCity()]);
          await this.applyInitialMode();
        } else {
          this.resetState();
        }
      },
      immediate: true,
    },
    mode() {
      this.applyInitialMode();
    },
  },
  methods: {
    async withTransitionLoader(task) {
      const showDelayMs = 100;
      const minVisibleMs = 100;

      let showTimer = null;
      let shownAt = 0;
      let didShow = false;

      showTimer = setTimeout(() => {
        didShow = true;
        shownAt = Date.now();
        this.transitionLoading = true;
      }, showDelayMs);

      try {
        return await task();
      } finally {
        if (showTimer) {
          clearTimeout(showTimer);
        }

        if (!didShow) {
          this.transitionLoading = false;
          return;
        }

        const visibleForMs = Date.now() - shownAt;
        const remainingMs = Math.max(minVisibleMs - visibleForMs, 0);
        if (remainingMs > 0) {
          await new Promise((resolve) => setTimeout(resolve, remainingMs));
        }

        this.transitionLoading = false;
      }
    },
    async applyInitialMode() {
      if (!this.open || this.currentStep !== "start") {
        return;
      }

      if (this.mode === "doctor") {
        this.currentStep = "doctor-select";
        return;
      }

      if (this.mode === "clinic") {
        await this.withTransitionLoader(async () => {
          await this.openClinicFlowWithAutoBranchSkip();
        });
      }
    },
    async openClinicFlowWithAutoBranchSkip() {
      await this.loadCityBranches();

      if (this.cityBranches.length > 1) {
        this.currentStep = "clinic-select";
        return;
      }

      if (this.cityBranches.length === 1) {
        await this.handleBranchSelect(this.cityBranches[0]);
      } else {
        this.selectedClinic = null;
        this.selectedBranch = null;
      }
      await this.goToClinicSchedule();
    },
    isClinicAllowed(clinicId) {
      if (!this.allowedClinicIds.length) {
        return true;
      }

      return this.allowedClinicIds.includes(Number(clinicId));
    },
    doctorExternalUuid(doctor) {
      const raw = doctor?.external_id || doctor?.uuid || null;
      if (!raw) {
        return null;
      }

      const normalized = String(raw).trim().toLowerCase();
      return normalized || null;
    },
    normalizeUuidList(uuids) {
      return Array.from(
        new Set(
          (uuids || [])
            .map((uuid) => String(uuid || "").trim().toLowerCase())
            .filter(Boolean)
        )
      ).sort();
    },
    getSiteDoctorsFromCache(key) {
      const cached = this.siteDoctorsCacheByUuids[key];
      if (!cached) {
        return null;
      }

      if (Date.now() - cached.ts > this.siteDoctorsCacheTtlMs) {
        delete this.siteDoctorsCacheByUuids[key];
        return null;
      }

      return cached.payload;
    },
    setSiteDoctorsToCache(key, payload) {
      this.siteDoctorsCacheByUuids[key] = {
        ts: Date.now(),
        payload,
      };
    },
    uniqueDoctors(doctors) {
      const map = new Map();

      (doctors || []).forEach((doctor) => {
        const key = this.doctorExternalUuid(doctor) || String(doctor?.id || "");
        if (!key || map.has(key)) {
          return;
        }
        map.set(key, doctor);
      });

      return Array.from(map.values());
    },
    async enrichDoctorsWithSiteData(doctors) {
      const list = this.uniqueDoctors(doctors);
      if (!list.length) {
        return [];
      }

      const uuids = this.normalizeUuidList(
        list.map((doctor) => this.doctorExternalUuid(doctor))
      );

      if (!uuids.length) {
        return [];
      }

      try {
        const cacheKey = uuids.join(",");
        const cachedPayload = this.getSiteDoctorsFromCache(cacheKey);
        const response = cachedPayload || (await bookingApi.getSiteDoctorsByUuids(uuids));
        if (!cachedPayload) {
          this.setSiteDoctorsToCache(cacheKey, response);
        }
        const siteDoctors = Array.isArray(response?.data)
          ? response.data
          : Array.isArray(response)
          ? response
          : [];
        const siteByUuid = siteDoctors.reduce((acc, doctor) => {
          const uuid = String(doctor?.uuid || "").toLowerCase().trim();
          if (uuid) {
            acc[uuid] = doctor;
          }
          return acc;
        }, {});

        return list
          .filter((doctor) => {
            const uuid = this.doctorExternalUuid(doctor);
            return Boolean(uuid && siteByUuid[uuid]);
          })
          .map((doctor) => {
            const uuid = this.doctorExternalUuid(doctor);
            const siteDoctor = siteByUuid[uuid];

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
          });
      } catch (e) {
        return [];
      }
    },
    async initCities() {
      if (this.allCities.length > 0) return;
      try {
        const response = await bookingApi.getCities();
        this.allCities = response.data || response || [];
      } catch (e) {
        // silent for now
      }
    },
    async loadClinics() {
      if (!this.currentCityId) return;
      this.loadingClinics = true;
      try {
        const response = await bookingApi.getClinicsByCity(this.currentCityId);
        const list = response.data || response || [];
        this.clinics = list
          .filter((clinic) => this.isClinicAllowed(clinic.id))
          .map((c) => ({ ...c, enabled: true }));
      } finally {
        this.loadingClinics = false;
      }
    },
    async loadDoctorsByCity() {
      if (!this.currentCityId) return;

      const cached = this.doctorsCacheByCity[this.currentCityId];
      if (
        cached &&
        Array.isArray(cached.data) &&
        Date.now() - cached.ts <= this.doctorsCacheTtlMs
      ) {
        this.doctors = cached.data;
        return;
      }

      this.loadingDoctors = true;
      try {
        const response = await bookingApi.getDoctorsByCity(this.currentCityId);
        const doctors = response.data || response || [];
        this.doctors = await this.enrichDoctorsWithSiteData(doctors);
        this.doctorsCacheByCity[this.currentCityId] = {
          ts: Date.now(),
          data: this.doctors,
        };
      } finally {
        this.loadingDoctors = false;
      }
    },
    async loadDoctorsByClinic(clinicId, branchId = null) {
      if (!clinicId || !this.isClinicAllowed(clinicId)) {
        this.clinicDoctors = [];
        return;
      }
      this.loadingDoctors = true;
      try {
        const response = await bookingApi.getClinicDoctors(
          clinicId,
          null,
          branchId
        );
        const doctors = response.data || response || [];
        this.clinicDoctors = await this.enrichDoctorsWithSiteData(doctors);
      } finally {
        this.loadingDoctors = false;
      }
    },
    async loadBranches(clinicId) {
      if (!clinicId) return;
      const response = await bookingApi.getClinicBranches(clinicId, this.currentCityId);
      this.branches = response.data || response || [];
    },
    async loadCityBranches() {
      this.loadingCityBranches = true;
      try {
        if (!this.clinics.length) {
          await this.loadClinics();
        }

        const branchGroups = await Promise.all(
          this.clinics.map(async (clinic) => {
            try {
              const response = await bookingApi.getClinicBranches(
                clinic.id,
                this.currentCityId
              );
              const branches = response.data || response || [];

              return branches.map((branch) => ({
                ...branch,
                clinic_id: branch.clinic_id || clinic.id,
                clinic_name: clinic.name,
              }));
            } catch (e) {
              return [];
            }
          })
        );

        this.cityBranches = branchGroups.flat();
      } finally {
        this.loadingCityBranches = false;
      }
    },
    async loadDoctorFlowBranches(clinicId) {
      if (!clinicId) return;
      this.loadingDoctorFlowBranches = true;
      try {
        const response = await bookingApi.getClinicBranches(
          clinicId,
          this.currentCityId
        );
        const branches = response.data || response || [];
        if (!this.selectedDoctor) {
          this.doctorFlowBranches = branches.map((branch) => ({
            ...branch,
            enabled: true,
          }));
          return;
        }

        const checkedBranches = await Promise.all(
          branches.map(async (branch) => {
            try {
              const doctorResponse = await bookingApi.getClinicDoctors(
                clinicId,
                null,
                branch.id
              );
              const doctors = doctorResponse.data || doctorResponse || [];
              const hasDoctor = doctors.some(
                (doctor) => doctor.id === this.selectedDoctor.id
              );
              return { ...branch, enabled: hasDoctor };
            } catch (e) {
              return { ...branch, enabled: false };
            }
          })
        );

        const sorted = checkedBranches.sort((a, b) => {
          const aEnabled = a.enabled !== false;
          const bEnabled = b.enabled !== false;
          if (aEnabled === bEnabled) {
            return 0;
          }
          return aEnabled ? -1 : 1;
        });

        this.doctorFlowBranches = sorted;
        await this.selectDoctorFlowBranchByDate(clinicId, sorted);
      } finally {
        this.loadingDoctorFlowBranches = false;
      }
    },
    async selectDoctorFlowBranchByDate(clinicId, branches = this.doctorFlowBranches) {
      if (!clinicId || !this.selectedDoctor || !this.selectedDate) {
        this.selectedBranch = null;
        this.slots = [];
        this.selectedSlot = null;
        return;
      }

      const enabledBranches = (branches || []).filter(
        (branch) => branch.enabled !== false
      );

      if (!enabledBranches.length) {
        this.selectedBranch = null;
        this.slots = [];
        this.selectedSlot = null;
        return;
      }

      this.loadingSlots = true;
      try {
        const currentDate =
          this.selectedDate instanceof Date
            ? this.selectedDate
            : new Date(this.selectedDate || Date.now());
        const dateStr = this.formatDateForApi(currentDate);

        const branchSlotEntries = await Promise.all(
          enabledBranches.map(async (branch) => {
            try {
              const slots = await this.getDoctorSlotsWithCache({
                doctorId: this.selectedDoctor.id,
                clinicId,
                branchId: branch.id,
                dateStr,
              });
              return [String(branch.id), slots];
            } catch (e) {
              return [String(branch.id), []];
            }
          })
        );

        const branchSlotsMap = Object.fromEntries(branchSlotEntries);
        const branchesWithAvailableSlots = enabledBranches.filter((branch) => {
          const slots = branchSlotsMap[String(branch.id)] || [];
          return Array.isArray(slots) && slots.some((slot) => this.isSlotAvailable(slot));
        });

        if (!branchesWithAvailableSlots.length) {
          const currentBranchId = this.selectedBranch?.id;
          const fallbackBranch =
            enabledBranches.find(
              (branch) => Number(branch.id) === Number(currentBranchId)
            ) || enabledBranches[0] || null;

          this.selectedBranch = fallbackBranch;
          this.slots = fallbackBranch
            ? branchSlotsMap[String(fallbackBranch.id)] || []
            : [];
          this.selectedSlot = null;
          return;
        }

        const currentBranchId = this.selectedBranch?.id;
        const nextBranch =
          branchesWithAvailableSlots.find(
            (branch) => Number(branch.id) === Number(currentBranchId)
          ) || branchesWithAvailableSlots[0];

        this.selectedBranch = nextBranch;
        this.slots = branchSlotsMap[String(nextBranch.id)] || [];
        this.selectedSlot = null;
      } finally {
        this.loadingSlots = false;
      }
    },
    async loadSlots() {
      if (!this.selectedDoctor || !this.selectedDate) return;
      this.loadingSlots = true;
      try {
        const dateStr = this.formatDateForApi(this.selectedDate);
        this.slots = await this.getDoctorSlotsWithCache({
          doctorId: this.selectedDoctor.id,
          clinicId: this.selectedClinic?.id || null,
          branchId: this.selectedBranch?.id || null,
          dateStr,
        });
        this.selectedSlot = null;
      } finally {
        this.loadingSlots = false;
      }
    },
    getSlotsCacheKey({ doctorId, clinicId = null, branchId = null, dateStr }) {
      return [
        String(doctorId ?? "none"),
        String(clinicId ?? "none"),
        String(branchId ?? "none"),
        String(dateStr ?? "none"),
      ].join("|");
    },
    getSlotsFromCache(key) {
      const cached = this.slotsCacheByQuery[key];
      if (!cached) {
        return null;
      }

      const ttlMs = 30 * 1000;
      if (Date.now() - cached.ts > ttlMs) {
        delete this.slotsCacheByQuery[key];
        return null;
      }

      return cached.slots;
    },
    setSlotsToCache(key, slots) {
      this.slotsCacheByQuery[key] = {
        ts: Date.now(),
        slots: Array.isArray(slots) ? slots : [],
      };
    },
    async getDoctorSlotsWithCache({
      doctorId,
      clinicId = null,
      branchId = null,
      dateStr,
    }) {
      if (!doctorId || !dateStr) {
        return [];
      }

      const key = this.getSlotsCacheKey({
        doctorId,
        clinicId,
        branchId,
        dateStr,
      });
      const cachedSlots = this.getSlotsFromCache(key);
      if (cachedSlots) {
        return cachedSlots;
      }

      const response = await bookingApi.getDoctorSlots(
        doctorId,
        dateStr,
        clinicId,
        branchId
      );
      const slots = response.data || response || [];
      this.setSlotsToCache(key, slots);
      return slots;
    },
    isSlotAvailable(slot) {
      if (!slot) return false;

      const isTrue = (value) =>
        value === true ||
        value === 1 ||
        value === "1" ||
        String(value).toLowerCase() === "true";

      const parseStatus = (value) => String(value || "").toLowerCase();

      const hasIsAvailable = Object.prototype.hasOwnProperty.call(
        slot,
        "is_available"
      );
      const hasFree = Object.prototype.hasOwnProperty.call(slot, "free");
      const hasIsOccupied = Object.prototype.hasOwnProperty.call(
        slot,
        "is_occupied"
      );
      const hasIsPast = Object.prototype.hasOwnProperty.call(slot, "is_past");

      const status = parseStatus(slot.status);

      const availabilitySignals = [];
      if (hasIsAvailable) {
        availabilitySignals.push(isTrue(slot.is_available));
      }
      if (hasFree) {
        availabilitySignals.push(isTrue(slot.free));
      }
      if (status) {
        if (["free", "available", "open"].includes(status)) {
          availabilitySignals.push(true);
        }
        if (
          ["occupied", "booked", "busy", "closed", "disabled"].includes(status)
        ) {
          availabilitySignals.push(false);
        }
      }

      const available = availabilitySignals.length
        ? availabilitySignals.some(Boolean)
        : true;

      const occupied = hasIsOccupied
        ? isTrue(slot.is_occupied)
        : status
        ? ["occupied", "booked", "busy"].includes(status)
        : false;

      const past = hasIsPast
        ? isTrue(slot.is_past)
        : slot.datetime
        ? new Date(slot.datetime).getTime() < Date.now()
        : false;

      return available && !occupied && !past;
    },
    slotComparableValue(slot) {
      if (!slot) return Number.MAX_SAFE_INTEGER;
      if (slot.datetime) {
        const ts = new Date(slot.datetime).getTime();
        if (!Number.isNaN(ts)) return ts;
      }
      if (slot.time) {
        const [h, m] = String(slot.time).split(":").map(Number);
        if (!Number.isNaN(h) && !Number.isNaN(m)) return h * 60 + m;
      }
      return Number.MAX_SAFE_INTEGER;
    },
    async setDefaultDoctorForClinicFlow(options = {}) {
      const keepSelectedDoctor = options.keepSelectedDoctor === true;
      if (this.currentStep !== "clinic-schedule") {
        return;
      }
      if (
        !this.selectedClinic?.id ||
        !this.selectedBranch?.id ||
        !this.clinicDoctors.length
      ) {
        this.clinicDoctorShiftMap = {};
        this.selectedDoctor = null;
        this.slots = [];
        this.selectedSlot = null;
        return;
      }

      const dateStr = this.formatDateForApi(this.selectedDate);
      this.loadingSlots = true;

      try {
        const resolved = await Promise.all(
          this.clinicDoctors.map(async (doctor) => {
            try {
              const slots = await this.getDoctorSlotsWithCache({
                doctorId: doctor.id,
                clinicId: this.selectedClinic.id,
                branchId: this.selectedBranch.id,
                dateStr,
              });
              const firstAvailable = slots
                .filter((slot) => this.isSlotAvailable(slot))
                .sort(
                  (a, b) =>
                    this.slotComparableValue(a) - this.slotComparableValue(b)
                )[0];

              return {
                doctor,
                slots,
                firstAvailable: firstAvailable || null,
              };
            } catch (e) {
              return { doctor, slots: [], firstAvailable: null };
            }
          })
        );

        const withAvailable = resolved
          .filter((item) => item.firstAvailable)
          .sort(
            (a, b) =>
              this.slotComparableValue(a.firstAvailable) -
              this.slotComparableValue(b.firstAvailable)
          );

        this.clinicDoctorShiftMap = resolved.reduce((acc, item) => {
          const doctorId = item?.doctor?.id;
          if (doctorId != null) {
            acc[String(doctorId)] =
              Array.isArray(item.slots) && item.slots.length > 0;
          }
          return acc;
        }, {});

        let selectedEntry = null;

        if (keepSelectedDoctor && this.selectedDoctor?.id != null) {
          selectedEntry =
            resolved.find(
              (item) => item?.doctor?.id === this.selectedDoctor.id
            ) || null;
        }

        if (!selectedEntry && this.clinicDoctors[0]?.id != null) {
          const firstDoctorId = this.clinicDoctors[0].id;
          selectedEntry =
            resolved.find((item) => item?.doctor?.id === firstDoctorId) || null;
        }

        if (!selectedEntry) {
          selectedEntry = withAvailable[0] || resolved[0] || null;
        }

        if (!selectedEntry) {
          this.selectedDoctor = null;
          this.slots = [];
          this.selectedSlot = null;
          return;
        }

        this.selectedDoctor = selectedEntry.doctor;
        this.slots = selectedEntry.slots;
        this.selectedSlot = selectedEntry.firstAvailable || null;
      } finally {
        this.loadingSlots = false;
      }
    },
    async handleModeSelect(mode) {
      if (mode === "doctor") {
        this.currentStep = "doctor-select";
      } else if (mode === "clinic") {
        await this.withTransitionLoader(async () => {
          await this.openClinicFlowWithAutoBranchSkip();
        });
      }
    },
    handleLeaveRequest() {
      this.currentStep = "leave-request";
    },
    async handleDoctorSelect(doctor) {
      this.selectedDoctor = doctor;
      if (this.currentStep === "doctor-schedule" && this.selectedClinic?.id) {
        await this.loadDoctorFlowBranches(this.selectedClinic.id);
        await this.updateDoctorFlowHighlightedDates();
      }
      if (this.currentStep === "clinic-schedule") {
        await this.loadSlots();
        await this.updateClinicFlowHighlightedDates();
      }
    },
    async handleClinicSelect(clinic) {
      this.selectedClinic = clinic;
      this.selectedBranch = null;
      if (this.currentStep === "clinic-select") {
        await this.loadDoctorsByClinic(clinic.id);
        await this.loadBranches(clinic.id);
      }
      if (this.currentStep === "doctor-schedule") {
        await this.loadDoctorFlowBranches(clinic.id);
        await this.loadSlots();
        await this.updateDoctorFlowHighlightedDates();
      }
    },
    async handleBranchSelect(branch) {
      if (branch.enabled === false) {
        return;
      }

      if (
        branch.clinic_id &&
        (!this.selectedClinic ||
          Number(this.selectedClinic.id) !== Number(branch.clinic_id))
      ) {
        const mappedClinic = this.clinics.find(
          (clinic) => Number(clinic.id) === Number(branch.clinic_id)
        );
        if (mappedClinic) {
          this.selectedClinic = mappedClinic;
        }
      }

      this.selectedBranch = branch;

      if (this.currentStep === "clinic-select") {
        if (this.selectedClinic?.id) {
          await this.loadDoctorsByClinic(this.selectedClinic.id, branch.id);
        } else {
          this.clinicDoctors = [];
        }
        return;
      }

      if (this.currentStep === "doctor-schedule") {
        await this.loadSlots();
        await this.updateDoctorFlowHighlightedDates();
        return;
      }

      if (this.currentStep === "clinic-schedule") {
        if (this.selectedClinic?.id) {
          await this.loadDoctorsByClinic(this.selectedClinic.id, branch.id);
        } else {
          this.clinicDoctors = [];
        }
        await this.setDefaultDoctorForClinicFlow();
        await this.updateClinicFlowHighlightedDates();
      }
    },
    async handleDateSelect(date) {
      this.selectedDate = date;
      if (this.currentStep === "doctor-schedule") {
        if (this.selectedClinic?.id) {
          if (this.selectedBranch?.id) {
            await this.loadSlots();
          } else {
            await this.selectDoctorFlowBranchByDate(this.selectedClinic.id);
          }
        } else {
          await this.loadSlots();
        }
        await this.updateDoctorFlowHighlightedDates();
        return;
      }
      if (this.currentStep === "clinic-schedule") {
        await this.setDefaultDoctorForClinicFlow({ keepSelectedDoctor: true });
        await this.updateClinicFlowHighlightedDates();
        return;
      }
      await this.loadSlots();
    },
    handleSlotSelect(slot) {
      this.selectedSlot = slot;
    },
    goToStart() {
      this.currentStep = "start";
    },
    goToDoctorSelect() {
      this.currentStep = "doctor-select";
    },
    async goToClinicSelect() {
      if (!this.cityBranches.length) {
        await this.loadCityBranches();
      }

      if (this.cityBranches.length <= 1) {
        this.currentStep = "start";
        return;
      }

      this.currentStep = "clinic-select";
    },
    async goToDoctorSchedule() {
      await this.withTransitionLoader(async () => {
        const enabledClinic = this.clinics.find((clinic) => clinic.enabled !== false);
        if (!this.selectedClinic && enabledClinic) {
          this.selectedClinic = enabledClinic;
        }

        const clinicId = this.selectedClinic?.id || null;
        if (clinicId) {
          await this.loadDoctorFlowBranches(clinicId);
        } else {
          this.doctorFlowBranches = [];
          this.slots = [];
          this.selectedSlot = null;
        }

        this.currentStep = "doctor-schedule";
        await this.updateDoctorFlowHighlightedDates();
      });
    },
    async goToClinicSchedule() {
      this.currentStep = "clinic-schedule";
      if (this.selectedClinic?.id && this.selectedBranch?.id) {
        await this.loadDoctorsByClinic(this.selectedClinic.id, this.selectedBranch.id);
      } else {
        this.clinicDoctors = [];
      }
      await this.setDefaultDoctorForClinicFlow();
      await this.updateClinicFlowHighlightedDates();
    },
    goToForm() {
      this.formSourceStep = this.currentStep;
      this.currentStep = "form";
    },
    goBackFromForm() {
      const sourceStep = this.formSourceStep;
      this.formSourceStep = null;

      if (sourceStep === "clinic-schedule") {
        this.currentStep = "clinic-schedule";
        return;
      }

      if (sourceStep === "doctor-schedule") {
        this.currentStep = "doctor-schedule";
        return;
      }

      this.currentStep = "start";
    },
    async handleFormSubmit(formData) {
      this.isSubmitting = true;

      try {
        const applicationData = {
          city_id: this.currentCityId,
          clinic_id:
            this.selectedSlot?.clinic_id ||
            this.selectedClinic?.id ||
            null,
          branch_id:
            this.selectedSlot?.branch_id ||
            this.selectedBranch?.id ||
            null,
          doctor_id:
            this.selectedDoctor?.id || this.selectedSlot?.doctor_id || null,
          cabinet_id: this.selectedSlot?.cabinet_id || null,
          appointment_datetime:
            this.selectedSlot?.datetime ||
            this.selectedSlot?.appointment_datetime ||
            this.buildAppointmentDateTime(),
          onec_slot_id: this.selectedSlot?.onec_slot_id || null,
          full_name: formData.full_name,
          full_name_parent: formData.full_name_parent || null,
          birth_date: formData.birth_date,
          phone: this.cleanPhone(formData.phone),
          promo_code: formData.promo_code || null,
          comment: formData.comment || null,
          appointment_source: "site",
        };

        await bookingApi.createApplication(applicationData);
        this.currentStep = "success";
      } catch (error) {
        if (error.status === 422 && error.errors) {
          this.$refs.patientForm?.setErrors(error.errors);
        } else {
          this.$refs.patientForm?.setGeneralError(
            error.message ||
              "Произошла ошибка при создании заявки. Попробуйте еще раз."
          );
        }
      } finally {
        this.isSubmitting = false;
      }
    },
    handleClose() {
      this.$emit("close");
    },
    resetState() {
      this.currentStep = "start";
      this.selectedDoctor = null;
      this.selectedClinic = null;
      this.selectedBranch = null;
      this.selectedDate = new Date();
      this.selectedSlot = null;
      this.slots = [];
      this.doctorFlowBranches = [];
      this.branches = [];
      this.cityBranches = [];
      this.clinicDoctors = [];
      this.clinicDoctorShiftMap = {};
      this.doctorFlowHighlightedDates = [];
      this.clinicFlowHighlightedDates = [];
      this.isSubmitting = false;
      this.formSourceStep = null;
      this.transitionLoading = false;
    },
    async updateDoctorFlowHighlightedDates() {
      if (
        this.currentStep !== "doctor-schedule" ||
        !this.selectedDoctor?.id ||
        !this.selectedClinic?.id ||
        !this.selectedBranch?.id
      ) {
        this.doctorFlowHighlightedDates = [];
        return;
      }

      const base =
        this.selectedDate instanceof Date
          ? this.selectedDate
          : new Date(this.selectedDate || Date.now());

      const start = new Date(base.getFullYear(), base.getMonth(), 1);
      const end = new Date(base.getFullYear(), base.getMonth() + 1, 0);

      const dateFrom = this.formatDateForApi(start);
      const dateTo = this.formatDateForApi(end);

      try {
        const response = await bookingApi.getCalendarAvailability({
          doctorId: this.selectedDoctor.id,
          dateFrom,
          dateTo,
          clinicId: this.selectedClinic.id,
          branchId: this.selectedBranch.id,
        });

        const items = Array.isArray(response?.data)
          ? response.data
          : Array.isArray(response?.items)
          ? response.items
          : Array.isArray(response)
          ? response
          : [];
        const dates = items
          .filter((item) => Number(item?.available_slots || 0) > 0 && item?.date)
          .map((item) => {
            const parts = String(item.date).split("-");
            if (parts.length !== 3) {
              return null;
            }
            const [year, month, day] = parts.map((part) => Number(part));
            if (!year || !month || !day) {
              return null;
            }
            return new Date(year, month - 1, day);
          })
          .filter((value) => value instanceof Date && !Number.isNaN(value.getTime()));

        this.doctorFlowHighlightedDates = dates;
        console.log(
          "[BookingWidgetV3] doctorFlowHighlightedDates",
          dates.map((d) => this.formatDateForApi(d))
        );
      } catch (e) {
        this.doctorFlowHighlightedDates = [];
      }
    },
    async updateClinicFlowHighlightedDates() {
      if (
        this.currentStep !== "clinic-schedule" ||
        !this.selectedDoctor?.id ||
        !this.selectedClinic?.id ||
        !this.selectedBranch?.id
      ) {
        this.clinicFlowHighlightedDates = [];
        return;
      }

      const base =
        this.selectedDate instanceof Date
          ? this.selectedDate
          : new Date(this.selectedDate || Date.now());

      const start = new Date(base.getFullYear(), base.getMonth(), 1);
      const end = new Date(base.getFullYear(), base.getMonth() + 1, 0);

      const dateFrom = this.formatDateForApi(start);
      const dateTo = this.formatDateForApi(end);

      try {
        const response = await bookingApi.getCalendarAvailability({
          doctorId: this.selectedDoctor.id,
          dateFrom,
          dateTo,
          clinicId: this.selectedClinic.id,
          branchId: this.selectedBranch.id,
        });

        const items = Array.isArray(response?.data)
          ? response.data
          : Array.isArray(response?.items)
          ? response.items
          : Array.isArray(response)
          ? response
          : [];
        const dates = items
          .filter((item) => Number(item?.available_slots || 0) > 0 && item?.date)
          .map((item) => {
            const parts = String(item.date).split("-");
            if (parts.length !== 3) {
              return null;
            }
            const [year, month, day] = parts.map((part) => Number(part));
            if (!year || !month || !day) {
              return null;
            }
            return new Date(year, month - 1, day);
          })
          .filter((value) => value instanceof Date && !Number.isNaN(value.getTime()));

        this.clinicFlowHighlightedDates = dates;
        console.log(
          "[BookingWidgetV3] clinicFlowHighlightedDates",
          dates.map((d) => this.formatDateForApi(d))
        );
      } catch (e) {
        this.clinicFlowHighlightedDates = [];
      }
    },
    formatDateForApi(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },
    cleanPhone(phone) {
      return String(phone || "").replace(/\D/g, "");
    },
    buildAppointmentDateTime() {
      if (!this.selectedDate || !this.selectedSlot?.time) {
        return null;
      }

      const year = this.selectedDate.getFullYear();
      const month = String(this.selectedDate.getMonth() + 1).padStart(2, "0");
      const day = String(this.selectedDate.getDate()).padStart(2, "0");
      const [hours, minutes] = String(this.selectedSlot.time)
        .split(":")
        .map((part) => String(part).padStart(2, "0"));

      if (!hours || !minutes) {
        return null;
      }

      return `${year}-${month}-${day} ${hours}:${minutes}:00`;
    },
  },
};
</script>
