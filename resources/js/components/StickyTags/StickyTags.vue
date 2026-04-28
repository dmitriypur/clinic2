<script>
import { eventBus } from '../../eventBus'

export default {
  data() {
    return {
      fixed: false,
      top: 0,
      left: 0,
      width: 0,
      height: 0,
      headerGap: 16,
      fixedBottomPadding: 24,
      mobileHorizontalInset: 15,
      constrainedToContainer: false,
      startOffset: 0,
      ticking: false,
      activeHash: '',
      tags: [],
      activeObserver: null,
      observerOffset: 0,
      programmaticScrollHash: '',
      programmaticScrollTimeout: null,
    }
  },

  computed: {
    placeholderStyle() {
      return this.fixed ? { height: `${this.height}px` } : null
    },

    barStyle() {
      if (!this.fixed) {
        return null
      }

      if (!this.constrainedToContainer) {
        return {
          top: `${this.top}px`,
          left: `${this.mobileHorizontalInset}px`,
          right: `${this.mobileHorizontalInset}px`,
        }
      }

      return {
        top: `${this.top}px`,
        left: `${this.left}px`,
        width: `${this.width}px`,
      }
    },
  },

  mounted() {
    window.addEventListener('scroll', this.requestUpdate, { passive: true })
    window.addEventListener('resize', this.handleResize)
    this.$el.addEventListener('click', this.handleAnchorClick, true)
    this.$nextTick(() => {
      this.refreshTags()
      this.update()
      this.setupActiveObserver()
      this.updateActiveTag()
    })
  },

  beforeDestroy() {
    window.removeEventListener('scroll', this.requestUpdate)
    window.removeEventListener('resize', this.handleResize)
    this.$el.removeEventListener('click', this.handleAnchorClick, true)
    this.disconnectActiveObserver()
    this.clearProgrammaticScrollTimeout()
  },

  methods: {
    getHashFromHref(href) {
      const hashIndex = href.indexOf('#')

      if (hashIndex === -1) {
        return ''
      }

      try {
        return decodeURIComponent(href.slice(hashIndex + 1))
      } catch (error) {
        return href.slice(hashIndex + 1)
      }
    },

    isActiveTag(hash) {
      return this.activeHash === hash
    },

    clearProgrammaticScrollTimeout() {
      if (!this.programmaticScrollTimeout) {
        return
      }

      window.clearTimeout(this.programmaticScrollTimeout)
      this.programmaticScrollTimeout = null
    },

    keepActiveTagDuringSmoothScroll(hash) {
      this.clearProgrammaticScrollTimeout()
      this.programmaticScrollHash = hash
      this.activeHash = hash

      this.programmaticScrollTimeout = window.setTimeout(() => {
        this.programmaticScrollHash = ''
        this.programmaticScrollTimeout = null
      }, 1600)
    },

    disconnectActiveObserver() {
      if (!this.activeObserver) {
        return
      }

      this.activeObserver.disconnect()
      this.activeObserver = null
    },

    refreshTags() {
      this.tags = [...this.$el.querySelectorAll('a[href*="#"]')]
        .map((link) => {
          const hash = this.getHashFromHref(link.getAttribute('href') || '')

          return {
            hash,
            target: hash ? document.getElementById(hash) : null,
          }
        })
        .filter((tag) => tag.hash && tag.target)

      if (this.activeHash || !this.tags.length) {
        return
      }

      const currentHash = this.getHashFromHref(window.location.hash || '')
      const currentTag = this.tags.find((tag) => tag.hash === currentHash)

      this.activeHash = currentTag ? currentTag.hash : this.tags[0].hash
    },

    setupActiveObserver() {
      this.disconnectActiveObserver()

      if (!('IntersectionObserver' in window) || !this.tags.length) {
        return
      }

      const offset = Math.ceil(this.getScrollOffset())
      const bottomMargin = Math.max(0, window.innerHeight - offset - 1)
      this.observerOffset = offset
      this.activeObserver = new IntersectionObserver(
        () => {
          this.updateActiveTag()
        },
        {
          rootMargin: `-${offset}px 0px -${bottomMargin}px 0px`,
          threshold: 0,
        },
      )

      this.tags.forEach((tag) => {
        this.activeObserver.observe(tag.target)
      })
    },

    getScrollOffset() {
      const bar = this.$refs.bar
      const barHeight = bar
        ? bar.getBoundingClientRect().height -
          (this.fixed ? this.fixedBottomPadding : 0)
        : 0

      return this.top + barHeight
    },

    updateActiveTag() {
      if (this.programmaticScrollHash) {
        this.activeHash = this.programmaticScrollHash
        return
      }

      if (!this.tags.length) {
        this.refreshTags()
      }

      if (!this.tags.length) {
        return
      }

      const scrollY = window.pageYOffset || document.documentElement.scrollTop
      const activationLine = scrollY + this.getScrollOffset() + 1
      let activeHash = this.tags[0].hash

      this.tags.forEach((tag) => {
        const targetTop =
          tag.target.getBoundingClientRect().top +
          (window.pageYOffset || document.documentElement.scrollTop)

        if (targetTop <= activationLine) {
          activeHash = tag.hash
        }
      })

      this.activeHash = activeHash
    },

    handleAnchorClick(event) {
      if (event.defaultPrevented) {
        return
      }

      const link = event.target.closest('a[href*="#"]')

      if (!link || !this.$el.contains(link)) {
        return
      }

      const targetHash = this.getHashFromHref(link.getAttribute('href') || '')

      if (!targetHash) {
        return
      }

      const target = document.getElementById(targetHash)

      if (!target) {
        return
      }

      event.preventDefault()
      event.stopPropagation()

      if (event.stopImmediatePropagation) {
        event.stopImmediatePropagation()
      }

      this.keepActiveTagDuringSmoothScroll(targetHash)
      this.update()

      const targetTop =
        target.getBoundingClientRect().top +
        (window.pageYOffset || document.documentElement.scrollTop)

      window.scrollTo({
        top: Math.max(0, targetTop - this.getScrollOffset()),
        behavior: 'smooth',
      })

      if (window.innerWidth < 1024) {
        eventBus.$emit('hideTopBar')
      }
    },

    requestUpdate() {
      if (this.ticking) {
        return
      }

      this.ticking = true

      window.requestAnimationFrame(() => {
        this.update()

        if (!this.activeObserver && !this.programmaticScrollHash) {
          this.updateActiveTag()
        }

        this.ticking = false
      })
    },

    handleResize() {
      this.refreshTags()
      this.update()
      this.setupActiveObserver()
      this.updateActiveTag()
    },

    update() {
      const placeholder = this.$refs.placeholder
      const bar = this.$refs.bar

      if (!placeholder || !bar) {
        return
      }

      const header = document.getElementById('AppHeader')
      const headerBottom = header ? header.getBoundingClientRect().bottom : 0
      const rect = placeholder.getBoundingClientRect()
      const scrollY = window.pageYOffset || document.documentElement.scrollTop
      const barStyles = window.getComputedStyle(bar)
      const marginLeft = parseFloat(barStyles.marginLeft) || 0
      const marginRight = parseFloat(barStyles.marginRight) || 0
      const verticalMargins =
        (parseFloat(barStyles.marginTop) || 0) +
        (parseFloat(barStyles.marginBottom) || 0)
      const fixedPaddingAdjustment = this.fixed ? this.fixedBottomPadding : 0

      this.constrainedToContainer = window.innerWidth >= 768
      this.top = Math.max(0, headerBottom + this.headerGap)
      this.left = rect.left + marginLeft
      this.width = rect.width - marginLeft - marginRight
      this.height = bar.offsetHeight + verticalMargins - fixedPaddingAdjustment
      this.startOffset = rect.top + scrollY - this.top
      this.fixed = scrollY >= this.startOffset
    },
  },
}
</script>
