<template>
  <div>
    <ScrollLock />
    <div :class="className" :style="zIndexStyle" @click="onClick" @touchStart="onTouchStart" />
  </div>
</template>

<script>
import { classNames } from '../../utilities/css'
import ScrollLock from '../ScrollLock'

export default {
  components: {
    ScrollLock,
  },

  props: {
    transparent: Boolean,
    zIndexOverride: Number,
  },

  computed: {
    className() {
      return classNames(
        'fixed z-40 inset-0 block will-change-[opacity] opacity-0 animate-backdrop',
        this.transparent ? 'bg-transparent' : 'bg-backdrop',
      )
    },

    zIndexStyle() {
      return {
        zIndex: this.zIndexOverride || 49,
      }
    },
  },

  methods: {
    onClick() {
      this.$emit('handleClick')
    },

    onTouchStart() {
      this.$emit('touchStart')
    },
  },
}
</script>
