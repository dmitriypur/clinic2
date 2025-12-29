<template>
  <div>
    <div class="mb-4">
      <h4 class="font-medium text-interactive text-lg lg:text-xl mb-2">
        Введите код
      </h4>

      <!-- <p>Мы отправили код подтверждения на номер</p> -->
      <p>Введите код для номера</p>
      <p class="flex space-x-2">
        <span class="font-medium">{{ finalPhone }}</span>
        <button :class="buttonClassName" @click="$emit('changePhone')">
          Изменить
        </button>
      </p>
    </div>

    <form class="space-y-6" @submit.prevent="submit">
      <div>
        <div class="mt-1 flex">
          <input
            :disabled="loading"
            id="code-input"
            name="code"
            type="text"
            placeholder="****"
            required=""
            v-model="code"
            maxlength="4"
            :class="inputClassName"
          />
        </div>
        <p
          v-if="!!error"
          class="mt-3 text-sm leading-6 text-critical"
          v-text="error"
        />
      </div>
    </form>

    <div class="flex justify-between items-center mt-4">
      <button
        :class="newCodeButtonClassName"
        @click="$emit('showCallbackModal')"
      >
        Получить новый код
      </button>
      <!-- <button
        :disabled="loading"
        :class="newCodeButtonClassName"
        @click="$emit('newCode')"
      >
        <span class="flex space-x-1 items-center">
          <span>Получить новый код</span>
          <span v-if="phoneLoading">
            <svg
              class="animate-spin-fast h-4 w-4 fill-icon-subdued"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 20 20"
            >
              <path
                d="M7.229 1.173a9.25 9.25 0 1011.655 11.412 1.25 1.25 0 10-2.4-.698 6.75 6.75 0 11-8.506-8.329 1.25 1.25 0 10-.75-2.385z"
              ></path>
            </svg>
          </span>
        </span>
      </button> -->

      <!-- <button
        :disabled="loading"
        :class="buttonClassName"
        @click="$emit('showCallbackModal')"
      >
        Не приходит СМС?
      </button> -->
    </div>
  </div>
</template>

<script>
import { classNames } from '../../../../utilities/css'

export default {
  props: {
    phone: String,
    error: String,
    loading: Boolean,
    phoneLoading: Boolean,
  },

  data() {
    return {
      code: '',
    }
  },

  computed: {
    finalPhone() {
      return `+7 ${this.phone}`
    },

    inputClassName() {
      return classNames(
        'text-xl text-black block w-full rounded-md border border-default px-5 py-2.5 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none',
        this.loading && 'bg-disabled',
      )
    },

    buttonClassName() {
      return classNames(
        'hover:no-underline',
        this.loading ? 'text-disabled' : 'text-interactive underline',
      )
    },

    newCodeButtonClassName() {
      return classNames(
        'hover:no-underline',
        this.phoneLoading || this.loading
          ? 'text-disabled'
          : 'text-interactive underline',
      )
    },
  },

  watch: {
    code(val) {
      if (val.length === 4) {
        this.submit(val)
      }
    },
  },

  methods: {
    submit() {
      this.$emit('submit', this.code)
    },
  },
}
</script>
