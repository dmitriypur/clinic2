<template>
  <Modal
    :open="open"
    :zIndexOverride="49"
    closeButtonHiddenOnMobile
    @close="handleClose"
  >
    <div class="h-auto">
      <StartStep
        v-if="currentStep === 'start'"
        :mode="mode"
        @select-mode="handleModeSelect"
        @leave-request="handleLeaveRequest"
      />

      <DoctorSelectStep
        v-else-if="currentStep === 'doctor-select'"
        :doctors="doctors"
        :selectedDoctorId="selectedDoctor?.id"
        :loading="loadingDoctors"
        @select="handleDoctorSelect"
        @next="goToDoctorSchedule"
        @back="goToStart"
      />

      <ClinicSelectStep
        v-else-if="currentStep === 'clinic-select'"
        :branches="cityBranches"
        :selectedBranchId="selectedBranch?.id"
        :loading="loadingCityBranches"
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
        :selectedDoctorId="selectedDoctor?.id"
        :selectedDate="selectedDate"
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
        @back="goBackFromForm"
        @submit="handleFormSubmit"
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
      slots: [],
      doctorFlowBranches: [],
      allCities: [],
      loadingClinics: false,
      loadingCityBranches: false,
      loadingDoctors: false,
      loadingSlots: false,
      loadingDoctorFlowBranches: false,
      isSubmitting: false,
    };
  },
  computed: {
    currentCityId() {
      const localCityName =
        window.currentCity?.name ||
        window.config?.state?.currentCity?.name ||
        "Москва";

      if (!this.allCities || !this.allCities.length) {
        return null;
      }

      // Нормализация имени (убираем "г." и приводим к нижнему регистру)
      const normalize = (str) => str ? str.toLowerCase().replace(/^г\.\s*/, '').trim() : '';
      const targetName = normalize(localCityName);

      // 1. Ищем совпадение по имени в списке API
      const matchedCity = this.allCities.find(
        (c) => normalize(c.name) === targetName
      );

      if (matchedCity) {
        return matchedCity.id;
      }

      // 2. Если не нашли, пробуем найти Москву как дефолт
      const moscow = this.allCities.find(c => normalize(c.name).includes('москва'));
      if (moscow) {
        return moscow.id;
      }

      // 3. Если совсем ничего не нашли, берем первый город из API
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
  },
  watch: {
    open: {
      async handler(val) {
        if (val) {
          await this.initCities();
          await this.loadClinics();
          await this.loadDoctorsByCity();
        } else {
          this.resetState();
        }
      },
      immediate: true,
    },
  },
  methods: {
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
      const uuids = list
        .map((doctor) => this.doctorExternalUuid(doctor))
        .filter(Boolean);

      if (!uuids.length) {
        return [];
      }

      try {
        const response = await bookingApi.getSiteDoctorsByUuids(uuids);
        const siteDoctors = response.data || response || [];
        const siteByUuid = siteDoctors.reduce((acc, doctor) => {
          const uuid = String(doctor?.uuid || "").toLowerCase().trim();
          if (uuid) {
            acc[uuid] = doctor;
          }
          return acc;
        }, {});

        return list
          .map((doctor) => {
            const uuid = this.doctorExternalUuid(doctor);
            if (!uuid || !siteByUuid[uuid]) {
              return null;
            }

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
          })
          .filter(Boolean);
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
      this.loadingDoctors = true;
      try {
        if (!this.clinics.length) {
          await this.loadClinics();
        }

        const doctorGroups = await Promise.all(
          this.clinics.map(async (clinic) => {
            try {
              const response = await bookingApi.getClinicDoctors(clinic.id);
              return response.data || response || [];
            } catch (e) {
              return [];
            }
          })
        );

        this.doctors = await this.enrichDoctorsWithSiteData(doctorGroups.flat());
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
        const response = await bookingApi.getClinicBranches(clinicId, this.currentCityId);
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
              const hasDoctor = doctors.some((doctor) => doctor.id === this.selectedDoctor.id);
              return { ...branch, enabled: hasDoctor };
            } catch (e) {
              return { ...branch, enabled: false };
            }
          })
        );

        this.doctorFlowBranches = checkedBranches;
      } finally {
        this.loadingDoctorFlowBranches = false;
      }
    },
    async markClinicsForDoctor(doctorId) {
      if (!doctorId || this.clinics.length === 0) return;
      const results = await Promise.all(
        this.clinics.map(async (clinic) => {
          try {
            const response = await bookingApi.getClinicDoctors(clinic.id);
            const list = response.data || response || [];
            const hasDoctor = list.some((d) => d.id === doctorId);
            return { ...clinic, enabled: hasDoctor };
          } catch (e) {
            return { ...clinic, enabled: false };
          }
        })
      );
      this.clinics = results;
    },
    async loadSlots() {
      if (!this.selectedDoctor || !this.selectedDate) return;
      this.loadingSlots = true;
      try {
        const dateStr = this.formatDateForApi(this.selectedDate);
        const response = await bookingApi.getDoctorSlots(
          this.selectedDoctor.id,
          dateStr,
          this.selectedClinic?.id || null,
          this.selectedBranch?.id || null
        );
        this.slots = response.data || response || [];
        this.selectedSlot = null;
      } finally {
        this.loadingSlots = false;
      }
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
    async setDefaultDoctorForClinicFlow() {
      if (this.currentStep !== "clinic-schedule") {
        return;
      }
      if (
        !this.selectedClinic?.id ||
        !this.selectedBranch?.id ||
        !this.clinicDoctors.length
      ) {
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
              const response = await bookingApi.getDoctorSlots(
                doctor.id,
                dateStr,
                this.selectedClinic.id,
                this.selectedBranch.id
              );
              const slots = response.data || response || [];
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

        const selected = withAvailable[0] || resolved[0] || null;

        if (!selected) {
          this.selectedDoctor = null;
          this.slots = [];
          this.selectedSlot = null;
          return;
        }

        this.selectedDoctor = selected.doctor;
        this.slots = selected.slots;
        this.selectedSlot = selected.firstAvailable || null;
      } finally {
        this.loadingSlots = false;
      }
    },
    handleModeSelect(mode) {
      if (mode === "doctor") {
        this.currentStep = "doctor-select";
      } else if (mode === "clinic") {
        this.currentStep = "clinic-select";
        this.loadCityBranches();
      }
    },
    handleLeaveRequest() {
      this.currentStep = "form";
    },
    async handleDoctorSelect(doctor) {
      this.selectedDoctor = doctor;
      if (this.currentStep === "doctor-select") {
        await this.markClinicsForDoctor(doctor.id);
      }
      if (this.currentStep === "doctor-schedule" && this.selectedClinic?.id) {
        await this.loadDoctorFlowBranches(this.selectedClinic.id);
      }
      if (this.currentStep === "clinic-schedule") {
        await this.loadSlots();
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
        return;
      }

      if (this.currentStep === "clinic-schedule") {
        if (this.selectedClinic?.id) {
          await this.loadDoctorsByClinic(this.selectedClinic.id, branch.id);
        } else {
          this.clinicDoctors = [];
        }
        await this.setDefaultDoctorForClinicFlow();
      }
    },
    async handleDateSelect(date) {
      this.selectedDate = date;
      if (this.currentStep === "clinic-schedule") {
        await this.setDefaultDoctorForClinicFlow();
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
    goToClinicSelect() {
      this.currentStep = "clinic-select";
    },
    async goToDoctorSchedule() {
      const enabledClinic = this.clinics.find((clinic) => clinic.enabled !== false);
      if (!this.selectedClinic && enabledClinic) {
        this.selectedClinic = enabledClinic;
      }
      if (this.selectedClinic?.id) {
        await this.loadDoctorFlowBranches(this.selectedClinic.id);
      }
      this.currentStep = "doctor-schedule";
      await this.loadSlots();
    },
    async goToClinicSchedule() {
      this.currentStep = "clinic-schedule";
      if (this.selectedClinic?.id && this.selectedBranch?.id) {
        await this.loadDoctorsByClinic(this.selectedClinic.id, this.selectedBranch.id);
      } else {
        this.clinicDoctors = [];
      }
      await this.setDefaultDoctorForClinicFlow();
    },
    goToForm() {
      this.currentStep = "form";
    },
    goBackFromForm() {
      if (this.selectedClinic && !this.selectedDoctor) {
        this.currentStep = "clinic-schedule";
        return;
      }
      if (this.selectedDoctor) {
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
      this.isSubmitting = false;
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
