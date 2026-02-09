<template>
  <div class="bg-white rounded-[24px] px-10 pt-10 pb-10">
    <div class="text-center py-8">
      <div class="mb-6 flex justify-center">
        <div class="size-20 rounded-full bg-green-100 flex items-center justify-center">
          <span class="text-green-600 text-3xl">✓</span>
        </div>
      </div>

      <h2 class="text-2xl md:text-3xl font-bold text-[#1F3462] mb-4">
        Спасибо!
      </h2>
      <p class="text-lg text-[#1F3462] mb-8">
        Ваша заявка успешно отправлена
      </p>

      <div class="max-w-md mx-auto bg-[#F6F7F9] rounded-lg p-6 border border-[#EBF0F3] text-left space-y-4">
        <div>
          <p class="text-sm text-[#1F3462]/60 mb-1">Врач</p>
          <p class="font-semibold text-[#1F3462]">{{ doctorName || '—' }}</p>
        </div>
        <div>
          <p class="text-sm text-[#1F3462]/60 mb-1">Дата и время</p>
          <p class="font-semibold text-[#1F3462]">{{ formattedDateTime }}</p>
        </div>
        <div>
          <p class="text-sm text-[#1F3462]/60 mb-1">Адрес</p>
          <p class="font-semibold text-[#1F3462]">{{ addressLine }}</p>
        </div>
      </div>

      <div class="mt-8">
        <PrimaryButton @click="$emit('close')">Закрыть</PrimaryButton>
      </div>
    </div>
  </div>
</template>

<script>
const PrimaryButton = () => import("./shared/PrimaryButton.vue");

export default {
  components: { PrimaryButton },
  props: {
    doctorName: {
      type: String,
      default: null,
    },
    clinicName: {
      type: String,
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
    formattedDateTime() {
      if (!this.appointmentDate || !this.appointmentTime) return '—';
      return `${this.appointmentDate.toLocaleDateString('ru-RU')} ${this.appointmentTime}`;
    },
    addressLine() {
      if (this.branchName) return this.branchName;
      if (this.clinicName) return this.clinicName;
      return '—';
    },
  },
};
</script>
