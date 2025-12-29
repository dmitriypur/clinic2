<template>
  <div class="p-6">
    <div
      v-if="!showSuccessMessage"
      class="flex items-center justify-between mb-6"
    >
      <button
        class="bg-white border border-[#FF8C00] text-[#FF8C00] hover:bg-[#FFF8F0] h-[32px] text-sm transition-colors duration-300 rounded-lg font-medium w-auto px-3"
        @click="$emit('back')"
      >
        Назад
      </button>
      <h2 class="text-lg font-medium text-gray-900">
        Введите данные о пациенте
      </h2>
    </div>

    <div class="flex flex-col gap-2">
      <form
        class="space-y-6"
        @submit.prevent="submit"
        v-if="!showSuccessMessage"
      >
        <TextField
          label="Фамилия"
          autofocus
          v-model="form.lastName"
          required
          :error="form.getError('lastName')"
        />

        <TextField
          label="Имя"
          autocomplete="name"
          v-model="form.firstName"
          required
          :error="form.getError('firstName')"
        />

        <TextField
          label="Отчество"
          v-model="form.middleName"
          required
          :error="form.getError('middleName')"
        />

        <div class="flex flex-col md:grid grid-cols-2 gap-6">
          <TextField
            label="Дата рождения"
            v-model="form.birthdate"
            placeholder="дд.мм.гггг"
            type="date"
            required
            :error="form.getError('birthdate')"
          />

          <TextField
            label="Номер телефона"
            type="phone"
            id="phone-input"
            autofocus
            name="phone"
            placeholder="(999) 999-99-99"
            required
            v-model="form.phone"
            :error="form.getError('phone')"
          />
        </div>

        <div>
          <div class="relative flex gap-x-3 max-w-4xl">
            <div class="flex h-6 items-center">
              <input
                id="cb-privacy"
                name="privacy"
                type="checkbox"
                v-model="form.privacy"
                class="h-4 w-4 rounded bg-transparent appearance-none border border-default text-interactive checked:[background-color:currentColor] checked:bg-checkbox-checked [background-size:100%] bg-center bg-no-repeat focus:outline focus:outline-2 focus:outline-offset-1"
              />
            </div>
            <div class="text-sm leading-6">
              <label for="cb-privacy" class="font-medium select-none">
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
            class="mt-3 text-sm leading-6 text-critical"
            v-text="form.getError('privacy')"
          />
        </div>
        <p v-if="showErrorMessages" class="text-sm font-semibold text-red-500 text-center">
          Выбранный вами доктор принимает детей от {{ receives }} лет и взрослых
        </p>
        <div v-if="showErrorMessages" class="mb-8">
          <button
            @click="() => $emit('change', 1)"
            class="flex items-center justify-center w-full text-center p-3 lg:px-6 font-semibold rounded text-white bg-interactive hover:bg-interactive-button-hovered active:bg-interactive-button-hovered shadow-md"
          >
            Перезвоните мне
          </button>
        </div>
        <div>
          <button
            type="submit"
            :class="buttonClassName"
            :disabled="form.processing || showErrorMessages"
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
            <span v-else>Записаться на приём</span>
          </button>
        </div>
      </form>
      <div v-else class="mt-4 text-center">
        <h3
          class="text-2xl lg:text-4xl text-heading font-semibold mb-2 text-center"
        >
          <span>Спасибо!</span>
        </h3>
        <p class="text-heading font-medium text-lg md:text-xl leading-none">
          Ваша заявка принята.
          <br />
          Вы записаны на прием к врачу <b>{{ doctorName }}</b> на <b>{{ finalDate }}</b>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { classNames } from "../../../../utilities/css";
import { Form } from "../../../../utilities/form";

const TextField = () => import('../../../TextField')

export default {
  components: { TextField },

  props: {
    doctorId: String,
    doctorName: String,
    doctorReceives: String,
    date: Date,
    time: String,
    target: {
      type: String,
      default: null,
    },
  },

  data() {
    return {
      showSuccessMessage: false,
      showErrorMessages: false,
      finalDate: null,
      form: new Form({
        doctorId: this.doctorId,
        time: this.time,
        date: this.date,
        firstName: this.name,
        lastName: "",
        middleName: "",
        phone: this.phone,
        birthdate: "",
        privacy: false,
      }),
    };
  },

  computed: {
    buttonClassName() {
      return classNames(
        "flex items-center justify-center w-full text-center p-3 lg:px-6 font-semibold rounded",
        this.form.processing || this.showErrorMessages
          ? "bg-disabled text-disabled"
          : "text-white bg-interactive hover:bg-interactive-button-hovered active:bg-interactive-button-hovered shadow-md"
      );
    },
    receives(){
      let numberPattern = /\d+/g
      return Number(this.doctorReceives.match( numberPattern )[0])
    }
  },

  methods: {
    getAge(dateString) {
      let birth = new Date(dateString);
      let now = new Date();
      let beforeBirth = ((() => {
        birth.setDate(now.getDate());
        birth.setMonth(now.getMonth());
        return birth.getTime()
      })() < birth.getTime()) ? 0 : 1;
      return (now.getFullYear() - birth.getFullYear() - beforeBirth) < 0 ? 0 : now.getFullYear() - birth.getFullYear() - beforeBirth;
    },
    submit() {
      if(this.getAge(this.form.birthdate) < this.receives){
        this.showErrorMessages = true
        return false
      }

      this.form.post(`/api/making-an-appointment${window.location.search}`).then((res) => {
        this.finalDate = res
        if (this.target) {
          ym(94302729, "reachGoal", this.target);
        }

        this.showSuccessMessage = true;
      });
    },
  },
};
</script>
