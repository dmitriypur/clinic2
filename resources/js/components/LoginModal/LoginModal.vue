<template>
  <Modal :open="open" @close="handleClose" small>
    <div v-if="open" class="lg:rounded-b-lg">
      <h3
        class="text-2xl lg:text-4xl text-heading font-semibold mb-2 text-center"
      >
        Вход в личный кабинет
      </h3>

      <Code
        v-if="showCodeView"
        :phone="phoneNumberForm.phone"
        :error="form.getError('code') || form.getError('phone')"
        :loading="form.processing"
        :phoneLoading="phoneNumberForm.processing"
        @submit="submit"
        @changePhone="handleChangePhone"
        @newCode="submitPhoneNumber"
        @showCallbackModal="handleShowCallbackModal"
      />
      <PhoneNumber
        v-else
        :loading="phoneNumberForm.processing"
        :error="phoneNumberForm.getError('phone')"
        @submit="submitPhoneNumber"
      />
    </div>
  </Modal>
</template>

<script>
import Modal from '../Modal'
import phoneInput from '../../mixins/phoneInput'
import {classNames} from '../../utilities/css'
import {Code, PhoneNumber} from './components'
import {eventBus} from '../../eventBus'
import Form from "../../utilities/form";

export default {
  components: {Modal, PhoneNumber, Code},

  mixins: [phoneInput],

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

  data() {
    return {
      showCodeView: false,
      phoneNumberForm: new Form({phone: ''}, {resetOnSuccess: false}),
      form: new Form({
        phone: '',
        password: '',
      }),
    }
  },

  computed: {
    buttonClassName() {
      return classNames(
        'flex items-center justify-center w-full text-center p-3 lg:px-6 font-semibold rounded',
        this.form.processing
          ? 'bg-disabled text-disabled'
          : 'text-white bg-interactive hover:bg-interactive-button-hovered active:bg-interactive-button-hovered shadow-md',
      )
    },
  },

  methods: {
    handlePhoneChange(e) {
      this.form.phone = e.target.value
    },

    submitPhoneNumber(phone = null) {
      if (phone) {
        this.phoneNumberForm.phone = phone
        this.form.phone = phone
      }

      this.phoneNumberForm.post('/api/send-verification-code').then(() => {
        this.showCodeView = true
        this.form.phone = phone
      })
    },

    handleClose() {
      this.showSuccessMessage = false
      this.$emit('close')
    },

    handleChangePhone() {
      this.showCodeView = false
      this.form.reset()
    },

    handleShowCallbackModal() {
      this.$emit('close')
      eventBus.$emit('showCallbackModal', this.phoneNumberForm.phone)
      setTimeout(() => {
        this.showCodeView = false
      }, 100)
    },

    submit(code) {
      this.form.password = code

      this.form.post('/login').then(() => {
        window.location.pathname = '/profile'
      })
    },
  },
}
</script>
