<template>
  <div class="flex items-center justify-between">
    <h2 class="text-[34px] leading-[1.2] font-semibold text-[#1F3462]">
      <slot />
    </h2>
    <div class="flex items-center gap-3">
      <span
        v-if="chipText"
        class="bg-[#F6F7F9] px-5 py-[5px] text-[12px] leading-[1.2] text-[#1D1D1D] shadow-[0px_0px_1.8px_rgba(31,52,98,0.26)]"
      >
        {{ chipText }}
      </span>
      <button
        class="grid h-6 w-6 place-items-center"
        aria-label="Close"
        @click="$emit('close')"
      >
        <span class="text-[#1F3462]">×</span>
      </button>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    chipText: {
      type: String,
      default: null,
    },
  },
};
</script>
