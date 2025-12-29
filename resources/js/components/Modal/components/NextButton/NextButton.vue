<template>
  <button :class="className" aria-label="Закрыть" @click="onClick">
    <span class="block max-h-full max-w-full m-auto">
      <svg viewBox="0 0 19 34"
           class="h-8 stroke-interactive group-hover:stroke-interactive-hovered lg:stroke-white lg:group-hover:stroke-white/60 fill-none"
           xmlns="http://www.w3.org/2000/svg">
          <path d="M1.05274 0.999999L17.0527 17L1.05273 33" stroke-width="2" stroke-miterlimit="10"
                stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </span>
  </button>
</template>

<script>
import {classNames} from '../../../../utilities/css'

export default {
  props: {
    title: Boolean,
  },

  computed: {
    className() {
      return classNames(
        'hidden lg:block',
        'appearance-none bg-transparent cursor-pointer -mr-2 ml-5 p-2 rounded-md hover:bg-surface-hovered absolute right-8 top-6 lg:-right-12 lg:top-1/2 -translate-y-1/2 z-10 group',
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
