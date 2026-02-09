<template>
  <button
    :disabled="disabled"
    class="flex-1 rounded-[10px] border border-[#1F3462] px-[30px] py-4 text-[16px] font-semibold text-[#1F3462]"
    :class="disabled ? 'opacity-50 cursor-not-allowed' : ''"
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
