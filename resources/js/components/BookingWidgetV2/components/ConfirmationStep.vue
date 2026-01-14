<template>
  <div class="p-6 md:p-8">
    <div class="text-center py-8">
      <!-- Success Icon -->
      <div class="mb-6 flex justify-center">
        <div class="size-20 rounded-full bg-green-100 flex items-center justify-center">
          <svg
            class="size-12 text-green-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 13l4 4L19 7"
            />
          </svg>
        </div>
      </div>

      <!-- Success Message -->
      <h2 class="text-2xl md:text-3xl font-bold text-heading mb-4">
        Спасибо!
      </h2>
      <p class="text-lg text-subdued mb-8">
        Ваша заявка успешно отправлена
      </p>

      <!-- Appointment Details -->
      <div class="max-w-md mx-auto bg-gray-50 rounded-lg p-6 border border-gray-200 text-left space-y-4">
        <div class="flex items-start gap-3">
          <svg class="size-6 text-interactive flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <p class="text-sm text-subdued mb-1">Врач</p>
            <p class="font-semibold text-heading">{{ doctorName }}</p>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <svg class="size-6 text-interactive flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <p class="text-sm text-subdued mb-1">Дата и время</p>
            <p class="font-semibold text-heading">{{ formattedDateTime }}</p>
          </div>
        </div>

        <div v-if="clinicInfo" class="flex items-start gap-3">
          <svg class="size-6 text-interactive flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <p class="text-sm text-subdued mb-1">Адрес</p>
            <p class="font-semibold text-heading">{{ clinicInfo }}</p>
          </div>
        </div>

        <div v-if="patientName" class="flex items-start gap-3">
          <svg class="size-6 text-interactive flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
            <path
              fill-rule="evenodd"
              d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <p class="text-sm text-subdued mb-1">Пациент</p>
            <p class="font-semibold text-heading">{{ patientName }}</p>
          </div>
        </div>
      </div>

      <!-- Additional Info -->
      <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <p class="text-sm text-blue-900">
          <strong>Важно:</strong> В ближайшее время с вами свяжется администратор для подтверждения записи.
          Пожалуйста, убедитесь, что указали правильный номер телефона.
        </p>
      </div>

      <!-- Close Button -->
      <div class="mt-8">
        <button
          @click="$emit('close')"
          class="w-full md:w-auto px-8 py-3 bg-interactive text-white font-semibold rounded-lg hover:bg-interactive-button-hovered transition-colors shadow-md"
        >
          Закрыть
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConfirmationStep',

  props: {
    doctorName: {
      type: String,
      required: true,
    },
    appointmentDateTime: {
      type: String,
      required: true,
    },
    clinicInfo: {
      type: String,
      default: null,
    },
    patientName: {
      type: String,
      default: null,
    },
  },

  computed: {
    formattedDateTime() {
      return this.appointmentDateTime;
    },
  },
};
</script>
