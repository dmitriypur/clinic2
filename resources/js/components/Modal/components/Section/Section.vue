<template>
  <section :class="className">
    <slot/>
  </section>
</template>

<script>
import {classNames} from '../../../../utilities/css'

export default {
  props: {
    flush: Boolean,
    subdued: Boolean,
  },

  computed: {
    className() {
      return classNames(
        'grow-0 shrink-0 basis-auto',
        this.flush ? 'p-0' : 'p-4 lg:p-8',
        this.subdued && 'bg-surface-subdued',
      )
    },
  },
}
</script>
