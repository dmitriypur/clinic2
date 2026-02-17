<template>
  <Modal :open="open" @close="$emit('close')" fix title="Видеовизитка"
         limitHeight>
    <div v-if="open" class="-m-4 lg:-m-8 overflow-hidden lg:rounded-b-lg md:min-w-[700px] xl:min-w-[1000px]">
      <iframe class="w-full aspect-video" :src="videoUrl"
              allow="autoplay; encrypted-media; fullscreen; picture-in-picture; screen-wake-lock;"
              frameborder="0"
              allowfullscreen></iframe>
    </div>
  </Modal>
</template>

<script>
const Modal = () => import('../Modal')

export default {
  components: {Modal},

  props: {
    open: {
      type: Boolean,
      default: false,
    },

    videoUrl: {
      type: String,
      require: true,
    },
  },
}
</script>
