<template>
  <BookingWidgetModal
    :open="open"
    :mode="mode"
    :zIndexOverride="49"
    :layoutMode="widgetLayoutMode"
    :showBackdrop="mode !== 'vk'"
    :showCloseButton="mode !== 'vk' && !showBookingServiceUnavailable"
    :flat="mode === 'vk'"
    closeButtonHiddenOnMobile
    @close="handleClose"
  >
    <div class="relative h-auto">
      <div
        v-if="showInitialPreparationLoader"
        class="flex min-h-[260px] items-center justify-center bg-white"
      >
        <div class="flex flex-col items-center gap-3 text-interactive">
          <span class="h-8 w-8 animate-spin rounded-full border-2 border-surface-subdued border-t-action-primary"></span>
          <p class="text-sm font-semibold">{{ initialPreparationMessage }}</p>
        </div>
      </div>

      <template v-else>
        <StartStep
          v-if="currentStep === 'start'"
          :mode="mode"
          @close="handleClose"
          @select-mode="handleModeSelect"
          @leave-request="handleLeaveRequest"
        />

        <BirthDateStep
          v-else-if="currentStep === 'birth-date'"
          :initialValue="patientBirthDateDisplay"
          :showBackButton="showBirthDateBackButton"
          :backDisabled="birthDateBackDisabled"
          @back="goToStart"
          @valid-change="handleBirthDateValidChange"
          @next="handleBirthDateSubmit"
        />

        <DoctorSelectStep
          v-else-if="currentStep === 'doctor-select'"
          :doctors="doctors"
          :selectedDoctorId="selectedDoctor?.id"
          :loading="loadingDoctors"
          :patientBirthDate="patientBirthDateIso"
          :stepChipText="doctorSelectStepChipText"
          @close="handleClose"
          @select="handleDoctorSelect"
          @next="goToDoctorSchedule"
          @back="goToBirthDate"
        />

        <ClinicSelectStep
          v-else-if="currentStep === 'clinic-select'"
          :branches="cityBranches"
          :selectedBranchId="selectedBranch?.id"
          :loading="loadingCityBranches"
          :stepChipText="clinicSelectStepChipText"
          @close="handleClose"
          @select-branch="handleBranchSelect"
          @next="goToClinicSchedule"
          @back="goToBirthDate"
        />

        <DoctorScheduleStep
          v-else-if="currentStep === 'doctor-schedule'"
          :doctor="selectedDoctor"
          :clinic="selectedClinic"
          :branches="doctorFlowBranches"
          :selectedBranch="selectedBranch"
          :selectedBranchId="selectedBranch?.id"
          :selectedDate="selectedDate"
          :highlightedDates="doctorFlowHighlightedDates"
          :slots="slots"
          :emptySlotsMessage="doctorFlowEmptySlotsMessage"
          :selectedSlot="selectedSlot"
          :loading="loadingSlots"
          :loadingBranches="loadingDoctorFlowBranches"
          :patientBirthDate="patientBirthDateIso"
          :stepChipText="doctorScheduleStepChipText"
          @select-branch="handleBranchSelect"
          @select-date="handleDateSelect"
          @select-slot="handleSlotSelect"
          @next="goToForm"
          @back="goToDoctorSelect"
        />

        <ClinicScheduleStep
          v-else-if="currentStep === 'clinic-schedule'"
          :selectedDoctor="selectedDoctor"
          :selectedBranch="selectedBranch"
          :doctors="clinicDoctors"
          :doctorShiftMap="clinicDoctorShiftMap"
          :selectedDoctorId="selectedDoctor?.id"
          :selectedDoctorKey="selectedDoctor?.entry_key || null"
          :selectedDate="selectedDate"
          :highlightedDates="clinicFlowHighlightedDates"
          :slots="slots"
          :emptySlotsMessage="clinicFlowEmptySlotsMessage"
          :selectedSlot="selectedSlot"
          :loadingDoctors="loadingDoctors"
          :loading="loadingSlots"
          :patientBirthDate="patientBirthDateIso"
          :stepChipText="clinicScheduleStepChipText"
          flowMode="clinic"
          @select-doctor="handleDoctorSelect"
          @select-date="handleDateSelect"
          @select-slot="handleSlotSelect"
          @next="goToForm"
          @back="goToClinicSelect"
        />

        <ClinicScheduleStep
          v-else-if="currentStep === 'date-select'"
          :selectedDoctor="selectedDoctor"
          :selectedBranch="selectedBranch"
          :doctors="dateFlowDoctors"
          :doctorShiftMap="dateFlowDoctorShiftMap"
          :selectedDoctorId="getDoctorApiId(selectedDoctor)"
          :selectedDoctorKey="selectedDoctor?.entry_key || null"
          :selectedDate="selectedDate"
          :highlightedDates="dateFlowHighlightedDates"
          :slots="slots"
          :emptySlotsMessage="dateFlowEmptySlotsMessage"
          :selectedSlot="selectedSlot"
          :loadingDoctors="loadingDateFlowDoctors"
          :loading="loadingSlots"
          :patientBirthDate="patientBirthDateIso"
          :stepChipText="dateSelectStepChipText"
          flowMode="date"
          @select-doctor="handleDoctorSelect"
          @select-date="handleDateSelect"
          @select-slot="handleSlotSelect"
          @next="goToForm"
          @back="goToBirthDate"
        />

        <template v-else-if="currentStep === 'form'">
          <PatientFormStep
            v-show="!showBookingServiceUnavailable"
            :selectedDoctor="selectedDoctor"
            :selectedClinic="selectedClinic"
            :selectedBranch="selectedBranch"
            :selectedDate="selectedDate"
            :selectedSlot="selectedSlot"
            :isSubmitting="isSubmitting"
            :initialBirthDate="patientBirthDateIso"
            :initialPatientData="initialPatientData"
            :birthDateReadonly="!isBirthDateEditableInForm"
            :stepChipText="formStepChipText"
            ref="patientForm"
            @close="handleClose"
            @back="goBackFromForm"
            @submit="handleFormSubmit"
          />

          <BookingServiceUnavailableStep
            v-if="showBookingServiceUnavailable"
            @back="showBookingServiceUnavailable = false"
            @close="handleClose"
          />
        </template>

        <DoctorAgeBlockedStep
          v-else-if="currentStep === 'doctor-age-blocked'"
          :message="doctorAgeBlockedMessage"
          @choose-other="goToDoctorSelectFromAgeBlocked"
        />

        <CallbackFormNew
          v-else-if="currentStep === 'leave-request'"
          button-content="Отправить"
          :target="callbackFormTarget"
          :source="submissionSource"
          :showOnlineLink="true"
          @open-online="goToStart"
        />

        <SuccessStep
          v-else-if="currentStep === 'success'"
          :selectedDoctor="selectedDoctor"
          :doctorName="selectedDoctor?.name"
          :clinicName="selectedClinic?.name"
          :selectedBranch="selectedBranch"
          :branchName="selectedBranch?.name"
          :appointmentDate="selectedDate"
          :appointmentTime="selectedSlot?.time"
          @close="handleClose"
        />
      </template>

      <div
        v-if="transitionLoading"
        class="absolute inset-0 z-30 flex min-h-[260px] items-center justify-center bg-white/85 backdrop-blur-[1px]"
      >
        <div class="flex flex-col items-center gap-3 text-interactive">
          <span class="h-8 w-8 animate-spin rounded-full border-2 border-surface-subdued border-t-action-primary"></span>
          <p class="text-sm font-semibold">{{ transitionLoadingMessage }}</p>
        </div>
      </div>
    </div>
  </BookingWidgetModal>
</template>

<script>
import bookingApi from "../../services/bookingApi";
import axios from "axios";
import {
  getDoctorExternalUuids,
  mergeDoctorsWithSitePayload,
  sortDoctorsByMinimumAge,
} from "./utils/doctorUtils";
import { validateBirthDateDisplay } from "./utils/birthDate";
import { getMonthRange } from "./utils/dateUtils";
import {
  calculateAgeMonthsFromBirthDate,
  getDoctorAgeRange,
} from "../../utilities/doctorAge";
import {
  buildDoctorAgeBlockedMessage as buildDoctorAgeBlockedMessageHelper,
  isDoctorAllowedForBirthDate as isDoctorAllowedForBirthDateHelper,
} from "./utils/doctorAgeBlocker";
import {
  buildCompositeCacheKey,
  buildVersionedCacheKey,
  clearObjectCaches,
  getTimestampedCacheEntry,
  isCurrentCacheVersion,
  resetBookingLoadingFlags,
  setTimestampedCacheEntry,
} from "./utils/cacheUtils";
import {
  getFirstAvailableSlot,
  sortResolvedByFirstAvailable,
  buildDoctorShiftMap,
  pickClinicFlowSelectedEntry,
} from "./utils/selectionUtils";
import {
  isSlotAvailable as isSlotAvailableHelper,
  slotComparableValue as slotComparableValueHelper,
} from "./utils/slotUtils";
import {
  getClinicDoctorSortOrders,
  getDoctorSelectSortOrders,
} from "../../services/bookingOrdering";
import { normalizeBookingLaunchContext } from "../../utilities/bookingLaunchContext";

const BookingWidgetModal = () => import("./components/BookingWidgetModal.vue");
const StartStep = () => import("./components/StartStep.vue");
const BirthDateStep = () => import("./components/BirthDateStep.vue");
const DoctorSelectStep = () => import("./components/DoctorSelectStep.vue");
const ClinicSelectStep = () => import("./components/ClinicSelectStep.vue");
const DoctorScheduleStep = () => import("./components/DoctorScheduleStep.vue");
const ClinicScheduleStep = () => import("./components/ClinicScheduleStep.vue");
const PatientFormStep = () => import("./components/PatientFormStep.vue");
const BookingServiceUnavailableStep = () =>
  import("./components/BookingServiceUnavailableStep.vue");
const DoctorAgeBlockedStep = () =>
  import("./components/DoctorAgeBlockedStep.vue");
const CallbackFormNew = () => import("../CallbackForm/CallbackFormNew.vue");
const SuccessStep = () => import("./components/SuccessStep.vue");

export default {
  name: "BookingWidgetV3",
  components: {
    BookingWidgetModal,
    StartStep,
    BirthDateStep,
    DoctorSelectStep,
    ClinicSelectStep,
    DoctorScheduleStep,
    ClinicScheduleStep,
    PatientFormStep,
    BookingServiceUnavailableStep,
    DoctorAgeBlockedStep,
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
      default: null, // 'doctor' | 'clinic' | 'date' | 'vk'
    },
    launchContext: {
      type: Object,
      default: null,
    },
    initialPatientData: {
      type: Object,
      default: null,
    },
    callbackTarget: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      currentStep: "start",
      selectedMode: null,
      selectedDoctor: null,
      selectedClinic: null,
      selectedBranch: null,
      patientBirthDateDisplay: "",
      patientBirthDateIso: "",
      selectedDate: new Date(),
      selectedSlot: null,
      clinics: [],
      branches: [],
      cityBranches: [],
      doctors: [],
      clinicDoctors: [],
      clinicDoctorShiftMap: {},
      dateFlowDoctors: [],
      dateFlowDoctorShiftMap: {},
      slots: [],
      doctorFlowBranches: [],
      doctorFlowHighlightedDates: [],
      clinicFlowHighlightedDates: [],
      dateFlowHighlightedDates: [],
      doctorFlowLastAvailableDate: null,
      clinicFlowLastAvailableDate: null,
      dateFlowLastAvailableDate: null,
      allCities: [],
      initCitiesPromise: null,
      clinicsCacheByCity: {},
      cityBranchesCacheByCity: {},
      doctorsCacheByCity: {},
      doctorLaunchCacheByQuery: {},
      doctorFlowBranchesAvailabilityCacheByQuery: {},
      dateFlowDoctorsCacheByQuery: {},
      dateFlowCalendarCacheByQuery: {},
      siteDoctorsCacheByUuids: {},
      slotsCacheByQuery: {},
      adminDataCacheEpoch: 0,
      clinicsCacheTtlMs: 60 * 1000,
      cityBranchesCacheTtlMs: 60 * 1000,
      doctorsCacheTtlMs: 60 * 1000,
      siteDoctorsCacheTtlMs: 60 * 1000,
      loadingClinics: false,
      loadingCityBranches: false,
      loadingDoctors: false,
      loadingDateFlowDoctors: false,
      loadingSlots: false,
      loadingDoctorFlowBranches: false,
      doctorFlowBranchesLoadedOnce: false,
      isSubmitting: false,
      showBookingServiceUnavailable: false,
      formSourceStep: null,
      ageBlockedDoctor: null,
      directDoctorLaunchPreload: null,
      directDoctorScheduleWarmup: null,
      isPreparingInitialStep: false,
      transitionLoading: false,
    };
  },
  computed: {
    widgetLayoutMode() {
      if (this.showBookingServiceUnavailable) {
        return "error";
      }

      return this.currentStep === "doctor-schedule" || this.currentStep === "clinic-schedule" || this.currentStep === "date-select"
        ? "schedule"
        : "default";
    },
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
    submissionSource() {
      return this.mode === "vk" ? "vk_mini_app" : "site";
    },
    doctorFlowEmptySlotsMessage() {
      return this.getEmptySlotsMessage(this.doctorFlowLastAvailableDate);
    },
    clinicFlowEmptySlotsMessage() {
      return this.getEmptySlotsMessage(this.clinicFlowLastAvailableDate);
    },
    dateFlowEmptySlotsMessage() {
      return this.getEmptySlotsMessage(this.dateFlowLastAvailableDate);
    },
    doctorSelectStepChipText() {
      return "Шаг №3";
    },
    clinicSelectStepChipText() {
      return "Шаг №3";
    },
    dateSelectStepChipText() {
      return "Шаг №3";
    },
    doctorScheduleStepChipText() {
      return "Шаг №4";
    },
    clinicScheduleStepChipText() {
      return this.cityBranches.length > 1 ? "Шаг №4" : "Шаг №3";
    },
    formStepChipText() {
      if (this.selectedMode === "date") {
        return "Шаг №4";
      }

      if (this.selectedMode === "clinic" && this.cityBranches.length <= 1) {
        return "Шаг №4";
      }

      return "Шаг №5";
    },
    effectiveLaunchContext() {
      return (
        normalizeBookingLaunchContext(this.launchContext) ||
        normalizeBookingLaunchContext({ bookingStartMode: this.mode })
      );
    },
    initialPreparationMessage() {
      return this.isDirectDoctorLaunchContext
        ? "Загрузка данных доктора..."
        : "Подбираем доступные варианты...";
    },
    transitionLoadingMessage() {
      return this.selectedMode === "doctor"
        ? "Загрузка данных доктора..."
        : "Подбираем доступные варианты...";
    },
    showInitialPreparationLoader() {
      return (
        this.isPreparingInitialStep ||
        (this.currentStep === "start" &&
          this.isForcedModeEntry &&
          !this.isDirectDoctorLaunchContext)
      );
    },
    doctorAgeBlockedMessage() {
      return this.buildDoctorAgeBlockedMessage(this.ageBlockedDoctor);
    },
    isDirectDoctorLaunchContext() {
      return Boolean(
        this.effectiveLaunchContext?.entry === "doctor" &&
          this.effectiveLaunchContext?.doctorId
      );
    },
    isBirthDateEditableInForm() {
      return false;
    },
    isForcedModeEntry() {
      return this.effectiveLaunchContext?.entry === "doctor" ||
        this.effectiveLaunchContext?.entry === "clinic";
    },
    showBirthDateBackButton() {
      return !this.isForcedModeEntry;
    },
    birthDateBackDisabled() {
      return this.isForcedModeEntry;
    },
  },
  watch: {
    open: {
      async handler(val) {
        if (val) {
          if (this.isDirectDoctorLaunchContext) {
            await this.applyLaunchContext();
            this.preloadDirectDoctorLaunch();
            return;
          }

          const shouldPrepare = this.shouldPrepareInitialStep();
          this.isPreparingInitialStep = shouldPrepare;

          try {
            await this.initCities();
            await this.applyLaunchContext();
          } finally {
            this.isPreparingInitialStep = false;
          }
        } else {
          this.resetState();
        }
      },
      immediate: true,
    },
    async mode() {
      const shouldPrepare = this.open && this.shouldPrepareInitialStep();
      if (shouldPrepare) {
        this.isPreparingInitialStep = true;
      }

      try {
        await this.applyLaunchContext();
      } finally {
        if (shouldPrepare) {
          this.isPreparingInitialStep = false;
        }
      }
    },
    async launchContext() {
      if (!this.open) {
        return;
      }

      this.resetState();

      if (this.isDirectDoctorLaunchContext) {
        await this.applyLaunchContext();
        this.preloadDirectDoctorLaunch();
        return;
      }

      const shouldPrepare = this.shouldPrepareInitialStep();
      if (shouldPrepare) {
        this.isPreparingInitialStep = true;
      }

      try {
        await this.initCities();
        await this.applyLaunchContext();
      } finally {
        if (shouldPrepare) {
          this.isPreparingInitialStep = false;
        }
      }
    },
  },
  methods: {
    getUtmParameters() {
      const params = new URLSearchParams(window.location.search);
      const configuredUtm =
        window.config?.utm && typeof window.config.utm === "object"
          ? window.config.utm
          : {};

      return [
        "utm_source",
        "utm_medium",
        "utm_campaign",
        "utm_content",
        "utm_term",
      ].reduce((utm, key) => {
        const value = params.get(key) || configuredUtm[key];

        if (value) {
          utm[key] = value;
        }

        return utm;
      }, {});
    },
    buildEventUuid() {
      if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
      }

      return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, (char) => {
        const random = Math.random() * 16 | 0;
        const value = char === "x" ? random : (random & 0x3 | 0x8);

        return value.toString(16);
      });
    },
    async trackArticleBookingConversion() {
      const conversionConfig = window.config?.articleBookingConversion;

      if (!conversionConfig?.pageId) {
        return;
      }

      try {
        await axios.post("/api/article-booking-conversions", {
          page_id: conversionConfig.pageId,
          city_id: window.config?.booking?.siteCityId || null,
          event_uuid: this.buildEventUuid(),
          page_url: conversionConfig.pageUrl || window.location.href,
          page_path: conversionConfig.pagePath || window.location.pathname,
          entry_point: "booking_widget",
          booking_mode: this.selectedMode || this.mode || this.effectiveLaunchContext?.entry || null,
        });
      } catch (error) {
        console.warn("Article booking conversion tracking failed", error);
      }
    },
    shouldPrepareInitialStep() {
      if (!this.open || this.currentStep !== "start") {
        return false;
      }

      return (
        this.effectiveLaunchContext?.entry === "clinic" ||
        (this.effectiveLaunchContext?.entry === "doctor" &&
          !this.isDirectDoctorLaunchContext)
      );
    },
    getCacheEntry(cache, key, ttlMs) {
      return getTimestampedCacheEntry(cache, key, ttlMs)?.data || null;
    },
    setCacheEntry(cache, key, data) {
      setTimestampedCacheEntry(cache, key, { data });
    },
    getAdminDataCacheKey(parts = []) {
      return buildVersionedCacheKey(this.adminDataCacheEpoch, parts);
    },
    isAdminDataRequestCurrent(requestEpoch) {
      return isCurrentCacheVersion(requestEpoch, this.adminDataCacheEpoch);
    },
    getEmptySlotsMessage(lastAvailableDate) {
      if (
        this.loadingSlots ||
        (Array.isArray(this.slots) && this.slots.length > 0) ||
        !(this.selectedDate instanceof Date) ||
        Number.isNaN(this.selectedDate.getTime()) ||
        !(lastAvailableDate instanceof Date) ||
        Number.isNaN(lastAvailableDate.getTime())
      ) {
        return "Данный врач не принимает в выбранный день";
      }

      const selectedDayTs = new Date(
        this.selectedDate.getFullYear(),
        this.selectedDate.getMonth(),
        this.selectedDate.getDate()
      ).getTime();
      const lastAvailableDayTs = new Date(
        lastAvailableDate.getFullYear(),
        lastAvailableDate.getMonth(),
        lastAvailableDate.getDate()
      ).getTime();

      return selectedDayTs > lastAvailableDayTs
        ? "Расписание на эту дату ещё не доступно"
        : "Данный врач не принимает в выбранный день";
    },
    mergeLastAvailableDate(currentValue, nextValue) {
      const currentIsValid =
        currentValue instanceof Date && !Number.isNaN(currentValue.getTime());
      const nextIsValid =
        nextValue instanceof Date && !Number.isNaN(nextValue.getTime());

      if (!currentIsValid) {
        return nextIsValid ? nextValue : null;
      }

      if (!nextIsValid) {
        return currentValue;
      }

      return nextValue.getTime() > currentValue.getTime()
        ? nextValue
        : currentValue;
    },
    getDoctorApiId(doctor) {
      return doctor?.doctor_id ?? doctor?.id ?? null;
    },
    idsMatch(left, right) {
      if (left === null || left === undefined || right === null || right === undefined) {
        return false;
      }

      return String(left).trim() === String(right).trim();
    },
    findDoctorByLaunchId(doctors = [], doctorId = null) {
      if (!doctorId) {
        return null;
      }

      return (doctors || []).find((doctor) => {
        const candidates = [
          doctor?.id,
          doctor?.doctor_id,
          doctor?.ulid,
          doctor?.local_id,
          doctor?.site_id,
          doctor?.local_uuid,
          doctor?.uuid,
          doctor?.external_id,
        ];

        return candidates.some((value) => this.idsMatch(value, doctorId));
      }) || null;
    },
    findBranchByLaunchId(branches = [], branchId = null) {
      if (!branchId) {
        return null;
      }

      return (branches || []).find((branch) => {
        const candidates = [
          branch?.id,
          branch?.branch_id,
          branch?.external_id,
          branch?.uuid,
        ];

        return candidates.some((value) => this.idsMatch(value, branchId));
      }) || null;
    },
    getDoctorSelectionKey(doctor) {
      return doctor?.entry_key || doctor?.id || null;
    },
    extractLastAvailableDate(items = []) {
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
        .filter((value) => value instanceof Date && !Number.isNaN(value.getTime()))
        .sort((a, b) => a.getTime() - b.getTime());

      return dates[dates.length - 1] || null;
    },
    async withTransitionLoader(task, options = {}) {
      const showDelayMs = options.showDelayMs ?? 100;
      const minVisibleMs = options.minVisibleMs ?? 100;

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
    normalizeLaunchId(id) {
      return String(id || "").trim().toLowerCase();
    },
    preloadDirectDoctorLaunch() {
      const context = this.effectiveLaunchContext;
      if (context?.entry !== "doctor" || !context.doctorId) {
        return null;
      }

      const doctorId = this.normalizeLaunchId(context.doctorId);
      if (
        this.directDoctorLaunchPreload?.doctorId === doctorId &&
        this.directDoctorLaunchPreload?.promise
      ) {
        return this.directDoctorLaunchPreload.promise;
      }

      const promise = this.loadDoctorByLaunchId(context.doctorId, {
        ignoreBirthDate: true,
      });

      this.directDoctorLaunchPreload = {
        doctorId,
        promise,
      };

      return promise;
    },
    async getDirectDoctorLaunchPayload(doctorId) {
      const normalizedDoctorId = this.normalizeLaunchId(doctorId);
      const preload = this.directDoctorLaunchPreload;

      if (
        preload?.doctorId === normalizedDoctorId &&
        preload?.promise
      ) {
        return preload.promise;
      }

      return this.loadDoctorByLaunchId(doctorId, {
        ignoreBirthDate: true,
      });
    },
    getDirectDoctorScheduleWarmupKey(doctorId, birthDateIso) {
      return [
        this.normalizeLaunchId(doctorId),
        String(birthDateIso || ""),
        this.formatDateForApi(this.selectedDate),
      ].join("|");
    },
    preloadDirectDoctorSchedule(payload) {
      const context = this.effectiveLaunchContext;
      if (
        context?.entry !== "doctor" ||
        !context.doctorId ||
        !payload?.iso
      ) {
        return null;
      }

      const key = this.getDirectDoctorScheduleWarmupKey(
        context.doctorId,
        payload.iso
      );

      if (
        this.directDoctorScheduleWarmup?.key === key &&
        this.directDoctorScheduleWarmup?.promise
      ) {
        return this.directDoctorScheduleWarmup.promise;
      }

      const promise = this.warmDirectDoctorScheduleCaches({
        doctorId: context.doctorId,
        birthDateIso: payload.iso,
      });

      this.directDoctorScheduleWarmup = {
        key,
        promise,
      };

      return promise;
    },
    async warmDirectDoctorScheduleCaches({ doctorId, birthDateIso }) {
      await this.initCities();

      const launchDoctor = await this.getDirectDoctorLaunchPayload(doctorId);
      if (
        !launchDoctor ||
        !this.isDoctorAllowedForBirthDate(launchDoctor, birthDateIso)
      ) {
        return;
      }

      if (!this.clinics.length) {
        await this.loadClinics();
      }

      const enabledClinic = this.clinics.find(
        (clinic) => clinic.enabled !== false
      );
      const clinicId = enabledClinic?.id || null;
      const doctorApiId = this.getDoctorApiId(launchDoctor);
      const dateStr = this.formatDateForApi(this.selectedDate);

      if (!clinicId || !doctorApiId || !this.currentCityId) {
        return;
      }

      const { branches, defaultBranchId } =
        await this.getDoctorFlowBranchesAvailabilityWithCache({
          doctorId: doctorApiId,
          dateStr,
          clinicId,
          cityId: this.currentCityId,
        });
      const normalizedBranches = branches.map((branch) => ({
        ...branch,
        enabled: true,
      }));
      const warmupBranch = this.getWarmupDoctorFlowBranch(
        normalizedBranches,
        defaultBranchId
      );

      if (!warmupBranch?.id) {
        return;
      }

      await this.getDoctorSlotsWithCache({
        doctorId: doctorApiId,
        clinicId,
        branchId: warmupBranch.id,
        dateStr,
      });
    },
    async applyLaunchContext() {
      if (!this.open || this.currentStep !== "start") {
        return;
      }

      const context = this.effectiveLaunchContext;

      if (!context?.entry) {
        return;
      }

      this.selectedMode = context.entry;

      this.currentStep = "birth-date";
    },
    async openClinicFlowWithAutoBranchSkip(options = {}) {
      if (!this.patientBirthDateIso) {
        this.currentStep = "birth-date";
        return;
      }

      await this.loadCityBranches();

      const preselectedBranch = this.findBranchByLaunchId(
        this.cityBranches,
        options.branchId
      );

      if (preselectedBranch) {
        await this.handleBranchSelect(preselectedBranch);
        await this.goToClinicSchedule();
        return;
      }

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
    async openDoctorFlow(options = {}) {
      if (!this.patientBirthDateIso) {
        this.currentStep = "birth-date";
        return false;
      }

      await this.initCities();
      if (options.openSchedule === true && options.doctorId) {
        const launchDoctor = await this.getDirectDoctorLaunchPayload(
          options.doctorId
        );

        if (launchDoctor) {
          if (!this.isDoctorAllowedForBirthDate(launchDoctor)) {
            this.ageBlockedDoctor = launchDoctor;
            this.currentStep = "doctor-age-blocked";
            return false;
          }

          this.doctors = [launchDoctor];
          await this.handleDoctorSelect(launchDoctor);
          await this.goToDoctorSchedule({ renderImmediately: true });
          return true;
        }
      }

      if (options.openSchedule !== true) {
        this.currentStep = "doctor-select";
      }
      await this.loadDoctorsByCity();

      const preselectedDoctor = this.findDoctorByLaunchId(
        this.doctors,
        options.doctorId
      );

      if (preselectedDoctor) {
        await this.handleDoctorSelect(preselectedDoctor);

        if (options.openSchedule === true) {
          await this.goToDoctorSchedule({ renderImmediately: true });
        }

        return true;
      }

      if (options.openSchedule === true) {
        this.currentStep = "birth-date";
        return false;
      }

      this.currentStep = "doctor-select";
      return true;
    },
    async openDateFlow() {
      if (!this.patientBirthDateIso) {
        this.currentStep = "birth-date";
        return;
      }

      await this.initCities();
      this.currentStep = "date-select";

      await Promise.all([
        this.loadDateFlowDoctors(),
        this.updateDateFlowHighlightedDates(),
      ]);
    },
    isClinicAllowed(clinicId) {
      if (!this.allowedClinicIds.length) {
        return true;
      }

      return this.allowedClinicIds.includes(Number(clinicId));
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
    async enrichDoctorsWithSiteData(doctors) {
      const uuids = getDoctorExternalUuids(doctors);
      if (!uuids.length) {
        return [];
      }

      try {
        const cacheKey = this.getAdminDataCacheKey([uuids.join(",")]);
        const cachedPayload = this.getSiteDoctorsFromCache(cacheKey);
        const response = cachedPayload || (await bookingApi.getSiteDoctorsByUuids(uuids));
        if (!cachedPayload) {
          this.setSiteDoctorsToCache(cacheKey, response);
        }
        return mergeDoctorsWithSitePayload(doctors, response);
      } catch (e) {
        return [];
      }
    },
    async initCities() {
      if (this.allCities.length > 0) {
        return this.allCities;
      }

      if (this.initCitiesPromise) {
        return this.initCitiesPromise;
      }

      this.initCitiesPromise = (async () => {
        try {
          const response = await bookingApi.getCities();
          this.allCities = response.data || response || [];
        } catch (e) {
          this.allCities = [];
        } finally {
          this.initCitiesPromise = null;
        }

        return this.allCities;
      })();

      return this.initCitiesPromise;
    },
    async loadClinics() {
      await this.initCities();
      if (!this.currentCityId) return;

      const cacheKey = String(this.currentCityId);
      const cached = this.getCacheEntry(
        this.clinicsCacheByCity,
        cacheKey,
        this.clinicsCacheTtlMs
      );

      if (cached) {
        this.clinics = cached;
        return;
      }

      this.loadingClinics = true;
      try {
        const response = await bookingApi.getClinicsByCity(this.currentCityId);
        const list = response.data || response || [];
        this.clinics = list
          .filter((clinic) => this.isClinicAllowed(clinic.id))
          .map((c) => ({ ...c, enabled: true }));
        this.setCacheEntry(this.clinicsCacheByCity, cacheKey, this.clinics);
      } finally {
        this.loadingClinics = false;
      }
    },
    async loadDoctorsByCity() {
      const requestEpoch = this.adminDataCacheEpoch;
      await this.initCities();
      if (!this.isAdminDataRequestCurrent(requestEpoch) || !this.currentCityId) return;

      const cacheKey = this.getAdminDataCacheKey([
        this.currentCityId,
        this.patientBirthDateIso || "all",
      ]);
      const cached = this.doctorsCacheByCity[cacheKey];
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
        const response = await bookingApi.getDoctorsByCity(
          this.currentCityId,
          this.patientBirthDateIso || null
        );
        const doctors = response.data || response || [];
        const enrichedDoctors = await this.enrichDoctorsWithSiteData(doctors);
        if (!this.isAdminDataRequestCurrent(requestEpoch)) return;

        this.doctors = sortDoctorsByMinimumAge(
          this.filterDoctorsByBirthDate(enrichedDoctors),
          {
            primaryOrders: getDoctorSelectSortOrders(),
          }
        );
        this.doctorsCacheByCity[cacheKey] = {
          ts: Date.now(),
          data: this.doctors,
        };
      } finally {
        if (this.isAdminDataRequestCurrent(requestEpoch)) {
          this.loadingDoctors = false;
        }
      }
    },
    async loadDoctorByLaunchId(doctorId, options = {}) {
      const requestEpoch = this.adminDataCacheEpoch;
      await this.initCities();

      if (
        !this.isAdminDataRequestCurrent(requestEpoch) ||
        !doctorId ||
        !this.currentCityId
      ) {
        return null;
      }

      const birthDate = options.ignoreBirthDate === true
        ? null
        : this.patientBirthDateIso || null;
      const cacheKey = this.getAdminDataCacheKey([
        this.currentCityId,
        birthDate || "all",
        String(doctorId).trim().toLowerCase(),
      ]);
      const cached = this.getCacheEntry(
        this.doctorLaunchCacheByQuery,
        cacheKey,
        this.doctorsCacheTtlMs
      );

      if (cached) {
        return cached;
      }

      try {
        const response = await bookingApi.getDoctorLaunchPayload(
          doctorId,
          this.currentCityId,
          birthDate
        );
        const doctor = response?.data || null;

        if (!this.isAdminDataRequestCurrent(requestEpoch) || !doctor) {
          return null;
        }

        this.setCacheEntry(this.doctorLaunchCacheByQuery, cacheKey, doctor);
        return doctor;
      } catch (e) {
        return null;
      }
    },
    async loadDoctorsByClinic(clinicId, branchId = null) {
      if (!clinicId || !this.isClinicAllowed(clinicId)) {
        this.clinicDoctors = [];
        return;
      }
      const requestEpoch = this.adminDataCacheEpoch;
      this.loadingDoctors = true;
      try {
        const response = await bookingApi.getClinicDoctors(
          clinicId,
          this.patientBirthDateIso || null,
          branchId
        );
        const doctors = response.data || response || [];
        const enrichedDoctors = await this.enrichDoctorsWithSiteData(doctors);
        if (!this.isAdminDataRequestCurrent(requestEpoch)) return;

        this.clinicDoctors = sortDoctorsByMinimumAge(
          this.filterDoctorsByBirthDate(enrichedDoctors),
          {
            primaryOrders: getClinicDoctorSortOrders(),
            fallbackOrders: getDoctorSelectSortOrders(),
          }
        );
      } finally {
        if (this.isAdminDataRequestCurrent(requestEpoch)) {
          this.loadingDoctors = false;
        }
      }
    },
    async loadDateFlowDoctors(options = {}) {
      const requestEpoch = this.adminDataCacheEpoch;
      await this.initCities();

      if (
        !this.isAdminDataRequestCurrent(requestEpoch) ||
        !this.currentCityId ||
        !this.selectedDate
      ) {
        this.dateFlowDoctors = [];
        this.dateFlowDoctorShiftMap = {};
        return;
      }

      const keepSelectedDoctor = options.keepSelectedDoctor === true;
      const dateStr = this.formatDateForApi(this.selectedDate);
      const cacheKey = this.getAdminDataCacheKey([
        this.currentCityId,
        dateStr,
        this.patientBirthDateIso || "all",
      ]);
      const cached = this.dateFlowDoctorsCacheByQuery[cacheKey];

      if (
        cached &&
        Array.isArray(cached.data) &&
        Date.now() - cached.ts <= this.doctorsCacheTtlMs
      ) {
        this.dateFlowDoctors = cached.data;
        await this.setDefaultDoctorForDateFlow({ keepSelectedDoctor });
        return;
      }

      this.loadingDateFlowDoctors = true;

      try {
        const response = await bookingApi.getDoctorsByDate(
          this.currentCityId,
          dateStr,
          this.patientBirthDateIso || null
        );
        const doctors = Array.isArray(response?.data)
          ? response.data
          : Array.isArray(response)
          ? response
          : [];

        if (!this.isAdminDataRequestCurrent(requestEpoch)) return;

        this.dateFlowDoctors = this.filterDoctorsByBirthDate(doctors);
        this.dateFlowDoctorsCacheByQuery[cacheKey] = {
          ts: Date.now(),
          data: this.dateFlowDoctors,
        };

        await this.setDefaultDoctorForDateFlow({ keepSelectedDoctor });
      } finally {
        if (this.isAdminDataRequestCurrent(requestEpoch)) {
          this.loadingDateFlowDoctors = false;
        }
      }
    },
    async loadBranches(clinicId) {
      if (!clinicId) return;
      const requestEpoch = this.adminDataCacheEpoch;
      const response = await bookingApi.getClinicBranches(clinicId, this.currentCityId);
      if (!this.isAdminDataRequestCurrent(requestEpoch)) return;

      this.branches = response.data || response || [];
    },
    async loadCityBranches() {
      const requestEpoch = this.adminDataCacheEpoch;
      await this.initCities();
      if (!this.isAdminDataRequestCurrent(requestEpoch)) return;

      this.loadingCityBranches = true;
      try {
        if (!this.clinics.length) {
          await this.loadClinics();
        }
        if (!this.isAdminDataRequestCurrent(requestEpoch)) return;

        const cacheKey = this.getAdminDataCacheKey([
          this.currentCityId || "default",
        ]);
        const cached = this.getCacheEntry(
          this.cityBranchesCacheByCity,
          cacheKey,
          this.cityBranchesCacheTtlMs
        );

        if (cached) {
          this.cityBranches = cached;
          return;
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

        if (!this.isAdminDataRequestCurrent(requestEpoch)) return;

        this.cityBranches = branchGroups.flat();
        this.setCacheEntry(
          this.cityBranchesCacheByCity,
          cacheKey,
          this.cityBranches
        );
      } finally {
        if (this.isAdminDataRequestCurrent(requestEpoch)) {
          this.loadingCityBranches = false;
        }
      }
    },
    syncDoctorFlowBranches(nextBranches = []) {
      const currentById = new Map(
        (this.doctorFlowBranches || []).map((branch) => [Number(branch.id), branch])
      );

      const mergedBranches = (nextBranches || []).map((branch) => {
        const existing = currentById.get(Number(branch.id));

        if (!existing) {
          return { ...branch };
        }

        Object.keys(existing).forEach((key) => {
          if (!Object.prototype.hasOwnProperty.call(branch, key)) {
            delete existing[key];
          }
        });

        Object.assign(existing, branch);
        return existing;
      });

      this.doctorFlowBranches.splice(
        0,
        this.doctorFlowBranches.length,
        ...mergedBranches
      );

      return this.doctorFlowBranches;
    },
    getPreferredDoctorFlowBranch(
      branches = this.doctorFlowBranches,
      preferredBranchId = null
    ) {
      const enabledBranches = (branches || []).filter(
        (branch) => branch.enabled !== false
      );

      if (!enabledBranches.length) {
        return null;
      }

      const currentBranchId = this.selectedBranch?.id;
      const currentBranch = enabledBranches.find(
        (branch) => Number(branch.id) === Number(currentBranchId)
      );
      const preferredBranch = enabledBranches.find(
        (branch) => Number(branch.id) === Number(preferredBranchId)
      );
      const firstAvailableBranch = enabledBranches.find(
        (branch) => branch.has_available_slots === true
      );

      if (currentBranch?.has_available_slots === true) {
        return currentBranch;
      }

      if (preferredBranch?.has_available_slots === true) {
        return preferredBranch;
      }

      if (firstAvailableBranch) {
        return firstAvailableBranch;
      }

      return currentBranch || preferredBranch || enabledBranches[0];
    },
    getWarmupDoctorFlowBranch(branches = [], preferredBranchId = null) {
      const enabledBranches = (branches || []).filter(
        (branch) => branch.enabled !== false
      );

      if (!enabledBranches.length) {
        return null;
      }

      const preferredBranch = enabledBranches.find(
        (branch) => Number(branch.id) === Number(preferredBranchId)
      );
      const firstAvailableBranch = enabledBranches.find(
        (branch) => branch.has_available_slots === true
      );

      if (preferredBranch?.has_available_slots === true) {
        return preferredBranch;
      }

      return firstAvailableBranch || preferredBranch || enabledBranches[0];
    },
    async loadDoctorFlowBranches(clinicId, options = {}) {
      if (!clinicId || !this.selectedDoctor || !this.selectedDate) {
        this.doctorFlowBranches = [];
        this.selectedBranch = null;
        this.slots = [];
        this.selectedSlot = null;
        this.doctorFlowBranchesLoadedOnce = false;
        return;
      }
      const requestEpoch = this.adminDataCacheEpoch;
      const keepVisibleBranches =
        options.keepVisibleBranches === true &&
        this.doctorFlowBranchesLoadedOnce &&
        Array.isArray(this.doctorFlowBranches) &&
        this.doctorFlowBranches.length > 0;

      this.loadingDoctorFlowBranches = !keepVisibleBranches;
      try {
        const { branches, defaultBranchId } =
          await this.getDoctorFlowBranchesAvailabilityWithCache({
            doctorId: this.getDoctorApiId(this.selectedDoctor),
            dateStr: this.formatDateForApi(this.selectedDate),
            clinicId,
            cityId: this.currentCityId,
          });
        if (!this.isAdminDataRequestCurrent(requestEpoch)) return;

        const normalizedBranches = branches.map((branch) => ({
          ...branch,
          enabled: true,
        }));

        const mergedBranches = this.syncDoctorFlowBranches(normalizedBranches);
        this.doctorFlowBranchesLoadedOnce = true;
        await this.selectDoctorFlowBranchByDate(
          clinicId,
          mergedBranches,
          defaultBranchId,
          {
            skipSlotReloadIfCurrentBranchStillSelected:
              options.skipSlotReloadIfCurrentBranchStillSelected === true,
          }
        );
      } finally {
        if (this.isAdminDataRequestCurrent(requestEpoch)) {
          this.loadingDoctorFlowBranches = false;
        }
      }
    },
    async selectDoctorFlowBranchByDate(
      clinicId,
      branches = this.doctorFlowBranches,
      preferredBranchId = null,
      options = {}
    ) {
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

      const currentBranchId = this.selectedBranch?.id;
      const nextBranch = this.getPreferredDoctorFlowBranch(
        enabledBranches,
        preferredBranchId
      );

      this.selectedBranch = nextBranch;

      if (
        options.skipSlotReloadIfCurrentBranchStillSelected === true &&
        Number(currentBranchId) === Number(nextBranch?.id)
      ) {
        return;
      }

      await this.loadSlots({ autoSelectFirstAvailable: true });
    },
    async loadSlots(options = {}) {
      if (!this.selectedDoctor || !this.selectedDate) return;
      this.loadingSlots = true;
      try {
        const dateStr = this.formatDateForApi(this.selectedDate);
        this.slots = await this.getDoctorSlotsWithCache({
          doctorId: this.getDoctorApiId(this.selectedDoctor),
          clinicId: this.selectedClinic?.id || null,
          branchId: this.selectedBranch?.id || null,
          dateStr,
        });

        if (options.autoSelectFirstAvailable) {
          this.selectedSlot = this.findFirstAvailableSlot(this.slots);
        } else {
          this.selectedSlot = null;
        }
      } finally {
        this.loadingSlots = false;
      }
    },
    findFirstAvailableSlot(slots = []) {
      return getFirstAvailableSlot(
        slots,
        this.isSlotAvailable,
        this.slotComparableValue
      );
    },
    getSlotsCacheKey({ doctorId, clinicId = null, branchId = null, dateStr }) {
      return buildCompositeCacheKey([doctorId, clinicId, branchId, dateStr]);
    },
    getDoctorFlowBranchesAvailabilityCacheKey({
      doctorId,
      dateStr,
      clinicId,
      cityId,
    }) {
      return this.getAdminDataCacheKey([
        doctorId,
        dateStr,
        clinicId,
        cityId,
      ]);
    },
    getDoctorFlowBranchesAvailabilityFromCache(key) {
      return (
        getTimestampedCacheEntry(
          this.doctorFlowBranchesAvailabilityCacheByQuery,
          key,
          30 * 1000
        )?.payload || null
      );
    },
    setDoctorFlowBranchesAvailabilityToCache(key, payload) {
      setTimestampedCacheEntry(
        this.doctorFlowBranchesAvailabilityCacheByQuery,
        key,
        { payload }
      );
    },
    normalizeDoctorFlowBranchesAvailabilityResponse(response) {
      const branches = Array.isArray(response?.data)
        ? response.data
        : Array.isArray(response)
        ? response
        : [];

      return {
        branches,
        defaultBranchId: response?.meta?.default_branch_id ?? null,
      };
    },
    async getDoctorFlowBranchesAvailabilityWithCache({
      doctorId,
      dateStr,
      clinicId,
      cityId,
    }) {
      if (!doctorId || !dateStr || !clinicId || !cityId) {
        return {
          branches: [],
          defaultBranchId: null,
        };
      }

      const key = this.getDoctorFlowBranchesAvailabilityCacheKey({
        doctorId,
        dateStr,
        clinicId,
        cityId,
      });
      const cachedPayload = this.getDoctorFlowBranchesAvailabilityFromCache(key);
      if (cachedPayload) {
        return cachedPayload;
      }

      const response = await bookingApi.getDoctorBranchesAvailability(
        doctorId,
        dateStr,
        clinicId,
        cityId
      );
      const payload =
        this.normalizeDoctorFlowBranchesAvailabilityResponse(response);
      this.setDoctorFlowBranchesAvailabilityToCache(key, payload);

      return payload;
    },
    getSlotsFromCache(key) {
      return (
        getTimestampedCacheEntry(this.slotsCacheByQuery, key, 30 * 1000)
          ?.slots || null
      );
    },
    setSlotsToCache(key, slots) {
      setTimestampedCacheEntry(this.slotsCacheByQuery, key, {
        slots: Array.isArray(slots) ? slots : [],
      });
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
      return isSlotAvailableHelper(slot);
    },
    slotComparableValue(slot) {
      return slotComparableValueHelper(slot);
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
              const firstAvailable = this.findFirstAvailableSlot(slots);

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

        const withAvailable = sortResolvedByFirstAvailable(
          resolved,
          this.slotComparableValue
        );

        this.clinicDoctorShiftMap = buildDoctorShiftMap(resolved);

        const selectedEntry = pickClinicFlowSelectedEntry({
          resolved,
          withAvailable,
          keepSelectedDoctor,
          selectedDoctorId: this.selectedDoctor?.id ?? null,
          firstDoctorId: this.clinicDoctors[0]?.id ?? null,
        });

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
    async setDefaultDoctorForDateFlow(options = {}) {
      const keepSelectedDoctor = options.keepSelectedDoctor === true;

      if (this.currentStep !== "date-select") {
        return;
      }

      if (!this.dateFlowDoctors.length) {
        this.dateFlowDoctorShiftMap = {};
        this.selectedDoctor = null;
        this.selectedClinic = null;
        this.selectedBranch = null;
        this.slots = [];
        this.selectedSlot = null;
        return;
      }

      this.dateFlowDoctorShiftMap = this.dateFlowDoctors.reduce((acc, doctor) => {
        const key = this.getDoctorSelectionKey(doctor);

        if (key != null) {
          acc[String(key)] = Number(doctor?.available_slots || 0) > 0;
        }

        return acc;
      }, {});

      let selectedDoctor = null;

      if (keepSelectedDoctor && this.selectedDoctor) {
        const selectedKey = this.getDoctorSelectionKey(this.selectedDoctor);
        selectedDoctor =
          this.dateFlowDoctors.find(
            (doctor) => this.getDoctorSelectionKey(doctor) === selectedKey
          ) || null;
      }

      if (!selectedDoctor) {
        selectedDoctor = this.dateFlowDoctors[0] || null;
      }

      if (!selectedDoctor) {
        this.selectedDoctor = null;
        this.selectedClinic = null;
        this.selectedBranch = null;
        this.slots = [];
        this.selectedSlot = null;
        return;
      }

      this.selectedDoctor = selectedDoctor;
      this.selectedClinic =
        selectedDoctor.clinic ||
        (selectedDoctor.clinic_id
          ? {
              id: selectedDoctor.clinic_id,
              name: selectedDoctor.clinic_name,
            }
          : null);
      this.selectedBranch =
        selectedDoctor.branch ||
        (selectedDoctor.branch_id
          ? {
              id: selectedDoctor.branch_id,
              name: selectedDoctor.branch_name,
              address: selectedDoctor.branch_address,
            }
          : null);

      await this.loadSlots({ autoSelectFirstAvailable: true });
    },
    handleModeSelect(mode) {
      this.selectedMode = mode;
      this.currentStep = "birth-date";
    },
    filterDoctorsByBirthDate(doctors = []) {
      const patientAgeMonths = calculateAgeMonthsFromBirthDate(
        this.patientBirthDateIso
      );

      if (!Number.isFinite(patientAgeMonths)) {
        return doctors;
      }

      return (doctors || []).filter((doctor) => {
        const { minAgeMonths, maxAgeMonths } = getDoctorAgeRange(doctor);
        const isBelowMinimum =
          Number.isFinite(minAgeMonths) && patientAgeMonths < minAgeMonths;
        const isAboveMaximum =
          Number.isFinite(maxAgeMonths) && patientAgeMonths > maxAgeMonths;

        return !isBelowMinimum && !isAboveMaximum;
      });
    },
    isDoctorAllowedForBirthDate(doctor, birthDateIso = this.patientBirthDateIso) {
      return isDoctorAllowedForBirthDateHelper(doctor, birthDateIso);
    },
    buildDoctorAgeBlockedMessage(doctor) {
      return buildDoctorAgeBlockedMessageHelper(doctor);
    },
    resetFlowSelections() {
      this.selectedDoctor = null;
      this.selectedClinic = null;
      this.selectedBranch = null;
      this.selectedDate = new Date();
      this.selectedSlot = null;
      this.doctors = [];
      this.clinicDoctors = [];
      this.clinicDoctorShiftMap = {};
      this.dateFlowDoctors = [];
      this.dateFlowDoctorShiftMap = {};
      this.slots = [];
      this.branches = [];
      this.doctorFlowBranches = [];
      this.doctorFlowHighlightedDates = [];
      this.clinicFlowHighlightedDates = [];
      this.dateFlowHighlightedDates = [];
      this.doctorFlowLastAvailableDate = null;
      this.clinicFlowLastAvailableDate = null;
      this.dateFlowLastAvailableDate = null;
      this.loadingDateFlowDoctors = false;
      this.formSourceStep = null;
      this.ageBlockedDoctor = null;
    },
    handleBirthDateValidChange(payload) {
      if (
        !this.isDirectDoctorLaunchContext ||
        this.selectedMode !== "doctor" ||
        this.currentStep !== "birth-date"
      ) {
        return;
      }

      this.preloadDirectDoctorLaunch();

      if (payload?.iso) {
        void this.preloadDirectDoctorSchedule(payload);
      }
    },
    async handleBirthDateSubmit({ display, iso }) {
      const validationMessage = validateBirthDateDisplay(display);
      if (validationMessage || !iso) {
        return;
      }

      this.patientBirthDateDisplay = display;
      this.patientBirthDateIso = iso;
      const context = this.effectiveLaunchContext;
      const isDirectDoctorSubmit =
        this.selectedMode === "doctor" &&
        context?.entry === "doctor" &&
        Boolean(context.doctorId);

      if (this.selectedMode === "doctor") {
        const warmupPromise = isDirectDoctorSubmit
          ? this.preloadDirectDoctorSchedule({ display, iso })
          : null;

        this.resetFlowSelections();

        const openDoctor = async () => {
          if (warmupPromise && !isDirectDoctorSubmit) {
            await warmupPromise.catch(() => null);
          }

          await this.openDoctorFlow({
            doctorId: context?.entry === "doctor" ? context.doctorId : null,
            openSchedule: context?.entry === "doctor" && Boolean(context.doctorId),
          });
        };

        if (isDirectDoctorSubmit) {
          void warmupPromise?.catch(() => null);
          await openDoctor();
        } else {
          await this.withTransitionLoader(openDoctor);
        }
        return;
      }

      this.resetFlowSelections();

      if (this.selectedMode === "clinic") {
        await this.withTransitionLoader(async () => {
          await this.openClinicFlowWithAutoBranchSkip({
            branchId: context?.entry === "clinic" ? context.branchId : null,
          });
        });
        return;
      }

      if (this.selectedMode === "date") {
        await this.withTransitionLoader(async () => {
          await this.openDateFlow();
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
        void this.updateDoctorFlowHighlightedDates();
      }
      if (this.currentStep === "clinic-schedule") {
        await this.loadSlots();
        await this.updateClinicFlowHighlightedDates();
        return;
      }
      if (this.currentStep === "date-select") {
        this.selectedClinic =
          doctor?.clinic ||
          (doctor?.clinic_id
            ? {
                id: doctor.clinic_id,
                name: doctor.clinic_name,
              }
            : null);
        this.selectedBranch =
          doctor?.branch ||
          (doctor?.branch_id
            ? {
                id: doctor.branch_id,
                name: doctor.branch_name,
                address: doctor.branch_address,
              }
            : null);
        await this.loadSlots({ autoSelectFirstAvailable: true });
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
        void this.updateDoctorFlowHighlightedDates();
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
        if (this.selectedClinic?.id && this.selectedBranch?.id) {
          const slotsPromise = this.loadSlots({ autoSelectFirstAvailable: true });
          const branchesPromise = this.loadDoctorFlowBranches(this.selectedClinic.id, {
            keepVisibleBranches: true,
            skipSlotReloadIfCurrentBranchStillSelected: true,
          });
          await Promise.all([slotsPromise, branchesPromise]);
        } else if (this.selectedClinic?.id) {
          await this.loadDoctorFlowBranches(this.selectedClinic.id, {
            keepVisibleBranches: true,
          });
        } else {
          await this.loadSlots({ autoSelectFirstAvailable: true });
        }
        void this.updateDoctorFlowHighlightedDates();
        return;
      }
      if (this.currentStep === "clinic-schedule") {
        await this.setDefaultDoctorForClinicFlow({ keepSelectedDoctor: true });
        await this.updateClinicFlowHighlightedDates();
        return;
      }
      if (this.currentStep === "date-select") {
        await this.loadDateFlowDoctors({ keepSelectedDoctor: true });
        await this.updateDateFlowHighlightedDates();
        return;
      }
      await this.loadSlots();
    },
    handleSlotSelect(slot) {
      this.selectedSlot = slot;
    },
    goToStart() {
      this.currentStep = "start";
      this.selectedMode = null;
    },
    goToBirthDate() {
      this.currentStep = "birth-date";
    },
    async goToDateSelect() {
      if (!this.patientBirthDateIso) {
        this.currentStep = "birth-date";
        return;
      }

      await this.openDateFlow();
    },
    async goToDoctorSelect() {
      if (!this.patientBirthDateIso) {
        this.currentStep = "birth-date";
        return;
      }

      await this.openDoctorFlow();
    },
    async goToDoctorSelectFromAgeBlocked() {
      this.ageBlockedDoctor = null;
      this.selectedDoctor = null;
      await this.openDoctorFlow();
    },
    async goToClinicSelect() {
      if (!this.patientBirthDateIso) {
        this.currentStep = "birth-date";
        return;
      }

      if (!this.cityBranches.length) {
        await this.loadCityBranches();
      }

      if (this.cityBranches.length <= 1) {
        this.currentStep = "birth-date";
        return;
      }

      this.currentStep = "clinic-select";
    },
    async goToDoctorSchedule(options = {}) {
      const prepareSchedule = async () => {
        if (!this.clinics.length) {
          await this.loadClinics();
        }

        const enabledClinic = this.clinics.find((clinic) => clinic.enabled !== false);
        if (!this.selectedClinic && enabledClinic) {
          this.selectedClinic = enabledClinic;
        }

        const clinicId = this.selectedClinic?.id || null;
        this.currentStep = "doctor-schedule";

        if (clinicId) {
          await this.loadDoctorFlowBranches(clinicId);
        } else {
          this.doctorFlowBranches = [];
          this.selectedBranch = null;
          this.slots = [];
          this.selectedSlot = null;
        }

        void this.updateDoctorFlowHighlightedDates();
      };

      if (options.renderImmediately === true) {
        this.currentStep = "doctor-schedule";
        this.loadingDoctorFlowBranches = true;
        this.loadingSlots = true;

        void prepareSchedule().catch(() => {
          this.doctorFlowBranches = [];
          this.selectedBranch = null;
          this.slots = [];
          this.selectedSlot = null;
          this.loadingDoctorFlowBranches = false;
          this.loadingSlots = false;
        });
        return;
      }

      await this.withTransitionLoader(prepareSchedule);
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

      if (sourceStep === "date-select") {
        this.currentStep = "date-select";
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
            this.getDoctorApiId(this.selectedDoctor) || this.selectedSlot?.doctor_id || null,
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
          appointment_source: this.submissionSource,
          source: this.submissionSource,
          type: "Онлайн-запись",
          ...this.getUtmParameters(),
        };

        await bookingApi.createApplication(applicationData);

        if (typeof ym === "function") {
          ym(
            94302729,
            "reachGoal",
            this.callbackTarget === "otpravka-widget_article"
              ? this.callbackTarget
              : "bloki-otpravka-formy"
          );
        }

        this.currentStep = "success";
        void this.trackArticleBookingConversion();
      } catch (error) {
        if (error.status === 422 && error.errors) {
          this.$refs.patientForm?.setErrors(error.errors);
        } else if (
          error.status === 503 &&
          error.code === "booking_service_unavailable"
        ) {
          this.showBookingServiceUnavailable = true;
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
    clearAdminManagedDataCaches() {
      clearObjectCaches(
        this.cityBranchesCacheByCity,
        this.doctorsCacheByCity,
        this.doctorLaunchCacheByQuery,
        this.doctorFlowBranchesAvailabilityCacheByQuery,
        this.dateFlowDoctorsCacheByQuery,
        this.siteDoctorsCacheByUuids
      );
    },
    resetState() {
      this.adminDataCacheEpoch += 1;
      this.clearAdminManagedDataCaches();
      this.currentStep = "start";
      this.selectedMode = null;
      this.selectedDoctor = null;
      this.selectedClinic = null;
      this.selectedBranch = null;
      this.patientBirthDateDisplay = "";
      this.patientBirthDateIso = "";
      this.selectedDate = new Date();
      this.selectedSlot = null;
      this.slots = [];
      this.doctorFlowBranches = [];
      this.doctorFlowBranchesLoadedOnce = false;
      this.branches = [];
      this.cityBranches = [];
      this.doctors = [];
      this.clinicDoctors = [];
      this.clinicDoctorShiftMap = {};
      this.dateFlowDoctors = [];
      this.dateFlowDoctorShiftMap = {};
      this.doctorFlowHighlightedDates = [];
      this.clinicFlowHighlightedDates = [];
      this.dateFlowHighlightedDates = [];
      this.doctorFlowLastAvailableDate = null;
      this.clinicFlowLastAvailableDate = null;
      this.dateFlowLastAvailableDate = null;
      resetBookingLoadingFlags(this);
      this.isSubmitting = false;
      this.showBookingServiceUnavailable = false;
      this.formSourceStep = null;
      this.ageBlockedDoctor = null;
      this.directDoctorLaunchPreload = null;
      this.directDoctorScheduleWarmup = null;
      this.isPreparingInitialStep = false;
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
        this.doctorFlowLastAvailableDate = null;
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
        const lastAvailableDate = this.extractLastAvailableDate(items);
        if (
          lastAvailableDate &&
          (
            !(this.doctorFlowLastAvailableDate instanceof Date) ||
            lastAvailableDate.getTime() > this.doctorFlowLastAvailableDate.getTime()
          )
        ) {
          this.doctorFlowLastAvailableDate = lastAvailableDate;
        }
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
        this.clinicFlowLastAvailableDate = null;
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
        const lastAvailableDate = this.extractLastAvailableDate(items);
        if (
          lastAvailableDate &&
          (
            !(this.clinicFlowLastAvailableDate instanceof Date) ||
            lastAvailableDate.getTime() > this.clinicFlowLastAvailableDate.getTime()
          )
        ) {
          this.clinicFlowLastAvailableDate = lastAvailableDate;
        }
      } catch (e) {
        this.clinicFlowHighlightedDates = [];
      }
    },
    async updateDateFlowHighlightedDates() {
      if (this.currentStep !== "date-select" || !this.currentCityId) {
        this.dateFlowHighlightedDates = [];
        this.dateFlowLastAvailableDate = null;
        return;
      }

      const base =
        this.selectedDate instanceof Date
          ? this.selectedDate
          : new Date(this.selectedDate || Date.now());

      const { dateFrom, dateTo } = getMonthRange(base);
      const cacheKey = `${this.currentCityId}:${dateFrom}:${dateTo}:${this.patientBirthDateIso || "all"}`;
      const cached = this.getCacheEntry(
        this.dateFlowCalendarCacheByQuery,
        cacheKey,
        this.doctorsCacheTtlMs
      );

      if (cached) {
        const cachedItems = Array.isArray(cached.items) ? cached.items : [];
        this.dateFlowHighlightedDates = cachedItems
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
        this.dateFlowLastAvailableDate = this.mergeLastAvailableDate(
          this.dateFlowLastAvailableDate,
          cached.lastAvailableDate || null
        );
        return;
      }

      try {
        const response = await bookingApi.getDoctorsByDateCalendarAvailability(
          this.currentCityId,
          dateFrom,
          dateTo,
          this.patientBirthDateIso || null
        );
        const items = Array.isArray(response?.data)
          ? response.data
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

        this.dateFlowHighlightedDates = dates;
        this.dateFlowLastAvailableDate = this.mergeLastAvailableDate(
          this.dateFlowLastAvailableDate,
          this.extractLastAvailableDate(items)
        );
        this.setCacheEntry(this.dateFlowCalendarCacheByQuery, cacheKey, {
          items,
          lastAvailableDate: this.extractLastAvailableDate(items),
        });
      } catch (e) {
        this.dateFlowHighlightedDates = [];
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
