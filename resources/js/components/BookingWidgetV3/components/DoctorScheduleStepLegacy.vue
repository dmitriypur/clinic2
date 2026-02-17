<template>
  <div class="bg-white rounded-[24px] px-10 pt-10 pb-10">
    <StepHeader chipText="Шаг №3" @close="$emit('close')">
      Выберите дату и время
    </StepHeader>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-[400px_24px_444px] gap-0 items-start">
      <div>
        <div class="flex items-center gap-[25px] rounded-[12px] border-2 border-[#EBF0F3] bg-white px-6 py-2">
          <div class="h-[60px] w-[60px] overflow-hidden rounded-[8px] bg-white"></div>
          <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462] whitespace-pre-line">
            {{ doctor?.name || 'Выберите врача' }}
          </p>
        </div>

        <div class="mt-8 text-center text-[20px] leading-[1.2] font-semibold text-[#1F3462]">
          Доступные филиалы
        </div>
        <p class="mt-2 text-center text-sm text-[#1F3462]/70">
          {{ clinicTitle }}
        </p>

        <div class="relative mt-[7px] h-[388px]">
          <div v-if="loadingBranches" class="h-full grid place-items-center text-[#1F3462]">
            Загрузка филиалов...
          </div>
          <div v-else class="h-full overflow-y-auto pr-2 space-y-[7px]">
            <button
              v-for="branch in branches"
              :key="branch.id"
              class="h-[64px] w-full rounded-[12px] border-2 px-6 py-[13px] text-left"
              :class="{
                'border-[#EBF0F3] bg-white': selectedBranchId !== branch.id,
                'border-[#EBF0F3] bg-[#EBF0F3]': selectedBranchId === branch.id,
                'opacity-40 cursor-not-allowed': branch.enabled === false,
              }"
              :disabled="branch.enabled === false"
              @click="$emit('select-branch', branch)"
            >
              <span class="text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
                {{ branch.address || branch.name || branch.title || "Филиал" }}
              </span>
            </button>
          </div>
          <div class="pointer-events-none absolute bottom-0 left-0 h-[71px] w-full bg-gradient-to-b from-white/0 to-white"></div>
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

        <div v-if="loading" class="mt-6 text-center text-[#1F3462]">
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
          <PrimaryButton :disabled="!selectedSlot" @click="$emit('next')">
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
    };
  },
  computed: {
    monthTitle() {
      const date = this.internalDate || new Date();
      return date.toLocaleDateString("ru-RU", { month: "long", year: "numeric" });
    },
    clinicTitle() {
      return this.clinic?.name || "Клиника не выбрана";
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
