<template>
  <div
    class="fixed inset-0 flex flex-col justify-end md:justify-center pointer-events-none"
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
    limitHeight: Boolean,
    zIndexOverride: Number,
  },

  computed: {
    className() {
      return classNames(
        "bg-surface pointer-events-auto fixed md:relative md:rounded-lg inset-x-0 bottom-0 flex flex-col w-full max-h-full md:max-h-[calc(100vh-6rem)] bg-surface shadow-lg",
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
        this.large && "max-w-6xl p-16",
        this.small && "max-w-2xl p-20",
        !this.large && !this.small && "md:max-w-[810px]"
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
