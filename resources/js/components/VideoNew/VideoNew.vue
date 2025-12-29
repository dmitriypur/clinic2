
<script>

import {classNames} from "../../utilities/css";

export default {
  props: {
    video: String,
    poster: String,
  },

  data() {
    return {
      hideBackdrop: false,
      isLoaded: false,
      observer: null,
    }
  },

  computed: {
    backdropClassName() {
      return classNames('absolute inset-0', this.hideBackdrop ? 'bg-transparent' : 'bg-white/50');
    }
  },

  methods: {
    videoEnded() {
      this.hideBackdrop = false;
      this.$refs.video.load();
    },
    togglePlay() {
      if (this.$refs.video.paused || this.$refs.video.ended) {
        this.$refs.video.play();
      } else {
        this.$refs.video.pause();
      }
    },
    initIntersectionObserver() {
      if ('IntersectionObserver' in window) {
        this.observer = new IntersectionObserver(
          this.handleIntersection,
          {
            root: null,
            rootMargin: '0px',
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
