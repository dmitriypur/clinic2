<template>
  <div class="flex w-full relative">
    <button
      v-for="(option, index) in options"
      :key="option"
      :class="[
        'flex-1',
        className,
        activeIndex === index
          ? 'bg-gray-100 text-white btn-blue-gradient'
          : 'bg-surface-subdued text-interactive hover:bg-gray-50 ',
      ]"
      @click="() => $emit('change', index)"
    >
      {{ option }}
    </button>

    <div :class="[className, 'absolute right-1 top-0 px-2 pt-2 md:hidden md:invisible']">
      <button
        class="rounded-full bg-gray-300 p-1"
        @click="$emit('close')"
      >
        <span class="block h-4 w-4 lg:h-6 lg:w-6 max-h-full max-w-full m-auto">
          <svg
            viewBox="0 0 20 20"
            class="fill-interactive group-hover:fill-interactive-hovered lg:group-hover:fill-white/60 lg:fill-white"
            focusable="false"
            aria-hidden="true"
          >
            <path
              d="M11.414 10l6.293-6.293a.999.999 0 10-1.414-1.414L10 8.586 3.707 2.293a.999.999 0 10-1.414 1.414L8.586 10l-6.293 6.293a.999.999 0 101.414 1.414L10 11.414l6.293 6.293a.997.997 0 001.414 0 .999.999 0 000-1.414L11.414 10z"
              fill-rule="evenodd"
            />
          </svg>
        </span>
      </button>
    </div>
  </div>
</template>

<script>
import { classNames } from "../../../../utilities/css";

export default {
  props: {
    options: Array,
    activeIndex: Number,
  },

  computed: {
    className() {
      return "p-4 md:p-6 text-base md:text-2xl text-center font-medium transition-colors duration-300";
    },
  },
};
</script>
