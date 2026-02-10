<template>
  <div class="flex flex-col-reverse items-center gap-2">
    <h2 class="text-2xl md:text-4xl font-semibold text-interactive">
      <slot />
    </h2>
    <div class="flex items-center gap-3">
      <span
        v-if="chipText"
        class="bg-interactive/10 px-5 py-1 text-xs rounded-md"
      >
        {{ chipText }}
      </span>
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
