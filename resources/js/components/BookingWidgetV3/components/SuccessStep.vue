<template>
  <div class="booking-success-step bg-white min-w-80 ">
    <div class="px-4 pb-8 pt-12 md:px-14 md:py-8">
      <h2 class="text-center text-[28px] font-semibold leading-[1.15] text-interactive md:text-[36px]">
        Вы записаны!
      </h2>

      <div class="mx-auto mt-8 flex w-full max-w-[404px] flex-col gap-3 md:mt-9 md:max-w-[420px]">
        <div class="rounded-full border border-surface-subdued bg-white px-4 py-3 md:px-5">
          <div class="flex items-center gap-4">
            <div class="h-[56px] w-[56px] shrink-0 overflow-hidden rounded-full border border-[#d8dee2] bg-surface-subdued">
              <img
                v-if="doctorAvatar"
                :src="doctorAvatar"
                :alt="doctorDisplayName"
                class="h-full w-full object-cover"
                loading="lazy"
              />
            </div>

            <div class="min-w-0">
              <div class="text-base font-semibold leading-[1.2] text-interactive md:text-lg">
                {{ doctorDisplayName }}
              </div>

              <div v-if="doctorSpeciality" class="mt-1 text-sm leading-[1.2] text-action-primary">
                {{ doctorSpeciality }}
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-full border border-surface-subdued bg-white px-4 py-4 md:px-5 md:py-[18px]">
          <div class="text-base font-semibold leading-[1.2] text-interactive">
            Адрес приёма
          </div>

          <div class="mt-2 text-base leading-[1.2] text-interactive">
            {{ addressLine }}
          </div>
        </div>

        <div class="rounded-full border border-surface-subdued bg-white px-4 py-4 md:px-5 md:py-[18px]">
          <div class="text-base font-semibold leading-[1.2] text-interactive">
            Дата и время
          </div>

          <div class="mt-2 text-base leading-[1.2] text-interactive">
            {{ formattedDateTime }}
          </div>
        </div>
      </div>

      <div class="mx-auto mt-8 flex w-full max-w-[404px] flex-col items-center md:mt-10 md:max-w-[420px]">
        <div class="text-center text-base leading-[1.2] text-interactive md:text-[18px]">
          Добавить к себе в календарь:
        </div>

        <div class="mt-5 flex items-start justify-center gap-6 md:gap-8">
          <a
            v-for="item in calendarLinks"
            :key="item.key"
            :href="item.href"
            :download="item.download || null"
            :target="item.target"
            :rel="item.rel"
            class="group flex w-[60px] flex-col items-center text-center no-underline"
          >
            <picture class="block h-[48px] w-[48px]">
              <source :srcset="item.webpSrc" type="image/webp" />
              <img
                :src="item.pngSrc"
                :alt="item.label"
                class="h-[48px] w-[48px] object-contain transition-transform duration-150 group-hover:scale-[1.04]"
                loading="lazy"
              />
            </picture>

            <span class="mt-2 text-[14px] leading-none text-interactive">
              {{ item.label }}
            </span>
          </a>
        </div>

        <a
          v-if="chatBotUrl"
          :href="chatBotUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="mt-10 text-center text-base leading-[1.2] text-action-primary underline underline-offset-4 md:mt-12"
        >
          Чат-бот Ангелы зрения
        </a>
      </div>
    </div>
  </div>
</template>

<script>
import { getBranchAddressLine } from "../utils/branchUtils";

const CALENDAR_ICON_BASE = "/images/calendar-icons";

export default {
  props: {
    selectedDoctor: {
      type: Object,
      default: null,
    },
    doctorName: {
      type: String,
      default: null,
    },
    clinicName: {
      type: String,
      default: null,
    },
    selectedBranch: {
      type: Object,
      default: null,
    },
    branchName: {
      type: String,
      default: null,
    },
    appointmentDate: {
      type: [Date, null],
      default: null,
    },
    appointmentTime: {
      type: [String, null],
      default: null,
    },
  },
  computed: {
    doctorDisplayName() {
      return (
        this.selectedDoctor?.name ||
        this.selectedDoctor?.full_name ||
        this.doctorName ||
        "—"
      );
    },
    doctorSpeciality() {
      return (
        this.selectedDoctor?.speciality ||
        this.selectedDoctor?.specialization ||
        null
      );
    },
    doctorAvatar() {
      return (
        this.selectedDoctor?.avatar_url ||
        this.selectedDoctor?.avatar_image ||
        this.selectedDoctor?.photo ||
        null
      );
    },
    formattedDateTime() {
      const dateTime = this.appointmentStartDate;

      if (!dateTime) {
        return "—";
      }

      const dateLabel = dateTime.toLocaleDateString("ru-RU", {
        day: "numeric",
        month: "long",
      });

      return `${dateLabel}, ${this.appointmentTime}`;
    },
    addressLine() {
      if (this.selectedBranch) {
        return getBranchAddressLine(this.selectedBranch);
      }

      if (this.branchName) {
        return this.branchName;
      }

      if (this.clinicName) {
        return this.clinicName;
      }

      return "—";
    },
    appointmentStartDate() {
      if (!(this.appointmentDate instanceof Date) || Number.isNaN(this.appointmentDate.getTime())) {
        return null;
      }

      const [hours, minutes] = String(this.appointmentTime || "")
        .split(":")
        .map((part) => Number(part));

      const date = new Date(this.appointmentDate.getTime());

      if (Number.isFinite(hours)) {
        date.setHours(hours);
      }

      if (Number.isFinite(minutes)) {
        date.setMinutes(minutes);
      }

      date.setSeconds(0);
      date.setMilliseconds(0);

      return date;
    },
    appointmentEndDate() {
      if (!this.appointmentStartDate) {
        return null;
      }

      return new Date(this.appointmentStartDate.getTime() + 30 * 60 * 1000);
    },
    calendarTitle() {
      return `Приём у врача: ${this.doctorDisplayName}`;
    },
    calendarDescription() {
      return this.doctorSpeciality
        ? `${this.doctorDisplayName}. ${this.doctorSpeciality}`
        : this.doctorDisplayName;
    },
    googleCalendarHref() {
      if (!this.appointmentStartDate || !this.appointmentEndDate) {
        return "#";
      }

      const params = new URLSearchParams({
        action: "TEMPLATE",
        text: this.calendarTitle,
        details: this.calendarDescription,
        location: this.addressLine,
        dates: `${this.formatGoogleDate(this.appointmentStartDate)}/${this.formatGoogleDate(this.appointmentEndDate)}`,
      });

      return `https://calendar.google.com/calendar/render?${params.toString()}`;
    },
    yandexCalendarHref() {
      if (!this.appointmentStartDate || !this.appointmentEndDate) {
        return "#";
      }

      const params = new URLSearchParams({
        name: this.calendarTitle,
        description: this.calendarDescription,
        location: this.addressLine,
        start_date: this.formatYandexDate(this.appointmentStartDate),
        end_date: this.formatYandexDate(this.appointmentEndDate),
      });

      return `https://calendar.yandex.ru/event?${params.toString()}`;
    },
    appleCalendarHref() {
      if (!this.appointmentStartDate || !this.appointmentEndDate) {
        return "#";
      }

      return `data:text/calendar;charset=utf-8,${encodeURIComponent(this.appleCalendarFile)}`;
    },
    appleCalendarFile() {
      return [
        "BEGIN:VCALENDAR",
        "VERSION:2.0",
        "PRODID:-//Zrenie Clinic//Booking Widget//RU",
        "BEGIN:VEVENT",
        `UID:${this.eventUid}`,
        `DTSTAMP:${this.formatIcsDate(new Date())}`,
        `DTSTART:${this.formatIcsDate(this.appointmentStartDate)}`,
        `DTEND:${this.formatIcsDate(this.appointmentEndDate)}`,
        `SUMMARY:${this.escapeIcsText(this.calendarTitle)}`,
        `DESCRIPTION:${this.escapeIcsText(this.calendarDescription)}`,
        `LOCATION:${this.escapeIcsText(this.addressLine)}`,
        "END:VEVENT",
        "END:VCALENDAR",
      ].join("\r\n");
    },
    eventUid() {
      const base = this.appointmentStartDate
        ? this.formatIcsDate(this.appointmentStartDate)
        : "booking";

      return `${base}@zrenie.clinic`;
    },
    chatBotUrl() {
      return (
        window.currentCity?.social_links?.telegram ||
        window.config?.state?.currentCity?.social_links?.telegram ||
        null
      );
    },
    calendarLinks() {
      return [
        {
          key: "google",
          label: "Google",
          href: this.googleCalendarHref,
          webpSrc: `${CALENDAR_ICON_BASE}/calendar-google.webp`,
          pngSrc: `${CALENDAR_ICON_BASE}/calendar-google.png`,
          target: "_blank",
          rel: "noopener noreferrer",
        },
        {
          key: "yandex",
          label: "Яндекс",
          href: this.yandexCalendarHref,
          webpSrc: `${CALENDAR_ICON_BASE}/calendar-yandex.webp`,
          pngSrc: `${CALENDAR_ICON_BASE}/calendar-yandex.png`,
          target: "_blank",
          rel: "noopener noreferrer",
        },
        {
          key: "apple",
          label: "Apple",
          href: this.appleCalendarHref,
          webpSrc: `${CALENDAR_ICON_BASE}/calendar-apple.webp`,
          pngSrc: `${CALENDAR_ICON_BASE}/calendar-apple.png`,
          download: "appointment.ics",
          target: null,
          rel: null,
        },
      ];
    },
  },
  methods: {
    formatGoogleDate(date) {
      const year = date.getUTCFullYear();
      const month = String(date.getUTCMonth() + 1).padStart(2, "0");
      const day = String(date.getUTCDate()).padStart(2, "0");
      const hours = String(date.getUTCHours()).padStart(2, "0");
      const minutes = String(date.getUTCMinutes()).padStart(2, "0");
      const seconds = String(date.getUTCSeconds()).padStart(2, "0");

      return `${year}${month}${day}T${hours}${minutes}${seconds}Z`;
    },
    formatYandexDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      const hours = String(date.getHours()).padStart(2, "0");
      const minutes = String(date.getMinutes()).padStart(2, "0");
      const seconds = String(date.getSeconds()).padStart(2, "0");

      return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
    },
    formatIcsDate(date) {
      const year = date.getUTCFullYear();
      const month = String(date.getUTCMonth() + 1).padStart(2, "0");
      const day = String(date.getUTCDate()).padStart(2, "0");
      const hours = String(date.getUTCHours()).padStart(2, "0");
      const minutes = String(date.getUTCMinutes()).padStart(2, "0");
      const seconds = String(date.getUTCSeconds()).padStart(2, "0");

      return `${year}${month}${day}T${hours}${minutes}${seconds}Z`;
    },
    escapeIcsText(value) {
      return String(value || "")
        .replace(/\\/g, "\\\\")
        .replace(/\n/g, "\\n")
        .replace(/,/g, "\\,")
        .replace(/;/g, "\\;");
    },
  },
};
</script>
