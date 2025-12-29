<script>
import {classNames} from "../../../utilities/css";

export default {
  data() {
    return {
      open: false,
    }
  },

  props: {
    count: Number
  },

  computed: {
    className() {
      return classNames('p-4 ml-auto select-none z-0 pointer-events-none pointer-events-none', this.open ? 'text-action-primary rotate-45 text-4xl' : 'text-3xl')
    },
  },

  methods: {
    toggle() {
      this.open = !this.open
    },
  }
}
</script>
