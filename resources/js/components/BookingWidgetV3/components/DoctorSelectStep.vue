<template>
  <div class="bg-white rounded-[24px] px-10 pt-10 pb-10">
    <StepHeader chipText="Шаг №2" @close="$emit('close')">
      Выберите специалиста
    </StepHeader>

    <div class="mt-6">
      <input
        type="text"
        class="w-full rounded-[12px] border border-[#EBF0F3] px-4 py-3 text-[16px]"
        placeholder="Поиск врача"
        v-model="query"
      />
    </div>

    <div class="mt-6 h-[352px] overflow-y-auto pr-2">
      <button
        v-for="doctor in filteredDoctors"
        :key="doctor.id"
        class="flex w-full items-center gap-4 rounded-[12px] border-2 bg-white px-4 py-3 text-left"
        :class="
          selectedDoctorId === doctor.id
            ? 'border-[#FF8212] bg-[#FFF5EB]'
            : 'border-[#EBF0F3]'
        "
        @click="$emit('select', doctor)"
      >
        <div class="h-[60px] w-[60px] rounded-[8px] bg-[#EBF0F3]"></div>
        <div class="text-[16px] font-semibold text-[#1F3462]">
          {{ doctor.name || doctor.full_name }}
        </div>
      </button>
    </div>

    <div v-if="loading" class="mt-4 text-sm text-[#1F3462]">Загрузка...</div>

    <div class="mt-6 flex gap-4">
      <SecondaryButton @click="$emit('back')">Назад</SecondaryButton>
      <PrimaryButton :disabled="!selectedDoctorId" @click="$emit('next')">
        Продолжить
      </PrimaryButton>
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
