<template>
  <button
    :disabled="disabled"
    class="flex-1 rounded-xl px-[30px] py-4 font-semibold text-white"
    :class="disabled ? 'bg-[#C7CED9]' : 'btn-gradient'"
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
