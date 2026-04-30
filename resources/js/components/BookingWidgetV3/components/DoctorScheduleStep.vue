<template>
  <div class="doctor-schedule-step bg-white rounded-3xl overflow-hidden">
    <div class="flex items-start justify-between gap-4">
      <div class="w-full flex flex-col-reverse md:flex-row flex-wrap items-center gap-3 md:gap-6 md:px-10 md:py-7">
        <h2 class="hidden md:block text-center text-[28px] font-semibold leading-[1.2] text-interactive md:text-[34px]">
          Выберите дату, время и филиал
        </h2>
      </div>
    </div>

    <div class="hidden md:block h-px w-full bg-surface-subdued"></div>

    <div class="md:bg-surface-subdued">
        <div class="grid grid-cols-1 items-stretch lg:grid-cols-[460px_460px] lg:justify-between">
          <div v-show="showCalendarPane" class="p-4 pb-6 md:p-8 md:pb-10 bg-white">
            <div class="flex items-center justify-center gap-4">
              <button
                type="button"
                class="grid h-6 w-6 place-items-center rounded text-interactive transition-colors hover:bg-surface-subdued"
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
                v-if="loading"
                class="grid grid-cols-4 md:grid-cols-5 gap-1"
                aria-hidden="true"
              >
                <div
                  v-for="n in skeletonSlotsCount"
                  :key="`doctor-slot-skeleton-${n}`"
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
              <PrimaryButton :disabled="!selectedSlot" @click="$emit('next')">
                Далее
              </PrimaryButton>
            </div>
          </div>

          <div v-show="showBranchPane" class="px-4 py-5 md:p-6">
            <p class="text-lg font-semibold text-center md:text-left">Выбранный врач</p>
            <div class="rounded-full border-2 border-[#e6ebef] bg-white p-2 mt-4 md:mt-2">
              <div class="flex items-center gap-4">
                <div class="h-[60px] w-[60px] min-w-[60px] overflow-hidden rounded-full bg-surface-subdued border border-[#d8dee2]">
                  <img
                    v-if="doctorAvatar"
                    :src="doctorAvatar"
                    :alt="doctorName"
                    class="h-full w-full object-contain object-top"
                    loading="lazy"
                  />
                </div>
                <div class="font-semibold text-interactive whitespace-pre-line leading-none">
                  <p>{{ doctorName }}</p>
                  <button
                    v-if="hasVideoVisit"
                    type="button"
                    class="text-xs text-action-primary"
                    @click="handleOpenVideo"
                  >
                    Видео-визитка
                  </button>
                </div>
              </div>
            </div>

            <div class="hidden md:block w-full mt-4 border-b border-dotted border-[#d8dee2]"></div>

            <div class="mt-4 text-xl font-semibold text-interactive text-center md:text-left">
              Адрес офтальмологии
            </div>

            <div class="relative mt-4 md:mt-2 px-4 md:px-0">
              <div
                v-if="loadingBranches"
                class="doctor-schedule-step__branches-scroll h-full max-h-64 md:max-h-[250px] space-y-4 overflow-y-auto pr-3"
                aria-hidden="true"
              >
                <div
                  v-for="n in branchSkeletonCount"
                  :key="`doctor-branch-skeleton-${n}`"
                  class="h-[24px] w-full rounded-xl bg-white animate-pulse"
                ></div>
              </div>

              <div
                v-else
                ref="branchesList"
                class="doctor-schedule-step__branches-scroll h-full max-h-64 md:max-h-[250px] space-y-4 overflow-y-auto pr-3"
                @scroll="handleBranchScroll"
              >
                <button
                  v-for="branch in branches"
                  :key="branch.id"
                  type="button"
                  class="h-auto w-full text-left relative before:w-5 before:h-5 before:rounded-full before:top-1/2 before:-translate-y-1/2 before:left-0 pl-8"
                  :class="{
                    'before:border-2 before:border-interactive/70': selectedBranchId !== branch.id,
                    'before:border-4 before:border-action-primary': selectedBranchId === branch.id,
                    'opacity-40 cursor-not-allowed': branch.enabled === false,
                  }"
                  :disabled="branch.enabled === false"
                  @click="$emit('select-branch', branch)"
                >
                  <div v-if="branchMetro(branch)" class="flex items-center gap-2 text-xs font-semibold">
                    <img :src="metroIconSrc()" alt="Иконка метро" class="w-3.5 h-3.5">
                    <p>{{ branchMetro(branch) }}</p>
                  </div>
                  <span class="text-interactive">
                    {{ branchLabel(branch) }}
                  </span>
                </button>
              </div>

              <div class="pointer-events-none absolute right-[2px] top-0 hidden h-full w-1 lg:block">
                <div
                  v-if="showBranchScrollThumb && !loadingBranches"
                  class="absolute h-6 w-1 rounded-[28px] bg-interactive"
                  :style="{ top: `${branchScrollThumbTop}px` }"
                ></div>
              </div>
            </div>

            <div v-if="doctorPrice" class="hidden md:block w-full mt-4 border-b border-dotted border-[#d8dee2]"></div>

            <div v-if="doctorPrice" class="hidden md:flex items-center text-xl mt-4">
              <p>Стоимость приёма: <b>{{ doctorPrice }}</b><span class="text-sm font-semibold"> ₽</span></p>
            </div>

            <div class="mx-auto mt-6 md:mt-8 flex w-full max-w-[444px] flex-col-reverse gap-4 md:hidden">
              <SecondaryButton @click="$emit('back')">Назад</SecondaryButton>
              <PrimaryButton :disabled="!canProceedFromBranch" @click="goToMobileCalendarStage">
                Далее
              </PrimaryButton>
            </div>
          </div>
        </div>
      </div>
  </div>
</template>

<script>
import { eventBus } from "@/eventBus";
import { getDoctorReceivesDisplay } from "@/utilities/doctorAge";
import { getDoctorDisplayPrice } from "@/utilities/doctorPrice";
import { addMonthsSafe } from "../utils/dateUtils";
import { getBranchAddressLine, getBranchMetro } from "../utils/branchUtils";
import {
  areSameSlot,
  isSlotAvailable as isSlotAvailableUtil,
} from "../utils/slotUtils";

const METRO_ICON_SRC = "/images/metro2.webp";
const PrimaryButton = () => import("./shared/PrimaryButton.vue");
const SecondaryButton = () => import("./shared/SecondaryButton.vue");

export default {
  components: { PrimaryButton, SecondaryButton },
  props: {
    doctor: {
      type: Object,
      default: null,
    },
    clinic: {
      type: Object,
      default: null,
    },
    branches: {
      type: Array,
      default: () => [],
    },
    selectedBranch: {
      type: Object,
      default: null,
    },
    selectedBranchId: {
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
    loading: {
      type: Boolean,
      default: false,
    },
    loadingBranches: {
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
  },
  data() {
    return {
      internalDate: this.selectedDate || new Date(),
      minDate: new Date(),
      branchScrollThumbTop: 0,
      showBranchScrollThumb: false,
      viewportWidth:
        typeof window !== "undefined" ? window.innerWidth : 1024,
      mobileStage: "branch",
    };
  },
  computed: {
    isMobile() {
      return this.viewportWidth < 768;
    },
    availableBranches() {
      return (this.branches || []).filter((branch) => branch?.enabled !== false);
    },
    isSingleBranchForMobile() {
      return this.isMobile && this.availableBranches.length <= 1;
    },
    showBranchPane() {
      return !this.isMobile || (!this.isSingleBranchForMobile && this.mobileStage === "branch");
    },
    showCalendarPane() {
      return !this.isMobile || this.isSingleBranchForMobile || this.mobileStage === "calendar";
    },
    canProceedFromBranch() {
      return Boolean(this.selectedBranchId);
    },
    monthTitle() {
      const date = this.internalDate instanceof Date
        ? this.internalDate
        : new Date(this.internalDate || Date.now());

      const value = date.toLocaleDateString("ru-RU", {
        month: "long",
        year: "numeric",
      });

      return value ? `${value.charAt(0).toUpperCase()}${value.slice(1)}` : "";
    },
    calendarRenderKey() {
      const date = this.internalDate instanceof Date
        ? this.internalDate
        : new Date(this.internalDate || Date.now());

      return `doctor-calendar-${date.getFullYear()}-${date.getMonth() + 1}`;
    },
    doctorName() {
      return this.doctor?.name || this.doctor?.full_name || "Выберите врача";
    },
    doctorAvatar() {
      return (
        this.doctor?.avatar_url ||
        this.doctor?.avatar_image ||
        this.doctor?.photo ||
        null
      );
    },
    hasVideoVisit() {
      return Boolean(this.doctor?.video_url);
    },
    doctorUrl() {
      return this.doctor?.id || null;
    },
    doctorPrice() {
      return getDoctorDisplayPrice(
        this.doctor,
        this.selectedBranch,
        this.patientBirthDate
      );
    },
    branchSkeletonCount() {
      return 3;
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
          key: "doctor-available-dates",
          dates: this.highlightedDates,
          content: {
            class: "booking-calendar-day-has-slots",
          },
        },
      ];
    },
  },
  methods: {
    doctorReceivesDisplay(doctor) {
      return getDoctorReceivesDisplay(doctor) || "—";
    },
    handleOpenVideo() {
      if (!this.hasVideoVisit) {
        return;
      }

      eventBus.$emit("showVideoModal", this.doctor.video_url);
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
    branchLabel(branch) {
      return getBranchAddressLine(branch);
    },
    branchMetro(branch) {
      return getBranchMetro(branch);
    },
    metroIconSrc() {
      return METRO_ICON_SRC;
    },
    handleBranchScroll() {
      this.updateBranchScrollThumb();
    },
    handleResize() {
      this.viewportWidth = window.innerWidth;
      this.syncMobileStage();
      this.updateBranchScrollThumb();
    },
    goToMobileCalendarStage() {
      if (!this.canProceedFromBranch) {
        return;
      }

      this.mobileStage = "calendar";
    },
    handleBackClick() {
      if (
        this.isMobile &&
        this.mobileStage === "calendar" &&
        !this.isSingleBranchForMobile
      ) {
        this.mobileStage = "branch";
        return;
      }

      this.$emit("back");
    },
    syncMobileStage() {
      if (!this.isMobile) {
        return;
      }

      if (this.isSingleBranchForMobile) {
        this.mobileStage = "calendar";
      }
    },
    updateBranchScrollThumb() {
      const el = this.$refs.branchesList;
      if (!el) {
        this.showBranchScrollThumb = false;
        this.branchScrollThumbTop = 0;
        return;
      }

      const hasOverflow = el.scrollHeight > el.clientHeight + 1;
      this.showBranchScrollThumb = hasOverflow;

      if (!hasOverflow) {
        this.branchScrollThumbTop = 0;
        return;
      }

      const thumbHeight = 24;
      const maxTrack = Math.max(el.clientHeight - thumbHeight, 0);
      const maxScroll = Math.max(el.scrollHeight - el.clientHeight, 1);

      this.branchScrollThumbTop = Math.round((el.scrollTop / maxScroll) * maxTrack);
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
        return "text-white bg-[radial-gradient(ellipse_at_center,_rgba(255,164,118,1)_0%,_rgba(255,150,89,1)_25%,_rgba(255,135,59,1)_50%,_rgba(255,121,30,1)_75%,_rgba(255,113,15,1)_87.5%,_rgba(255,106,0,1)_100%)]";
      }

      return "text-interactive";
    },
  },
  mounted() {
    this.syncMobileStage();
    this.$nextTick(() => {
      this.updateBranchScrollThumb();
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
    branches() {
      this.syncMobileStage();
      this.$nextTick(() => {
        this.updateBranchScrollThumb();
      });
    },
    selectedBranchId() {
      this.syncMobileStage();
    },
    loadingBranches() {
      this.syncMobileStage();
      this.$nextTick(() => {
        this.updateBranchScrollThumb();
      });
    },
  },
};
</script>

<style>
.doctor-schedule-step {
  --calendar-cell-width: 100%;
}

.doctor-schedule-step__branches-scroll {
  scrollbar-width: none;
}

.doctor-schedule-step__branches-scroll::-webkit-scrollbar {
  width: 0;
  height: 0;
}
.doctor-schedule-step .booking-calendar .vc-arrows-container.title-center{
  display: none;
}
.doctor-schedule-step .booking-calendar  {
  border: 0;
  width: calc(var(--calendar-cell-width) * 7 + 24px);
  max-width: 100%;
}

.doctor-schedule-step .booking-calendar .vc-header {
  display: none;
}

.doctor-schedule-step .booking-calendar .vc-weeks {
  gap: 4px;
  grid-template-columns: repeat(7, minmax(0, var(--calendar-cell-width)));
  justify-content: center;
  padding: 0;
}

.doctor-schedule-step .booking-calendar .vc-weekday,
.doctor-schedule-step .booking-calendar .vc-day {
  width: var(--calendar-cell-width);
  min-width: var(--calendar-cell-width);
  height: 27px;
  min-height: 27px;
  margin: 0;
  padding: 0;
}

.doctor-schedule-step .booking-calendar .vc-weekday {
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

.doctor-schedule-step .booking-calendar .vc-day-content {
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

.doctor-schedule-step .booking-calendar .vc-day-content:hover {
  background: #f6f7f9;
}

.doctor-schedule-step .booking-calendar .vc-day-content:focus {
  background: transparent;
}

.doctor-schedule-step .booking-calendar .vc-day.in-prev-month .vc-day-content,
.doctor-schedule-step .booking-calendar .vc-day.in-next-month .vc-day-content,
.doctor-schedule-step .booking-calendar .vc-day .vc-day-content.is-disabled {
  color: rgba(0, 0, 0, 0.3);
}

.doctor-schedule-step .booking-calendar .vc-highlight,
.doctor-schedule-step .booking-calendar .vc-highlight-bg-solid,
.doctor-schedule-step .booking-calendar .vc-highlight-content-solid {
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

.doctor-schedule-step .booking-calendar .vc-highlight,
.doctor-schedule-step .booking-calendar .vc-highlight-bg-solid,
.doctor-schedule-step .booking-calendar .vc-highlight-content-solid {
  width: 100% !important;
  height: 100% !important;
  border-radius: 4px !important; /* или 0, если нужен совсем квадрат */
}

.doctor-schedule-step .booking-calendar .vc-day .vc-day-content.is-highlighted,
.doctor-schedule-step .booking-calendar .vc-day .vc-day-content.is-selected,
.doctor-schedule-step .booking-calendar .vc-day .vc-highlight-content-solid {
  color: #ffffff !important;
}
.doctor-schedule-step .booking-calendar .vc-day-content.booking-calendar-day-has-slots {
  color: #F5841F !important;
  font-weight: 600 !important;
}

.doctor-schedule-step .booking-calendar .vc-highlights + .vc-day-content{
  color: #ffffff !important;
}

.clinic-schedule-step .booking-calendar .is-today .vc-day-content.vc-focusable.vc-content.booking-calendar-day-has-slots{
  color: #f5841f !important;
}
.clinic-schedule-step .booking-calendar .is-today .vc-highlights.vc-day-layer + .vc-day-content.vc-focusable.vc-content.booking-calendar-day-has-slots{
  color: #ffffff !important;
}
</style>
