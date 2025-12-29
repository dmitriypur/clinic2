<template>
  <div class="lg:rounded-b-lg">
    <h3 class="text-2xl lg:text-4xl text-heading font-semibold mb-2 text-center">
      <span v-if="showSuccessMessage">Спасибо!</span>
      <span v-else-if="!titleHidden">Перезвоните мне</span>
    </h3>

    <form class="space-y-6" @submit.prevent="submit" v-if="!showSuccessMessage">
      <TextField label="Имя" id="name-input" autofocus name="name" autocomplete="name" v-model="form.name"
        required :error="form.getError('name')" />
      <TextField label="Номер телефона" type="phone" id="phone-input" autofocus name="phone"
        placeholder="(999) 999-99-99" required v-model="form.phone" :error="form.getError('phone')" />

      <div>
        <div class="relative flex gap-x-3 max-w-4xl">
          <div class="flex h-6 items-center">
            <input id="cb-privacy" name="privacy" type="checkbox" v-model="form.privacy"
              class="h-4 w-4 rounded bg-transparent appearance-none border border-default text-interactive checked:[background-color:currentColor] checked:bg-checkbox-checked [background-size:100%] bg-center bg-no-repeat focus:outline focus:outline-2 focus:outline-offset-1" />
          </div>
          <div class="text-sm leading-6">
            <label for="cb-privacy" class="font-medium select-none">
              Оставляя заявку, я соглашаюсь на использование
              <a href="/documents" target="_blank" class="underline hover:no-underline">
                персональных данных.
              </a>
            </label>
          </div>
        </div>
        <p v-if="form.errors.has('privacy')" class="mt-3 text-sm leading-6 text-critical"
          v-text="form.getError('privacy')" />
      </div>

      <div>
        <button type="submit" :class="buttonClassName" :disabled="form.processing">
          <svg v-if="form.processing" class="animate-spin-fast h-5 w-5 fill-icon-subdued"
            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
            <path
              d="M7.229 1.173a9.25 9.25 0 1011.655 11.412 1.25 1.25 0 10-2.4-.698 6.75 6.75 0 11-8.506-8.329 1.25 1.25 0 10-.75-2.385z">
            </path>
          </svg>
          <span v-else v-text="buttonContent" />
        </button>
      </div>
    </form>

    <div v-else class="mt-4 text-center">
      <p class="text-heading font-medium text-lg md:text-xl leading-none">
        Ваша заявка принята.
        <br />
        Мы скоро с вами свяжемся.
      </p>
    </div>
  </div>
</template>
<script>
import { Form } from '../../utilities/form'
import { classNames } from '../../utilities/css.js'

const TextField = () => import('../TextField')

export default {
  components: { TextField },

  props: {
    buttonContent: {
      type: String,
      require: false,
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

    titleHidden: {
      type: Boolean,
      default: false,
    }
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
        'flex items-center justify-center w-full text-center p-3 lg:px-6 font-semibold rounded',
        this.form.processing
          ? 'bg-disabled text-disabled'
          : 'text-white bg-interactive hover:bg-interactive-button-hovered active:bg-interactive-button-hovered shadow-md',
      )
    },
  },

  // watch: {
  //   phone(val) {
  //     this.form.phone = val
  //   },

  //   name(val) {
  //     this.form.name = val
  //   },
  // },

  methods: {
    submit() {
      this.form
        .post(`/api/callback${window.location.search}`)
        .then(() => {
          if (this.target) {
            ym(94302729, 'reachGoal', this.target)
          }

          this.showSuccessMessage = true
        })
    },
  },
}
</script>
