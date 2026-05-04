<template>
  <div class="!m-0 p-2 md:px-14 md:py-12 w-full md:w-[536px]">
    <h3 class="text-center text-[26px] font-semibold leading-[1.06] text-black md:text-[34px]">
      <span v-if="showSuccessMessage">Спасибо!</span>
      <span v-else>Заполните данные</span>
    </h3>

    <div v-if="!showSuccessMessage" class="mx-auto mt-6 md:mt-10 w-full max-w-[888px]">
      <form class="space-y-5 md:space-y-6" @submit.prevent="submit">
        <div>
          <input
            v-model="form.name"
            type="text"
            name="name"
            autocomplete="name"
            class="text-sm w-full rounded-lg bg-[#EBF0F3] py-4 px-6"
            :class="form.getError('name') ? 'border border-[#E04F4F]' : ''"
            placeholder="Введите имя"
          />
          <p
            v-if="form.getError('name')"
            class="mt-2 text-xs text-[#E04F4F]"
            v-text="form.getError('name')"
          />
        </div>

        <div>
          <input
            v-model="form.phone"
            v-mask="'+7 (###) ###-##-##'"
            type="tel"
            name="phone"
            class="text-sm w-full rounded-lg bg-[#EBF0F3] py-4 px-6"
            :class="form.getError('phone') ? 'border border-[#E04F4F]' : ''"
            placeholder="+7 (___) ___-__-__"
          />
          <p
            v-if="form.getError('phone')"
            class="mt-2 text-xs text-[#E04F4F]"
            v-text="form.getError('phone')"
          />
        </div>

        <div class="pt-1 md:pt-[16px]">
          <label class="mx-auto flex justify-center items-center max-w-[720px] items-start gap-4">
            <input
              id="cb-privacy"
              v-model="form.privacy"
              name="privacy"
              type="checkbox"
              class="shrink-0 appearance-none rounded-md border border-[#3E79DA] bg-white checked:bg-[#3E79DA] checked:bg-checkbox-checked bg-center bg-no-repeat [background-size:100%] focus:outline-none h-6 w-6"
            />
            <span class="text-sm">
              Оставляя заявку, я соглашаюсь
              <br />
              на использование
              <a href="/documents" target="_blank" class="font-semibold underline hover:no-underline">
                персональных данных
              </a>
            </span>
          </label>
          <p
            v-if="form.errors.has('privacy')"
            class="mx-auto mt-3 text-xs text-[#E04F4F]"
            v-text="form.getError('privacy')"
          />
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full rounded-xl btn-gradient h-[52px] bg-action-primary font-semibold leading-none text-white transition-opacity disabled:cursor-not-allowed disabled:opacity-70"
        >
          <span v-if="form.processing">Отправка...</span>
          <span v-else>{{ buttonContent || 'Отправить' }}</span>
        </button>
      </form>

      <div v-if="showOnlineLink" class="text-center mt-6">
        <button
          type="button"
          class="font-semibold leading-none text-[#253B6A] underline hover:no-underline"
          @click="$emit('open-online')"
        >
          Записаться онлайн
        </button>
      </div>
    </div>

    <div v-else class="mt-8 text-center md:mt-10">
      <p class="font-semibold leading-[1.2] text-[#253B6A]">
        Ваша заявка принята.
        <br />
        Мы скоро с вами свяжемся.
      </p>

      <div v-if="showOnlineLink" class="mt-6">
        <button
          type="button"
          class="font-semibold leading-none text-[#253B6A] underline hover:no-underline"
          @click="$emit('open-online')"
        >
          Записаться онлайн
        </button>
      </div>
    </div>
  </div>
</template>
<script>
import { Form } from '../../utilities/form'
import { getCurrentCityName } from '../../utilities/currentCity'
import { getUtmParameters } from '../../utilities/utmParameters'

export default {
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
    },

    showOnlineLink: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      showSuccessMessage: false,
      form: new Form({
        name: this.name,
        phone: this.phone,
        city: getCurrentCityName(),
        privacy: false,
        ...getUtmParameters(),
      }),
    }
  },

  methods: {
    submit() {
      this.form
        .post(`/api/callback${window.location.search}`)
        .then(() => {
          if (typeof ym === 'function') {
            ym(94302729, 'reachGoal', 'otpravka-formy')
          }

          this.showSuccessMessage = true
        })
    },
  },
}
</script>
