import { eventBus } from '../../eventBus.js'

export default {
  name: 'MobileBottomNavigation',

  data() {
    return {}
  },

  methods: {
    openBookingWidget() {
      eventBus.$emit('openBookingWidgetV3', 'otpravka-formy')
    },
  },
}
