<template>
  <div class="bg-white md:p-3">
    <StepHeader :chipText="stepChipText" @close="$emit('close')">
      Выберите специалиста
    </StepHeader>

    <div class="mt-6 relative">
      <input
        type="text"
        class="w-full rounded-lg text-sm border-none px-6 py-4 bg-surface-subdued outline-outline-primary focus:outline-action-primary focus:bg-white"
        placeholder="Поиск врача"
        v-model="query"
      />
      <span class="absolute top-1/2 -translate-y-1/2 right-4 w-4 h-4">
        <IconSearch class="w-full h-full" />
      </span>
    </div>

    <div class="relative">
      
    <div class="relative mt-6 h-[300px] md:h-[380px] overflow-y-auto pr-1 md:w-[520px]">
      <div
        v-if="!loading && !filteredDoctors.length"
        class="rounded-[12px] border border-surface-subdued bg-[#F6F7F9] px-4 py-4 text-sm font-medium text-interactive"
      >
        {{ emptyMessage }}
      </div>

      <div
        v-for="doctor in filteredDoctors"
        :key="doctor.id"
        class="w-full rounded-xl border-2 p-2 text-left mt-2 first:mt-0"
        :class="
          selectedDoctorId === doctor.id
            ? 'border-action-primary bg-action-primary/5'
            : 'border-surface-subdued bg-white'
        "
        @click="$emit('select', doctor)"
      >
        <div class="relative w-full flex items-center gap-2 md:gap-4 cursor-pointer">
          <div class="h-14 w-14 min-w-14 overflow-hidden rounded-full bg-surface-subdued border border-interactive/20 md:h-24 md:w-24 md:min-w-24">
            <img
              v-if="doctor.avatar_url"
              :src="doctor.avatar_url"
              :alt="doctor.name || 'Фото врача'"
              class="h-full w-full object-contain object-top"
              loading="lazy"
            />
          </div>
          <div class="text-sm w-auto">
            <div class="md:text-base font-bold leading-tight">
              {{ doctor.name || doctor.full_name }}
            </div>
            <div class="hidden md:block">
              {{ doctor.speciality || doctor.specialization || "Специалист" }}
            </div>
            <div class="hidden md:block">
              Стаж: {{ doctor.seniority || doctor.extra?.seniority || "—" }}, {{ doctorReceivesDisplay(doctor) }}
            </div>
            <div class="flex md:items-center justify-between md:justify-start md:gap-3 mt-1">
              <p v-if="doctorDisplayPrice(doctor)" class="text-xl font-semibold leading-normal">{{ doctorDisplayPrice(doctor) }} ₽</p>
              <button
                  v-if="doctorVideoUrl(doctor)"
                  type="button"
                  class="text-xs font-semibold py-1 px-3 md:py-2 md:px-5 border border-interactive rounded-xl"
                  @click.stop="openDoctorVideo(doctor)"
                >
                  Видео-визитка
              </button>
            </div>
          </div>
          <div v-if="selectedDoctorId === doctor.id" class="absolute top-2 right-2 text-action-primary ml-auto">
            <IconCheck class="w-5 h-5" />
          </div>
        </div>
        
      </div>
    </div>
    <div class="absolute inset-x-0 bottom-0 z-10 h-10 bg-gradient-to-t from-white via-white/70 to-transparent pointer-events-none"></div>
    </div>
    <div v-if="loading" class="mt-4 text-sm text-[#1F3462]">Загрузка...</div>

    <div class="mt-6 flex flex-col md:flex-row-reverse gap-4">
      <PrimaryButton :disabled="!selectedDoctorId" @click="$emit('next')">
        Далее
      </PrimaryButton>
      <SecondaryButton @click="$emit('back')">Назад</SecondaryButton>
    </div>
  </div>
</template>

<script>
import { eventBus } from "@/eventBus";
import { getDoctorReceivesDisplay } from "@/utilities/doctorAge";
import { getDoctorDisplayPrice } from "@/utilities/doctorPrice";

const StepHeader = () => import("./shared/StepHeader.vue");
const PrimaryButton = () => import("./shared/PrimaryButton.vue");
const SecondaryButton = () => import("./shared/SecondaryButton.vue");
const IconSearch = () => import("./shared/IconSearch.vue");
const IconCheck = () => import("./shared/IconCheck.vue");

export default {
  components: { StepHeader, PrimaryButton, SecondaryButton, IconSearch, IconCheck },
  props: {
    doctors: {
      type: Array,
      default: () => [],
    },
    selectedDoctorId: {
      type: [String, Number],
      default: null,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    stepChipText: {
      type: String,
      default: "Шаг №2",
    },
    emptyMessage: {
      type: String,
      default: "Для указанной даты рождения подходящих специалистов не найдено",
    },
  },
  data() {
    return {
      query: "",
    };
  },
  methods: {
    doctorReceivesDisplay(doctor) {
      return getDoctorReceivesDisplay(doctor) || "—";
    },
    doctorDisplayPrice(doctor) {
      return getDoctorDisplayPrice(doctor);
    },
    doctorVideoUrl(doctor) {
      return doctor?.video_url || doctor?.actual_video_url || doctor?.video || null;
    },
    openDoctorVideo(doctor) {
      const url = this.doctorVideoUrl(doctor);
      if (!url) {
        return;
      }

      eventBus.$emit("showVideoModal", url);
    },
  },
  computed: {
    filteredDoctors() {
      const q = this.query.trim().toLowerCase();
      if (!q) return this.doctors;
      return this.doctors.filter((d) =>
        String(d.name || d.full_name || "").toLowerCase().includes(q)
      );
    },
  },
};
</script>
