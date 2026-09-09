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
      latestRequestId: 0,
      errorMessage: null,
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
    applyFilter(){
      this.perpage = 1
      this.sendFilter(this.$refs.handle.role)
    },

    sendFilter(handle) {
      const requestId = ++this.latestRequestId
      this.loading = true;
      this.errorMessage = null
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
          if (requestId !== this.latestRequestId) {
            return
          }

          this.totalCountItems = res.data.meta.total
          this.currentCountItems = res.data.meta.per_page

          if (res.data.data.length) {
            this.reviewArr = res.data.data
          } else {
            this.reviewArr = [1]
          }
        })
        .catch(() => {
          if (requestId === this.latestRequestId) {
            this.errorMessage = 'Не удалось загрузить результаты. Попробуйте ещё раз.'
          }
        })
        .finally(() => {
          setTimeout(() => {
            if (requestId === this.latestRequestId) {
              this.loading = false;
            }
          },300)
        })
    },

    addResource(id) {
      this.toggleItem(this.filters.resources, id)
      this.applyFilter()
    },
    addDoctor(id) {
      this.toggleItem(this.filters.doctors, id)
      this.applyFilter()
    },
    addService(id) {
      this.toggleItem(this.filters.services, id)
      this.applyFilter()
    },
    addTag(id) {
      this.toggleItem(this.filters.tags, id)
      this.applyFilter()
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
      this.applyFilter()
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
