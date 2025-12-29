<template>
  <div>
    <div class="space-y-2 my-4">
      <h4 class="font-medium text-interactive text-lg lg:text-xl">
        Введите номер телефона
      </h4>

      <p>
        Оставьте заявку на прием по кнопке выше и мы пришлем вам в смс код доступа в личный кабинет.
      </p>
    </div>

    <form class="space-y-6" @submit.prevent="submit">
      <div>
        <div class="mt-1 flex">
          <span
            class="rounded-l-md border border-default px-5 inline-flex border-r-0 justify-center items-center text-black text-xl pt-1"
          >
            +7
          </span>
          <input
            ref="phone"
            id="phone-input"
            name="phone"
            inputmode="numeric"
            type="text"
            placeholder="(999) 999-99-99"
            required=""
            @keydown="onPhoneKeyDown"
            @paste="onPhonePaste"
            @input="onPhoneInput"
            :class="inputClassName"
          />
        </div>
        <p
          v-if="!!error"
          class="mt-3 text-sm leading-6 text-critical"
          v-text="error"
        />
      </div>

      <div>
        <button type="submit" :class="buttonClassName" :disabled="loading">
          <svg
            v-if="loading"
            class="animate-spin-fast h-5 w-5 fill-icon-subdued"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 20 20"
          >
            <path
              d="M7.229 1.173a9.25 9.25 0 1011.655 11.412 1.25 1.25 0 10-2.4-.698 6.75 6.75 0 11-8.506-8.329 1.25 1.25 0 10-.75-2.385z"
            ></path>
          </svg>
          <span v-else>Войти</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import {classNames} from '../../../../utilities/css'
import PhoneInputMixin from '../../../../mixins/phoneInput'

export default {
  mixins: [PhoneInputMixin],

  props: {
    loading: Boolean,
    error: String,
  },

  computed: {
    buttonClassName() {
      return classNames(
        'flex items-center justify-center w-full text-center p-3 lg:px-6 font-semibold rounded',
        this.loading
          ? 'bg-disabled text-disabled'
          : 'text-white bg-interactive hover:bg-interactive-button-hovered active:bg-interactive-button-hovered shadow-md',
      )
    },

    inputClassName() {
      return classNames(
        'text-xl text-black block w-full rounded-r-md border border-default px-5 py-2.5 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none',
        this.loading && 'bg-disabled',
      )
    },
  },

  methods: {
    submit() {
      this.$emit('submit', this.$refs.phone.value)
    },
  },
}
</script>
