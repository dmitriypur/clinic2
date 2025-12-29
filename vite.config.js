import {defineConfig} from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue2";

export default defineConfig({
  base: "",
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ["vue", "vue-yandex-maps", "v-calendar", "axios"],
          ui: ["swiper", "glightbox", "photoswipe"],
        },
      },
    },
  },
  plugins: [
    laravel({
      input: [
        "resources/css/app.css",
        "resources/js/app.js",
        "node_modules/glightbox/dist/css/glightbox.css",
      ],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: "./",
          includeAbsolute: false,
        },
      },
    }),
  ],
  resolve: {
    alias: {
      vue: "vue/dist/vue.esm.js",
      "vue-yandex-maps": "vue-yandex-maps/dist/vue-yandex-maps.esm.js",
      "@": "/resources/js",
    },
  },
});
