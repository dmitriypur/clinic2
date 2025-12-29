<template>
  <transition
    enter-active-class="transform ease-out duration-300 transition"
    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="show"
      aria-live="assertive"
      class="z-50 js-cookie-consent cookie-consent max-w-lg w-full fixed bottom-0 right-0 pb-6 pointer-events-none"
    >
      <div
        class="w-full flex flex-col items-center space-y-4 sm:items-end pr-6"
      >
        <div
          class="w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
        >
          <div class="p-4">
            <div class="flex items-start">
              <div class="ml-3 w-0 flex-1 pt-0.5">
                <!--                <p class="text-sm font-medium text-heading">-->
                <!--                  -->
                <!--                </p>-->
                <p class="mt-1 text-sm">
                  <slot/>
                </p>
              </div>
              <div class="ml-4 flex-shrink-0 flex">
                <button
                  class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 js-cookie-consent-agree cookie-consent__agree"
                  @click="consentWithCookies"
                >
                  <span class="sr-only">Закрыть</span>
                  <span class="w-4 h-3">
                    <svg
                      viewBox="0 0 32 27"
                      class="stroke-[3] fill-none stroke-current"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path d="M6 1.08621L30.8276 25.9138" stroke-width="3"/>
                      <path d="M30.8276 1.08621L6 25.9138" stroke-width="3"/>
                    </svg>
                  </span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
const COOKIE_VALUE = 1

export default {
  props: {
    cookieDomain: String,
    cookieName: String,
    cookieLifetime: String,
    secure: Boolean,
    samesite: String,
  },

  data() {
    return {
      show: false,
    }
  },

  mounted() {
    this.show =
      document.cookie
        .split('; ')
        .indexOf(this.cookieName + '=' + COOKIE_VALUE) === -1
  },

  watch: {
    show(val) {
      if (val) {
        this.setCookie()
      }
    },
  },

  methods: {
    hideCookieDialog() {
      this.show = false
    },

    consentWithCookies() {
      this.setCookie()
      this.hideCookieDialog()
    },

    setCookie() {
      const date = new Date()
      date.setTime(date.getTime() + this.cookieLifetime * 24 * 60 * 60 * 1000)
      document.cookie = `${
        this.cookieName
      }=${COOKIE_VALUE};expires=${date.toUTCString()};domain=${
        this.cookieDomain
      };path=/${this.secure ? ';secure' : null}${
        this.samesite ? `;samesite=${this.samesite}` : null
      }`
    },
  },
}
</script>
