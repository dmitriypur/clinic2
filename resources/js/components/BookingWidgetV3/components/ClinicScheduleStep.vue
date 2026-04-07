<template>
  <div class="clinic-schedule-step overflow-hidden bg-white">
    <div class="flex items-start justify-between gap-4">
        <div class="w-full flex flex-col-reverse md:flex-row flex-wrap items-center gap-3 md:gap-6">
          <h2 class="hidden md:block text-center text-[28px] font-semibold leading-[1.2] text-interactive md:text-[34px]">
            Выберите дату и время
          </h2>
          <span
            class="inline-flex h-[22px] items-center justify-center rounded-[4px] bg-[#F6F7F9] px-5 text-xs font-semibold leading-[1.2] text-[#1D1D1D] shadow-[0_0_1.8px_0_rgba(31,52,98,0.26)]"
          >
            Шаг №3
          </span>
        </div>
      </div>

    <div class="hidden md:block mt-5 h-px w-full bg-surface-subdued md:mt-7"></div>

    <div class="pb-6 pt-6 md:pb-10 md:pt-[30px]">
      <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[400px_444px] lg:justify-between lg:gap-10">
        <div>
          <div v-if="doctors.length" class="clinic-schedule-step__doctors-scroll flex gap-2 overflow-x-auto pb-1">
            <button
              v-for="(doctor, index) in doctors"
              :key="doctor.id || index"
              type="button"
              class="min-w-[74px] shrink-0 rounded-[8px] border px-[10px] py-3 text-base font-semibold leading-[1.2] transition-colors"
              :class="doctorTabClass(doctor)"
              @click="$emit('select-doctor', doctor)"
            >
              {{ doctorTabTitle(doctor, index) }}
            </button>
          </div>

          <div
            v-else
            class="rounded-[12px] border border-surface-subdued bg-[#F6F7F9] px-4 py-3 text-sm font-medium text-interactive"
          >
            В выбранном филиале врачей нет
          </div>

          <div class="w-full flex items-center gap-4 md:hidden cursor-pointer rounded-xl border-2 bg-white px-2 py-1 mt-2">
            <div class="h-auto w-14 min-w-14 overflow-hidden rounded-lg md:rounded-full md:bg-surface-subdued md:h-16 md:w-16 md:min-w-16">
              <img
                v-if="doctorAvatar"
                :src="doctorAvatar"
                :alt="selectedDoctorName"
                class="h-full w-full object-cover"
                loading="lazy"
              />
            </div>
            <div class="w-2/3 md:w-1/2">
              <button
                type="button"
                class="text-xs font-semibold mb-1"
                :class="hasVideoVisit ? 'text-action-primary hover:underline cursor-pointer' : 'text-subdued cursor-default'"
                :disabled="!hasVideoVisit"
                @click="handleOpenVideo"
              >
                Видео-визитка
              </button>
              <div class="text-sm md:text-base font-semibold leading-tight">
                <template v-if="selectedDoctorNameParts.last">
                  {{ selectedDoctorNameParts.main }}<br>{{ selectedDoctorNameParts.last }}
                </template>
                <template v-else>
                  {{ selectedDoctorName }}
                </template>
              </div>
            </div>
          </div>

          <div v-if="doctors.length" class="hidden md:block relative mt-3 h-auto overflow-hidden rounded-[16px] border border-[rgba(29,29,29,0.2)] bg-white">
            <div class="absolute bottom-0 -right-12 md:right-0 h-[250px] w-[220px] overflow-hidden z-20">
              <img
                v-if="doctorAvatar"
                :src="doctorAvatar"
                :alt="selectedDoctorName"
                class="h-full w-full object-cover object-top"
                loading="lazy"
              />
            </div>

            <div class="relative z-10 p-3.5">
              <div class="flex flex-col gap-[6px]">
                <div class="flex gap-[3px] text-[#FF8A3B]">
                  <svg
                    v-for="star in 5"
                    :key="star"
                    viewBox="0 0 20 20"
                    class="h-4 w-4"
                    fill="currentColor"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path d="M10 1.6L12.6 6.9L18.4 7.8L14.2 11.8L15.2 17.5L10 14.8L4.8 17.5L5.8 11.8L1.6 7.8L7.4 6.9L10 1.6Z"/>
                  </svg>
                </div>

                <p class="text-[12px] font-semibold leading-[1.2] text-interactive/60">
                  100% пациентов <br>рекомендуют врача
                </p>
              </div>

              <p class="mt-[12px] text-[24px] font-semibold leading-[1.2] text-interactive whitespace-pre-line">
                <template v-if="selectedDoctorNameParts.last">
                  {{ selectedDoctorNameParts.main }}<br>{{ selectedDoctorNameParts.last }}
                </template>
                <template v-else>
                  {{ selectedDoctorName }}
                </template>
              </p>

              <div class="flex gap-2.5 flex-col relative mt-6">
                <div
                  v-for="chip in doctorChips"
                  :key="chip.title"
                  class="bg-transparent backdrop-blur-sm border border-l-[10px] py-3 px-2 rounded-md relative overflow-hidden before:absolute  before:white-to-gray-gradient before:inset-0 before:accessibility:content-[none] before:opacity-15 before:-z-10"
                  :class="chip.borderClass"
                  style="background: rgba(243, 250, 255, 0.3)"
                >
                  <p class="text-[12px] font-semibold leading-[1.2] text-interactive">
                    {{ chip.title }}:
                  </p>
                  <p class="text-[14px] font-normal leading-[1.4] text-interactive">
                    {{ chip.value }}
                  </p>
                </div>
              </div>
            </div>

            <button
              type="button"
              class="absolute right-3.5 top-3.5 flex inline-flex items-center gap-1.5 rounded-lg border border-interactive px-3 py-1.5 z-10"
              :class="hasVideoVisit ? 'text-interactive cursor-pointer' : 'text-interactive/40 border-interactive/40 cursor-default'"
              :disabled="!hasVideoVisit"
              @click="handleOpenVideo"
            >
              <svg width="15" height="20" viewBox="0 0 15 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11.0562 9.34246C11.0562 10.3122 10.6819 11.2421 10.0156 11.9278C9.34937 12.6135 8.44574 12.9987 7.50352 12.9987C6.56131 12.9987 5.65768 12.6135 4.99143 11.9278C4.32519 11.2421 3.95089 10.3122 3.95089 9.34246C3.95089 8.37276 4.32519 7.44278 4.99143 6.7571C5.65768 6.07142 6.56131 5.6862 7.50352 5.6862C8.44574 5.6862 9.34937 6.07142 10.0156 6.7571C10.6819 7.44278 11.0562 8.37276 11.0562 9.34246Z" :fill="hasVideoVisit ? '#1F3462' : '#1f346266'"/>
              <path d="M15 17.0625V5.48437L9.375 0H2.5C1.83696 0 1.20107 0.256807 0.732233 0.713927C0.263392 1.17105 0 1.79103 0 2.4375V17.0625C0 17.709 0.263392 18.329 0.732233 18.7861C1.20107 19.2432 1.83696 19.5 2.5 19.5H12.5C13.163 19.5 13.7989 19.2432 14.2678 18.7861C14.7366 18.329 15 17.709 15 17.0625ZM9.375 3.65625C9.375 4.1411 9.57254 4.60609 9.92417 4.94893C10.2758 5.29177 10.7527 5.48437 11.25 5.48437H13.75V16.7639C13.75 16.7639 12.5 14.625 7.5 14.625C2.5 14.625 1.25 16.7639 1.25 16.7639V2.4375C1.25 2.11427 1.3817 1.80427 1.61612 1.57571C1.85054 1.34715 2.16848 1.21875 2.5 1.21875H9.375V3.65625Z" :fill="hasVideoVisit ? '#1F3462' : '#1f346266'"/>
              </svg>
              <span class="text-[12px] font-semibold leading-[1.2]">Видео-визитка</span>
            </button>
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

            <div class="text-[20px] font-semibold leading-[1.2] text-interactive">
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
              trim-weeks
              :key="calendarRenderKey"
              v-model="internalDate"
              :min-date="minDate"
              :attributes="calendarAttributes"
              color="orange"
              is-expanded
              @input="handleDate"
              class="booking-calendar"
              :theme="{ highlight: { color: 'orange' } }"
            />
          </div>

          <div class="hidden md:block mt-6 h-px w-full bg-surface-subdued"></div>

          <div class="mt-4 md:mt-6 text-center text-base font-semibold leading-[1.2] text-interactive">
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

          <div class="mx-auto mt-6 flex flex-col md:flex-row w-full max-w-[444px] gap-4 md:mt-10">
            <SecondaryButton @click="$emit('back')">Назад</SecondaryButton>
            <PrimaryButton :disabled="!selectedSlot || !doctors.length" @click="$emit('next')">
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
    selectedDoctor: {
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
  },
  data() {
    return {
      internalDate: this.selectedDate || new Date(),
      minDate: new Date(),
    };
  },
  computed: {
    activeDoctor() {
      if (this.selectedDoctor && Object.keys(this.selectedDoctor).length) {
        return this.selectedDoctor;
      }

      if (this.selectedDoctorId != null) {
        const byId = this.doctors.find((doctor) => doctor?.id === this.selectedDoctorId);
        if (byId) {
          return byId;
        }
      }

      return this.doctors[0] || null;
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
    selectedDoctorName() {
      if (!this.activeDoctor) return "Выберите врача";
      return this.activeDoctor.name || this.activeDoctor.full_name || "Выберите врача";
    },
    selectedDoctorNameParts() {
      const fullName = (this.selectedDoctorName || "").trim();

      if (!this.activeDoctor || !fullName) {
        return { main: fullName, last: "" };
      }

      const words = fullName.split(/\s+/).filter(Boolean);
      if (words.length < 2) {
        return { main: fullName, last: "" };
      }

      return {
        main: words.slice(0, -1).join(" "),
        last: words[words.length - 1],
      };
    },
    doctorAvatar() {
      return (
        this.activeDoctor?.avatar_url ||
        this.activeDoctor?.avatar_image ||
        this.activeDoctor?.photo ||
        null
      );
    },
    hasVideoVisit() {
      return Boolean(this.activeDoctor?.video_url);
    },
    doctorChips() {
      return [
        {
          title: "Специальность",
          value: this.activeDoctor?.speciality || this.activeDoctor?.specialization || "—",
          borderClass: "border-l-blue-label w-8/12",
        },
        {
          title: "Ведёт приём",
          value: getDoctorReceivesDisplay(this.activeDoctor) || "—",
          borderClass: "border-l-action-primary w-6/12",
        },
        {
          title: "Врачебный стаж",
          value:
            this.activeDoctor?.seniority ||
            this.activeDoctor?.experience ||
            this.activeDoctor?.extra?.seniority ||
            "—",
          borderClass: "border-l-blue-label w-6/12 md:w-5/12",
        },
      ];
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
    doctorTabTitle(doctor, index) {
      return doctor?.tab_label || `Врач ${index + 1}`;
    },
    doctorTabClass(doctor) {
      const hasShifts = this.hasDoctorShifts(doctor);

      if (this.activeDoctor && doctor?.id === this.activeDoctor?.id) {
        return hasShifts
          ? "border-[#FF9B00] text-white bg-[radial-gradient(ellipse_at_center,_rgba(255,164,118,1)_0%,_rgba(255,150,89,1)_25%,_rgba(255,135,59,1)_50%,_rgba(255,121,30,1)_75%,_rgba(255,113,15,1)_87.5%,_rgba(255,106,0,1)_100%)]"
          : "border-transparent text-white bg-[radial-gradient(ellipse_at_center,_rgba(255,164,118,1)_0%,_rgba(255,150,89,1)_25%,_rgba(255,135,59,1)_50%,_rgba(255,121,30,1)_75%,_rgba(255,113,15,1)_87.5%,_rgba(255,106,0,1)_100%)]";
      }

      if (hasShifts) {
        return "border-[#FF9B00] bg-white text-interactive hover:border-[#FF9B00]";
      }

      return "border-[rgba(29,29,29,0.2)] bg-white text-interactive hover:border-[#FF9B00]";
    },
    hasDoctorShifts(doctor) {
      const doctorId = doctor?.id;
      if (doctorId == null) {
        return false;
      }

      return Boolean(this.doctorShiftMap[String(doctorId)]);
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
    handleOpenVideo() {
      if (!this.hasVideoVisit) {
        return;
      }

      eventBus.$emit("showVideoModal", this.activeDoctor.video_url);
    },
  },
  watch: {
    selectedDate(newVal) {
      if (newVal) {
        this.internalDate = newVal;
      }
    },
  },
};
</script>

<style>
.clinic-schedule-step {
  --calendar-cell-width: 60px;
}

.clinic-schedule-step__doctors-scroll {
  scrollbar-width: none;
}

.clinic-schedule-step__doctors-scroll::-webkit-scrollbar {
  width: 0;
  height: 0;
}

.clinic-schedule-step .booking-calendar .vc-arrows-container.title-center{
  display: none;
}

.clinic-schedule-step .booking-calendar {
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
  border-radius: 4px !important; /* или 0, если нужен совсем квадрат */
}

.clinic-schedule-step .booking-calendar .vc-day .vc-day-content.is-highlighted,
.clinic-schedule-step .booking-calendar .vc-day .vc-day-content.is-selected,
.clinic-schedule-step .booking-calendar .vc-day .vc-highlight-content-solid {
  color: #ffffff !important;
}
.clinic-schedule-step .booking-calendar .vc-day-content.booking-calendar-day-has-slots {
  color: #F5841F !important;
  font-weight: 600 !important;
}

.clinic-schedule-step .booking-calendar .is-today .vc-day-content.booking-calendar-day-has-slots {
  color: #ffffff;
}

@media (max-width: 1023px) {
  .clinic-schedule-step {
    --calendar-cell-width: 52px;
  }
}

@media (max-width: 767px) {
  .clinic-schedule-step {
    --calendar-cell-width: 46px;
  }
}

@media (max-width: 479px) {
  .clinic-schedule-step {
    --calendar-cell-width: 40px;
  }
}
</style>
