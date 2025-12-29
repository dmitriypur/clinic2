<template>
  <div v-observe-visibility="setIsElementInViewport">
    <iframe v-if="isElementInViewport" src="https://yandex.ru/sprav/widget/rating-badge/134457918290?type=rating"
            width="150" height="50" frameborder="0" title="Рейтинг"></iframe>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isElementInViewport: false,
    }
  },

  methods: {
    setIsElementInViewport(val) {
      this.isElementInViewport = val
    }
  }
}
</script>
