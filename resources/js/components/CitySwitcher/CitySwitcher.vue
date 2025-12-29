<template>
  <div class="relative group" v-click-outside="close">
    <button @click="toggle" class="flex items-center gap-2 text-interactive hover:text-action-primary transition-colors">
      <div class="w-5 h-5">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
        </svg>
      </div>
      <span class="font-medium">{{ currentCityName }}</span>
      <div class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
      </div>
    </button>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div v-show="open"
           class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 z-50 overflow-hidden">
        <div class="py-1 max-h-96 overflow-y-auto">
          <a v-for="city in cities" 
             :key="city.id" 
             :href="city.url"
             class="block px-4 py-2 text-sm text-gray-700 hover:bg-surface-subdued hover:text-action-primary"
             :class="{ 'bg-surface-subdued text-action-primary font-medium': city.is_current }">
            {{ city.name }}
          </a>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  name: 'CitySwitcher',
  directives: {
    'click-outside': {
      bind: function (el, binding, vnode) {
        el.clickOutsideEvent = function (event) {
          if (!(el == event.target || el.contains(event.target))) {
            vnode.context[binding.expression](event);
          }
        };
        document.body.addEventListener('click', el.clickOutsideEvent)
      },
      unbind: function (el) {
        document.body.removeEventListener('click', el.clickOutsideEvent)
      }
    }
  },
  props: {
    cities: {
      type: Array,
      required: true
    },
    currentCityName: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      open: false
    }
  },
  methods: {
    toggle() {
      this.open = !this.open
    },
    close() {
      this.open = false
    }
  }
}
</script>
