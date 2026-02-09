<template>
  <button
    :disabled="disabled"
    class="flex-1 rounded-[10px] px-[30px] py-4 text-[16px] font-semibold text-white"
    :class="disabled ? 'bg-[#C7CED9]' : 'bg-[radial-gradient(ellipse_at_center,_rgba(255,164,118,1)_0%,_rgba(255,150,89,1)_25%,_rgba(255,135,59,1)_50%,_rgba(255,121,30,1)_75%,_rgba(255,113,15,1)_87.5%,_rgba(255,106,0,1)_100%)]'"
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
