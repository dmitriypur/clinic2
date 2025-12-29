<template>
  <div
    class="flex flex-col p-4 bg-surface-subdued border border-white rounded-[20px] h-full shadow-calendar overflow-hidden"
  >
    <div class="doctor__card relative h-full z-10">
      <span
        v-html="doctor.avatar_image"
        class="[&_img]:w-56 [&_img]:h-56 [&_img]:object-contain absolute -left-12 -bottom-6 z-0"
      ></span>
      <div class="doctor__card-header">
        <div class="header__top flex justify-between items-center">
          <div class="stars flex [&_svg]:w-4">
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3.28617 13.1078C2.95039 13.2781 2.56937 12.9797 2.63722 12.5987L3.35924 8.53105L0.294585 5.64499C0.00838807 5.37496 0.157141 4.88133 0.540766 4.82801L4.80153 4.22947L6.70139 0.508378C6.87276 0.172989 7.33642 0.172989 7.50779 0.508378L9.40765 4.22947L13.6684 4.82801C14.052 4.88133 14.2008 5.37496 13.9137 5.64499L10.8499 8.53105L11.572 12.5987C11.6398 12.9797 11.2588 13.2781 10.923 13.1078L7.10328 11.1677L3.28617 13.1078Z" fill="#F5841F"/>
            </svg>
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3.28617 13.1078C2.95039 13.2781 2.56937 12.9797 2.63722 12.5987L3.35924 8.53105L0.294585 5.64499C0.00838807 5.37496 0.157141 4.88133 0.540766 4.82801L4.80153 4.22947L6.70139 0.508378C6.87276 0.172989 7.33642 0.172989 7.50779 0.508378L9.40765 4.22947L13.6684 4.82801C14.052 4.88133 14.2008 5.37496 13.9137 5.64499L10.8499 8.53105L11.572 12.5987C11.6398 12.9797 11.2588 13.2781 10.923 13.1078L7.10328 11.1677L3.28617 13.1078Z" fill="#F5841F"/>
            </svg>
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3.28617 13.1078C2.95039 13.2781 2.56937 12.9797 2.63722 12.5987L3.35924 8.53105L0.294585 5.64499C0.00838807 5.37496 0.157141 4.88133 0.540766 4.82801L4.80153 4.22947L6.70139 0.508378C6.87276 0.172989 7.33642 0.172989 7.50779 0.508378L9.40765 4.22947L13.6684 4.82801C14.052 4.88133 14.2008 5.37496 13.9137 5.64499L10.8499 8.53105L11.572 12.5987C11.6398 12.9797 11.2588 13.2781 10.923 13.1078L7.10328 11.1677L3.28617 13.1078Z" fill="#F5841F"/>
            </svg>
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3.28617 13.1078C2.95039 13.2781 2.56937 12.9797 2.63722 12.5987L3.35924 8.53105L0.294585 5.64499C0.00838807 5.37496 0.157141 4.88133 0.540766 4.82801L4.80153 4.22947L6.70139 0.508378C6.87276 0.172989 7.33642 0.172989 7.50779 0.508378L9.40765 4.22947L13.6684 4.82801C14.052 4.88133 14.2008 5.37496 13.9137 5.64499L10.8499 8.53105L11.572 12.5987C11.6398 12.9797 11.2588 13.2781 10.923 13.1078L7.10328 11.1677L3.28617 13.1078Z" fill="#F5841F"/>
            </svg>
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3.28617 13.1078C2.95039 13.2781 2.56937 12.9797 2.63722 12.5987L3.35924 8.53105L0.294585 5.64499C0.00838807 5.37496 0.157141 4.88133 0.540766 4.82801L4.80153 4.22947L6.70139 0.508378C6.87276 0.172989 7.33642 0.172989 7.50779 0.508378L9.40765 4.22947L13.6684 4.82801C14.052 4.88133 14.2008 5.37496 13.9137 5.64499L10.8499 8.53105L11.572 12.5987C11.6398 12.9797 11.2588 13.2781 10.923 13.1078L7.10328 11.1677L3.28617 13.1078Z" fill="#F5841F"/>
            </svg>
          </div>
          <p class="recommend text-[10px] opacity-40">100% пациентов рекомендуют врача</p>
        </div>
        <p class="doctor__card-header--name text-lg font-bold mt-1">{{ doctor.name }}</p>
      </div>

      <div class="doctor__card-body flex flex-col mt-6 relative z-10">
        <ul class="doctor__card-info flex gap-1.5 flex-col ml-auto w-48">
          <li class="bg-white py-1.5 px-3.5 rounded-md text-interactive">
            <p class="text-[10px] font-normal leading-[100%] opacity-60">Специальность:</p>
            <p class="text-xs font-semibold leading-[14px]">{{ doctor.speciality }}</p>
          </li>
          <li class="bg-white py-1.5 px-3.5 rounded-md text-interactive">
            <p class="text-xs font-normal leading-[100%] opacity-60">Врачебный стаж:</p>
            <p class="text-[13px] font-semibold leading-4 mt-0.5">{{ doctor.seniority }}</p>
          </li>
          <li class="bg-white py-1.5 px-3.5 rounded-md text-interactive">
            <p class="text-xs font-normal leading-[100%] opacity-60">Ведёт приём:</p>
            <p class="text-[13px] font-semibold leading-4 mt-0.5">{{ doctor.receives }}</p>
          </li>
        </ul>

        <button
          :disabled="!doctor.video_url"
          @click="handleOpenVideo"
          :class="newButtonClassName"
        >
          <span class="flex justify-center items-center w-5 h-5 rounded-full bg-white mr-2">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="#FF8C00"
              stroke="none"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="lucide lucide-play ml-0.5"
            >
            <polygon points="5 3 19 12 5 21 5 3"></polygon>
          </svg>
          </span>
          <span>видеовизитка</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import {eventBus} from "@/eventBus";
import {classNames} from "@/utilities/css.js";

export default {
  props: {
    doctor: Object,
    selectedDate: Date,
  },

  computed: {
    buttonClassName() {
      return classNames('mt-2 inline-flex items-center text-sm', this.doctor.video_url ? 'text-[#FF8C00] hover:text-[#E67A00]' : 'cursor-default opacity-30')
    },
    newButtonClassName(){
      return classNames('mt-2.5 ml-auto inline-flex items-center text-sm font-medium orange-gr w-48 h-8 justify-center rounded-md text-white', this.doctor.video_url ? '' : 'cursor-default opacity-30')
    }
  },

  methods: {
    handleOpenVideo() {
      eventBus.$emit("showVideoModal", this.doctor.video_url);
    },
  },
};
</script>
