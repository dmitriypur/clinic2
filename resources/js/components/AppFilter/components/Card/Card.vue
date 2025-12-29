<script>
export default {
  props: {
    review: Object,
  },
}
</script>

<template>
  <div class="mb-5 md:mb-0 rounded-2xl p-4 lg:p-7 bg-white h-full">
    <div class="flex flex-col h-full">
      <div :class="['flex flex-wrap items-center gap-2 justify-between mb-5', review.resource ? '' : 'h-10']">

        <div v-if="review.resource"
             class="accessibility:border-none accessibility:p-0 flex items-center rounded-md pr-2 text-base font-medium gap-2 border border-surface-subdued">
          <div class="accessibility:hidden  w-auto min-w-10 h-10 px-1 rounded-md bg-surface-subdued flex items-center justify-center">
            <img :src="review.resources[0]" :alt="review.resources[1]">
          </div>
          {{ review.resources[1] }}
        </div>
        <div v-if="review.doctor"
             class="flex flex-shrink-0 items-center justify-center rounded-md bg-surface-subdued p-2 h-10">
          <p class="text-sm"><span
            class="text-interactive/60">врач:</span> {{ review.doctor }}
          </p>
        </div>

      </div>
      <div class="space-y-2">
        <p class="text-disabled font-medium">{{ review.get_date }}</p>
        <div class="flex space-x-1.5">
            <span v-for="star in review.rating" class="w-5 h-5 text-action-primary">
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 16" class="fill-current">
                  <path d="M8.07668 1.1899C8.41827 0.368607 9.58173 0.368607 9.92332 1.1899L11.4105 4.76543C11.5545 5.11167 11.8801 5.34824 12.2539 5.3782L16.114 5.68767C17.0006 5.75875 17.3601 6.86526 16.6846 7.44393L13.7436 9.96319C13.4588 10.2071 13.3345 10.5899 13.4215 10.9547L14.32 14.7215C14.5264 15.5867 13.5851 16.2705 12.826 15.8069L9.52125 13.7884C9.20124 13.5929 8.79876 13.5929 8.47875 13.7884L5.17397 15.8069C4.41487 16.2705 3.47362 15.5867 3.68001 14.7215L4.57852 10.9547C4.66553 10.5899 4.54116 10.2071 4.25637 9.96319L1.31539 7.44393C0.639854 6.86526 0.99938 5.75875 1.88603 5.68767L5.74612 5.3782C6.11991 5.34824 6.44552 5.11167 6.58953 4.76543L8.07668 1.1899Z"/>
              </svg>
            </span>
        </div>
      </div>

      <div class="flex flex-col space-y-4 mb-4 mt-2 relative">
        <a :href="review.link_resource" target="_blank">
          <span class="text-2xl font-semibold">{{ review.name }}</span>
        </a>
        <div v-html="review.body_html" class="content overflow-y-auto max-h-48"></div>

      </div>
      <div class="mt-auto flex flex-wrap gap-2">

        <div v-for="(page, handle) in review.pages"
             class="bg-surface-subdued py-1 px-3 text-sm inline-block rounded-md">
          <a :href="handle">{{ page}}</a>
        </div>
      </div>
    </div>
  </div>
</template>
