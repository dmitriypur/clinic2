<template>
  <form class="w-full" @submit.prevent="submit" v-if="!showSuccessMessage">
    <!--    <div class="flex items-end gap-4 justify-center">-->
    <div :class="['flex flex-col px-0', fox === 1 ? 'items-center' : '']">
      <div
        class="flex flex-col md:grid grid-cols-8 md:items-end gap-4 max-w-4xl w-full"
      >
        <div class="col-span-3">
          <label for="cta-name-input" class="block text-base hidden">
            Имя
          </label>
          <div class="mt-1">
            <input
              id="cta-name-input"
              name="name"
              type="text"
              autocomplete="name"
              v-model="form.name"
              placeholder="Введите имя"
              required=""
              class="text-black block w-full rounded-lg px-5 py-3 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none shadow shadow-md"
            />
          </div>
        </div>

        <div class="col-span-3">
          <div class="flex items-center justify-between">
            <label for="cta-phone-input" class="block text-base hidden">
              Номер телефона
            </label>
          </div>
          <div class="mt-1 relative">
            <span class="absolute top-1/2 left-4 -translate-y-1/2 text-md font-medium">+7</span>
            <input
              id="cta-phone-input"
              name="phone"
              type="text"
              placeholder="Контактный телефон"
              required=""
              ref="phone"
              inputmode="numeric"
              :value="form.phone"
              @keydown="onPhoneKeyDown"
              @paste="onPhonePaste"
              @input="onPhoneInput"
              @blur="handlePhoneChange"
              @change="handlePhoneChange"
              class="text-black block w-full rounded-lg pr-5 pl-11 py-3 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none shadow shadow-md"
            />
          </div>
          <p
            v-if="form.errors.has('phone')"
            class="mt-3 text-sm leading-6 text-critical md:absolute"
            v-text="form.getError('phone')"
          />
        </div>

        <div class="col-span-2 pt-1 w-full">
          <button
            type="submit"
            :class="buttonClassName"
            :disabled="form.processing"
          >
            <svg
              v-if="form.processing"
              class="animate-spin-fast h-5 w-5 fill-white"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 20 20"
            >
              <path
                d="M7.229 1.173a9.25 9.25 0 1011.655 11.412 1.25 1.25 0 10-2.4-.698 6.75 6.75 0 11-8.506-8.329 1.25 1.25 0 10-.75-2.385z"
              ></path>
            </svg>
            <span v-else>Записаться на прием</span>
          </button>
        </div>
      </div>

      <div>
        <div
          :class="['relative flex gap-x-3 max-w-4xl pt-6 md:mt-8', fox === 1 ? 'justify-center' : '']"
        >
          <div class="flex h-6 items-center">
            <input
              id="cta-privacy"
              name="privacy"
              type="checkbox"
              v-model="form.privacy"
              class="h-4 w-4 rounded border-white bg-transparent appearance-none border checked:bg-checkbox-checked checked:accessibility:!bg-black [background-size:100%] bg-center bg-no-repeat focus:outline focus:outline-2 focus:outline-offset-1"
            />
          </div>
          <div class="text-sm leading-6">
            <label for="cta-privacy" class="font-medium text-white select-none">
              Оставляя заявку, я соглашаюсь на использование
              <a
                href="/documents"
                target="_blank"
                class="underline hover:no-underline"
              >
                персональных данных.
              </a>
            </label>
          </div>
        </div>
        <p
          v-if="form.errors.has('privacy')"
          class="py-1 px-3 bg-white rounded-lg mt-3 text-sm leading-6 text-critical"
          v-text="form.getError('privacy')"
        />
      </div>
    </div>
  </form>

  <div v-else class="mt-4 text-center">
    <p
      class="text-heading font-medium text-white text-lg md:text-xl leading-none"
    >
      Ваша заявка принята.
      <br />
      Мы скоро с вами свяжемся.
    </p>
  </div>
</template>

<script>
import phoneInput from '../../mixins/phoneInput'
import { classNames } from '../../utilities/css'
import { Form } from '../../utilities/form'

const Modal = () => import('../Modal')
export default {
  components: { Modal },

  mixins: [phoneInput],

  props: {
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

    fox: Number,
  },

  data() {
    return {
      showSuccessMessage: false,
      form: new Form({
        name: this.name,
        phone: this.phone,
        privacy: false,
      }),
    }
  },

  computed: {
    buttonClassName() {
      return classNames(
        'w-full flex items-center justify-center w-full text-center p-3 lg:px-5 font-semibold rounded-lg',
        this.form.processing
          ? 'bg-disabled text-disabled'
          : 'text-white btn-blue-gradient shadow-md',
      )
    },
  },

  watch: {
    phone(val) {
      this.form.phone = val
    },

    name(val) {
      this.form.name = val
    },
  },

  methods: {
    handlePhoneChange(e) {
      this.form.phone = e.target.value
    },

    submit() {
      this.form.post(`/api/callback${window.location.search}`).then(() => {
        if (this.target) {
          ym(94302729, 'reachGoal', this.target)
        }
        this.showSuccessMessage = true
      })
    },
    handleClose() {
      this.showSuccessMessage = false
    },
  },
}
</script>
