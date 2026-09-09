<template>
  <div
    class="fixed inset-0 flex flex-col justify-center pointer-events-none px-4"
    data-layer
    data-overlay
    ref="containerNode"
    :style="zIndexStyle"
  >
    <div
      role="dialog"
      aria-modal="true"
      :aria-labelledby="labelledBy"
      :aria-label="labelledBy ? null : label"
      :tabIndex="-1"
      class="focus:outline-none"
      ref="dialog"
      @keydown="onKeydown"
    >
      <div :class="wrapperClassName">
        <div :class="className">
          <KeypressListener :key-code="27" :handler="onClose" />
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import KeypressListener from "../../../KeypressListener";
import { classNames } from "../../../../utilities/css";

export default {
  components: {
    KeypressListener,
  },

  props: {
    labelledBy: String,
    label: {
      type: String,
      default: "Диалог",
    },
    instant: Boolean,
    large: Boolean,
    small: Boolean,
    fix: Boolean,
    limitHeight: Boolean,
    flat: Boolean,
    zIndexOverride: Number,
  },

  computed: {
    className() {
      return classNames(
        "bg-surface pointer-events-auto relative inset-x-0 bottom-0 flex flex-col w-full max-h-[95vh] md:max-w-max mx-auto",
        this.flat ? "rounded-none shadow-none" : "rounded-xl md:rounded-3xl shadow-lg",
        !this.limitHeight && "h-full"
      );
    },

    zIndexStyle() {
      return {
        zIndex: this.zIndexOverride || 50,
      };
    },

    wrapperClassName() {
      return classNames(
        "md:mx-auto overflow-hidden",
        this.large && "max-w-6xl md:p-16",
        this.small && "max-w-2xl md:p-20",
        this.fix && "max-w-6xl fixed inset-x-0 bottom-0 md:relative",
        !this.large && !this.small && !this.fix && "md:max-w-auto"
      );
    },
  },

  data() {
    return {
      openingElement: null,
    };
  },

  mounted() {
    this.openingElement = document.activeElement;

    this.$nextTick(() => {
      this.focusInitialElement();
    });
  },

  beforeDestroy() {
    if (this.openingElement && typeof this.openingElement.focus === "function") {
      this.openingElement.focus();
    }
  },

  methods: {
    getFocusableElements() {
      if (this.$refs.dialog == null) {
        return [];
      }

      return Array.from(
        this.$refs.dialog.querySelectorAll(
          'a[href], area[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )
      ).filter((element) => element.getAttribute("aria-hidden") !== "true");
    },

    focusInitialElement() {
      const [firstFocusableElement] = this.getFocusableElements();
      const elementToFocus = firstFocusableElement || this.$refs.dialog;

      if (elementToFocus && typeof elementToFocus.focus === "function") {
        elementToFocus.focus();
      }
    },

    onKeydown(event) {
      if (event.key !== "Tab") {
        return;
      }

      const focusableElements = this.getFocusableElements();
      const [firstFocusableElement] = focusableElements;
      const lastFocusableElement = focusableElements[focusableElements.length - 1];

      if (firstFocusableElement == null || lastFocusableElement == null) {
        event.preventDefault();
        this.$refs.dialog.focus();
        return;
      }

      const isFocusOutsideDialog = !this.$refs.dialog.contains(event.target);
      const shouldFocusLast = event.shiftKey && (isFocusOutsideDialog || event.target === firstFocusableElement);
      const shouldFocusFirst = !event.shiftKey && (isFocusOutsideDialog || event.target === lastFocusableElement);

      if (shouldFocusLast) {
        event.preventDefault();
        lastFocusableElement.focus();
      }

      if (shouldFocusFirst) {
        event.preventDefault();
        firstFocusableElement.focus();
      }
    },

    onClose() {
      this.$emit("close");
    },
  },
};
</script>
