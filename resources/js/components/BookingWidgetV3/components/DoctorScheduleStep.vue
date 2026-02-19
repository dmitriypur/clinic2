<template>
  <div class="doctor-schedule-step bg-white">
    <div class="flex items-start justify-between gap-4">
      <div class="w-full flex flex-col-reverse md:flex-row flex-wrap items-center gap-3 md:gap-6">
        <h2 class="text-center text-[28px] font-semibold leading-[1.2] text-interactive md:text-[34px]">
          Выберите дату, время и филиал
        </h2>
        <span
          class="inline-flex h-[22px] items-center justify-center rounded-[4px] bg-[#F6F7F9] px-5 text-xs font-semibold leading-[1.2] text-[#1D1D1D] shadow-[0_0_1.8px_0_rgba(31,52,98,0.26)]"
        >
          Шаг №3
        </span>
      </div>
    </div>

    <div class="hidden md:block mt-5 h-px w-full bg-surface-subdued md:mt-7"></div>

    <div class="pb-6 pt-6 md:pb-10 md:pt-[34px]">
      <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[400px_444px] lg:justify-between lg:gap-[40px]">
        <div>
          <div class="flex items-center gap-[25px] rounded-[12px] border-2 border-surface-subdued bg-white pl-1 pr-6 py-2">
            <div class="h-[60px] w-[60px] overflow-hidden rounded-[8px] bg-white">
              <img
                v-if="doctorAvatar"
                :src="doctorAvatar"
                :alt="doctorName"
                class="h-full w-full object-cover"
                loading="lazy"
              />
            </div>

            <p class="text-base font-semibold leading-[1.2] text-interactive whitespace-pre-line">
              {{ doctorName }}
            </p>
          </div>

          <div class="mt-6 text-center text-xl font-semibold leading-[1.2] text-interactive md:mt-8">
            Филиалы
          </div>

          <div class="relative mt-2 ">
            <div
              v-if="loadingBranches"
              class="doctor-schedule-step__branches-scroll max-h-64 min-h-[272px] space-y-2 overflow-y-auto pr-3"
              aria-hidden="true"
            >
              <div
                v-for="n in branchSkeletonCount"
                :key="`doctor-branch-skeleton-${n}`"
                class="h-[64px] w-full rounded-[12px] border-2 border-surface-subdued bg-surface-subdued animate-pulse"
              ></div>
            </div>

            <div
              v-else
              ref="branchesList"
              class="doctor-schedule-step__branches-scroll h-full max-h-64 min-h-[272px] space-y-2 overflow-y-auto pr-3"
              @scroll="handleBranchScroll"
            >
              <button
                v-for="branch in branches"
                :key="branch.id"
                type="button"
                class="h-[64px] w-full rounded-[12px] border-2 px-6 py-[13px] text-left"
                :class="{
                  'border-surface-subdued bg-white': selectedBranchId !== branch.id,
                  'border-surface-subdued bg-surface-subdued': selectedBranchId === branch.id,
                  'opacity-40 cursor-not-allowed': branch.enabled === false,
                }"
                :disabled="branch.enabled === false"
                @click="$emit('select-branch', branch)"
              >
                <span class="text-base font-semibold leading-[1.2] text-interactive">
                  {{ branchLabel(branch) }}
                </span>
              </button>
            </div>

            <div class="pointer-events-none absolute bottom-0 left-0 h-[71px] w-full bg-gradient-to-b from-white/0 to-white"></div>

            <div class="pointer-events-none absolute right-[2px] top-0 hidden h-full w-1 lg:block">
              <div
                v-if="showBranchScrollThumb && !loadingBranches"
                class="absolute h-6 w-1 rounded-[28px] bg-interactive"
                :style="{ top: `${branchScrollThumbTop}px` }"
              ></div>
            </div>
          </div>
        </div>

        <div>
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

          <div class="hidden md:block mt-2 h-px w-full bg-surface-subdued"></div>

          <div class="md:mt-6 text-center text-base font-semibold leading-[1.2] text-interactive">
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
              <p class="font-semibold">Данный врач не принимает в выбранный день</p>
              <p class="text-sm text-interactive/50">Пожалуйста выберите другой день</p>
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

          <div class="mx-auto mt-8 flex flex-col md:flex-row w-full max-w-[444px] gap-4">
            <SecondaryButton @click="$emit('back')">Назад</SecondaryButton>
            <PrimaryButton :disabled="!selectedSlot" @click="$emit('next')">
              Далее
            </PrimaryButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { addMonthsSafe } from "../utils/dateUtils";
import {
  areSameSlot,
  isSlotAvailable as isSlotAvailableUtil,
} from "../utils/slotUtils";

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
  },
  data() {
    return {
      internalDate: this.selectedDate || new Date(),
      minDate: new Date(),
      branchScrollThumbTop: 0,
      showBranchScrollThumb: false,
    };
  },
  computed: {
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
    branchSkeletonCount() {
      return 4;
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
      if (!branch) {
        return "Филиал";
      }

      return branch.address || branch.name || branch.title || "Филиал";
    },
    handleBranchScroll() {
      this.updateBranchScrollThumb();
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
    this.$nextTick(() => {
      this.updateBranchScrollThumb();
    });

    window.addEventListener("resize", this.updateBranchScrollThumb);
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.updateBranchScrollThumb);
  },
  watch: {
    selectedDate(newVal) {
      if (newVal) {
        this.internalDate = newVal;
      }
    },
    branches() {
      this.$nextTick(() => {
        this.updateBranchScrollThumb();
      });
    },
    loadingBranches() {
      this.$nextTick(() => {
        this.updateBranchScrollThumb();
      });
    },
  },
};
</script>

<style>
.doctor-schedule-step {
  --calendar-cell-width: 60px;
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

@media (max-width: 1023px) {
  .doctor-schedule-step {
    --calendar-cell-width: 52px;
  }
}

@media (max-width: 767px) {
  .doctor-schedule-step {
    --calendar-cell-width: 46px;
  }
}

@media (max-width: 479px) {
  .doctor-schedule-step {
    --calendar-cell-width: 40px;
  }
}
</style>
