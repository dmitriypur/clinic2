<script>
import {classNames} from "../../utilities/css";

export default {
  props: {
    isOpening: Number
  },
  data() {
    return {
      open: false,
    }
  },

  methods: {
    toggle(e) {
      this.open = !this.open

      if (!this.open) {
        this.$refs.infoBox.style.height = 1128 + 'px'
      } else {
        this.$refs.infoBox.style.height = 100 + '%'
      }
    },
    matchHeight() {
      let height = this.$refs.infoBox.clientHeight;
      this.$refs.infoBox.style.height = 1128 + 'px'
    }
  },

  mounted() {
    if(this.isOpening){
      this.matchHeight()
    }
  },
}
</script>
