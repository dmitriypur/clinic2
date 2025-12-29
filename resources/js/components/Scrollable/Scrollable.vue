<template>
  <div ref="scrollArea" :class="className" data-scrollable="true">
    <slot />
  </div>
</template>

<script>
import { debounce } from '../../utilities/debounce'
import { classNames } from '../../utilities/css'

export default {
  props: {
    /** Scroll content vertically */
    vertical: {
      type: Boolean,
      default: true,
    },
    /** Scroll content horizontally */
    horizontal: Boolean,
    /** Add a shadow when content is scrollable */
    shadow: Boolean,
  },

  data() {
    return {
      topShadow: false,
      bottomShadow: false,
      scrollPosition: 0,
      canScroll: false,
    }
  },

  computed: {
    className() {
      return classNames(
        'relative max-h-none',
        this.vertical ? 'overflow-y-auto' : 'overflow-y-hidden',
        this.horizontal ? 'overflow-x-auto' : 'overflow-x-hidden',
        this.topShadow && !this.bottomShadow && 'shadow-scrollable-top',
        this.bottomShadow && !this.topShadow && 'shadow-scrollable-bottom',
        this.bottomShadow && this.topShadow && 'shadow-scrollable',
      )
    },
  },

  mounted() {
    if (this.$refs.scrollArea == null) {
      return
    }

    this.$refs.scrollArea.addEventListener('scroll', () => {
      window.requestAnimationFrame(this.handleScroll)
    })
    window.addEventListener('resize', this.handleResize)
    window.requestAnimationFrame(() => {
      this.handleScroll()
    })
  },

  beforeDestroy() {
    if (
      this.scrollPosition &&
      this.$refs.scrollArea &&
      this.scrollPosition > 0
    ) {
      this.$refs.scrollArea.scrollTop = this.scrollPosition
    }
  },

  methods: {
    handleScroll() {
      const { scrollArea } = this.$refs
      const { shadow, onScrolledToBottom } = this
      if (scrollArea == null) {
        return
      }
      const { scrollTop, clientHeight, scrollHeight } = scrollArea
      const shouldBottomShadow = Boolean(
        shadow && !(scrollTop + clientHeight >= scrollHeight),
      )
      const shouldTopShadow = Boolean(shadow && scrollTop > 0)

      const canScroll = scrollHeight > clientHeight
      const hasScrolledToBottom = scrollHeight - scrollTop === clientHeight

      if (canScroll && hasScrolledToBottom && onScrolledToBottom) {
        onScrolledToBottom()
      }

      this.topShadow = shouldTopShadow
      this.bottomShadow = shouldBottomShadow
      this.scrollPosition = scrollTop
      this.canScroll = canScroll
    },

    handleResize() {
      debounce(
        () => {
          this.handleScroll()
        },
        50,
        { trailing: true },
      )
    },
  },
}
</script>
