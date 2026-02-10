<template>
  <button :class="className" aria-label="Закрыть" @click="onClick">
    <span class="block h-4 w-4 max-h-full max-w-full m-auto">
      <svg
        viewBox="0 0 20 20"
        class="fill-icon-subdued"
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
        this.hiddenOnMobile && 'flex',
        'appearance-none bg-transparent cursor-pointer right-4 top-4 z-10',
        this.title ? 'relative lg:absolute' : 'absolute right-4 top-4',
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
