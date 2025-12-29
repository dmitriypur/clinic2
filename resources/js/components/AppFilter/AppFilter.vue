<script>
import axios from "axios";
const Card = () => import('./components/Card/Card.vue')
const CardPost = () => import('./components/CardPost/CardPost.vue')
const FilterList = () => import('./components/FilterList/FilterList.vue')

export default {
  components: {Card, CardPost, FilterList},

  data() {
    return {
      filters: {
        doctors: [],
        resources: [],
        services: [],
        tags: [],
      },
      mobileSize: false,
      perpage: 1,
      totalCountItems: null,
      currentCountItems: null,
      reviewArr: [],
      loading: false,
      isVisible: false,
      activeTags: [],
    }
  },

  methods: {
    toggleVisible(){
      this.isVisible = !this.isVisible
    },
    readMore(){
      this.perpage++
      this.sendFilter(this.$refs.handle.role)
    },

    sendFilter(handle) {
      this.loading = true;
      axios.get(handle, {
        params: {
          'doctors': this.filters.doctors,
          'resources': this.filters.resources,
          'services': this.filters.services,
          'perpage': this.perpage,
          'handle': handle,
          'tags': this.filters.tags,
        },
      })
        .then(res => {
          console.log(res.data)
          this.totalCountItems = res.data.meta.total
          this.currentCountItems = res.data.meta.per_page

          if (res.data.data.length) {
            this.reviewArr = res.data.data
          } else {
            this.reviewArr = [1]
          }
        })
        .finally(() => {
          setTimeout(() => {
            this.loading = false;
          },300)
        })
    },

    addDoctor(id) {
      this.toggleItem(this.filters.doctors, id)
      this.sendFilter(this.$refs.handle.role)
    },
    addResource(id) {
      this.toggleItem(this.filters.resources, id)
      this.sendFilter(this.$refs.handle.role)
    },
    addService(id) {
      this.toggleItem(this.filters.services, id)
      this.sendFilter(this.$refs.handle.role)
    },
    addTag(id) {
      this.toggleItem(this.filters.tags, id)
      this.sendFilter(this.$refs.handle.role)
    },
    toggleTag(index) {
      const i = this.activeTags.indexOf(index);
      if (i !== -1) {
        this.activeTags.splice(i, 1); // удалить
      } else {
        this.activeTags.push(index); // добавить
      }
    },
    clearFilterTag(){
      this.filters.tags = []
      this.sendFilter(this.$refs.handle.role)
      this.activeTags = [];
    },
    toggleItem(arr, value) {
      let index = arr.indexOf(value)
      index === -1 ? arr.push(value) : arr.splice(index, 1)
    },
    isMobile() {
      if (window.innerWidth < 768) {
        this.isVisible = false
      } else {
        this.isVisible = true
      }
    },
  },
  mounted() {
    this.isMobile()
  },
}
</script>
