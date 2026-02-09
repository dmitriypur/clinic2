<template>
  <div class="bg-white rounded-[24px] px-10 pt-10 pb-10">
    <StepHeader chipText="Шаг №3" @close="$emit('close')">
      Выберите дату, время и специалиста
    </StepHeader>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-[400px_24px_444px] gap-0 items-start">
      <div>
        <div v-if="doctors.length" class="flex gap-2 relative">
          <button
            v-for="(doctor, index) in doctors"
            :key="doctor.id"
            @click="$emit('select-doctor', doctor)"
            :class="[
              'flex-1 h-12 text-base font-medium rounded-lg border-2',
              selectedDoctorId === doctor.id
                ? 'bg-[#FF8C00] text-white border-[#FF8C00]'
                : 'bg-white text-[#1F3462] border-[#EBF0F3]',
            ]"
          >
            Врач {{ index + 1 }}
          </button>
        </div>
        <div v-else class="rounded-[12px] border border-[#EBF0F3] bg-[#F6F7F9] px-4 py-3 text-sm font-medium text-[#1F3462]">
          В выбранном филиале врачей нет
        </div>

        <div class="mt-4" v-if="doctors.length">
          <div
            class="flex flex-col p-4 bg-[#F6F7F9] border border-white rounded-[20px] h-full shadow-calendar overflow-hidden"
          >
            <div class="relative h-full z-10">
              <div class="text-[10px] opacity-40 mb-2">100% пациентов рекомендуют врача</div>
              <p class="text-lg font-bold mt-1 text-[#1F3462]">
                {{ selectedDoctorName }}
              </p>

              <div class="flex flex-col mt-4 relative z-10">
                <ul class="flex gap-1.5 flex-col ml-auto w-64">
                  <li class="bg-white py-1.5 px-3.5 rounded-md text-[#1F3462]">
                    <p class="text-[10px] font-normal leading-[100%] opacity-60">Специальность:</p>
                    <p class="text-xs font-semibold leading-[14px]">{{ selectedDoctor?.speciality || selectedDoctor?.specialization || '—' }}</p>
                  </li>
                  <li class="bg-white py-1.5 px-3.5 rounded-md text-[#1F3462]">
                    <p class="text-xs font-normal leading-[100%] opacity-60">Врачебный стаж:</p>
                    <p class="text-[13px] font-semibold leading-4 mt-0.5">{{ selectedDoctor?.seniority || selectedDoctor?.experience || '—' }}</p>
                  </li>
                  <li class="bg-white py-1.5 px-3.5 rounded-md text-[#1F3462]">
                    <p class="text-xs font-normal leading-[100%] opacity-60">Ведёт приём:</p>
                    <p class="text-[13px] font-semibold leading-4 mt-0.5">{{ selectedDoctor?.receives || '—' }}</p>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="hidden lg:flex justify-center pt-[133px]">
        <div class="hidden h-[24px] w-[4px] rounded-[28px] bg-[#1F3462]"></div>
      </div>

      <div class="pt-[34px] lg:pt-0">
        <div class="flex items-center justify-center gap-4">
          <button class="grid h-8 w-8 place-items-center rounded-lg text-[#1F3462] hover:bg-slate-50" aria-label="Prev">
            ‹
          </button>
          <div class="text-[20px] leading-[1.2] font-semibold text-[#1F3462]">
            {{ monthTitle }}
          </div>
          <button class="grid h-8 w-8 place-items-center rounded-lg text-[#1F3462] hover:bg-slate-50" aria-label="Next">
            ›
          </button>
        </div>

        <div class="mt-[27px]">
          <v-date-picker
            v-model="internalDate"
            :min-date="minDate"
            color="orange"
            is-expanded
            @input="handleDate"
            class="booking-calendar"
          />
        </div>

        <div class="mt-6 h-px w-full bg-[#EBF0F3]"></div>

        <div class="mt-6 text-center text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
          Время
        </div>

        <div v-if="loadingDoctors" class="mt-6 text-center text-[#1F3462]">
          Загрузка врачей...
        </div>

        <div v-else-if="loading" class="mt-6 text-center text-[#1F3462]">
          Загрузка слотов...
        </div>

        <div v-else class="mt-4 flex flex-wrap gap-1 w-[444px] max-w-full justify-center">
          <button
            v-for="slot in slots"
            :key="slot.id || slot.datetime || slot.time"
            class="h-[27px] w-[85px] rounded-[4px] border border-[#EBF0F3] text-[16px] font-semibold"
            :class="slotClass(slot)"
            :disabled="!isSlotAvailable(slot)"
            @click="$emit('select-slot', slot)"
          >
            {{ slot.time }}
          </button>
        </div>

        <div class="mt-10 flex gap-4">
          <SecondaryButton @click="$emit('back')">Назад</SecondaryButton>
          <PrimaryButton :disabled="!selectedSlot || !doctors.length" @click="$emit('next')">
            Записаться на приём
          </PrimaryButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
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
    doctors: {
      type: Array,
      default: () => [],
    },
    selectedDoctorId: {
      type: [String, Number],
      default: null,
    },
    selectedDate: {
      type: [Date, null],
      default: null,
    },
    slots: {
      type: Array,
      default: () => [],
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
    monthTitle() {
      const date = this.internalDate || new Date();
      return date.toLocaleDateString("ru-RU", { month: "long", year: "numeric" });
    },
    selectedDoctorName() {
      if (!this.selectedDoctor) return "Выберите врача";
      return this.selectedDoctor.name || this.selectedDoctor.full_name || "Выберите врача";
    },
  },
  methods: {
    handleDate(date) {
      this.$emit("select-date", date);
    },
    isSlotAvailable(slot) {
      if (!slot) return false;

      const isTrue = (value) =>
        value === true ||
        value === 1 ||
        value === "1" ||
        String(value).toLowerCase() === "true";

      const parseStatus = (value) => String(value || "").toLowerCase();
      const status = parseStatus(slot.status);

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

      const available = hasIsAvailable
        ? isTrue(slot.is_available)
        : hasFree
        ? isTrue(slot.free)
        : status
        ? !["occupied", "booked", "busy", "closed", "disabled"].includes(
            status
          )
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
    slotClass(slot) {
      if (!this.isSlotAvailable(slot)) {
        return "text-[#1F3462]/40 bg-[#EBF0F3]";
      }
      if (this.selectedSlot && this.selectedSlot.id === slot.id) {
        return "text-white bg-[radial-gradient(ellipse_at_center,_rgba(255,164,118,1)_0%,_rgba(255,150,89,1)_25%,_rgba(255,135,59,1)_50%,_rgba(255,121,30,1)_75%,_rgba(255,113,15,1)_87.5%,_rgba(255,106,0,1)_100%)]";
      }
      return "text-[#1F3462]";
    },
  },
  watch: {
    selectedDate(newVal) {
      if (newVal) this.internalDate = newVal;
    },
  },
};
</script>
