<template>
  <Modal :open="open" @close="handleClose" large>
    <Skeleton v-if="loading"/>
    <DoctorMarkup v-else-if="currentDoctor" :doctor="currentDoctor" @close="handleClose"/>
    <div v-else>
      что-то пошло не так
    </div>
  </Modal>
</template>

<script>
import Modal from '../Modal'
import axios from "axios";
import Skeleton from "./components/Skeleton/Skeleton.vue";
import DoctorMarkup from "./components/DoctorMarkup/DoctorMarkup.vue";

export default {
  components: {DoctorMarkup, Skeleton, Modal},

  props: {
    open: {
      type: Boolean,
      default: false,
    },

    doctor: {
      type: [Number, String],
      require: true,
    },
  },

  data() {
    return {
      loading: true,
      currentDoctor: null
    }
  },

  watch: {
    open(val) {
      if (val) {
        this.fetch()
      }
    }
  },

  methods: {
    fetch() {
      this.loading = true;
      axios.get(`/api/doctors/${this.doctor}`)
        .then(response => this.currentDoctor = response.data.doctor)
        .finally(() => this.loading = false)
    },

    handleClose() {
      this.$emit('close')
    },
  },
}
</script>
