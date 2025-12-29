<script>
import { eventBus } from '../../eventBus'
import { classNames } from '../../utilities/css'
import { ScrollLockManager } from '../../utilities/scroll-lock-manager'
import CitySwitcher from '../CitySwitcher/CitySwitcher.vue'

const SearchLive = () => import('../SearchLive/SearchLive.vue')
const MegaMenu = () => import('../MegaMenu/MegaMenu.vue')
const AccessibilityToggle = () => import('../AccessibilityToggle/AccessibilityToggle.vue')

export default {
  components: {SearchLive, MegaMenu, AccessibilityToggle, CitySwitcher},
  data() {
    return {
      open: false, // Fix for "Property or method 'open' is not defined" warning
      active: false,
      body: document.body,
      stickyClassNames: ['lg:fixed', 'lg:bg-white', 'lg:shadow-md', 'lg:py-6'],
      relativeClassNames: ['lg:relative', 'lg:py-12'],
      bodyClassNameForStickyHeader: 'lg:pt-[161px]',
      bodyClassNameForRelativeHeader: 'lg:pt-0',
      isSticky: false,
      scrollLockManager: new ScrollLockManager(),
      subNavOpen: null,
    }
  },

  computed: {
    navClassName() {
      return classNames(
        'absolute lg:relative top-full lg:top-[unset] bg-surface lg:bg-transparent lg:shadow-top-bottom w-full mt-4 pb-40 lg:pb-0 group-[.lg\:fixed]:mt-0 overflow-auto lg:overflow-visible max-h-screen min-h-screen lg:min-h-0',
        this.active ? '' : 'hidden lg:block',
      )
    },
  },

  mounted() {
    const self = this
    this.body = document.body
    // window.addEventListener('scroll', this.handleScroll, { passive: true })

    eventBus.$on('hideTopBar', function () {
      self.active = false
    })
  },

  beforeDestroy() {
    // window.removeEventListener('scroll', this.handleScroll)
    // this.scrollLockManager.unregisterScrollLock()
  },

  watch: {
    active(val) {
      if (val && !this.isSticky) {
        this.$el.classList.add(['lg:bg-white'])
      }

      if (!val && !this.isSticky) {
        this.$el.classList.remove(['lg:bg-white'])
      }

      if (val && window.innerWidth < 1024) {
        this.scrollLockManager.registerScrollLock()
      }

      if (!val) {
        this.scrollLockManager.unregisterScrollLock()
      }
    },
  },

  methods: {
    toggle() {
      this.active = !this.active
    },

    toggleSubNav(id) {
      if (this.subNavOpen === id) {
        this.subNavOpen = null
      } else {
        this.subNavOpen = id
      }
    },

    handleScroll() {
      if (window.innerWidth < 1024) {
        return
      }

      if (window.top.scrollY > 24) {
        this.$el.classList.remove(...this.relativeClassNames)
        this.$el.classList.add(...this.stickyClassNames)
        this.body.classList.remove(this.bodyClassNameForRelativeHeader)
        this.body.classList.add(this.bodyClassNameForStickyHeader)
        this.isSticky = true
      } else {
        this.$el.classList.remove(...this.stickyClassNames)
        this.$el.classList.add(...this.relativeClassNames)

        if (this.active) {
          this.$el.classList.add(['lg:bg-white'])
        }
        this.body.classList.remove(this.bodyClassNameForStickyHeader)
        this.body.classList.add(this.bodyClassNameForRelativeHeader)
        this.isSticky = false
      }
    },

    showCallbackModal() {
      eventBus.$emit('showCallbackModal', null, 'otpravka-formy')
    },

    closeCallbackModal() {
      eventBus.$emit('closeCallbackModal')
    },

    showLoginModal() {
      eventBus.$emit('showLoginModal')
    },

    closeLoginModal() {
      eventBus.$emit('closeLoginModal')
    },
  },
}
</script>
