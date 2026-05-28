import Vue from "vue";
import VueTheMask from "vue-the-mask";
import VCalendar from "v-calendar";
import vkBridge from "@vkontakte/vk-bridge";

import BookingWidgetV3 from "./components/BookingWidgetV3/BookingWidgetV3.vue";
import { buildBookingLaunchContextFromSearchParams } from "./utilities/bookingLaunchContext";

Vue.use(VueTheMask);
Vue.use(VCalendar, {
  locale: "ru-RU",
});

const FORWARDED_HASH_KEYS = [
  "city",
  "utm_source",
  "utm_medium",
  "utm_campaign",
  "utm_content",
  "utm_term",
  "booking_doctor_id",
  "booking_branch_id",
];

function getHashParams() {
  const hash = window.location.hash.replace(/^#/, "");

  if (!hash) {
    return new URLSearchParams();
  }

  return new URLSearchParams(hash);
}

function forwardHashParamsToQuery() {
  const hashParams = getHashParams();
  const url = new URL(window.location.href);
  let changed = false;

  FORWARDED_HASH_KEYS.forEach((key) => {
    const value = hashParams.get(key);

    if (!value || url.searchParams.has(key)) {
      return;
    }

    url.searchParams.set(key, value);
    changed = true;
  });

  if (changed) {
    window.location.replace(url.toString());
  }

  return changed;
}

function normalizePhone(value) {
  const digits = String(value || "").replace(/\D/g, "");
  if (!digits) {
    return "";
  }

  if (digits.startsWith("8") && digits.length === 11) {
    return `+7${digits.slice(1)}`;
  }

  if (digits.startsWith("7") && digits.length === 11) {
    return `+${digits}`;
  }

  if (digits.length === 10) {
    return `+7${digits}`;
  }

  return String(value || "");
}

async function fetchVkInitialPatientData() {
  const initialPatientData = {
    first_name: "",
    last_name: "",
    middle_name: "",
    phone_number: "",
  };

  try {
    await vkBridge.send("VKWebAppInit");
  } catch (error) {
    return initialPatientData;
  }

  try {
    const userInfo = await vkBridge.send("VKWebAppGetUserInfo");
    initialPatientData.first_name = String(userInfo?.first_name || "").trim();
    initialPatientData.last_name = String(userInfo?.last_name || "").trim();
  } catch (error) {
    // Пользовательские данные необязательны.
  }

  try {
    const phoneResponse = await vkBridge.send("VKWebAppGetPhoneNumber");
    initialPatientData.phone_number = normalizePhone(
      phoneResponse?.phone_number || phoneResponse?.phone || ""
    );
  } catch (error) {
    // Телефон опционален: отказ пользователя не должен ломать форму.
  }

  return initialPatientData;
}

if (!forwardHashParamsToQuery()) {
const launchContext = buildBookingLaunchContextFromSearchParams(
  window.location.search
);

new Vue({
  data() {
    return {
      initialPatientData: null,
    };
  },
  async created() {
    this.initialPatientData = await fetchVkInitialPatientData();
  },
  render(h) {
    return h(BookingWidgetV3, {
      props: {
        open: true,
        mode: "vk",
        launchContext,
        initialPatientData: this.initialPatientData,
      },
    });
  },
}).$mount("#vk-appointment-app");
}
