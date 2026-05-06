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
      :aria-labelledby="labelledBy"
      :tabIndex="-1"
      class="focus:outline-none"
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

  methods: {
    onClose() {
      this.$emit("close");
    },
  },
};
</script>
