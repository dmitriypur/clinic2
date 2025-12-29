<template>
  <div class="aspect-video relative">
    <div :class="backdropClassName">
      <button
        type="button"
        v-show="!hideBackdrop"
        class="absolute z-10 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"
        @click="togglePlay"
      >
        <span class="block w-14 h-14 md:w-36 md:h-36">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 150" fill="none">
            <circle cx="75" cy="75" r="73.5" fill="transparent" stroke="white" stroke-width="3"/>
            <path d="M108.791 75L58.1042 104.264L58.1042 45.7359L108.791 75Z" fill="white"/>
          </svg>
        </span>
      </button>
    </div>
    <video ref="video" class="w-full aspect-video" preload="auto" playsinline="" :poster="poster"
           @play="hideBackdrop = true" @pause="hideBackdrop = false" @ended="videoEnded" :controls="hideBackdrop">
      <source type="video/mp4" :src="video">
    </video>
  </div>
</template>

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
    }
  },

  computed: {
    backdropClassName() {
      return classNames('absolute inset-0', this.hideBackdrop ? 'bg-transparent' : 'bg-black/50');
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
    }
  }
}
</script>
