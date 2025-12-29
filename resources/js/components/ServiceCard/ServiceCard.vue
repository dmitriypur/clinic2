<script>
import {classNames} from "../../utilities/css";

export default {
  props: {
    uuid: String,
  },

  data() {
    return {
      active: false,
    }
  },

  computed: {
    className() {
      return classNames('px-4 lg:px-6', this.active ? 'block' : 'hidden')
    },

    mainCardClassName() {
      return classNames(
        'flex gap-4 items-center cursor-pointer group p-4 lg:p-7',
        this.active && 'border-b-2'
      )
    },
  },

  mounted() {
    const elementUuid = window.location.hash.replace(/^#/, "");

    if (elementUuid === this.uuid) {
      this.active = true
    }
  },

  methods: {
    toggle() {
      this.active = !this.active
    },
  },
}
</script>
