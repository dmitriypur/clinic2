<script>
export default {
  props: {
    keyCode: {
      type: Number,
      required: true,
    },
    keyEvent: {
      type: String,
      validator(value) {
        return ['keyup', 'keydown'].indexOf(value) !== -1
      },
      default: 'keyup',
    },
    handler: {
      type: Function,
      default() {
        return null;
      }
    },
  },

  mounted() {
    document.addEventListener(this.keyEvent, this.handleKeyEvent);
  },

  beforeDestroy() {
    document.removeEventListener(this.keyEvent, this.handleKeyEvent);
  },

  methods: {
    handleKeyEvent(event) {
      if (event.keyCode === this.keyCode) {
        this.handler(event);
      }
    }
  },

  render() {
    return null;
  }
}
</script>
