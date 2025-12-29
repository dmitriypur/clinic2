<template>
  <button :class="className" aria-label="Закрыть" @click="onClick">
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
</template>

<script>
import {classNames} from '../../../../utilities/css'

export default {
  props: {
    title: Boolean,
    hiddenOnMobile: Boolean,
  },

  computed: {
    className() {
      return classNames(
        this.hiddenOnMobile && 'hidden md:flex',
        'appearance-none bg-transparent cursor-pointer -mr-2 ml-5 p-2 rounded-md hover:bg-surface-hovered lg:-right-8 lg:-top-10 z-10 group',
        this.title ? 'relative lg:absolute' : 'absolute right-4 top-6',
        'outline-none focus:ring-border-interactive-focus focus:ring'
      )
    },
  },

  methods: {
    onClick() {
      this.$emit('click')
    },
  },
}
</script>
