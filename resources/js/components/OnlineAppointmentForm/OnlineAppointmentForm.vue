<template>
  <Modal
    :open="open"
    :zIndexOverride="49"
    closeButtonHiddenOnMobile
    @close="handleClose"
  >
    <div v-if="!mobileSize">
      <div v-if="loading" class="flex justify-center items-center min-h-80">
        <svg
          class="mr-3 -ml-1 size-8 animate-spin text-action-primary"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
      </div>
      <div v-else class="-m-4 md:-m-8 flex flex-col">

        <Tabs
          v-if="doctors.length"
          :options="['Выбрать время', 'Оставить заявку']"
          :activeIndex="activeTab"
          @change="setActiveTab"
          @close="handleClose"
        />

        <div v-if="activeTab === 0 && doctors.length">
          <TimeStep
            v-if="step === 'time'"
            :doctors="doctors"
            :selectedDoctor="selectedDoctor"
            :selectedDate="selectedDate"
            :selectedTime="selectedTime"
            @selectDoctor="setSelectedDoctor"
            @selectDate="setSelectedDate"
            @selectTime="setSelectedTime"
            @next="handleSelectPatientStep"
          />
          <PatientForm
            :doctorId="selectedDoctor.id"
            :doctorReceives="selectedDoctor.receives"
            :doctorName="selectedDoctor.name"
            :time="selectedTime"
            :date="selectedDate"
            @back="handleSelectTimeStep"
            @change="setActiveTab"
            v-else
          />
        </div>
        <div v-else class="p-6 md:p-8">
          <CallbackForm
            :phone="phone"
            :name="name"
            button-content="Перезвонить"
            :target="target"
            titleHidden
          />
        </div>
      </div>
    </div>
    <div v-else>
      <div v-if="loading" class="flex justify-center items-center min-h-80">
        <svg
          class="mr-3 -ml-1 size-8 animate-spin text-action-primary"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
      </div>
      <div v-else class="-m-4 md:-m-8 flex flex-col">
        <Tabs
          v-if="doctors.length"
          :options="['Выбрать время', 'Оставить заявку']"
          :activeIndex="activeTab"
          @change="setActiveTab"
          @close="handleClose"
        />
        <MobileToggler
          :activeToggler="activeToggler"
          @change="setActiveToggler"
        />

        <div v-if="activeTab === 0 && doctors.length">
          <TimeStepMobile
            v-if="step === 'time'"
            :doctors="doctors"
            :selectedDoctor="selectedDoctor"
            :selectedDate="selectedDate"
            :selectedTime="selectedTime"
            :activeToggler="activeToggler"
            @selectDoctor="setSelectedDoctor"
            @selectDate="setSelectedDate"
            @selectTime="setSelectedTime"
            @next="handleSelectPatientStep"
          />
          <PatientForm
            :doctorId="selectedDoctor.id"
            :doctorReceives="selectedDoctor.receives"
            :doctorName="selectedDoctor.name"
            :time="selectedTime"
            :date="selectedDate"
            :target="target"
            @change="setActiveTab"
            @back="handleSelectTimeStep"
            v-else
          />

        </div>
        <div v-else class="p-6 md:p-8">
          <CallbackForm
            :phone="phone"
            :name="name"
            button-content="Перезвонить"
            :target="target"
            titleHidden
          />
        </div>
      </div>
    </div>
  </Modal>
</template>

<script>
import axios from "axios";
const Modal = () => import('../Modal')
const PatientForm = () => import('./components/PatientForm/PatientForm.vue')
const Tabs = () => import('./components/Tabs/Tabs.vue')
const TimeStep = () => import('./components/TimeStep/TimeStep.vue')
const TimeStepMobile = () => import('./components/TimeStepMobile/TimeStepMobile.vue')
const MobileToggler = () => import('./components/MobileToggler/MobileToggler.vue')
const CallbackForm = () => import('../CallbackForm')

export default {
  components: {CallbackForm, Modal, PatientForm, Tabs, TimeStepMobile, MobileToggler,  TimeStep},
  props: {
    open: {
      type: Boolean,
      default: false,
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
  },

  data() {
    return {
      mobileSize: false,
      doctors: [],
      activeTab: 0,
      activeToggler: null,
      step: "time",
      selectedDate: new Date(),
      selectedTime: null,
      selectedDoctor: null,
      loading: false,
    };
  },

  computed: {
    getToday(){
      const date = new Date()
      return `${date.getFullYear()}-${date.getMonth() + 1 < 10 ? ('0' + (date.getMonth() + 1)) : date.getMonth() + 1}-${date.getDate() < 10 ? '0' + date.getDate() : date.getDate()}`
    },
  },

  watch: {
    open: {
      handler: function (val) {
        if (val) {
          this.fetchDoctors();
        }
      },
      immediate: true,
    },

    doctors(val) {
      if (!val.length) {
        this.selectedDoctor = null;
        return;
      }

      const doctorWithToday = val.find((doctor) =>
        doctor?.cells?.some(({ dt }) => dt === this.getToday)
      );

      this.selectedDoctor = doctorWithToday ?? val[0];
    },
  },

  updated() {
    this.isMobile()
  },

  methods: {
    handleClose() {
      this.showSuccessMessage = false;
      this.$emit("close");
      this.step = "time";
      this.setActiveTab(0);
    },

    fetchDoctors() {
      this.loading = true;

      axios
        .get("/api/schedule")
        .then((response) => {
          this.doctors = response.data.doctors;
        })
        .finally(() => {
          this.loading = false;
        });
    },

    setActiveTab(index) {
      this.activeTab = index;
    },

    setActiveToggler(index){
      this.selectedDate = new Date()
      this.activeToggler = index;
    },

    setSelectedDoctor(value) {
      this.selectedDoctor = value;
    },

    setSelectedDate(value) {
      this.selectedDate = value;
    },

    setSelectedTime(value) {
      this.selectedTime = value;
    },

    handleSelectPatientStep() {
      this.step = "patient";
    },

    handleSelectTimeStep() {
      this.step = "time";
    },
    isMobile() {
      if (window.innerWidth < 768) {
        this.mobileSize = true
      } else {
        this.mobileSize = false
      }
    },
  },
};
</script>
