<script>

export default {
  props: {
    post: Object,
  },
}
</script>

<template>
  <div class="px-3 pt-3 pb-6 bg-white rounded-2xl h-full flex flex-col">
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

    <div class="flex flex-wrap items-center justify-between gap-2 mt-3 mb-2">
      <p class="text-sm text-interactive/40">{{ post.created_at }}</p>
      <div class="flex flex-wrap items-center gap-3">
        <span aria-label="Просмотров: 357" title="Просмотров" class="inline-flex items-center gap-1 text-sm font-medium text-interactive/60">
          <svg aria-hidden="true" viewBox="0 0 38 38" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 h-4 w-4"><path fill="currentColor" d="M24.9375 19C24.9375 20.5747 24.3119 22.0849 23.1984 23.1984C22.0849 24.3119 20.5747 24.9375 19 24.9375C17.4253 24.9375 15.9151 24.3119 14.8016 23.1984C13.6881 22.0849 13.0625 20.5747 13.0625 19C13.0625 17.4253 13.6881 15.9151 14.8016 14.8016C15.9151 13.6881 17.4253 13.0625 19 13.0625C20.5747 13.0625 22.0849 13.6881 23.1984 14.8016C24.3119 15.9151 24.9375 17.4253 24.9375 19Z"></path> <path fill="currentColor" d="M0 19C0 19 7.125 5.9375 19 5.9375C30.875 5.9375 38 19 38 19C38 19 30.875 32.0625 19 32.0625C7.125 32.0625 0 19 0 19ZM19 27.3125C21.2046 27.3125 23.3189 26.4367 24.8778 24.8778C26.4367 23.3189 27.3125 21.2046 27.3125 19C27.3125 16.7954 26.4367 14.6811 24.8778 13.1222C23.3189 11.5633 21.2046 10.6875 19 10.6875C16.7954 10.6875 14.6811 11.5633 13.1222 13.1222C11.5633 14.6811 10.6875 16.7954 10.6875 19C10.6875 21.2046 11.5633 23.3189 13.1222 24.8778C14.6811 26.4367 16.7954 27.3125 19 27.3125Z"></path></svg>
          <span>{{ Number(post.article_views_count || 0).toLocaleString('ru-RU') }}</span>
        </span>
      </div>
    </div>
    <hr class="mt-2">
    <div class="flex flex-col flex-auto">
      <a :href="post.handle">
        <h3 class="text-lg font-semibold mt-2 leading-tight">{{ post.title }}</h3>
      </a>

      <div v-if="post.body_html" class="text-sm my-2" v-html="post.body_html"></div>
      <span class="hidden items-center gap-1 font-medium text-interactive/50 mt-auto text-xs">
        <span>Записей на диагностику:</span> <span>{{ Number(post.booking_conversions_count || 0).toLocaleString('ru-RU') }}</span>
    </span>
    </div>
  </div>

</template>
