import Vue from "vue";
import YmapPlugin from "vue-yandex-maps";
import LightBox from "vue-image-lightbox";
import VueTheMask from "vue-the-mask";
import VCalendar from "v-calendar";

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
  InfiniteDoctorsList,
  StickyTags,
} from "./components";

const CallbackForm = () => import("./components/CallbackForm/CallbackForm.vue");
const CallbackModal = () =>
  import("./components/CallbackModal/CallbackModal.vue");
const CallbackModalNew = () =>
  import("./components/CallbackModal/CallbackModalNew.vue");
const CallToAction = () => import("./components/CallToAction/CallToAction.vue");
const VideoComponent = () => import("./components/Video/Video.vue");
const VideoNew = () => import("./components/VideoNew/VideoNew.vue");
const VideoModal = () => import("./components/VideoModal/VideoModal.vue");
const Faq = () => import("./components/Faq/Faq.vue");
const AppFilter = () => import("./components/AppFilter/AppFilter.vue");
const BookingWidgetV3 = () =>
  import("./components/BookingWidgetV3/BookingWidgetV3.vue");
const DoctorCard = () => import("./components/DoctorCard/DoctorCard.vue");
const SearchLive = () => import("./components/SearchLive/SearchLive.vue");
const CityConfirmationModal = () =>
  import("./components/CityConfirmationModal.vue");
const AccessibilityToggle = () =>
  import("./components/AccessibilityToggle/AccessibilityToggle.vue");
const OnlineAppointmentForm = () =>
  import("./components/OnlineAppointmentForm/OnlineAppointmentForm.vue");

import { eventBus } from "./eventBus";
import {
  BOOKING_LAUNCH_SELECTOR,
  buildBookingLaunchContextFromElement,
  normalizeBookingLaunchContext,
} from "./utilities/bookingLaunchContext";
import VueObserveVisibility from "vue-observe-visibility";
import VueLazyload from "vue-lazyload";

import "vue-image-lightbox/dist/vue-image-lightbox.min.css";

Vue.use(VueObserveVisibility);
Vue.use(YmapPlugin);
Vue.use(VueLazyload);
Vue.use(VueTheMask);
Vue.use(VCalendar, {
  locale: "ru-RU",
});

new Vue({
  components: {
    ElementModal,
    VideoModal,
    ServiceCard,
    TopBar,
    CallbackModal,
    CallbackModalNew,
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
    BookingWidgetV3,
    ImageLazy,
    SearchLive,
    AccessibilityToggle,
    CitySwitcher,
    CityConfirmationModal,
    InfiniteDoctorsList,
    StickyTags,
  },

  data: {
    baseUrl: window.config.baseUrl,
    activeElementModalBlockId: null,
    activeElementModalIndex: null,
    videoUrl: null,
    doctor: null,
    reviewModalActive: false,
    callbackModalActive: false,
    callbackModalNewActive: false,
    callbackModalName: window.config.state.user?.name,
    callbackModalPhone: window.config.state.user?.phone,
    callbackModalTarget: null,
    loginModalActive: false,
    bookingWidgetV2Active: false,
    bookingWidgetV3Active: false,
    bookingWidgetV3Target: null,
    bookingWidgetV3Mode: null,
    bookingWidgetV3LaunchContext: null,
    currentCityId: null,
    showToTopButton: false,
  },

  created() {
    const self = this;

    eventBus.$on(
      "showCallbackModal",
      function (phone = null, target = null, options = null) {
        self.showCallbackModal(phone, target, options);
      }
    );

    eventBus.$on(
      "showCallbackFormNew",
      function (phone = null, target = null) {
        self.showCallbackFormNew(phone, target);
      }
    );

    eventBus.$on("closeCallbackModal", function () {
      self.closeCallbackModal();
    });

    eventBus.$on("closeCallbackFormNew", function () {
      self.closeCallbackFormNew();
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

    this.autoOpenBookingWidgetV3FromUrl();
    this.mountLightbox();
    this.refreshLightboxListener = () => this.mountLightbox();
    window.addEventListener("refresh-lightbox", this.refreshLightboxListener);
    document.addEventListener("click", this.handleBookingLaunchContextClick);

    const links = [...document.links].filter(
      (link) => link.href.includes(this.baseUrl) && link.href.includes("#")
    );

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
      const scrollHeight = Number.isNaN(window.innerHeight)
        ? window.clientHeight
        : window.innerHeight;

      self.showToTopButton = window.scrollY >= scrollHeight;
    });
  },

  beforeDestroy() {
    const self = this;

    if (this.refreshLightboxListener) {
      window.removeEventListener("refresh-lightbox", this.refreshLightboxListener);
    }
    document.removeEventListener("click", this.handleBookingLaunchContextClick);

    const links = [...document.links].filter(
      (link) => link.href.includes(this.baseUrl) && link.href.includes("#")
    );

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
    autoOpenBookingWidgetV3FromUrl() {
      const shouldOpenBookingWidget =
        window.location.hash.trim().toLowerCase() === "#appointment-form";

      if (!shouldOpenBookingWidget) {
        return;
      }

      this.openBookingWidgetV3();
    },

    toTop() {
      window.scrollTo({
        top: 0,
        behavior: "smooth",
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
      const href =
        e.target.getAttribute("href") ||
        e.target.parentElement.getAttribute("href");

      const target = document.querySelector(
        "#" + href.slice(href.indexOf("#") + 1)
      );

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

    normalizeBookingWidgetStartMode(options = null) {
      const mode = normalizeBookingLaunchContext(options)?.entry || null;

      if (mode === "doctor" || mode === "clinic") {
        return mode;
      }

      return null;
    },

    handleBookingLaunchContextClick(event) {
      if (event.defaultPrevented) {
        return;
      }

      const trigger = event.target?.closest?.(BOOKING_LAUNCH_SELECTOR);
      if (!trigger) {
        return;
      }

      const launchContext = buildBookingLaunchContextFromElement(trigger);
      if (!launchContext) {
        return;
      }

      event.preventDefault();
      this.showCallbackModal(
        null,
        trigger.dataset.doctorCallbackTarget ||
          trigger.dataset.callbackTarget ||
          trigger.dataset.appointmentTarget ||
          "otpravka-formy",
        { launchContext }
      );
    },

    openBookingWidgetV3(target = null, options = null) {
      const launchContext = normalizeBookingLaunchContext(
        options?.launchContext || options
      );
      this.callbackModalActive = false;
      this.bookingWidgetV3Target = target;
      this.bookingWidgetV3LaunchContext = launchContext;
      this.bookingWidgetV3Mode =
        launchContext?.entry || this.normalizeBookingWidgetStartMode(options);
      this.bookingWidgetV3Active = true;
    },

    showCallbackModal(phone = null, target = null, options = null) {
      this.callbackModalPhone = phone || window.config.state.user?.phone || "";
      this.callbackModalTarget = target;
      this.callbackModalNewActive = false;

      const bookingFormVariant = window.config?.booking?.formVariant || "old";
      if (bookingFormVariant === "new") {
        this.openBookingWidgetV3(target, options);
        return;
      }

      this.bookingWidgetV3Active = false;
      this.bookingWidgetV3Target = null;
      this.bookingWidgetV3Mode = null;
      this.bookingWidgetV3LaunchContext = null;
      this.callbackModalActive = true;
    },

    showCallbackFormNew(phone = null, target = null) {
      this.callbackModalPhone = phone || window.config.state.user?.phone || "";
      this.callbackModalName = window.config.state.user?.name || "";
      this.callbackModalTarget = target;
      this.callbackModalActive = false;
      this.callbackModalNewActive = true;
      this.bookingWidgetV3Active = false;
      this.bookingWidgetV3Target = null;
      this.bookingWidgetV3Mode = null;
      this.bookingWidgetV3LaunchContext = null;
    },

    closeCallbackModal() {
      this.callbackModalActive = false;
      this.callbackModalPhone = window.config.state.user?.phone || "";
      this.callbackModalName = window.config.state.user?.name || "";
      this.callbackModalTarget = "";
    },

    closeCallbackFormNew() {
      this.callbackModalNewActive = false;
      this.callbackModalPhone = window.config.state.user?.phone || "";
      this.callbackModalName = window.config.state.user?.name || "";
      this.callbackModalTarget = "";
    },

    closeBookingWidgetV3() {
      this.bookingWidgetV3Active = false;
      this.bookingWidgetV3Target = null;
      this.bookingWidgetV3Mode = null;
      this.bookingWidgetV3LaunchContext = null;
    },

    showLoginModal() {
      this.loginModalActive = true;
    },

    closeLoginModal() {
      this.loginModalActive = false;
    },

    setActiveElementModal(
      show = false,
      blockId = null,
      currentElementIndex = null
    ) {
      this.activeElementModalBlockId = show ? blockId : null;
      this.activeElementModalIndex = show ? currentElementIndex : null;
    },

    setVideoUrl(url = null) {
      this.videoUrl = url;
    },

    setDoctor(doctor = null) {
      this.doctor = doctor;
    },

    async mountLightbox() {
      if (!document.querySelector(".glightbox")) {
        if (this.lightboxInstance) {
          this.lightboxInstance.destroy();
          this.lightboxInstance = null;
        }

        return;
      }

      const [{ default: GLightbox }] = await Promise.all([
        import("glightbox"),
        import("glightbox/dist/css/glightbox.css"),
      ]);

      if (this.lightboxInstance) {
        this.lightboxInstance.destroy();
      }

      this.lightboxInstance = GLightbox({
        touchNavigation: true,
        loop: false,
        autoplayVideos: false,
        selector: ".glightbox",
      });
    },

    async mountSwiper() {
      if (!document.querySelector('[class*="swiper"]')) {
        return;
      }

      const [{ default: Swiper }, { Autoplay, Navigation, Pagination }] =
        await Promise.all([
          import("swiper"),
          import("swiper/modules"),
        ]);

      new Swiper(".doctors-swiper", {
        modules: [Navigation, Pagination],
        loop: true,
        cssMode: true,
        slidesPerView: 1,
        breakpoints: {
          640: {
            cssMode: false,
            slidesPerView: 2,
            spaceBetween: 32,
          },
        },

        pagination: {
          el: ".doctors-swiper-pagination",
          clickable: true,
        },
        navigation: {
          nextEl: ".doctors-swiper-next",
          prevEl: ".doctors-swiper-prev",
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
            cssMode: false,
            slidesPerView: 2,
            spaceBetween: 20,
          },
          1024: {
            cssMode: false,
            slidesPerView: 3,
            spaceBetween: 20,
          },
        },

        pagination: {
          el: ".doctors-alt-swiper-pagination",
          // renderBullet: function (index, className) {
          //   return '<div class="custom-bullet ' + className + '">' +(index+1)+'</div>';
          // },
          clickable: true,
        },
        navigation: {
          nextEl: ".doctors-swiper-next",
          prevEl: ".doctors-swiper-prev",
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
            cssMode: false,
            slidesPerView: 2,
            spaceBetween: 24,
          },
          920: {
            cssMode: false,
            slidesPerView: 3,
            spaceBetween: 32,
          },
        },

        pagination: {
          el: ".review-swiper-pagination",
          clickable: true,
        },
        navigation: {
          nextEl: ".review-swiper-next",
          prevEl: ".review-swiper-prev",
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
            cssMode: false,
            slidesPerView: 3,
            spaceBetween: 32,
          },
        },

        pagination: {
          el: ".gallery-swiper-pagination",
          clickable: true,
        },
        navigation: {
          nextEl: ".gallery-swiper-next",
          prevEl: ".gallery-swiper-prev",
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
          el: ".promotions-swiper-pagination",
          clickable: true,
        },
        navigation: {
          nextEl: ".promotions-swiper-next",
          prevEl: ".promotions-swiper-prev",
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
          el: ".promotions-swiper-pagination",
          clickable: true,
        },
        navigation: {
          nextEl: ".promotions-swiper-next",
          prevEl: ".promotions-swiper-prev",
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
          nextEl: ".utp-swiper-next",
          prevEl: ".utp-swiper-prev",
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
          el: ".doctor-documents-mobile-swiper-pagination",
          clickable: true,
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
            el: ".grid-carousel-swiper-pagination",
            clickable: true,
          },
        });
      } else {
        gridCarouselSwiper?.disable();
      }

      let pointsCarouselSwiper;
      if (window.innerWidth < 768) {
        pointsCarouselSwiper = new Swiper(".points-carousel-swiper", {
          modules: [Pagination],
          slidesPerView: 1.2,
          loop: true,
          spaceBetween: 40,
          pagination: {
            el: ".points-carousel-swiper-pagination",
            clickable: true,
          },
        });
      } else {
        pointsCarouselSwiper?.disable();
      }

      let nightLensesSelectionSwiper;
      if (window.innerWidth < 768 && document.querySelector(".night-lenses-selection-swiper")) {
        nightLensesSelectionSwiper = new Swiper(".night-lenses-selection-swiper", {
          modules: [Pagination],
          slidesPerView: 1.15,
          loop: true,
          spaceBetween: 16,
          pagination: {
            el: ".night-lenses-selection-swiper-pagination", clickable: true,
          },
        });
      } else {
        nightLensesSelectionSwiper?.disable()
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
          el: ".video-carousel-swiper-pagination",
          clickable: true,
        },
        navigation: {
          nextEl: ".video-carousel-swiper-next",
          prevEl: ".video-carousel-swiper-prev",
        },
      });

      new Swiper(".stories-swiper", {
        modules: [Navigation, Pagination, Autoplay],
        slidesPerView: 1,
        loop: true,
        spaceBetween: 12,
        pagination: {
          el: ".stories-swiper-pagination",
          clickable: true,
        },
        navigation: {
          nextEl: ".stories-swiper-next",
          prevEl: ".stories-swiper-prev",
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
            slidesPerView: typeof countCards !== "undefined" ? countCards : 3,
          },
        },
        pagination: {
          el: ".cards-swiper-pagination",
          clickable: true,
        },
        navigation: {
          nextEl: ".cards-swiper-next",
          prevEl: ".cards-swiper-prev",
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
            slidesPerView: typeof countCards !== "undefined" ? countCards : 3,
          },
        },
        pagination: {
          el: ".advantages-swiper-pagination",
          clickable: true,
        },
        navigation: {
          nextEl: ".advantages-swiper-next",
          prevEl: ".cards-swiper-prev",
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
            slidesPerView: typeof countBlog !== "undefined" ? countBlog : 3,
          },
        },
        pagination: {
          el: ".blog-swiper-pagination",
          clickable: true,
        },
        navigation: {
          nextEl: ".blog-swiper-next",
          prevEl: ".blog-swiper-prev",
        },
      });

      let bannerGridSwiper;
      if (window.innerWidth < 768) {
        bannerGridSwiper = new Swiper(".banner-grid-swiper", {
          modules: [Navigation, Autoplay],
          slidesPerView: 1,
          loop: true,
          spaceBetween: 20,
          autoplay: {
            delay: 3000,
          },
          navigation: {
            nextEl: ".banner-grid-swiper-next",
            prevEl: ".banner-grid-swiper-prev",
          },
        });
      } else {
        bannerGridSwiper?.disable();
      }
    },
  },
}).$mount("#app");

function setHeight(el, val) {
  if (typeof val === "function") val = val();
  if (typeof val === "string") el.style.height = val;
  else el.style.height = val + "px";
}
