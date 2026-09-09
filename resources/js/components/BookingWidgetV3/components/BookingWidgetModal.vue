<template>
  <div>
    <transition
      appear
      :appear-active-class="fadeUpClasses.appearActive"
      :appear-class="fadeUpClasses.appear"
      :enter-class="fadeUpClasses.enter"
      :enter-to-class="fadeUpClasses.enterActive"
      :leave-to-class="fadeUpClasses.exit"
      :leave-active-class="fadeUpClasses.exitActive"
    >
      <Dialog
        v-if="open"
        :labelled-by="dialogTitleId"
        :zIndexOverride="zIndexOverride"
        :flat="flat"
        @close="onClose"
      >
        <h2 :id="dialogTitleId" class="sr-only">Запись на приём</h2>

        <CloseButton
          v-if="showCloseButton"
          :hiddenOnMobile="closeButtonHiddenOnMobile"
          @click="onClose"
        />

        <div 
        class="flex grow md:rounded-t-lg min-w-full md:min-w-[500px] md:min-h-[300px]"
        :class="[ mode === 'vk' ? '' : 'overflow-x-hidden' ]">
          <Scrollable shadow class="w-full">
            <section :class="bodyClassName">
              <slot />
            </section>
          </Scrollable>
        </div>
      </Dialog>
    </transition>

    <Backdrop
      v-if="open && showBackdrop"
      :zIndexOverride="backdropZIndex"
      @handleClick="onClose"
    />
  </div>
</template>

<script>
import Backdrop from "../../Backdrop";
import Scrollable from "../../Scrollable";
import { Dialog } from "../../Modal/components";
import { classNames } from "../../../utilities/css";

const CloseButton = () => import("../../Modal/components/CloseButton/CloseButton.vue");

export default {
  name: "BookingWidgetModal",
  components: {
    Backdrop,
    CloseButton,
    Dialog,
    Scrollable,
  },
  props: {
    mode: {
      type: String,
      default: 'site',
    },
    open: Boolean,
    closeButtonHiddenOnMobile: Boolean,
    showCloseButton: {
      type: Boolean,
      default: true,
    },
    showBackdrop: {
      type: Boolean,
      default: true,
    },
    flat: {
      type: Boolean,
      default: false,
    },
    zIndexOverride: Number,
    layoutMode: {
      type: String,
      default: "default",
      validator: (value) => ["default", "schedule", "error"].includes(value),
    },
  },
  computed: {
    dialogTitleId() {
      return `BookingWidgetModal-title-${this._uid}`;
    },
    fadeUpClasses() {
      return {
        appear: classNames("animateFadeUp", "entering"),
        appearActive: classNames("animateFadeUp", "entered"),
        enter: classNames("animateFadeUp", "entering"),
        enterActive: classNames("animateFadeUp", "entered"),
        exit: classNames("animateFadeUp", "exiting"),
        exitActive: classNames("animateFadeUp", "exited"),
      };
    },
    bodyClassName() {
      return classNames(
        "grow-0 shrink-0 basis-auto",
        ["schedule", "error"].includes(this.layoutMode)
          ? "p-0"
          : "px-4 py-6 lg:p-8"
      );
    },
    backdropZIndex() {
      return (this.zIndexOverride || 50) - 1;
    },
  },
  methods: {
    onClose() {
      this.$emit("close");
    },
  },
};
</script>
