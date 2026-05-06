import Vue from "vue";
import VueTheMask from "vue-the-mask";
import VCalendar from "v-calendar";
import vkBridge from "@vkontakte/vk-bridge";

import BookingWidgetV3 from "./components/BookingWidgetV3/BookingWidgetV3.vue";

Vue.use(VueTheMask);
Vue.use(VCalendar, {
  locale: "ru-RU",
});

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
        initialPatientData: this.initialPatientData,
      },
    });
  },
}).$mount("#vk-appointment-app");
