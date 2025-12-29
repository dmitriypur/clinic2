<template>
  <Modal :open="open" @close="handleClose" small>
    <div v-if="open" class="lg:rounded-b-lg">
      <h3
        class="text-2xl lg:text-4xl text-heading font-semibold mb-2 text-center"
      >
        <span v-if="showSuccessMessage">Спасибо!</span>
        <span v-else>Оставить отзыв</span>
      </h3>

      <form
        class="space-y-6"
        @submit.prevent="submit"
        v-if="!showSuccessMessage"
      >
        <div>
          <label for="name-input" class="block text-base">
            Имя
          </label>
          <div class="mt-1">
            <input
              id="name-input"
              autofocus="true"
              name="name"
              type="text"
              autocomplete="name"
              v-model="form.name"
              required=""
              class="text-xl text-black block w-full rounded-md border border-default px-5 py-2.5 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none"
            />
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between">
            <label for="body" class="block text-base">
              Текст отзыва
            </label>
          </div>
          <div class="mt-1 flex">
            <textarea id="body" v-model="form.body"
                      rows="6"
                      class="text-xl text-black block w-full rounded-md border px-5 py-2.5 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none"
                      :class="[form.errors.has('body') ? 'border-red-500': 'border-default']"
            ></textarea>
          </div>
          <p
            v-if="form.errors.has('body')"
            class="mt-3 text-sm leading-6 text-critical"
            v-text="form.getError('body')"
          />
        </div>

        <div>
          <div class="flex flex-row-reverse justify-end">
            <div :class="starClassName" v-for="n in [5, 4, 3, 2, 1]" :key="`star-${n}`">
              <input
                type="radio"
                :id="`star${n}`"
                name="rating"
                :value="n"
                @change="form.rating = n"
                class="hidden peer"
              />
              <label :for="`star${n}`"
                     :title="n"
                     class="inline-block peer-checked:[&_svg]:fill-action-primary peer-checked:[&_svg]:stroke-transparent"
                     :class="n <= form.rating ? '[&_svg]:fill-action-primary [&_svg]:stroke-transparent': '[&_svg]:fill-none'"
              >
                <StarIcon/>
              </label>
            </div>
          </div>
          <p
            v-if="form.errors.has('rating')"
            class="mt-3 text-sm leading-6 text-critical"
            v-text="form.getError('rating')"
          />
        </div>

        <div>
          <button
            type="submit"
            :class="buttonClassName"
            :disabled="form.processing"
          >
            <svg
              v-if="form.processing"
              class="animate-spin-fast h-5 w-5 fill-icon-subdued"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 20 20"
            >
              <path
                d="M7.229 1.173a9.25 9.25 0 1011.655 11.412 1.25 1.25 0 10-2.4-.698 6.75 6.75 0 11-8.506-8.329 1.25 1.25 0 10-.75-2.385z"
              ></path>
            </svg>
            <span v-else>Оставить отзыв</span>
          </button>
        </div>
      </form>

      <div v-else class="mt-4 text-center">
        <p class="text-heading font-medium text-lg md:text-xl leading-none">
          Ваш отзыв отправлен.
        </p>
      </div>
    </div>
  </Modal>
</template>

<script>
import Modal from '../Modal'
import {classNames} from '../../utilities/css'
import {Form} from '../../utilities/form'
import {StarIcon} from "./components";

export default {
  components: {StarIcon, Modal},

  props: {
    open: {
      type: Boolean,
      default: false,
    },

    name: {
      type: String,
      required: false,
    },

    service: {
      type: String,
      required: false,
    },
  },

  data() {
    return {
      showSuccessMessage: false,
      form: new Form({
        service: this.service,
        name: this.name,
        body: '',
        rating: 1
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

    starClassName() {
      return classNames(
        'flex px-1 peer [&_svg]:stroke-icon-subdued',
        'peer-hover:[&_svg]:fill-action-primary hover:[&_svg]:fill-action-primary',
        'peer-hover:[&_svg]:stroke-none hover:[&_svg]:stroke-none',
      )
    }
  },

  watch: {
    name(val) {
      this.form.name = val
    },

    service(val) {
      this.form.service = val
    },
  },

  methods: {
    submit() {
      this.form.post('/api/review').then(() => {
        this.showSuccessMessage = true
      })
    },
    handleClose() {
      this.showSuccessMessage = false
      this.$emit('close')
    },
  },
}
</script>
