<template>
  <div class="clinic-schedule-step bg-white rounded-3xl overflow-hidden">
    <div class="flex items-start justify-between gap-4">
      <div class="w-full flex flex-col-reverse md:flex-row flex-wrap items-center gap-3 md:gap-6 md:px-10 md:py-7">
        <h2 class="hidden md:block text-center text-[28px] font-semibold leading-[1.2] text-interactive md:text-[34px]">
          Выберите дату, время и специалиста
        </h2>
      </div>
    </div>

    <div class="hidden md:block h-px w-full bg-surface-subdued"></div>

    <div class="md:bg-surface-subdued">
      <div class="grid grid-cols-1 items-stretch md:grid-cols-2 lg:grid-cols-[460px_460px] md:justify-between">
        <div v-show="showCalendarPane" class="p-4 pb-6 md:p-8 md:pb-10 bg-white">
          <div>
            <div class="flex items-center justify-center gap-4">
              <button
                type="button"
                class="before:content-none grid h-6 w-6 place-items-center rounded text-interactive transition-colors hover:bg-surface-subdued"
                aria-label="Предыдущий месяц"
                @click="goToPrevMonth"
              >
                <svg viewBox="0 0 16 16" class="h-4 w-4" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M10 3L5 8L10 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>

              <div class="text-xl font-semibold leading-[1.2] text-interactive">
                {{ monthTitle }}
              </div>

              <button
                type="button"
                class="grid h-6 w-6 place-items-center rounded text-interactive transition-colors hover:bg-surface-subdued"
                aria-label="Следующий месяц"
                @click="goToNextMonth"
              >
                <svg viewBox="0 0 16 16" class="h-4 w-4" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M6 3L11 8L6 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
            </div>

            <div class="mt-[23px]">
              <v-date-picker
                :key="calendarRenderKey"
                v-model="internalDate"
                :min-date="minDate"
                :attributes="calendarAttributes"
                color="orange"
                is-expanded
                @input="handleDate"
                class="booking-calendar"
              />
            </div>

            <div class="md:mt-3 text-center text-base font-semibold leading-[1.2] text-interactive">
              Время
            </div>

            <div class="mx-auto mt-4 w-full max-w-[444px] min-h-[92px]">
                <div
                  v-if="loadingDoctors || loading"
                  class="grid grid-cols-4 md:grid-cols-5 gap-1"
                  aria-hidden="true"
                >
                  <div
                    v-for="n in skeletonSlotsCount"
                    :key="`clinic-slot-skeleton-${n}`"
                    class="h-7 rounded-md bg-surface-subdued animate-pulse"
                  ></div>
                </div>

                <div v-else-if="!slots.length" class="text-center">
                  <p class="font-semibold">{{ emptySlotsMessage }}</p>
                  <p
                    v-if="emptySlotsMessage === 'Данный врач не принимает в выбранный день'"
                    class="text-sm text-interactive/50"
                  >
                    Пожалуйста выберите другой день
                  </p>
                </div>

                <div v-else class="grid grid-cols-4 md:grid-cols-5 w-full flex-wrap gap-1">
                  <button
                    v-for="slot in slots"
                    :key="slotKey(slot)"
                    type="button"
                    class="h-7 rounded-md border border-surface-subdued text-base font-semibold leading-[1.2]"
                    :class="slotClass(slot)"
                    :disabled="!isSlotAvailable(slot)"
                    @click="$emit('select-slot', slot)"
                  >
                    {{ slot.time }}
                  </button>
                </div>
            </div>

            <div class="mx-auto mt-6 md:mt-8 flex flex-col-reverse md:flex-row w-full max-w-[444px] gap-4">
              <SecondaryButton @click="handleBackClick">Назад</SecondaryButton>
              <PrimaryButton :disabled="calendarPrimaryButtonDisabled" @click="handleCalendarPrimaryClick">
                Далее
              </PrimaryButton>
            </div>
          </div>
        </div>

        <div
          v-show="showDoctorsPane"
          class="clinic-schedule-step__doctor-pane p-4 md:p-6 md:pr-4"
        >
          <div v-if="!hasDoctors && !loadingDoctors" class="hidden md:flex h-full items-center">
            <div class="w-full px-5 py-6 text-center">
              <p class="text-base font-semibold text-interactive">
                {{ emptyDoctorsMessage }}
              </p>
            </div>
          </div>

          <div v-else-if="loadingDoctors" class="flex min-h-[220px] items-center justify-center">
            <div class="flex flex-col items-center gap-3 text-interactive">
              <span class="h-8 w-8 animate-spin rounded-full border-2 border-surface-subdued border-t-action-primary"></span>
              <p class="text-sm font-semibold">Подбираем доступные варианты...</p>
            </div>
          </div>

          <div v-else class="h-full">
            <div class="md:hidden text-center text-[28px] font-semibold leading-none text-interactive mb-4">
              {{ mobileTitle }}
            </div>

            <div v-if="!isMobile" class="text-lg font-semibold text-center md:text-left">
              Специалисты
            </div>

            <div class="relative mt-6 md:mt-3 md:px-0 min-h-[300px]">
              
              <div
                ref="doctorList"
                class="clinic-schedule-step__doctor-list h-full max-h-64 max-h-[400px] md:max-h-[465px] space-y-3 overflow-y-auto md:pr-2 pb-7"
                @scroll="handleDoctorListScroll"
              >
                <button
                  v-for="doctor in doctors"
                  :key="doctorCardKey(doctor)"
                  type="button"
                  class="clinic-schedule-step__doctor-card w-full rounded-[16px] border-2 p-3 md:p-4 text-left transition-colors before:content-none"
                  :class="doctorCardClass(doctor)"
                  @click="$emit('select-doctor', doctor)"
                >
                  <span
                    v-if="isDoctorSelected(doctor)"
                    class="absolute right-3 top-3 h-4 w-4 grid md:h-6 md:w-6 place-items-center rounded-full bg-action-primary text-white"
                  >
                    <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M4 8.2L6.7 11L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </span>

                  <div class="flex items-center gap-4">
                    <div class="h-[60px] w-[60px] min-w-[60px] overflow-hidden rounded-full bg-surface-subdued border border-[#d8dee2]">
                      <img
                        v-if="doctorAvatar(doctor)"
                        :src="doctorAvatar(doctor)"
                        :alt="doctorName(doctor)"
                        class="h-full w-full object-contain object-top"
                        loading="lazy"
                      />
                    </div>

                    <div class="min-w-0 flex-1">
                      <div class="font-semibold text-interactive leading-none text-sm md:text-base">
                        {{ doctorName(doctor) }}
                      </div>
                      <div class="hidden md:block mt-1 text-xs text-interactive/80">
                        {{ doctorSpeciality(doctor) }}
                      </div>

                      <div v-if="doctorBranchAddress(doctor)" class="mt-2 md:mt-1 text-sm text-interactive flex flex-col md:flex-row md:gap-1">
                        <div v-if="doctorBranchMetro(doctor)" class="flex items-center gap-2 text-xs font-semibold text-interactive">
                          <img :src="metroIconSrc()" alt="Иконка метро" class="h-3.5 w-3.5">
                          <span>{{ doctorBranchMetro(doctor) }}, </span>
                        </div>
                        {{ doctorBranchAddress(doctor) }}
                      </div>

                      <div class="mt-1 md:mt-3 flex items-end justify-between gap-3">
                        <div class="text-xl font-semibold leading-normal text-interactive">
                          <template v-if="doctorPrice(doctor)">
                            {{ doctorPrice(doctor) }} <span class="text-sm">₽</span>
                          </template>
                        </div>

                        <button
                          v-if="doctorHasVideo(doctor)"
                          type="button"
                          class="rounded-lg border border-interactive px-2 md:px-4 py-2 text-xs font-semibold leading-[1.2] text-interactive transition-colors before:content-none"
                          :class="doctorHasVideo(doctor) ? 'cursor-pointer hover:bg-[#F6F7F9]' : 'cursor-default border-interactive/30 text-interactive/30'"
                          :disabled="!doctorHasVideo(doctor)"
                          @click.stop="handleOpenVideo(doctor)"
                        >
                          Видео-визитка
                        </button>
                      </div>
                    </div>
                  </div>

                  
                </button>
              </div>

              <div class="pointer-events-none absolute right-[2px] top-0 hidden h-full w-1 lg:block">
                <div
                  v-if="showDoctorListScrollThumb"
                  class="absolute h-6 w-1 rounded-[28px] bg-interactive"
                  :style="{ top: `${doctorListScrollThumbTop}px` }"
                ></div>
              </div>

              <div class="absolute inset-x-0 bottom-0 z-10 h-10 bg-gradient-to-t from-white via-white/70 md:from-[#EBF0F3] md:via-[#EBF0F3]/70 to-transparent pointer-events-none"></div>
            </div>

            <div v-if="isMobile" class="mx-auto mt-6 md:mt-8 flex w-full max-w-[444px] flex-col gap-4">
              <PrimaryButton :disabled="doctorPrimaryButtonDisabled" @click="handleDoctorPrimaryClick">
                Далее
              </PrimaryButton>
              <SecondaryButton @click="handleDoctorBackClick">Назад</SecondaryButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { eventBus } from "@/eventBus";
import { getDoctorDisplayPrice } from "@/utilities/doctorPrice";
import { addMonthsSafe } from "../utils/dateUtils";
import { getBranchMetro } from "../utils/branchUtils";
import {
  areSameSlot,
  isSlotAvailable as isSlotAvailableUtil,
} from "../utils/slotUtils";

const METRO_ICON_SRC = "/images/metro2.webp";
const StepHeader = () => import("./shared/StepHeader.vue");
const PrimaryButton = () => import("./shared/PrimaryButton.vue");
const SecondaryButton = () => import("./shared/SecondaryButton.vue");

export default {
  components: { StepHeader, PrimaryButton, SecondaryButton },
  props: {
    selectedDoctor: {
      type: Object,
      default: null,
    },
    selectedBranch: {
      type: Object,
      default: null,
    },
    doctors: {
      type: Array,
      default: () => [],
    },
    doctorShiftMap: {
      type: Object,
      default: () => ({}),
    },
    selectedDoctorId: {
      type: [String, Number],
      default: null,
    },
    selectedDoctorKey: {
      type: [String, Number],
      default: null,
    },
    selectedDate: {
      type: [Date, null],
      default: null,
    },
    highlightedDates: {
      type: Array,
      default: () => [],
    },
    slots: {
      type: Array,
      default: () => [],
    },
    emptySlotsMessage: {
      type: String,
      default: "Данный врач не принимает в выбранный день",
    },
    selectedSlot: {
      type: Object,
      default: null,
    },
    loadingDoctors: {
      type: Boolean,
      default: false,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    patientBirthDate: {
      type: String,
      default: "",
    },
    stepChipText: {
      type: String,
      default: "Шаг №3",
    },
    emptyDoctorsMessage: {
      type: String,
      default: "По этому запросу специалисты не найдены",
    },
    flowMode: {
      type: String,
      default: "clinic",
    },
  },
  data() {
    return {
      internalDate: this.selectedDate || new Date(),
      minDate: new Date(),
      viewportWidth:
        typeof window !== "undefined" ? window.innerWidth : 1024,
      mobileStage: this.flowMode === "date" ? "calendar" : "doctor",
      doctorListScrollThumbTop: 0,
      showDoctorListScrollThumb: false,
    };
  },
  computed: {
    isMobile() {
      return this.viewportWidth < 768;
    },
    showDoctorsPane() {
      return !this.isMobile || this.mobileStage === "doctor" || (this.flowMode === "date" && !this.hasDoctors);
    },
    showCalendarPane() {
      return !this.isMobile || this.mobileStage === "calendar" || (this.flowMode === "date" && !this.hasDoctors);
    },
    mobileTitle() {
      return this.mobileStage === "calendar" ? "Выберите дату и время" : "Выберите специалиста";
    },
    hasDoctors() {
      return Array.isArray(this.doctors) && this.doctors.length > 0;
    },
    activeDoctor() {
      if (this.selectedDoctor && Object.keys(this.selectedDoctor).length) {
        return this.selectedDoctor;
      }

      if (this.selectedDoctorKey !== null && this.selectedDoctorKey !== undefined) {
        const byKey = this.doctors.find(
          (doctor) => this.doctorSelectionKey(doctor) === this.selectedDoctorKey
        );
        if (byKey) {
          return byKey;
        }
      }

      if (this.selectedDoctorId != null) {
        const byId = this.doctors.find(
          (doctor) => this.doctorApiId(doctor) === this.selectedDoctorId
        );
        if (byId) {
          return byId;
        }
      }

      return this.doctors[0] || null;
    },
    canProceedFromDoctor() {
      return Boolean(this.activeDoctor?.id);
    },
    calendarPrimaryButtonDisabled() {
      if (this.isMobile && this.flowMode === "date") {
        return !this.selectedSlot || !this.doctors.length;
      }

      return !this.selectedSlot || !this.doctors.length;
    },
    doctorPrimaryButtonDisabled() {
      if (this.flowMode === "date") {
        return !this.selectedSlot || !this.canProceedFromDoctor;
      }

      return !this.canProceedFromDoctor;
    },
    monthTitle() {
      const date = this.internalDate instanceof Date
        ? this.internalDate
        : new Date(this.internalDate || Date.now());

      const value = date.toLocaleDateString("ru-RU", { month: "long", year: "numeric" });
      return value ? `${value.charAt(0).toUpperCase()}${value.slice(1)}` : "";
    },
    calendarRenderKey() {
      const date = this.internalDate instanceof Date
        ? this.internalDate
        : new Date(this.internalDate || Date.now());

      return `clinic-calendar-${date.getFullYear()}-${date.getMonth() + 1}`;
    },
    skeletonSlotsCount() {
      return 10;
    },
    calendarAttributes() {
      if (!Array.isArray(this.highlightedDates) || !this.highlightedDates.length) {
        return [];
      }

      return [
        {
          key: "clinic-available-dates",
          dates: this.highlightedDates,
          content: {
            class: "booking-calendar-day-has-slots",
          },
        },
      ];
    },
  },
  methods: {
    doctorSelectionKey(doctor) {
      return doctor?.entry_key || doctor?.id || null;
    },
    doctorApiId(doctor) {
      return doctor?.doctor_id ?? doctor?.id ?? null;
    },
    doctorCardKey(doctor) {
      return this.doctorSelectionKey(doctor) || this.doctorApiId(doctor) || "doctor-card";
    },
    doctorBranch(doctor) {
      return doctor?.branch || this.selectedBranch || null;
    },
    doctorName(doctor) {
      return doctor?.name || doctor?.full_name || "Выберите врача";
    },
    doctorAvatar(doctor) {
      return (
        doctor?.avatar_url ||
        doctor?.avatar_image ||
        doctor?.photo ||
        null
      );
    },
    doctorSpeciality(doctor) {
      return doctor?.speciality || doctor?.specialization || "Специалист";
    },
    doctorPrice(doctor) {
      return getDoctorDisplayPrice(
        doctor,
        this.doctorBranch(doctor),
        this.patientBirthDate
      );
    },
    doctorHasVideo(doctor) {
      return Boolean(doctor?.video_url);
    },
    metroIconSrc() {
      return METRO_ICON_SRC;
    },
    doctorCardClass(doctor) {
      if (this.isDoctorSelected(doctor)) {
        return "border-action-primary bg-action-primary/5";
      }

      if (this.flowMode === "date") {
        return "border-interactive/15 bg-white";
      }

      if (this.hasDoctorShifts(doctor)) {
        return "border-action-primary bg-white";
      }

      return "border-interactive/15 text-interactive/55 bg-white";
    },
    isDoctorSelected(doctor) {
      const activeKey = this.doctorSelectionKey(this.activeDoctor);
      const currentKey = this.doctorSelectionKey(doctor);

      if (activeKey && currentKey) {
        return currentKey === activeKey;
      }

      return Boolean(
        this.doctorApiId(this.activeDoctor) != null &&
        this.doctorApiId(doctor) === this.doctorApiId(this.activeDoctor)
      );
    },
    hasDoctorShifts(doctor) {
      const doctorKey = this.doctorSelectionKey(doctor) || this.doctorApiId(doctor);
      if (doctorKey == null) {
        return false;
      }

      return Boolean(this.doctorShiftMap[String(doctorKey)]);
    },
    doctorBranchMetro(doctor) {
      return getBranchMetro(this.doctorBranch(doctor));
    },
    doctorBranchAddress(doctor) {
      const branch = this.doctorBranch(doctor);
      const rawAddress = branch?.address;

      if (rawAddress === null || rawAddress === undefined) {
        return null;
      }

      const normalized = String(rawAddress).trim();

      return normalized || null;
    },
    handleOpenVideo(doctor) {
      if (!this.doctorHasVideo(doctor)) {
        return;
      }

      eventBus.$emit("showVideoModal", doctor.video_url);
    },
    handleDate(date) {
      if (!date) {
        return;
      }

      this.internalDate = date;
      this.$emit("select-date", date);
    },
    goToPrevMonth() {
      const nextDate = addMonthsSafe(this.internalDate, -1);
      this.handleDate(nextDate);
    },
    goToNextMonth() {
      const nextDate = addMonthsSafe(this.internalDate, 1);
      this.handleDate(nextDate);
    },
    goToMobileCalendarStage() {
      if (!this.canProceedFromDoctor) {
        return;
      }

      this.mobileStage = "calendar";
    },
    goToMobileDoctorStage() {
      if (!this.doctors.length) {
        return;
      }

      this.mobileStage = "doctor";
    },
    handleCalendarPrimaryClick() {
      if (this.isMobile && this.flowMode === "date") {
        this.goToMobileDoctorStage();
        return;
      }

      this.$emit("next");
    },
    handleDoctorPrimaryClick() {
      if (this.flowMode === "date") {
        this.$emit("next");
        return;
      }

      this.goToMobileCalendarStage();
    },
    handleBackClick() {
      if (this.isMobile && this.flowMode === "date" && this.mobileStage === "calendar") {
        this.$emit("back");
        return;
      }

      if (this.isMobile && this.mobileStage === "calendar") {
        this.mobileStage = "doctor";
        return;
      }

      this.$emit("back");
    },
    handleDoctorBackClick() {
      if (this.isMobile && this.flowMode === "date") {
        this.mobileStage = "calendar";
        return;
      }

      this.$emit("back");
    },
    syncMobileStage() {
      if (!this.isMobile) {
        return;
      }

      if (this.flowMode === "date") {
        if (this.mobileStage !== "calendar" && this.mobileStage !== "doctor") {
          this.mobileStage = "calendar";
        }
        return;
      }

      if (this.mobileStage !== "calendar") {
        this.mobileStage = "doctor";
      }
    },
    handleResize() {
      this.viewportWidth = window.innerWidth;
      this.syncMobileStage();
      this.updateDoctorListScrollThumb();
    },
    handleDoctorListScroll() {
      this.updateDoctorListScrollThumb();
    },
    updateDoctorListScrollThumb() {
      const el = this.$refs.doctorList;
      if (!el) {
        this.showDoctorListScrollThumb = false;
        this.doctorListScrollThumbTop = 0;
        return;
      }

      const hasOverflow = el.scrollHeight > el.clientHeight + 1;
      this.showDoctorListScrollThumb = hasOverflow;

      if (!hasOverflow) {
        this.doctorListScrollThumbTop = 0;
        return;
      }

      const thumbHeight = 24;
      const maxTrack = Math.max(el.clientHeight - thumbHeight, 0);
      const maxScroll = Math.max(el.scrollHeight - el.clientHeight, 1);

      this.doctorListScrollThumbTop = Math.round((el.scrollTop / maxScroll) * maxTrack);
    },
    slotKey(slot) {
      return slot?.id || slot?.datetime || slot?.time;
    },
    isSameSlot(slot) {
      return areSameSlot(slot, this.selectedSlot);
    },
    isSlotAvailable(slot) {
      return isSlotAvailableUtil(slot);
    },
    slotClass(slot) {
      if (!this.isSlotAvailable(slot)) {
        return "text-interactive/40 bg-surface-subdued";
      }

      if (this.isSameSlot(slot)) {
        return "text-white border-[#FF7D1A] bg-[radial-gradient(ellipse_at_center,_rgba(255,164,118,1)_0%,_rgba(255,150,89,1)_25%,_rgba(255,135,59,1)_50%,_rgba(255,121,30,1)_75%,_rgba(255,113,15,1)_87.5%,_rgba(255,106,0,1)_100%)]";
      }

      return "text-interactive hover:bg-[#F6F7F9]";
    },
  },
  mounted() {
    this.syncMobileStage();
    this.$nextTick(() => {
      this.updateDoctorListScrollThumb();
    });

    window.addEventListener("resize", this.handleResize);
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.handleResize);
  },
  watch: {
    selectedDate(newVal) {
      if (newVal) {
        this.internalDate = newVal;
      }
    },
    doctors() {
      this.syncMobileStage();
      this.$nextTick(() => {
        this.updateDoctorListScrollThumb();
      });
    },
    loadingDoctors() {
      this.$nextTick(() => {
        this.updateDoctorListScrollThumb();
      });
    },
  },
};
</script>

<style>
.clinic-schedule-step {
  --calendar-cell-width: 100%;
}

.clinic-schedule-step__doctor-list {
  scrollbar-width: none;
}

.clinic-schedule-step__doctor-list::-webkit-scrollbar {
  width: 0;
  height: 0;
}

.clinic-schedule-step__doctor-card {
  position: relative;
}

.clinic-schedule-step .booking-calendar .vc-arrows-container.title-center{
  display: none;
}

.clinic-schedule-step .booking-calendar  {
  border: 0;
  width: calc(var(--calendar-cell-width) * 7 + 24px);
  max-width: 100%;
}

.clinic-schedule-step .booking-calendar .vc-header {
  display: none;
}

.clinic-schedule-step .booking-calendar .vc-weeks {
  gap: 4px;
  grid-template-columns: repeat(7, minmax(0, var(--calendar-cell-width)));
  justify-content: center;
  padding: 0;
}

.clinic-schedule-step .booking-calendar .vc-weekday,
.clinic-schedule-step .booking-calendar .vc-day {
  width: var(--calendar-cell-width);
  min-width: var(--calendar-cell-width);
  height: 27px;
  min-height: 27px;
  margin: 0;
  padding: 0;
}

.clinic-schedule-step .booking-calendar .vc-weekday {
  align-items: center;
  background: #ebf0f3;
  border-radius: 4px;
  color: #1f3462;
  display: flex;
  font-family: "Gilroy", sans-serif;
  font-size: 16px;
  font-weight: 600;
  justify-content: center;
  line-height: 1.2;
  text-transform: capitalize;
}

.clinic-schedule-step .booking-calendar .vc-day-content {
  align-items: center;
  border: 1px solid #ebf0f3;
  border-radius: 4px;
  color: #1f3462;
  display: flex;
  font-size: 16px;
  font-weight: 600;
  height: 27px;
  justify-content: center;
  line-height: 1.2;
  margin: 0;
  width: var(--calendar-cell-width);
}

.clinic-schedule-step .booking-calendar .vc-day-content:hover {
  background: #f6f7f9;
}

.clinic-schedule-step .booking-calendar .vc-day-content:focus {
  background: transparent;
}

.clinic-schedule-step .booking-calendar .vc-day.in-prev-month .vc-day-content,
.clinic-schedule-step .booking-calendar .vc-day.in-next-month .vc-day-content,
.clinic-schedule-step .booking-calendar .vc-day .vc-day-content.is-disabled {
  color: rgba(0, 0, 0, 0.3);
}

.clinic-schedule-step .booking-calendar .vc-highlight,
.clinic-schedule-step .booking-calendar .vc-highlight-bg-solid,
.clinic-schedule-step .booking-calendar .vc-highlight-content-solid {
  background: radial-gradient(
    ellipse at center,
    rgba(255, 164, 118, 1) 0%,
    rgba(255, 150, 89, 1) 25%,
    rgba(255, 135, 59, 1) 50%,
    rgba(255, 121, 30, 1) 75%,
    rgba(255, 113, 15, 1) 87.5%,
    rgba(255, 106, 0, 1) 100%
  ) !important;
  border-radius: 4px;
}

.clinic-schedule-step .booking-calendar .vc-highlight,
.clinic-schedule-step .booking-calendar .vc-highlight-bg-solid,
.clinic-schedule-step .booking-calendar .vc-highlight-content-solid {
  width: 100% !important;
  height: 100% !important;
  border-radius: 4px !important;
}

.clinic-schedule-step .booking-calendar .vc-day .vc-day-content.is-highlighted,
.clinic-schedule-step .booking-calendar .vc-day .vc-day-content.is-selected,
.clinic-schedule-step .booking-calendar .vc-day .vc-highlight-content-solid {
  color: #ffffff !important;
}

.clinic-schedule-step .booking-calendar .vc-day-content.booking-calendar-day-has-slots {
  color: #f5841f !important;
  font-weight: 600 !important;
}

.clinic-schedule-step .booking-calendar .vc-highlights.vc-day-layer + .vc-day-content.booking-calendar-day-has-slots {
  color: #ffffff !important;
}

.clinic-schedule-step .booking-calendar .is-today .vc-day-content.booking-calendar-day-has-slots {
  color: #ffffff !important;
}

.clinic-schedule-step .booking-calendar .is-today .vc-day-content.vc-focusable.vc-content.booking-calendar-day-has-slots{
  color: #f5841f !important;
}
.clinic-schedule-step .booking-calendar .is-today .vc-highlights.vc-day-layer + .vc-day-content.vc-focusable.vc-content.booking-calendar-day-has-slots{
  color: #ffffff !important;
}

@media (max-width: 767px) {
  .clinic-schedule-step {
    --calendar-cell-width: 100%;
  }

  .clinic-schedule-step__mobile-title {
    margin-top: 4px;
  }
}

@media (max-width: 479px) {
  .clinic-schedule-step {
    --calendar-cell-width: 100%;
  }
}
</style>
