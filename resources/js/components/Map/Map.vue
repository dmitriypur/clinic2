<template>
  <div v-observe-visibility="setIsElementInViewport"
       class="absolute w-full h-[100vw] lg:h-[600px] [&_>_.ymap-container]:w-full [&_>_.ymap-container]:h-[100vw] [&_>_.ymap-container]:lg:h-[600px] [&_>_.ymap-container]:absolute">
    <yandex-map v-if="isElementInViewport" :settings="settings" :coords="actualCoordinates"
                :scroll-zoom="false">
      <ymap-marker marker-id="main" :coords="actualCoordinates"
                   :icon="{ layout: 'default#image', imageHref: pinUrl }"/>
    </yandex-map>
  </div>
</template>

<script>
export default {
  props: {
    coordinates: String,
    pinUrl: String,
  },

  data() {
    return {
      isElementInViewport: false,
      settings: {
        apiKey: window.YANDEX_API_KEY,
        lang: "ru_RU",
        coords: [window.COORDINATES],
        enterprise: false,
        version: "2.1",
      },
    }
  },

  computed: {
    actualCoordinates() {
      return this.coordinates.split(', ');
    }
  },

  methods: {
    setIsElementInViewport(val) {
      this.isElementInViewport = val
    }
  }
}
</script>
