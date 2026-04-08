<template>
  <button
    :disabled="disabled"
    class="flex-1 md:rounded-xl md:border border-interactive md:px-7 md:py-4 font-semibold text-interactive md:hover:text-white md:hover:border-action-primary"
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
