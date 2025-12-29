<script>

const Item = () => import('./components/Item.vue')

export default {
  components: {Item},
  data() {
    return {
      openAll: false,
      countItems: 4,
    }
  },

  methods: {
    toggleAll(count) {
      this.openAll = !this.openAll
      this.countItems = this.openAll ? count : 4
    },
  },
}
</script>

