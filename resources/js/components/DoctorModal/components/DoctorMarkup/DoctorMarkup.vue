<template>
  <div class="space-y-4">
    <div class="flex items-center gap-4 lg:gap-8">
      <div
        class="flex-none rounded overflow-hidden w-36 lg:w-[167px] lg:h-[200px]"
        v-html="doctor.avatar_image"/>

      <div>
        <div class="space-y-2">
          <p class="leading-tight lg:text-2xl font-semibold text-left">
            {{ doctor.surname }}
            <br/>
            {{ doctor.name }}
          </p>
          <p class="text-sm lg:text-base">
            <span v-html="doctor.speciality"/>
            <br class="hidden lg:block"/>
            {{ doctor.job_title }}
          </p>

          <button
            :disabled="!doctor.video_url"
            :class="videoButtonClassName"
            @click="handleShowVideo"
          >
              <span :class="videoIconClassName">
                <svg
                  width="25"
                  height="24"
                  viewBox="0 0 25 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                  class="fill-current"
                >
                  <path
                    d="M12.0527 24C18.6802 24 24.0527 18.6274 24.0527 12C24.0527 5.37258 18.6802 0 12.0527 0C5.42532 0 0.0527344 5.37258 0.0527344 12C0.0527344 18.6274 5.42532 24 12.0527 24Z"
                  />
                  <path
                    d="M10.1174 15.9307L15.6535 12.6743C16.1858 12.3374 16.1858 11.6637 15.6535 11.3268L10.1174 8.07035C9.69152 7.84576 9.05273 8.18264 9.05273 8.74409V15.1447C9.05273 15.8184 9.58505 16.1553 10.1174 15.9307Z"
                    fill="white"
                  />
                </svg>
              </span>
            <span
              :class="videoTextClassName"
            >
                видеовизитка
              </span>
          </button>
        </div>
      </div>
    </div>
    <div class="text-sm lg:text-base space-y-4">
      <div>{{ doctor.excerpt }}</div>
      <div
        class="[&_p]:pb-4 [&_ul]:list-disc [&_ul]:pl-4 [&_ul]:pb-4"
        v-html="doctor.bio"
      />
    </div>
  </div>
</template>

<script>
import {eventBus} from "../../../../eventBus";
import {classNames} from "../../../../utilities/css";

export default {
  props: {
    doctor: {
      type: Object,
      required: true,
    }
  },

  computed: {
    videoButtonClassName() {
      return classNames(
        'pt-2 flex gap-2 items-center group',
        this.doctor?.video_url && 'cursor-pointer'
      );
    },


    videoIconClassName() {
      return classNames(
        this.doctor?.video_url ? 'text-action-primary' : 'text-icon-subdued'
      );
    },

    videoTextClassName() {
      return classNames(
        'font-medium border-b border-b-transparent text-sm/none lg:text-base/none',
        this.doctor?.video_url ? 'text-interactive group-hover:border-b-interactive' : 'text-subdued'
      )
    }
  },

  methods: {
    handleClose() {
      this.$emit('close')
    },

    handleShowVideo() {
      this.handleClose()

      eventBus.$emit('showVideoModal', this.doctor.video_url)
    },
  }
}
</script>
