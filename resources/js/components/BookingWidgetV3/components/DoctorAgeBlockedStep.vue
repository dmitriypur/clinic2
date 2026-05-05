<template>
  <div class="bg-white p-2 md:py-6 md:px-14">
    <StepHeader>
      Дата рождения пациента
    </StepHeader>

    <div class="mx-auto mt-8 max-w-[520px] rounded-3xl border border-surface-subdued bg-surface-subdued/40 px-5 py-6 text-center md:mt-10 md:px-8 md:py-8">
      <p class="text-xl font-semibold leading-[1.25] text-interactive md:text-2xl">
        {{ message }}
      </p>

      <button
        type="button"
        class="mt-6 text-base font-semibold text-action-primary underline underline-offset-4 transition hover:text-interactive"
        @click="$emit('choose-other')"
      >
        Пожалуйста выберите другого специалиста
      </button>
    </div>
  </div>
</template>

<script>
const StepHeader = () => import("./shared/StepHeader.vue");

export default {
  name: "DoctorAgeBlockedStep",
  components: {
    StepHeader,
  },
  props: {
    message: {
      type: String,
      default: "Данный врач принимает пациентов другого возраста",
    },
  },
};
</script>
