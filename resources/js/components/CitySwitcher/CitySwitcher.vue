<template>
  <div class="relative group" v-click-outside="close">
    <button @click="toggle" class="text-action-primary font-medium flex items-center">
      <span class="font-semibold text-base/6 border-b border-action-primary hover:border-transparent">{{ currentCityName }}</span>
      <span class="flex w-6 h-6 shadow rounded ml-3">
        <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" >
          <path fill="#1F3462"
                d="M6.24 8.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 1 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06Z"
          />
        </svg>
      </span>
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
           class="absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 z-50 overflow-hidden">
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
