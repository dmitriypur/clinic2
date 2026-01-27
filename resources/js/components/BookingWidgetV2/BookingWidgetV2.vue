<template>
  <Modal
    :open="open"
    :zIndexOverride="49"
    closeButtonHiddenOnMobile
    @close="handleClose"
  >
    <div class="min-h-[400px]">
      <!-- Step 1: Doctor Selection -->
      <DoctorSelectionStep
        v-if="currentStep === 'doctor'"
        :doctors="doctors"
        :loading="loadingDoctors"
        :error="doctorsError"
        :selectedDoctorId="selectedDoctor?.id"
        @select="handleDoctorSelect"
        @next="handleDoctorNext"
        @retry="fetchDoctors"
      />

      <!-- Step 2: Date Selection -->
      <DateSelectionStep
        v-else-if="currentStep === 'date'"
        :selectedDoctor="selectedDoctor"
        :selectedDate="selectedDate"
        :availableDates="availableDates"
        @select="handleDateSelect"
        @next="handleDateNext"
        @back="currentStep = 'doctor'"
      />

      <!-- Step 3: Time Selection -->
      <TimeSelectionStep
        v-else-if="currentStep === 'time'"
        :selectedDoctor="selectedDoctor"
        :selectedDate="selectedDate"
        :slots="slots"
        :loading="loadingSlots"
        :error="slotsError"
        :selectedSlot="selectedSlot"
        @select="handleSlotSelect"
        @next="handleTimeNext"
        @back="currentStep = 'date'"
        @retry="fetchSlots"
      />

      <!-- Step 4: Patient Form -->
      <PatientFormStep
        v-else-if="currentStep === 'form'"
        :selectedDoctor="selectedDoctor"
        :selectedDate="selectedDate"
        :selectedSlot="selectedSlot"
        :isSubmitting="isSubmitting"
        ref="patientForm"
        @submit="handleFormSubmit"
        @back="currentStep = 'time'"
      />

      <!-- Step 5: Confirmation -->
      <ConfirmationStep
        v-else-if="currentStep === 'confirmation'"
        :doctorName="selectedDoctor?.full_name"
        :appointmentDateTime="formattedAppointmentDateTime"
        :clinicInfo="clinicInfo"
        :patientName="patientData?.full_name"
        @close="handleClose"
      />
    </div>
  </Modal>
</template>

<script>
import bookingApi from "../../services/bookingApi";

const Modal = () => import("../Modal");
const DoctorSelectionStep = () =>
  import("./components/DoctorSelectionStep.vue");
const DateSelectionStep = () => import("./components/DateSelectionStep.vue");
const TimeSelectionStep = () => import("./components/TimeSelectionStep.vue");
const PatientFormStep = () => import("./components/PatientFormStep.vue");
const ConfirmationStep = () => import("./components/ConfirmationStep.vue");

export default {
  name: "BookingWidgetV2",

  components: {
    Modal,
    DoctorSelectionStep,
    DateSelectionStep,
    TimeSelectionStep,
    PatientFormStep,
    ConfirmationStep,
  },

  props: {
    open: {
      type: Boolean,
      default: false,
    },
    cityId: {
      type: [String, Number],
      default: null,
    },
    // Можно передать target для метрики
    target: {
      type: String,
      default: null,
    },
  },

  data() {
    return {
      currentStep: "doctor", // doctor, date, time, form, confirmation

      // Cities from API for mapping
      allCities: [],
      // Doctors
      doctors: [],
      loadingDoctors: false,
      doctorsError: null,
      selectedDoctor: null,

      // Date
      selectedDate: new Date(),
      availableDates: [],

      // Slots
      slots: [],
      loadingSlots: false,
      slotsError: null,
      selectedSlot: null,

      // Form
      isSubmitting: false,
      patientData: null,
    };
  },

  computed: {
    formattedAppointmentDateTime() {
      if (!this.selectedDate || !this.selectedSlot) return "";

      const dateOptions = { day: "numeric", month: "long", year: "numeric" };
      const dateStr = this.selectedDate.toLocaleDateString(
        "ru-RU",
        dateOptions
      );

      return `${dateStr} в ${this.selectedSlot.time}`;
    },

    clinicInfo() {
      if (!this.selectedSlot) return null;

      let info = this.selectedSlot.clinic_name || "";
      if (this.selectedSlot.branch_name) {
        info += ` — ${this.selectedSlot.branch_name}`;
      }

      return info || null;
    },

    currentCityId() {
      // Получаем локальное название города из системных переменных
      const localCityName =
        window.currentCity?.name ||
        window.config?.state?.currentCity?.name ||
        "Москва";

      console.log("BookingWidgetV2: Local city name is", localCityName);

      // ПРИОРИТЕТ 1: Ищем соответствие ID во внешнем API по названию города
      if (this.allCities && this.allCities.length > 0) {
        const matchedCity = this.allCities.find(
          (c) => c.name.toLowerCase() === localCityName.toLowerCase()
        );
        if (matchedCity) {
          console.log(
            "BookingWidgetV2: Found API ID by name mapping:",
            matchedCity.id
          );
          return matchedCity.id;
        }
      }

      // ПРИОРИТЕТ 2: Хардкод маппинг для известных городов
      const fallbackMapping = {
        Москва: 2,
        Киров: 1,
        Краснодар: 7,
      };

      if (fallbackMapping[localCityName]) {
        console.log(
          "BookingWidgetV2: Using fallback mapping for",
          localCityName,
          "->",
          fallbackMapping[localCityName]
        );
        return fallbackMapping[localCityName];
      }

      // ПРИОРИТЕТ 3: Явно переданный ID из пропсов (только если это не 1 для Москвы)
      if (this.cityId && !(localCityName === "Москва" && this.cityId === 1)) {
        return this.cityId;
      }

      console.log("BookingWidgetV2: Defaulting to Moscow API ID (2)");
      return 2; // Default to Moscow API ID
    },
  },

  watch: {
    open: {
      async handler(val) {
        if (val) {
          await this.initCities();
          this.fetchDoctors();
        } else {
          this.resetWidget();
        }
      },
      immediate: true,
    },

    selectedDoctor(newDoctor) {
      if (newDoctor) {
        // Можно сразу загрузить доступные даты врача
        // this.fetchAvailableDates();
      }
    },
  },

  methods: {
    // ===== INIT CITIES =====
    async initCities() {
      if (this.allCities.length > 0) return;
      try {
        const response = await bookingApi.getCities();
        this.allCities = response.data || response || [];
      } catch (error) {
        console.error("Error fetching cities for mapping:", error);
      }
    },

    // ===== DOCTOR STEP =====
    async fetchDoctors() {
      this.loadingDoctors = true;
      this.doctorsError = null;

      const apiCityId = this.currentCityId;
      console.log(
        "BookingWidgetV2: Requesting doctors for API City ID:",
        apiCityId
      );

      try {
        const response = await bookingApi.getDoctorsByCity(apiCityId);
        this.doctors = response.data || response || [];

        if (this.doctors.length === 0) {
          this.doctorsError = "В данный момент нет доступных врачей";
        }
      } catch (error) {
        console.error("Error fetching doctors:", error);
        this.doctorsError =
          error.message || "Не удалось загрузить список врачей";
      } finally {
        this.loadingDoctors = false;
      }
    },

    handleDoctorSelect(doctor) {
      this.selectedDoctor = doctor;
    },

    handleDoctorNext() {
      if (this.selectedDoctor) {
        this.currentStep = "date";
      }
    },

    // ===== DATE STEP =====
    handleDateSelect(date) {
      this.selectedDate = date;
      // Автоматически загружаем слоты при выборе даты
      this.fetchSlots();
    },

    handleDateNext() {
      if (this.selectedDate) {
        this.currentStep = "time";
        if (this.slots.length === 0) {
          this.fetchSlots();
        }
      }
    },

    // ===== TIME STEP =====
    async fetchSlots() {
      if (!this.selectedDoctor || !this.selectedDate) return;

      this.loadingSlots = true;
      this.slotsError = null;

      try {
        const dateStr = this.formatDateForApi(this.selectedDate);
        const response = await bookingApi.getDoctorSlots(
          this.selectedDoctor.id,
          dateStr
        );

        this.slots = response.data || response || [];

        if (this.slots.length === 0) {
          this.slotsError = "На выбранную дату нет доступных слотов";
        }
      } catch (error) {
        console.error("Error fetching slots:", error);
        this.slotsError = error.message || "Не удалось загрузить расписание";
      } finally {
        this.loadingSlots = false;
      }
    },

    handleSlotSelect(slot) {
      this.selectedSlot = slot;
    },

    handleTimeNext() {
      if (this.selectedSlot) {
        this.currentStep = "form";
      }
    },

    // ===== FORM STEP =====
    async handleFormSubmit(formData) {
      this.isSubmitting = true;
      this.patientData = formData;

      try {
        // Формируем данные для отправки согласно API
        const applicationData = {
          city_id: this.currentCityId,
          clinic_id: this.selectedSlot.clinic_id,
          branch_id: this.selectedSlot.branch_id,
          doctor_id: this.selectedDoctor.id,
          cabinet_id: this.selectedSlot.cabinet_id,
          appointment_datetime: this.selectedSlot.datetime,
          onec_slot_id: this.selectedSlot.onec_slot_id,
          full_name: formData.full_name,
          full_name_parent: formData.full_name_parent || null,
          birth_date: formData.birth_date,
          phone: this.cleanPhone(formData.phone),
          promo_code: formData.promo_code || null,
          comment: formData.comment || null,
          appointment_source: "site",
        };

        // Опционально: проверка слота перед созданием заявки
        // if (this.selectedSlot.onec_slot_id) {
        //   await this.checkSlotAvailability(applicationData);
        // }

        // Создаем заявку
        await bookingApi.createApplication(applicationData);

        // Метрика (если нужно)
        if (this.target && window.ym) {
          ym(94302729, "reachGoal", this.target);
        }

        // Переходим к подтверждению
        this.currentStep = "confirmation";
      } catch (error) {
        console.error("Error creating application:", error);

        // Обработка ошибок валидации (422)
        if (error.status === 422 && error.errors) {
          this.$refs.patientForm?.setErrors(error.errors);
        } else {
          this.$refs.patientForm?.setGeneralError(
            error.message ||
              "Произошла ошибка при создании заявки. Попробуйте еще раз."
          );
        }
      } finally {
        this.isSubmitting = false;
      }
    },

    async checkSlotAvailability(data) {
      const checkData = {
        clinic_id: data.clinic_id,
        branch_id: data.branch_id,
        doctor_id: data.doctor_id,
        onec_slot_id: data.onec_slot_id,
      };

      const result = await bookingApi.checkSlot(checkData);

      if (!result.available) {
        throw new Error(
          "К сожалению, выбранный слот уже занят. Пожалуйста, выберите другое время."
        );
      }
    },

    // ===== UTILS =====
    formatDateForApi(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },

    cleanPhone(phone) {
      // Убираем все символы кроме цифр
      return phone.replace(/\D/g, "");
    },

    handleClose() {
      this.$emit("close");
      // Задержка перед сбросом для плавной анимации закрытия
      setTimeout(() => {
        this.resetWidget();
      }, 300);
    },

    resetWidget() {
      this.currentStep = "doctor";
      this.selectedDoctor = null;
      this.selectedDate = new Date();
      this.selectedSlot = null;
      this.slots = [];
      this.patientData = null;
      this.doctorsError = null;
      this.slotsError = null;
    },
  },
};
</script>
