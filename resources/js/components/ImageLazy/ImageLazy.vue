<script>

export default {
  props: {
    eager: {
      type: Boolean,
      default: false,
    },
    threshold: {
      type: Number,
      default: 0,
    },
    rootMargin: {
      type: String,
      default: "0px",
    },
  },

  data() {
    return {
      isLoaded: false,
      observer: null,
    }
  },

  methods: {
    initIntersectionObserver() {
      if (this.eager) {
        this.loadVideo();
        return;
      }

      if ('IntersectionObserver' in window) {
        this.observer = new IntersectionObserver(
          this.handleIntersection,
          {
            root: null,
            rootMargin: this.rootMargin,
            threshold: this.threshold
          }
        );
        this.observer.observe(this.$refs.container);
      }else {
        // Fallback для браузеров без IntersectionObserver
        this.loadVideo();
      }
    },
    handleIntersection(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          this.loadVideo();
          if (this.observer) {
            this.observer.unobserve(entry.target);
          }
        }
      });
    },
    loadVideo() {
      if (!this.isLoaded) {
        this.isLoaded = true;
      }
    },
  },
  mounted() {
    this.initIntersectionObserver();
  },
  beforeDestroy() {
    if (this.observer) {
      this.observer.disconnect();
    }
  },
}
</script>
