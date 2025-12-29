<template>
  <div class="flex-1 py-8 md:px-5">
    <div class="flex flex-col-reverse md:grid grid-cols-2 gap-4">
      <div class="[&_.vc-container]:border-none">
        <v-date-picker
          trim-weeks
          is-expanded
          :min-date="new Date"
          @dayclick="handleSelectDate"
          :value="selectedDate"
          color="orange"
          key="today"
          :theme="{ highlight: { color: 'orange' } }"
        />

<!--       :available-dates='availableDatesAttr'-->
        <!-- <Calendar :selectedDate="selectedDate" @change="(date) => setSelectedDate(date)" /> -->
      </div>

      <div class="flex flex-col gap-6 h-full px-4 md:px-0">

        <DoctorSelector
          :doctors="doctors"
          :selectedDoctor="selectedDoctor"
          :day="dateString"
          @select="handleSelectDoctor"
        />
        <DoctorCard :doctor="selectedDoctor" :selectedDate="selectedDate"/>
      </div>
    </div>
    <div class="mt-7 px-4 md:px-0">
      <TimeSlots
        :weekday="weekday"
        :timeSlots="time"
        :selectedTime="selectedTime"
        @select="handleSelectTime"
      />
    </div>
    <div v-if="selectedDate && selectedTime" class="mt-4 px-4 md:px-0">
      <button
        class="btn-gradient text-lg text-white w-full h-12 rounded-xl"
        @click="$emit('next')"
      >
        Выбрать время
      </button>
    </div>
  </div>
</template>

<script>
import {DoctorCard, DoctorSelector, TimeSlots} from "./components";

export default {
  components: {DoctorCard, DoctorSelector, TimeSlots},

  props: {
    doctors: Array,
    selectedDoctor: Object,
    selectedDate: Date,
    selectedTime: String,
    activeToggler: Number
  },

  data() {
    return {
      weekday: null,
      dateString: '',
      doctorsToday: [],
    }
  },

  computed: {
    availableDates() {
      if (!this.selectedDoctor || !this.selectedDoctor.cells) {
        return [];
      }

      return [...new Set(this.selectedDoctor.cells.map(({ dt }) => dt))];
    },

    availableDatesAttr(){
      let dates = []
      this.availableDates.map(item => {
        dates.push(
          {
            start: new Date(item),
            end: new Date(item)
          }
        )
      })
      return dates
    },

    availableFormattedDates() {
      return this.availableDates.map((item) => ({
        start: new Date(item),
        end: new Date(item),
      }));
    },

    selectedDateString() {
      if (!this.selectedDate) {
        return '';
      }

      const month = ("0" + (this.selectedDate.getMonth() + 1)).slice(-2);
      const day = ("0" + this.selectedDate.getDate()).slice(-2);

      return `${this.selectedDate.getFullYear()}-${month}-${day}`;
    },

    time() {
      if (!this.selectedDoctor || !this.selectedDoctor.cells) {
        return [];
      }

      return this.selectedDoctor.cells
        .filter(({ dt }) => dt === this.selectedDateString)
        .map((item) => ({
          time: item.time_start,
          available: item.free,
        }));
    },
  },

  methods: {
    handleSelectDoctor(value) {
      this.$emit("selectDoctor", value);
    },

    handleSelectTime(value) {
      this.$emit("selectTime", value);
    },

    handleSelectDate(value) {
      if(value.isDisabled){
        return false
      }
      // if (this.availableDates.filter((item) => item === value.id).length) {
      this.weekday = value.weekday
      this.dateString = value.id
      this.$emit("selectDate", new Date(value.id));
      // }
    },
  },
};
</script>

<style>
.vc-pane-container {
  --text-sm: 16px;
  box-shadow: 0px 20px 40px 0px #00000012;
  border-radius: 16px;

  .vc-header {
    padding: 16px !important;
    background: radial-gradient(71.86% 71.86% at 50.75% 49.25%, #FFA759 0%, #FF8212 100%);
    border-radius: 10px;

    .vc-title {
      font-family: 'Gilroy', sans-serif;
      font-weight: 500;
      font-size: 20px;
      line-height: 100%;
      color: #fff;

    }
  }

  .vc-weeks {
    --rounded-full: 5px;
    gap: 7px;
    padding: 32px 10px !important;

    & > div {
      font-family: 'Gilroy', sans-serif;
      min-height: 24px;
      height: 24px;
      border-radius: 5px;
      display: flex;
      justify-content: center;
      align-items: center;
      color: #0032A0;
      background: #EBEBEB4D;

      span {
        width: 100%;
        height: 100%;
      }
    }

    .vc-weekday {
      padding: 0;
      background: radial-gradient(71.86% 71.86% at 50.75% 49.25%, #FFA759 0%, #FF8212 100%);
      color: #fff;
      margin-bottom: 20px;

      &:nth-child(6), &:nth-child(7) {
        background: #0084FF;
      }
    }

    .vc-day {
      --orange-600: #FF8212FF;

      &.in-next-month, &.in-prev-month {
        background: #EBEBEB4D;
      }

      .vc-day-content {
        .is-disabled {
          color: #0032A06B;
        }

        &:focus {
          background: transparent !important;
        }
      }

      &.weekday-7, &.weekday-1 {
        color: #0084FF;

        .is-disabled {
          color: #0084FF6B;
        }
      }

      .vc-highlight {
        width: 100%;
        height: 100%;
      }
    }

  }
}

.vc-arrows-container {
  top: 2px !important;

  .vc-arrow {
    color: #fff;
  }

}
</style>
