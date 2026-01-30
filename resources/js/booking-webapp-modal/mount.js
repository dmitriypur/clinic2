import Vue from "vue";
import BookingWebAppModal from "./App.vue";

let instance = null;
let options = {
  apiBase: "",
  title: "Онлайн‑запись",
  primaryColor: "#2563eb",
  onSuccess: null,
};

function mount() {
  if (instance) return instance;
  const root = document.createElement("div");
  root.id = "booking-webapp-modal-root";
  document.body.appendChild(root);

  instance = new Vue({
    render: (h) => h(BookingWebAppModal, { props: options }),
  }).$mount("#booking-webapp-modal-root");

  return instance;
}

function open() {
  const app = mount();
  app.$children[0].open();
}

function close() {
  if (!instance) return;
  instance.$children[0].close();
}

function init(opts = {}) {
  options = { ...options, ...opts };
  if (instance) {
    instance.$destroy();
    instance = null;
  }
}

const BookingWebApp = { init, open, close };

window.BookingWebApp = BookingWebApp;

export default BookingWebApp;
