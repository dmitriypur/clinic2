<template>
  <div class="flex gap-2 relative">
    <button
      v-for="(doctor, index) in activeDoctorsToday"
      :key="doctor.id"
      @click="handleSelectDoctor(doctor)"
      :class="[
        'flex-1 h-12 text-base font-medium rounded-lg',
        selected.id === doctor.id
          ? 'bg-[#FF8C00] orange-gr  text-white'
          : !doctor.freeToday ? 'bg-white text-interactive border-2 border-gray-200' : 'bg-white text-interactive border-2 border-[#F5841F]',
      ]"
    >
      Врач {{ index + 1 }}
    </button>
  </div>
</template>

<script>
export default {
  props: {
    doctors: Array,
    selectedDoctor: Object,
    day: String,
    activeToggler: Number
  },

  data(){
    return {
      doctorsFree: this.doctors,
      selected: this.selectedDoctor,
      arr: []
    }
  },

  watch: {
    activeToggler: {
      handler: function (val) {
        this.allAvailableDates.map((el, i) => {
          if(el.includes(this.getToday)){
            this.selected = this.doctorsFree[i];
          }
          this.doctorsFree[i].freeToday = el.includes(this.getToday)
        })
        this.handleSelectDoctor(this.selected)
      },
      immediate: true,
    },

  },

  computed: {
    getToday(){
      const date = new Date()
      return `${date.getFullYear()}-${date.getMonth() + 1 < 10 ? ('0' + (date.getMonth() + 1)) : date.getMonth() + 1}-${date.getDate()}`
    },

    allAvailableDates(){
      return this.doctorsFree.map(doctor => [...new Set(doctor.cells.map(({dt}) => dt))])
    },

    activeDoctorsToday(){
      this.arr = []
      let day = this.day ? this.day : this.getToday
      this.allAvailableDates.map((el, i) => {
          if(el.includes(day)){
            this.arr.push(this.doctorsFree[i])
          }
        this.doctorsFree[i].freeToday = el.includes(day)
      })
      // this.handleSelectDoctor(this.arr.length ? this.arr[0] : this.selectedDoctor)
      return this.doctorsFree
    },

  },

  methods: {
    handleSelectDoctor(value) {
      this.selected = value
      this.$emit("select", value);
    },
  },
};
</script>
