<template>
  <div v-if="visible" class="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center p-4" @click.self="close">
    <div class="w-full max-w-[560px] bg-white rounded-2xl shadow-2xl overflow-hidden" :style="rootStyle">
      <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
        <div class="text-lg font-semibold text-slate-900">{{ title }}</div>
        <button class="text-2xl text-slate-400 hover:text-slate-700 transition" @click="close">×</button>
      </div>
      <div class="px-5 py-5">
        <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden mb-4">
          <div class="h-full bg-blue-600 transition-all" :style="{ width: progress + '%' }"></div>
        </div>

        <!-- Step 1 -->
        <div v-if="step === 1">
          <div class="mb-3">
            <div class="text-xs text-slate-500 mb-1">ФИО родителя</div>
            <input class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" v-model="fio" />
          </div>
          <div class="mb-3">
            <div class="text-xs text-slate-500 mb-1">ФИО пациента</div>
            <input class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" v-model="childFio" />
          </div>
          <div class="mb-3">
            <div class="text-xs text-slate-500 mb-1">Телефон</div>
            <input class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" v-model="phone" />
          </div>
          <div v-if="errors.step1" class="text-xs text-rose-600">{{ errors.step1 }}</div>
          <div class="flex gap-2 mt-4">
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-white" :style="primaryStyle" @click="validateStep1">Далее</button>
          </div>
        </div>

        <!-- Step 2 -->
        <div v-else-if="step === 2">
          <div class="mb-3">
            <div class="text-xs text-slate-500 mb-1">Город</div>
            <select class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" v-model="cityId">
              <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="flex gap-2 mt-4">
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium bg-slate-50" @click="goBack">Назад</button>
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-white" :style="primaryStyle" @click="selectCity">Далее</button>
          </div>
        </div>

        <!-- Step 3 -->
        <div v-else-if="step === 3">
          <div class="mb-3">
            <div class="text-xs text-slate-500 mb-1">Дата рождения (для фильтра врачей)</div>
            <input class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" type="date" v-model="birthDate" />
          </div>
          <div class="flex gap-2 mt-4">
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium bg-slate-50" @click="goBack">Назад</button>
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium" @click="skipBirthDate">Пропустить</button>
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-white" :style="primaryStyle" @click="goTo(4)">Далее</button>
          </div>
        </div>

        <!-- Step 4 -->
        <div v-else-if="step === 4">
          <div class="flex gap-2 mt-2">
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-white" :style="primaryStyle" @click="startClinicFlow">Выбрать клинику</button>
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium" @click="startDoctorFlow">Выбрать врача</button>
          </div>
          <div class="flex gap-2 mt-4">
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium bg-slate-50" @click="goBack">Назад</button>
          </div>
        </div>

        <!-- Step 5 -->
        <div v-else-if="step === 5">
          <div class="grid gap-3">
            <div class="border border-slate-200 rounded-xl p-3 hover:border-sky-400 cursor-pointer" v-for="c in clinics" :key="c.id" @click="toggleClinic(c)">
              <div class="font-medium">{{ c.name }}</div>
              <div class="text-xs text-slate-500" v-if="branchesByClinic[c.id]">Филиалов: {{ branchesByClinic[c.id].length }}</div>
              <div class="grid gap-2 mt-2" v-if="branchesByClinic[c.id]">
                <div class="border border-slate-200 rounded-xl p-2 hover:border-sky-400 cursor-pointer" v-for="b in branchesByClinic[c.id]" :key="b.id" @click.stop="selectBranch(c.id, b)">
                  {{ b.name }}
                </div>
              </div>
            </div>
          </div>
          <div class="flex gap-2 mt-4">
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium bg-slate-50" @click="goBack">Назад</button>
          </div>
        </div>

        <!-- Step 6 -->
        <div v-else-if="step === 6">
          <div class="grid gap-2">
            <div class="border border-slate-200 rounded-xl p-3 hover:border-sky-400 cursor-pointer" v-for="d in doctors" :key="d.id" @click="selectDoctor(d)">
              {{ d.last_name }} {{ d.first_name }} {{ d.second_name }}
            </div>
            <div v-if="!isLoadingDoctors && doctors.length === 0" class="text-sm text-slate-500">
              Врачей не найдено. Проверьте дату рождения или связи клиники/города.
            </div>
          </div>
          <div class="flex gap-2 mt-4">
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium bg-slate-50" @click="goBack">Назад</button>
          </div>
        </div>

        <!-- Step 7 -->
        <div v-else-if="step === 7">
          <div class="mb-3">
            <div class="text-xs text-slate-500 mb-1">Дата</div>
            <input class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" type="date" :value="dateValue" @change="onDateChange" />
          </div>
          <div>
            <span
              v-for="s in slots"
              :key="s.id"
              class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 text-xs mt-2 mr-2 cursor-pointer"
              :class="{
                'text-white': selectedSlot && selectedSlot.id === s.id,
                'opacity-50 cursor-not-allowed': !s.is_available
              }"
              :style="selectedSlot && selectedSlot.id === s.id ? primaryStyle : null"
              @click="selectSlot(s)"
            >{{ s.time }}</span>
          </div>
          <div v-if="slotValidationError" class="text-xs text-rose-600 mt-2">{{ slotValidationError }}</div>
          <div class="flex gap-2 mt-4">
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium bg-slate-50" @click="goBack">Назад</button>
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-white" :style="primaryStyle" @click="handleScheduleNext" :disabled="isSlotValidationInProgress">Далее</button>
          </div>
        </div>

        <!-- Step 8 -->
        <div v-else-if="step === 8">
          <div class="text-sm text-slate-600">Проверьте данные и отправьте заявку.</div>
          <div class="flex gap-2 mt-4">
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium bg-slate-50" @click="goBack">Назад</button>
            <button class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-white" :style="primaryStyle" @click="submit">Записаться</button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { createApiClient } from "./api";
import { getCache, setCache } from "./cache";
import { formatDate, debounce } from "./utils";

export default {
  name: "BookingWebAppModal",
  props: {
    apiBase: { type: String, required: true },
    title: { type: String, default: "Онлайн‑запись" },
    primaryColor: { type: String, default: "#2563eb" },
    onSuccess: { type: Function, default: null },
  },
  data() {
    return {
      visible: false,
      step: 1,
      steps: [1, 2, 3, 4, 5, 6, 7, 8],
      history: [1],
      fio: "",
      childFio: "",
      phone: "",
      consent: false,
      birthDate: null,
      cityId: null,
      clinicId: null,
      branchId: null,
      selectedDoctorId: null,
      selectedDoctor: null,
      selectedDate: new Date(),
      selectedSlot: null,
      cities: [],
      clinics: [],
      branchesByClinic: {},
      doctors: [],
      slots: [],
      isLoadingCities: false,
      isLoadingClinics: false,
      isLoadingDoctors: false,
      isLoadingSlots: false,
      slotValidationError: null,
      isSlotValidationInProgress: false,
      api: null,
      errors: {},
    };
  },
  computed: {
    progress() {
      const idx = this.steps.indexOf(this.step);
      return ((idx + 1) / this.steps.length) * 100;
    },
    dateValue() {
      return formatDate(this.selectedDate);
    },
    rootStyle() {
      return {
        "--bw-primary": this.primaryColor,
      };
    },
    primaryStyle() {
      return {
        background: this.primaryColor,
        borderColor: this.primaryColor,
      };
    },
  },
  created() {
    this.api = createApiClient(this.apiBase);
    this.debouncedLoadSlots = debounce(this.loadSlots, 300);
  },
  methods: {
    open() {
      this.visible = true;
      if (!this.cities.length) this.loadCities();
    },
    close() {
      this.visible = false;
    },
    goTo(step) {
      this.step = step;
      this.history.push(step);
    },
    goBack() {
      if (this.history.length <= 1) return;
      const current = this.step;
      this.history.pop();
      const prev = this.history[this.history.length - 1];
      this.cleanupAfterBack(current, prev);
      this.step = prev;
    },
    cleanupAfterBack(from, to) {
      if (from >= 7 && to <= 6) this.resetSchedule();
      if (from >= 6 && to <= 5) this.resetSelectedDoctor();
      if (from >= 5 && to <= 4) this.resetClinicContext();
    },
    resetSchedule() {
      this.selectedDate = new Date();
      this.selectedSlot = null;
      this.slots = [];
      this.slotValidationError = null;
      this.isSlotValidationInProgress = false;
    },
    resetSelectedDoctor() {
      this.selectedDoctorId = null;
      this.selectedDoctor = null;
      this.resetSchedule();
    },
    resetClinicContext() {
      this.clinicId = null;
      this.branchId = null;
      this.branchesByClinic = {};
      this.clinics = [];
    },
    skipBirthDate() {
      this.birthDate = null;
      this.goTo(4);
    },
    validateStep1() {
      this.errors = {};
      if (!this.childFio || !this.phone) {
        this.errors.step1 = "Заполните ФИО пациента и телефон";
        return;
      }
      this.goTo(2);
    },
    async loadCities() {
      const cacheKey = "cities";
      const cached = getCache(cacheKey);
      if (cached) {
        this.cities = cached;
        return;
      }
      this.isLoadingCities = true;
      try {
        const { data } = await this.api.get("/api/v1/cities");
        this.cities = data?.data || [];
        setCache(cacheKey, this.cities, 300000);
      } finally {
        this.isLoadingCities = false;
      }
    },
    async selectCity() {
      if (!this.cityId) return;
      this.goTo(3);
      await this.loadClinics();
    },
    async loadClinics() {
      if (!this.cityId) return;
      const cacheKey = `clinics:${this.cityId}`;
      const cached = getCache(cacheKey);
      if (cached) {
        this.clinics = cached;
        return;
      }
      this.isLoadingClinics = true;
      try {
        const { data } = await this.api.get(`/api/v1/cities/${this.cityId}/clinics`);
        this.clinics = data?.data || [];
        setCache(cacheKey, this.clinics, 300000);
      } finally {
        this.isLoadingClinics = false;
      }
    },
    async startClinicFlow() {
      this.goTo(5);
      if (!this.clinics.length) await this.loadClinics();
    },
    async startDoctorFlow() {
      this.goTo(6);
      await this.loadDoctors();
    },
    async toggleClinic(clinic) {
      if (this.clinicId === clinic.id) {
        this.clinicId = null;
        this.branchId = null;
        return;
      }
      this.clinicId = clinic.id;
      this.branchId = null;
      if (!this.branchesByClinic[clinic.id]) {
        await this.loadBranches(clinic.id);
      }
    },
    async loadBranches(clinicId) {
      const cacheKey = `branches:${clinicId}:${this.cityId || "any"}`;
      const cached = getCache(cacheKey);
      if (cached) {
        this.$set(this.branchesByClinic, clinicId, cached);
        return;
      }
      const { data } = await this.api.get(`/api/v1/clinics/${clinicId}/branches`, {
        params: { city_id: this.cityId || undefined },
      });
      const list = data?.data || [];
      this.$set(this.branchesByClinic, clinicId, list);
      setCache(cacheKey, list, 300000);
    },
    async selectBranch(clinicId, branch) {
      this.clinicId = clinicId;
      this.branchId = branch.id;
      await this.goToDoctors();
    },
    async goToDoctors() {
      this.goTo(6);
      await this.loadDoctors();
    },
    async loadDoctors() {
      if (!this.cityId) return;
      this.isLoadingDoctors = true;
      try {
        const params = {};
        if (this.birthDate) params.birth_date = this.birthDate;
        if (this.branchId) params.branch_id = this.branchId;

        const cacheKey = `doctors:${this.cityId}:${this.clinicId || "none"}:${this.branchId || "none"}:${this.birthDate || "none"}`;
        const cached = getCache(cacheKey);
        if (cached) {
          this.doctors = cached;
          return;
        }

        const url = this.clinicId
          ? `/api/v1/clinics/${this.clinicId}/doctors`
          : `/api/v1/cities/${this.cityId}/doctors`;

        const { data } = await this.api.get(url, { params });
        this.doctors = data?.data || [];
        setCache(cacheKey, this.doctors, 300000);
      } finally {
        this.isLoadingDoctors = false;
      }
    },
    async selectDoctor(doctor) {
      this.selectedDoctorId = doctor.id;
      this.selectedDoctor = doctor;
      this.goTo(7);
      await this.loadSlots();
    },
    onDateChange(event) {
      this.selectedDate = new Date(event.target.value);
      this.debouncedLoadSlots();
    },
    async loadSlots() {
      if (!this.selectedDoctorId) return;
      this.isLoadingSlots = true;
      this.slotValidationError = null;
      try {
        const { data } = await this.api.get(`/api/v1/doctors/${this.selectedDoctorId}/slots`, {
          params: {
            date: formatDate(this.selectedDate),
            clinic_id: this.clinicId || undefined,
            branch_id: this.branchId || undefined,
          },
        });
        this.slots = data?.data || [];
        const first = this.slots.find((s) => s.is_available);
        this.selectedSlot = first || null;
      } finally {
        this.isLoadingSlots = false;
      }
    },
    selectSlot(slot) {
      if (!slot || !slot.is_available) return;
      this.selectedSlot = slot;
    },
    async handleScheduleNext() {
      if (!this.selectedSlot) return;

      if (!this.selectedSlot.onec_slot_id) {
        this.goTo(8);
        return;
      }

      this.isSlotValidationInProgress = true;
      this.slotValidationError = null;
      try {
        await this.api.post("/api/v1/applications/check-slot", {
          clinic_id: this.clinicId || this.selectedSlot.clinic_id,
          branch_id: this.branchId || this.selectedSlot.branch_id,
          doctor_id: this.selectedDoctorId,
          onec_slot_id: this.selectedSlot.onec_slot_id,
        });
        this.goTo(8);
      } catch (error) {
        this.slotValidationError =
          error?.response?.data?.message ||
          "Слот только что заняли. Выберите другое время.";
        await this.loadSlots();
      } finally {
        this.isSlotValidationInProgress = false;
      }
    },
    async submit() {
      await this.api.post("/api/v1/applications", {
        city_id: this.cityId,
        clinic_id: this.clinicId,
        branch_id: this.branchId,
        doctor_id: this.selectedDoctorId,
        cabinet_id: this.selectedSlot ? this.selectedSlot.cabinet_id : null,
        onec_slot_id: this.selectedSlot ? this.selectedSlot.onec_slot_id : null,
        appointment_datetime: this.selectedSlot ? this.selectedSlot.datetime : null,
        full_name_parent: (this.fio || "").trim(),
        full_name: (this.childFio || "").trim(),
        phone: this.phone,
        birth_date: this.birthDate,
      });

      if (typeof this.onSuccess === "function") {
        this.onSuccess();
      } else {
        alert("Вы успешно записаны!");
      }

      this.resetAll();
    },
    resetAll() {
      this.step = 1;
      this.history = [1];
      this.fio = "";
      this.childFio = "";
      this.phone = "";
      this.consent = false;
      this.birthDate = null;
      this.cityId = null;
      this.clinicId = null;
      this.branchId = null;
      this.selectedDoctorId = null;
      this.selectedDoctor = null;
      this.selectedDate = new Date();
      this.selectedSlot = null;
      this.clinics = [];
      this.branchesByClinic = {};
      this.doctors = [];
      this.slots = [];
      this.loadCities();
    },
  },
};
</script>
