<template>
  <Modal :open="open" @close="handleClose">
    <CallbackFormNew
      :phone="phone"
      :name="name"
      button-content="Отправить"
      :target="target"
    />
  </Modal>
</template>

<script>
const Modal = () => import("../Modal");
const CallbackFormNew = () => import("../CallbackForm/CallbackFormNew.vue");

export default {
  components: { CallbackFormNew, Modal },

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
      this.$emit("close");
    },
  },
};
</script>
