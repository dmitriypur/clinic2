<script>
import { ScrollLockManager } from '../../utilities/scroll-lock-manager'

export default {
  render() {
    return false
  },

  data() {
    return {
      scrollLockManager: new ScrollLockManager(),
    }
  },

  mounted() {
    this.scrollLockManager.registerScrollLock()
  },

  beforeDestroy() {
    this.scrollLockManager.unregisterScrollLock()
  },
}
</script>
