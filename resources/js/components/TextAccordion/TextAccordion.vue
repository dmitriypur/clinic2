<script>
import {classNames} from "../../utilities/css";

export default {
  props: {
    count: Number,
  },

  data() {
    return {
      open: false
    }
  },

  computed: {
    className() {
      return classNames(
        this.count > 1 ? classNames(
          '',
          this.open ? 'block' : 'hidden'
        ) : 'mb-6 lg:mb-8'
      )
    }
  },

  methods: {
    toggle() {
      this.open = !this.open
    }
  }
}
</script>
