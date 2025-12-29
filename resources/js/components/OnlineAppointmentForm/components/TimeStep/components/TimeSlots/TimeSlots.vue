<template>
  <div :class="['flex', !timeSlots.length ? 'py-3.5 -mx-4 md:mx-0 md:rounded-xl bg-surface-subdued border border-gray-200' : '']">
    <div v-if="timeSlots.length" :class="['grid gap-2.5 w-full', weekday === 1 ? 'grid-cols-2 md:grid-cols-4' : 'grid-cols-2 md:grid-cols-5']">
      <button
        v-for="item in getTimeSlots"
        @click="handleSelectTime(item)"
        :key="item.time"
        :disabled="!item.available"
        :class="[
          'text-sm md:text-2xl text-center transition-colors duration-300 rounded-lg py-1 px-2 md:p-1',
          item.available
            ? selectedTime === item.time
              ? 'orange-gr text-white'
              : 'bg-surface-subdued text-interactive-50 hover:!bg-[#FFE5CC] hover:text-[#FF8C00]'
            : 'bg-surface-subdued text-gray-300 cursor-not-allowed',
        ]"
      >
        {{ item.time }}
      </button>
    </div>
    <div v-else class="text-center flex flex-col mx-auto text-interactive">
      <p class="text-base md:text-2xl font-medium">
        Данный врач не принимает в выбранный день
      </p>
      <p class="text-sm md:text-xl opacity-50">Пожалуйста выберите другой день</p>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    weekday: Number,
    timeSlots: Array,
    selectedTime: [String, null],
    activeToggler: Number
  },

  computed: {
    getTimeSlots(){
      this.timeSlots.pop()
      if(this.weekday === 1){
        this.timeSlots.pop()
      }
      return this.timeSlots
    }
  },

  mounted() {

  },

  methods: {
    handleSelectTime(value) {
      if (value.available) {
        this.$emit("select", value.time);
      }
    },
  },
};
</script>
