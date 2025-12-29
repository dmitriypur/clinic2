<template>
  <div class="bg-white flex items-center shrink-0 py-3 px-4 rounded-t-lg">
    <div :id="id" class="break-words flex-1 mt-0.5">
      <h2 class="text-heading font-medium text-lg md:text-xl leading-none">
        <slot/>
      </h2>
    </div>

    <CloseButton title @click="onClose"/>
  </div>
</template>

<script>
import {CloseButton} from '../CloseButton'

export default {
  components: {
    CloseButton,
  },

  props: {
    id: {
      type: String,
      required: true,
    },
  },

  methods: {
    onClose() {
      this.$emit('close')
    },
  },
}
</script>
