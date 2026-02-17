<template>
  <button
    :disabled="disabled"
    class="flex-1 rounded-xl border border-interactive px-[30px] py-4 font-semibold text-interactive hover:text-white hover:border-action-primary"
    :class="disabled ? 'opacity-50 cursor-not-allowed' : 'btn-white'"
    @click="$emit('click')"
  >
    <slot />
  </button>
</template>

<script>
export default {
  props: {
    disabled: {
      type: Boolean,
      default: false,
    },
  },
};
</script>
