<template>
  <Modal
    :open="open"
    @close="handleClose"
    large
    :hasNext="hasNext"
    :hasPrev="hasPrev"
    @prev="handlePrev"
    @next="handleNext"
  >
    <div v-if="open">
      <h2 class="lg:hidden text-2xl text-heading font-semibold mb-4 mt-2 pr-6">
        {{ element.title }}
      </h2>
      <div class="lg:flex">
        <picture
          class="flex-none w-32 h-32 lg:w-56 lg:h-56 mr-8 inline-block mb-2"
          v-html="element.image_html"
        />
        <div>
          <h2 class="hidden lg:block text-4xl text-heading font-semibold mb-2">
            {{ element.title }}
          </h2>
          <div
            v-html="element.description_html || element.body_html"
            class="text-sm lg:text-base content"
          />
        </div>
      </div>
    </div>
    <template v-slot:footer>
      <div
        class="flex flex-col lg:flex-row gap-4"
        v-if="element.has_an_appointment || element.has_price"
      >
        <button
          class="text-center py-3 px-6 bg-action-primary hover:bg-action-primary-hovered active:bg-action-primary-pressed font-semibold text-white rounded shadow-md"
          v-if="element.has_an_appointment"
          @click="handleShowCallbackModal"
        >
          Оставить заявку на приём
        </button>
        <a
          class="text-center p-3 lg:px-6 bg-interactive hover:bg-interactive-button-hovered active:bg-interactive-button-hovered font-semibold text-white rounded shadow-md"
          :href="`/uslugi-i-ceny?element=true#${element.uuid}`"
          v-if="element.has_price"
        >
          Цены на {{ element.title }}
        </a>
      </div>
    </template>
  </Modal>
</template>

<script>
import {eventBus} from '../../eventBus'
import Modal from '../Modal'

export default {
  components: {Modal},

  props: {
    open: {
      type: Boolean,
      default: false,
    },

    elements: {
      type: Array,
      require: true,
    },

    currentIndex: {
      type: Number,
      require: true,
    },
  },

  data() {
    return {
      loading: false,
      index: this.currentIndex,
    }
  },

  computed: {
    element() {
      return this.elements[this.index]
    },

    hasNext() {
      return this.elements.length - 1 > this.index
    },

    hasPrev() {
      return this.index > 0
    },
  },

  watch: {
    currentIndex(val) {
      this.index = val
    },
  },

  methods: {
    handlePrev() {
      this.index--
    },

    handleNext() {
      console.log('ddf')
      this.index++
    },

    handleClose() {
      this.index = null
      this.$emit('close')
    },

    handleShowCallbackModal() {
      this.handleClose()
      eventBus.$emit('showCallbackModal')
    },
  },
}
</script>
