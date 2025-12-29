<template>
  <div>
    <!-- <portal to="modal"> -->
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
        :labelled-by="labelledBy"
        :large="large"
        :small="small"
        :limit-height="limitHeight"
        :zIndexOverride="zIndexOverride"
        @close="onClose"
      >
        <Header v-if="title" :id="headerId" @close="onClose">
          {{ title }}
        </Header>

        <CloseButton v-else @click="onClose" :title="!!title" :hiddenOnMobile="closeButtonHiddenOnMobile"/>

        <PrevButton v-if="hasPrev" @click="$emit('prev')"/>
        <NextButton v-if="hasNext" @click="$emit('next')"/>

        <div class="flex grow overflow-x-hidden md:rounded-t-lg">
          <Scrollable shadow class="w-full">
            <Section>
              <slot/>
            </Section>
          </Scrollable>
        </div>
        <Footer
          v-if="primaryAction || secondaryActions || $slots.footer"
          :primary-action="primaryAction"
          :secondary-actions="secondaryActions"
        >
          <slot name="footer"/>
        </Footer>
      </Dialog>
    </transition>

    <Backdrop v-if="open" @handleClick="onClose"/>
    <!-- </portal> -->
  </div>
</template>

<script>
import Backdrop from '../Backdrop'
import Scrollable from '../Scrollable'
import {Dialog, Section} from './components'

const CloseButton = () => import('./components/CloseButton/CloseButton.vue')
const Footer = () => import('./components/Footer/Footer.vue')
const Header = () => import('./components/Header/Header.vue')
const NextButton = () => import('./components/NextButton/NextButton.vue')
const PrevButton = () => import('./components/PrevButton/PrevButton.vue')

import {classNames} from '../../utilities/css'



export default {
  components: {
    PrevButton,
    NextButton,
    Footer,
    Scrollable,
    Backdrop,
    Dialog,
    Section,
    Header,
    CloseButton,
  },

  props: {
    /** Whether the modal is open or not */
    open: Boolean,
    /** The content for the title of the modal */
    title: String,
    closeButtonHiddenOnMobile: Boolean,
    large: Boolean,
    small: Boolean,
    limitHeight: {
      type: Boolean,
      default: false,
    },
    /** Replaces modal content with a spinner while a background action is being performed */
    loading: Boolean,
    hasPrev: Boolean,
    hasNext: Boolean,
    zIndexOverride: Number,
    primaryAction: {
      type: Object,
      default: null,
    },
    secondaryActions: {
      type: Array,
      default: null,
    },
  },

  computed: {
    fadeUpClasses() {
      return {
        appear: classNames('animateFadeUp', 'entering'),
        appearActive: classNames('animateFadeUp', 'entered'),
        enter: classNames('animateFadeUp', 'entering'),
        enterActive: classNames('animateFadeUp', 'entered'),
        exit: classNames('animateFadeUp', 'exiting'),
        exitActive: classNames('animateFadeUp', 'exited'),
      }
    },

    headerId() {
      return 'ZrenieModal-header'
    },

    labelledBy() {
      return this.title ? this.headerId : undefined
    },
  },

  methods: {
    onClose() {
      this.$emit('close')
    },
  },
}
</script>
