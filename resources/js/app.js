import Vue from "vue";
import Swiper from "swiper";
import {Autoplay, Navigation, Pagination} from "swiper/modules";
import YmapPlugin from "vue-yandex-maps";
import LightBox from "vue-image-lightbox";
import GLightbox from "glightbox";
import VueTheMask from 'vue-the-mask'
import VCalendar from 'v-calendar';

import {
  CertificateForm,
  CookieToast,
  DoctorModal,
  ElementModal,
  ReadMore,
  LoginModal,
  Map as AppMap,
  RatingBadge,
  ReviewModal,
  ServiceCard,
  TextAccordion,
  TopBar,
  ImageLazy,
  CitySwitcher,
} from "./components";

const CallbackForm = () => import('./components/CallbackForm/CallbackForm.vue')
const CallbackModal = () => import('./components/CallbackModal/CallbackModal.vue')
const CallToAction = () => import('./components/CallToAction/CallToAction.vue')
const VideoComponent = () => import('./components/Video/Video.vue')
const VideoNew = () => import('./components/VideoNew/VideoNew.vue')
const VideoModal = () => import('./components/VideoModal/VideoModal.vue')
const Faq = () => import('./components/Faq/Faq.vue')
const AppFilter = () => import('./components/AppFilter/AppFilter.vue')
const OnlineAppointmentForm = () => import('./components/OnlineAppointmentForm/OnlineAppointmentForm.vue')
const DoctorCard = () => import('./components/DoctorCard/DoctorCard.vue')
const SearchLive = () => import('./components/SearchLive/SearchLive.vue')
const AccessibilityToggle = () => import('./components/AccessibilityToggle/AccessibilityToggle.vue')

import {eventBus} from "./eventBus";
import VueObserveVisibility from "vue-observe-visibility";
import VueLazyload from "vue-lazyload";

import "vue-image-lightbox/dist/vue-image-lightbox.min.css";

Vue.use(VueObserveVisibility);
Vue.use(YmapPlugin);
Vue.use(VueLazyload);
Vue.use(VueTheMask)
Vue.use(VCalendar)

new Vue({
  components: {
    ElementModal,
    VideoModal,
    ServiceCard,
    TopBar,
    CallbackModal,
    LoginModal,
    DoctorModal,
    DoctorCard,
    AppMap,
    RatingBadge,
    CookieToast,
    VideoComponent,
    VideoNew,
    ReviewModal,
    CallToAction,
    LightBox,
    Faq,
    AppFilter,
    ReadMore,
    TextAccordion,
    CallbackForm,
    CertificateForm,
    OnlineAppointmentForm,
    ImageLazy,
    SearchLive,
    AccessibilityToggle,
    CitySwitcher,
  },

  data: {
    baseUrl: window.config.baseUrl,
    activeElementModalBlockId: null,
    activeElementModalIndex: null,
    videoUrl: null,
    doctor: null,
    reviewModalActive: false,
    callbackModalActive: false,
    callbackModalName: window.config.state.user?.name,
    callbackModalPhone: window.config.state.user?.phone,
    callbackModalTarget: null,
    loginModalActive: false,
    showToTopButton: false,
  },

  created() {
    const self = this;

    eventBus.$on("showCallbackModal", function (phone = null, target = null) {
      self.showCallbackModal(phone, target);
    });

    eventBus.$on("closeCallbackModal", function () {
      self.closeCallbackModal();
    });

    eventBus.$on("showLoginModal", function () {
      self.showLoginModal();
    });

    eventBus.$on("closeLoginModal", function () {
      self.closeLoginModal();
    });

    eventBus.$on("showVideoModal", function (url) {
      self.videoUrl = url;
    });
  },

  mounted() {
    const self = this;

    setTimeout(() => {
      this.mountSwiper();
    }, 100);

    GLightbox({
      touchNavigation: true,
      loop: false,
      autoplayVideos: false,
      selector: ".glightbox",
    });

    const links = [...document.links].filter((link) => link.href.includes(this.baseUrl) && link.href.includes("#"));

    links.forEach((link) => {
      link.addEventListener("click", this.handleSmoothScroll);
    });

    window.addEventListener("load", function () {
      self.equalHeight(".utp-title");
    });

    window.addEventListener("resize", function () {
      setTimeout(function () {
        self.equalHeight(".utp-title");
      }, 100);
    });

    window.addEventListener("scroll", function () {
      const scrollHeight = Number.isNaN(window.innerHeight) ? window.clientHeight : window.innerHeight;

      self.showToTopButton = window.scrollY >= scrollHeight;
    });
  },


  beforeDestroy() {
    const self = this;

    const links = [...document.links].filter((link) => link.href.includes(this.baseUrl) && link.href.includes("#"));

    links.forEach((link) => {
      link.removeEventListener("click", this.handleSmoothScroll);
    });

    window.removeEventListener("load", function () {
      self.equalHeight(".utp-title");
    });
    window.removeEventListener("resize", function () {
      setTimeout(function () {
        self.equalHeight(".utp-title");
      }, 100);
    });
  },

  methods: {
    toTop() {
      window.scrollTo({
        top: 0, behavior: "smooth",
      });
    },

    equalHeight(container) {
      let height = "auto";
      let divs = [];
      // let currentTallest = 0;
      // let currentRowStart = 0;
      // const rowDivs = [];
      // let topPosition = 0;

      Array.from(document.querySelectorAll(container)).forEach((el) => {
        if (height === "auto" || height < el.offsetHeight) {
          height = el.offsetHeight;
        }
        divs.push(el);
      });

      Array.from(document.querySelectorAll(container)).forEach((el) => {
        setHeight(el, height);
      });
    },

    handleSmoothScroll(e) {
      const href = e.target.getAttribute("href") || e.target.parentElement.getAttribute("href");

      const target = document.querySelector("#" + href.slice(href.indexOf("#") + 1));

      if (!target) {
        return;
      }

      e.preventDefault();

      target.scrollIntoView({
        behavior: "smooth",
      });

      if (window.innerWidth < 1024) {
        eventBus.$emit("hideTopBar");
      }
    },

    showCallbackModal(phone = null, target = null) {
      this.callbackModalPhone = phone || window.config.state.user?.phone || "";
      this.callbackModalTarget = target;
      this.callbackModalActive = true;
    },

    closeCallbackModal() {
      this.callbackModalActive = false;
      this.callbackModalPhone = window.config.state.user?.phone || "";
      this.callbackModalName = window.config.state.user?.name || "";
      this.callbackModalTarget = "";
    },

    showLoginModal() {
      this.loginModalActive = true;
    },

    closeLoginModal() {
      this.loginModalActive = false;
    },

    setActiveElementModal(show = false, blockId = null, currentElementIndex = null) {
      this.activeElementModalBlockId = show ? blockId : null;
      this.activeElementModalIndex = show ? currentElementIndex : null;
    },

    setVideoUrl(url = null) {
      this.videoUrl = url;
    },

    setDoctor(doctor = null) {
      this.doctor = doctor;
    },

    mountSwiper() {
      new Swiper(".doctors-swiper", {
        modules: [Navigation, Pagination],
        loop: true,
        cssMode: true,
        slidesPerView: 1,
        breakpoints: {
          640: {
            cssMode: false, slidesPerView: 2, spaceBetween: 32,
          },
        },

        pagination: {
          el: ".doctors-swiper-pagination", clickable: true,
        },
        navigation: {
          nextEl: ".doctors-swiper-next", prevEl: ".doctors-swiper-prev",
        },
      });

      new Swiper(".doctors-alt-swiper", {
        modules: [Navigation, Pagination],
        loop: true,
        cssMode: true,
        spaceBetween: 20,
        slidesPerView: 1,
        breakpoints: {
          768: {
            cssMode: false, slidesPerView: 2, spaceBetween: 20,
          },
          1024: {
            cssMode: false, slidesPerView: 3, spaceBetween: 20,
          },
        },

        pagination: {
          el: '.doctors-alt-swiper-pagination',
          // renderBullet: function (index, className) {
          //   return '<div class="custom-bullet ' + className + '">' +(index+1)+'</div>';
          // },
          clickable: true,
        },
        navigation: {
          nextEl: ".doctors-swiper-next", prevEl: ".doctors-swiper-prev",
        },
      });

      new Swiper(".reviews-swiper", {
        modules: [Navigation, Pagination],
        loop: true,
        cssMode: true,
        slidesPerView: 1,
        spaceBetween: 16,
        breakpoints: {
          640: {
            cssMode: false, slidesPerView: 2, spaceBetween: 24,
          },
          920: {
            cssMode: false, slidesPerView: 3, spaceBetween: 32,
          },
        },

        pagination: {
          el: ".review-swiper-pagination", clickable: true,
        },
        navigation: {
          nextEl: ".review-swiper-next", prevEl: ".review-swiper-prev",
        },
      });

      new Swiper(".gallery-swiper", {
        modules: [Navigation, Pagination],
        loop: true,
        slidesPerView: 1,
        spaceBetween: 16,
        cssMode: true,
        breakpoints: {
          640: {
            cssMode: false, slidesPerView: 3, spaceBetween: 32,
          },
        },

        pagination: {
          el: ".gallery-swiper-pagination", clickable: true,
        },
        navigation: {
          nextEl: ".gallery-swiper-next", prevEl: ".gallery-swiper-prev",
        },
      });

      new Swiper(".top-carousel-swiper", {
        modules: [Navigation, Pagination, Autoplay],
        loop: true,
        autoplay: {
          delay: 5000,
        },
        autoHeight: true,
        slidesPerView: 1,
        centeredSlides: true,
        spaceBetween: 0,
        lazyLoading: true, // breakpoints: {

        pagination: {
          el: ".promotions-swiper-pagination", clickable: true,
        },
        navigation: {
          nextEl: ".promotions-swiper-next", prevEl: ".promotions-swiper-prev",
        },
      });

      new Swiper(".promotions-swiper", {
        modules: [Navigation, Pagination, Autoplay],
        loop: true,
        autoplay: {
          delay: 5000,
        },
        slidesPerView: 1,
        spaceBetween: 10,
        lazyLoading: true, // breakpoints: {

        pagination: {
          el: ".promotions-swiper-pagination", clickable: true,
        },
        navigation: {
          nextEl: ".promotions-swiper-next", prevEl: ".promotions-swiper-prev",
        },

        breakpoints: {
          768: {
            slidesPerView: 2,
            spaceBetween: 62,
          },
        },
      });

      new Swiper(".utp-swiper", {
        modules: [Navigation],
        loop: true,
        slidesPerView: 2,
        spaceBetween: 12,
        lazyLoading: true,
        breakpoints: {
          1024: {
            slidesPerView: 3,
          },
        },
        navigation: {
          nextEl: ".utp-swiper-next", prevEl: ".utp-swiper-prev",
        },
      });

      new Swiper(".doctor-documents-swiper", {
        modules: [Navigation, Pagination],
        loop: true,
        slidesPerView: "auto",
        autoHeight: true,
        spaceBetween: 12,
        lazyLoading: true,
        pagination: {
          el: ".doctor-documents-swiper-pagination",
          clickable: true,
          renderBullet: function (index, className) {
            return '<span class="' + className + '">' + (index + 1) + "</span>";
          },
        },
        navigation: {
          nextEl: ".doctor-documents-swiper-next",
          prevEl: ".doctor-documents-swiper-prev",
        },
      });

      new Swiper(".doctor-documents-mobile-swiper", {
        modules: [Pagination],
        slidesPerView: 1,
        loop: true,
        spaceBetween: 16,
        lazyLoading: true,
        pagination: {
          el: ".doctor-documents-mobile-swiper-pagination", clickable: true,
        },
      });

      let gridCarouselSwiper;
      if (window.innerWidth < 768) {
        gridCarouselSwiper = new Swiper(".grid-carousel-swiper", {
          modules: [Pagination],
          slidesPerView: "1",
          loop: true,
          spaceBetween: 12,
          pagination: {
            el: ".grid-carousel-swiper-pagination", clickable: true,
          },
        });
      } else {
        gridCarouselSwiper?.disable()
      }

      let pointsCarouselSwiper;
      if (window.innerWidth < 768) {
        pointsCarouselSwiper = new Swiper(".points-carousel-swiper", {
          modules: [Pagination],
          slidesPerView: 1.2,
          loop: true,
          spaceBetween: 40,
          pagination: {
            el: ".points-carousel-swiper-pagination", clickable: true,
          },
        });
      } else {
        pointsCarouselSwiper?.disable()
      }

      new Swiper(".video-carousel-swiper", {
        modules: [Pagination, Navigation],
        slidesPerView: 1,
        loop: true,
        spaceBetween: 12,
        breakpoints: {
          768: {
            slidesPerView: 3,
          },
        },
        pagination: {
          el: ".video-carousel-swiper-pagination", clickable: true,
        },
        navigation: {
          nextEl: ".video-carousel-swiper-next", prevEl: ".video-carousel-swiper-prev",
        },
      });

      new Swiper(".stories-swiper", {
        modules: [Navigation, Pagination, Autoplay],
        slidesPerView: 1,
        loop: true,
        spaceBetween: 12,
        pagination: {
          el: ".stories-swiper-pagination", clickable: true,
        },
        navigation: {
          nextEl: ".stories-swiper-next", prevEl: ".stories-swiper-prev",
        },
      });

      new Swiper(".cards-swiper", {
        modules: [Navigation, Pagination, Autoplay],
        slidesPerView: 1,
        loop: true,
        spaceBetween: 33,
        breakpoints: {
          768: {
            slidesPerView: 2,
          },
          992: {
            slidesPerView: (typeof countCards !== 'undefined') ? countCards : 3,
          },
        },
        pagination: {
          el: ".cards-swiper-pagination", clickable: true,
        },
        navigation: {
          nextEl: ".cards-swiper-next", prevEl: ".cards-swiper-prev",
        },
      });

      new Swiper(".advantages-swiper", {
        modules: [Navigation, Pagination, Autoplay],
        slidesPerView: 1,
        loop: true,
        spaceBetween: 20,
        breakpoints: {
          768: {
            slidesPerView: 2,
          },
          992: {
            slidesPerView: (typeof countCards !== 'undefined') ? countCards : 3,
          },
        },
        pagination: {
          el: ".advantages-swiper-pagination", clickable: true,
        },
        navigation: {
          nextEl: ".advantages-swiper-next", prevEl: ".cards-swiper-prev",
        },
      });

      new Swiper(".blog-swiper", {
        modules: [Navigation, Pagination, Autoplay],
        slidesPerView: 1,
        loop: true,
        spaceBetween: 33,
        breakpoints: {
          768: {
            slidesPerView: 2,
          },
          992: {
            slidesPerView: (typeof countBlog !== 'undefined') ? countBlog : 3,
          },
        },
        pagination: {
          el: ".blog-swiper-pagination", clickable: true,
        },
        navigation: {
          nextEl: ".blog-swiper-next", prevEl: ".blog-swiper-prev",
        },
      });

      let bannerGridSwiper;
      if (window.innerWidth < 768) {
        bannerGridSwiper = new Swiper(".banner-grid-swiper", {
          modules: [Navigation, Autoplay],
          slidesPerView: 1,
          loop: true,
          spaceBetween: 20,
          navigation: {
            nextEl: ".banner-grid-swiper-next", prevEl: ".banner-grid-swiper-prev",
          },
        });
      } else {
        bannerGridSwiper?.disable()
      }
    },

  },
}).$mount("#app");

function setHeight(el, val) {
  if (typeof val === "function") val = val();
  if (typeof val === "string") el.style.height = val; else el.style.height = val + "px";
}
