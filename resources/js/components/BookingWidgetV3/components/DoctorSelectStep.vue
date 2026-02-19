<template>
  <div class="bg-white md:p-6">
    <StepHeader chipText="Шаг №2" @close="$emit('close')">
      Выберите специалиста
    </StepHeader>

    <div class="mt-6 relative">
      <input
        type="text"
        class="w-full rounded-lg text-sm border-none px-6 py-4 bg-surface-subdued outline-icon-subdued"
        placeholder="Поиск врача"
        v-model="query"
      />
      <span class="absolute top-1/2 -translate-y-1/2 right-4 w-4 h-4">
        <IconSearch class="w-full h-full" />
      </span>
    </div>

    <div class="mt-6 h-[352px] overflow-y-auto pr-1 md:w-[450px]">
      <div
        v-for="doctor in filteredDoctors"
        :key="doctor.id"
        class="w-full rounded-xl border-2 bg-white p-2 text-left mt-2 first:mt-0"
        :class="
          selectedDoctorId === doctor.id
            ? 'border-action-primary bg-action-primary/5'
            : 'border-surface-subdued'
        "
        @click="$emit('select', doctor)"
      >
        <div class="w-full flex items-center gap-2 md:gap-4 cursor-pointer">
          <div class="h-14 w-14 min-w-14 overflow-hidden rounded-lg md:rounded-full md:bg-surface-subdued md:h-16 md:w-16 md:min-w-16">
            <img
              v-if="doctor.avatar_url"
              :src="doctor.avatar_url"
              :alt="doctor.name || 'Фото врача'"
              class="h-full w-full object-cover"
              loading="lazy"
            />
          </div>
          <div class="w-2/3 md:w-1/2">
            <button
              type="button"
              class="text-xs font-semibold mb-1"
              :class="doctorVideoUrl(doctor) ? 'text-action-primary hover:underline cursor-pointer' : 'text-subdued cursor-default'"
              :disabled="!doctorVideoUrl(doctor)"
              @click.stop="openDoctorVideo(doctor)"
            >
              Видео-визитка
            </button>
            <div class="text-sm md:text-base font-semibold leading-tight">
              {{ doctor.name || doctor.full_name }}
            </div>
          </div>
          <div v-if="selectedDoctorId === doctor.id" class="text-action-primary ml-auto">
            <IconCheck class="w-4 h-4 md:w-6 md:h-6" />
          </div>
        </div>
        <div class="hidden md:flex md:items-stretch md:gap-2 md:mt-3 text-xs">
          <div class="bg-light-gray p-1 border border-interactive/10 rounded-md flex-1 flex items-center justify-center">
            {{ doctor.speciality || doctor.specialization || "Специалист" }}
          </div>
          <div class="bg-light-gray p-1 border border-interactive/10 rounded-md flex-1 flex items-center justify-center">
            {{ doctor.receives || doctor.extra?.receives || "—" }}
          </div>
          <div class="bg-light-gray p-1 border border-interactive/10 rounded-md flex-1 flex items-center justify-center">
            Стаж: {{ doctor.seniority || doctor.extra?.seniority || "—" }}
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="mt-4 text-sm text-[#1F3462]">Загрузка...</div>

    <div class="mt-6 flex flex-col md:flex-row gap-4">
      <SecondaryButton @click="$emit('back')">Назад</SecondaryButton>
      <PrimaryButton :disabled="!selectedDoctorId" @click="$emit('next')">
        Продолжить
      </PrimaryButton>
    </div>
  </div>
</template>

<script>
import { eventBus } from "@/eventBus";

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
  },
  data() {
    return {
      query: "",
    };
  },
  methods: {
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
