<script>
import DoctorCard from '../DoctorCard/DoctorCard.vue';

export default {
  name: 'MegaMenu',
  components: {
    DoctorCard
  },
  data() {
    return {
      activeTop: null,               // Активный пункт 1-го уровня
      activeSecond: [null, null],   // [индекс 1, индекс 2]
      previewImage: '',             // URL текущей картинки
      mobileOpen: false,
      mobileSubIndex: null,
      mobileThirdIndex: null,
      isMobile: false,
      mediaQueryList: null,
      selectedDoctor: null,
    }
  },

  mounted() {
    // Инициализация matchMedia
    this.mediaQueryList = window.matchMedia('(max-width: 767px)');
    this.handleMediaChange(this.mediaQueryList); // начальное значение
    this.mediaQueryList.addEventListener('change', this.handleMediaChange);
  },
  beforeDestroy() {
    this.mediaQueryList.removeEventListener('change', this.handleMediaChange);
  },
  methods: {
    handleMediaChange(e) {
      this.isMobile = e.matches;
      if (!this.isMobile) {
        this.mobileOpen = false;
        this.mobileSubIndex = null;
      }
    },
    setActiveTop(index, second, img) {
      this.activeTop = index;
      this.activeSecond = [index, second];
      this.previewImage = img;

      const activeItem = this.menuItems.find(item => item.active === true);
      if (activeItem && activeItem.is_doctor_grid && activeItem.children.length > 0) {
        const activeChild = activeItem.children.find(item => item.active === true);

        if(activeChild) {
          this.selectedDoctor = activeChild.data.doctor;
        }else{
          this.selectedDoctor = activeItem.children[0].data.doctor;
        }
      }else{
        const doctors = this.menuItems.find(item => item.is_doctor_grid);
        if (doctors && doctors.children.length > 0) {
          this.selectedDoctor = doctors.children[0].data.doctor;
        }
      }
    },
    setActiveSecond(top, second, img) {
      this.activeSecond = [top, second];
      this.previewImage = img;
    },
    clearActive() {
      this.activeTop = null;
      this.activeSecond = [null, null];
      this.previewImage = '';
      this.selectedDoctor = null;
    },
    toggleMobileSub(index) {
      this.mobileSubIndex = this.mobileSubIndex === index ? null : index;
      this.activeTop = index;
      this.activeSecond = [index, 0];
    },
    toggleMobileThird(index) {
      this.mobileThirdIndex = this.mobileThirdIndex === index ? null : index;
    },
    selectDoctor(doctor) {
      if (doctor) {
        this.selectedDoctor = {...doctor};
      }
    },
  },
  props: {
    menuItems: {
      type: Array,
      required: true
    }
  }
}
</script>
