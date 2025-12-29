<template>
  <Modal :open="open" @close="handleClose" small>
    <CallbackForm
      :phone="phone"
      :name="name"
      button-content="Перезвонить"
      :target="target"
    />
  </Modal>
</template>

<script>
const Modal = () => import('../Modal')
const CallbackForm = () => import('../CallbackForm/CallbackForm.vue')

export default {
  components: { CallbackForm, Modal },

  props: {
    open: {
      type: Boolean,
      default: false,
    },

    phone: {
      type: String,
      require: false,
    },

    name: {
      type: String,
      required: false,
    },

    target: {
      type: String,
      default: null,
    },
  },

  methods: {
    handleClose() {
      this.showSuccessMessage = false
      this.$emit('close')
    },
  },
}
</script>
