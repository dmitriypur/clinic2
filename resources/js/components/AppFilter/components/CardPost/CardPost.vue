<script>

export default {
  props: {
    post: Object,
  },
}
</script>

<template>
  <div class="px-3 pt-3 pb-6 bg-white rounded-2xl h-full">
    <div
      class="relative rounded-xl overflow-hidden border border-interactive/40 h-56 [&_img]:w-full [&_img]:h-full [&_img]:object-cover">
      <a :href="post.handle">
        <img v-if="post.image" :src="post.image" :alt="post.title">
        <picture v-else>
          <source type="image/webp" class="lazy" :srcset="'images/no-image.webp'">
          <img :src="'images/no-image.jpg'" :alt="post.title" width="342" height="222">
        </picture>
      </a>
      <div class="absolute left-2 bottom-2 h-auto flex flex-wrap gap-2">
        <a v-for="(tag, handle) in post.tags" :href="'tags/' + handle"
           class="bg-white/90 text-es rounded-sm px-2 py-1 leading-none"
        >{{ tag }}</a>
      </div>
    </div>

    <p class="text-sm text-interactive/40 mt-3 mb-2">{{ post.created_at }}</p>
    <hr>
    <a :href="post.handle">
      <h3 class="text-lg font-semibold mt-2 leading-tight">{{ post.title }}</h3>
    </a>

    <div v-if="post.body_html" class="text-sm mt-2" v-html="post.body_html"></div>

  </div>

</template>
