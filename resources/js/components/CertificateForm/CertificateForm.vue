<template>
  <div class="space-y-6 px-6 lg:px-0 pb-10">
    <h3 class="text-2xl lg:text-4xl text-heading font-semibold mb-2">
      <span v-if="showSuccessMessage" class="flex justify-center"
        >Ваша заявка получена</span
      >
      <span v-else
        >Заявка на предоставление справки об оплате медицинских услуг для
        возврата НДФЛ</span
      >
    </h3>
    <div v-if="showSuccessMessage" class="text-center">
      Заявка в обработке нашими специалистами
    </div>
    
    <form @submit.prevent="submit" class="space-y-6" v-if="!showSuccessMessage">
      <div class="space-y-4">
        <h4 class="text-xl lg:text-2xl text-heading font-semibold mb-2">
          Клиент
        </h4>
        <TextField
          label="ФИО клиента"
          name="client_fio"
          autocomplete="full-name"
          v-model="form.client_fio"
          :error="form.getError('client_fio')"
        />

        <div class="flex flex-col lg:grid gap-4 grid-cols-2">
          <TextField
            label="Телефон клиента"
            name="client_phone"
            placeholder="+7 (912) 345-67-89"
            v-model="form.client_phone"
            mask="+7 (###) ###-##-##"
            :error="form.getError('client_phone')"
          />
          <TextField
            label="Дата рождения"
            name="client_dob"
            placeholder="дд.мм.гггг"
            v-model="form.client_dob"
            mask="##.##.####"
            :error="form.getError('client_dob')"
          />
        </div>

        <div class="flex flex-col lg:grid gap-4 grid-cols-2 grid-rows-2">
          <TextField
            label="Серия и номер паспорта"
            name="client_passnumber"
            placeholder="#### ######"
            v-model="form.client_passnumber"
            mask="#### ######"
            :error="form.getError('client_passnumber')"
          />

          <TextField
            label="Кем выдан паспорт"
            name="client_passwhom"
            v-model="form.client_passwhom"
            :error="form.getError('client_passwhom')"
          />

          <TextField
            label="Код подразделения"
            name="client_passkod"
            v-model="form.client_passkod"
            mask="###-###"
            placeholder="###-###"
            :error="form.getError('client_passkod')"
          />

          <TextField
            label="Когда выдан паспорт"
            name="client_passdate"
            placeholder="дд.мм.гггг"
            v-model="form.client_passdate"
            mask="##.##.####"
            :error="form.getError('client_passdate')"
          />
        </div>

        <TextField
          label="ИНН клиента"
          name="client_inn"
          placeholder="############"
          v-model="form.client_inn"
          mask="############"
          :maxLength="12"
          :error="form.getError('client_inn')"
        />
      </div>
      <div class="space-y-4 border-t border-t-gray-300 pt-4">
        <h4 class="text-xl lg:text-2xl text-heading font-semibold mb-2">
          Пациент
        </h4>
        <TextField
          label="ФИО пациента"
          name="patient_fio"
          autocomplete="full-name"
          v-model="form.patient_fio"
          :error="form.getError('patient_fio')"
        />
        <div class="flex flex-col lg:grid gap-4 grid-cols-2">
          <TextField
            label="СНИЛС пациента"
            name="patient_snils"
            placeholder="###-###-### ##"
            v-model="form.patient_snils"
            mask="###-###-### ##"
            :error="form.getError('patient_snils')"
          />

          <TextField
            label="ИНН клиента"
            name="patient_inn"
            placeholder="############"
            v-model="form.patient_inn"
            mask="############"
            :maxLength="12"
            :error="form.getError('patient_inn')"
          />
        </div>
        <div class="flex flex-col lg:grid gap-4 grid-cols-2">
          <TextField
            label="Дата рождения"
            name="patient_dob"
            placeholder="дд.мм.гггг"
            v-model="form.patient_dob"
            mask="##.##.####"
            :error="form.getError('patient_dob')"
          />

          <div>
            <label for="patient_kin" class="block text-base mb-1"
              >Степень родства</label
            >
            <select
              id="patient_kin"
              name="location"
              :class="['text-xl text-black block w-full rounded-md border border-default px-4 pt-3.5 pb-2.5 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none pr-10', form.hasError('patient_kin') ? 'border-red-500' : '']"
              v-model="form.patient_kin"
            >
              <option v-for="item in kinTypes" :value="item">
                {{ item }}
              </option>
            </select>
            <p
              v-if="form.hasError('patient_kin')"
              class="mt-3 text-sm leading-6 text-critical"
              v-text="form.getError('patient_kin')"
            />
          </div>
        </div>
        <div>
          <label for="patient_type" class="block text-base mb-1"
            >Документ</label
          >
          <select
            id="patient_type"
            name="location"
            class="text-xl text-black block w-full rounded-md border border-default px-4 pt-3.5 pb-2.5 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none pr-10"
            v-model="form.patient_type"
          >
            <option v-for="item in documentTypes" :value="item.value">
              {{ item.label }}
            </option>
          </select>
          <p
            v-if="form.hasError('patient_type')"
            class="mt-3 text-sm leading-6 text-critical"
            v-text="form.getError('patient_type')"
          />
        </div>

        <div class="flex flex-col lg:grid grid-cols-2 gap-4">
          <TextField
            label="Серия и номер документа"
            name="patient_number"
            :placeholder="form.patient_type === 0 ? '#### ######' : undefined"
            v-model="form.patient_number"
            :mask="form.patient_type === 0 ? '#### ######' : undefined"
            :error="form.getError('patient_number')"
          />

          <TextField
            label="Дата выдачи документа"
            name="patient_date"
            placeholder="дд.мм.гггг"
            v-model="form.patient_date"
            mask="##.##.####"
            :error="form.getError('patient_date')"
          />
        </div>
      </div>

      <div
        class="flex flex-col lg:grid grid-cols-2 gap-4 border-t border-t-gray-300 pt-4"
      >
        <TextField
          label="Email"
          name="email"
          autocomplete="email"
          v-model="form.email"
          :error="form.getError('email')"
        />

        <div>
          <label for="year" class="block text-base mb-1">Отчётный год</label>
          <select
            id="year"
            name="location"
            class="text-xl text-black block w-full rounded-md border border-default px-4 pt-3.5 pb-2.5 ring-offset-1 placeholder:text-gray-400 focus:ring-border-interactive-focus focus:ring-2 outline-none pr-10"
            v-model="form.year"
          >
            <option v-for="item in years" :value="item">{{ item }}</option>
          </select>
        </div>
        <p
          v-if="form.hasError('year')"
          class="mt-3 text-sm leading-6 text-critical"
          v-text="form.getError('year')"
        />
      </div>

      <div class="relative flex items-start">
        <div class="flex items-center h-5">
          <input
            id="privacy"
            name="privacy"
            type="checkbox"
            v-model="form.privacy"
            class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
          />
        </div>
        <div class="ml-3 text-sm">
          <label for="privacy" class="font-medium text-gray-700"
            >Согласен на обработку
            <a
              href="/documents"
              target="_blank"
              class="underline hover:no-underline"
              >персональных данных</a
            >
          </label>
          <p
            v-if="form.hasError('privacy')"
            v-text="form.getError('privacy')"
            class="text-sm leading-6 text-critical"
          />
        </div>
      </div>

      <div class="relative flex items-start">
        <div class="flex items-center h-5">
          <input
            id="confirm"
            name="confirm"
            type="checkbox"
            v-model="form.confirm"
            class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
          />
        </div>
        <div class="ml-3 text-sm">
          <label for="confirm" class="font-medium text-gray-700"
            >Полноту сведений подтверждаю
          </label>
          <p
            v-if="form.hasError('confirm')"
            v-text="form.getError('confirm')"
            class="text-sm leading-6 text-critical"
          />
        </div>
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
          <span v-else>Отправить</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { TextField } from "./components";
import Form from "../../utilities/form/index.js";
import { classNames } from "../../utilities/css.js";

export default {
  components: { TextField },
  data() {
    return {
      documentTypes: [
        { value: 0, label: "Паспорт" },
        {
          value: 1,
          label: "Свидетельство о рождении",
        },
      ],
      kinTypes: ["Нет родства", "сам", "супруг", "ребенок", "отец/мать"],
      showSuccessMessage: false,
      form: new Form({
        client_fio: "",
        client_inn: "",
        client_phone: "",
        client_passnumber: "",
        client_passdate: "",
        client_passwhom: "",
        client_passkod: "",
        client_dob: "",
        patient_fio: "",
        patient_snils: "",
        patient_type: 0,
        patient_number: "",
        patient_date: "",
        patient_dob: "",
        patient_inn: "",
        patient_kin: "",
        email: "",
        year: "",
        privacy: false,
        confirm: false,
      }),
    };
  },

  computed: {
    buttonClassName() {
      return classNames(
        "flex items-center justify-center w-full text-center p-3 lg:px-6 font-semibold rounded",
        this.form.processing
          ? "bg-disabled text-disabled"
          : "text-white bg-interactive hover:bg-interactive-button-hovered active:bg-interactive-button-hovered shadow-md"
      );
    },

    years() {
      const currentYear = new Date().getFullYear();
      return [
        currentYear,
        currentYear - 1,
        currentYear - 2,
        currentYear - 3,
      ].map((item) => item.toString());
    },
  },

  watch: {
    years: {
      handler(value) {
        if (value.length) {
          this.form.year = value[0].toString();
        }
      },
      immediate: true,
    },
  },

  methods: {
    submit() {
      this.form.post(`/`).then(() => {
        this.showSuccessMessage = true;
      });
    },
  },
};
</script>
