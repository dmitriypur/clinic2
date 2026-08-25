import { eventBus } from '../../eventBus.js'
import { ScrollLockManager } from '../../utilities/scroll-lock-manager/scroll-lock-manager.js'

export default {
  name: 'MobileBottomNavigation',

  data() {
    return {
      servicesOpen: false,
      desktopMediaQuery: null,
      scrollLockManager: new ScrollLockManager(),
      inertedElements: [],
    }
  },

  mounted() {
    this.desktopMediaQuery = window.matchMedia('(min-width: 1024px)')
    this.desktopMediaQuery.addEventListener('change', this.handleBreakpointChange)
    eventBus.$on('hideMobileServices', this.closeServices)
    document.addEventListener('keydown', this.handleKeydown)
  },

  beforeDestroy() {
    this.closeServices(false)
    this.desktopMediaQuery?.removeEventListener('change', this.handleBreakpointChange)
    eventBus.$off('hideMobileServices', this.closeServices)
    document.removeEventListener('keydown', this.handleKeydown)
  },

  methods: {
    toggleServices() {
      if (this.servicesOpen) {
        this.closeServices()
        return
      }

      eventBus.$emit('hideTopBar')
      this.servicesOpen = true
      this.scrollLockManager.registerScrollLock()
      this.setBackgroundInert(true)
      this.$nextTick(() => this.$refs.servicesPanel?.focus())
    },

    closeServices(restoreFocus = true) {
      if (!this.servicesOpen) {
        return
      }

      this.servicesOpen = false
      this.scrollLockManager.unregisterScrollLock()
      this.setBackgroundInert(false)

      if (restoreFocus) {
        this.$nextTick(() => this.$refs.servicesToggle?.focus())
      }
    },

    openBookingWidget() {
      this.closeServices(false)
      eventBus.$emit('hideTopBar')
      eventBus.$emit('openBookingWidgetV3', 'otpravka-formy')
    },

    handleBreakpointChange(event) {
      if (event.matches) {
        this.closeServices(false)
      }
    },

    setBackgroundInert(active) {
      if (active) {
        const siblings = Array.from(this.$el.parentElement?.children ?? [])
        this.inertedElements = siblings.filter((element) => (
          element !== this.$el &&
          !element.inert
        ))
        this.inertedElements.forEach((element) => { element.inert = true })
        return
      }

      this.inertedElements.forEach((element) => { element.inert = false })
      this.inertedElements = []
    },

    handleKeydown(event) {
      if (!this.servicesOpen) {
        return
      }

      if (event.key === 'Escape') {
        this.closeServices()
        return
      }

      if (event.key !== 'Tab') {
        return
      }

      const focusable = Array.from(
        this.$refs.servicesPanel.querySelectorAll('a[href], button:not([disabled])')
      ).filter((element) => element.offsetParent !== null)

      if (!focusable.length) {
        event.preventDefault()
        return
      }

      const first = focusable[0]
      const last = focusable[focusable.length - 1]

      if (event.shiftKey && (document.activeElement === first || document.activeElement === this.$refs.servicesPanel)) {
        event.preventDefault()
        last.focus()
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault()
        first.focus()
      }
    },
  },
}
