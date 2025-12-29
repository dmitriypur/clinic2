<template>
  <div v-if="doctor">
    <div class="flex flex-col rounded-xl p-4 lg:p-4 bg-white h-full shadow-xl">
      <div>
        <div class="header__top flex justify-between items-center">
          <div class="stars flex [&_svg]:w-5 [&_svg]:h-5">
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg"
                 class="mr-0.5">
              <path
                d="M3.28617 13.1078C2.95039 13.2781 2.56937 12.9797 2.63722 12.5987L3.35924 8.53105L0.294585 5.64499C0.00838807 5.37496 0.157141 4.88133 0.540766 4.82801L4.80153 4.22947L6.70139 0.508378C6.87276 0.172989 7.33642 0.172989 7.50779 0.508378L9.40765 4.22947L13.6684 4.82801C14.052 4.88133 14.2008 5.37496 13.9137 5.64499L10.8499 8.53105L11.572 12.5987C11.6398 12.9797 11.2588 13.2781 10.923 13.1078L7.10328 11.1677L3.28617 13.1078Z"
                fill="#F5841F"/>
            </svg>
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg"
                 class="mr-0.5">
              <path
                d="M3.28617 13.1078C2.95039 13.2781 2.56937 12.9797 2.63722 12.5987L3.35924 8.53105L0.294585 5.64499C0.00838807 5.37496 0.157141 4.88133 0.540766 4.82801L4.80153 4.22947L6.70139 0.508378C6.87276 0.172989 7.33642 0.172989 7.50779 0.508378L9.40765 4.22947L13.6684 4.82801C14.052 4.88133 14.2008 5.37496 13.9137 5.64499L10.8499 8.53105L11.572 12.5987C11.6398 12.9797 11.2588 13.2781 10.923 13.1078L7.10328 11.1677L3.28617 13.1078Z"
                fill="#F5841F"/>
            </svg>
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg"
                 class="mr-0.5">
              <path
                d="M3.28617 13.1078C2.95039 13.2781 2.56937 12.9797 2.63722 12.5987L3.35924 8.53105L0.294585 5.64499C0.00838807 5.37496 0.157141 4.88133 0.540766 4.82801L4.80153 4.22947L6.70139 0.508378C6.87276 0.172989 7.33642 0.172989 7.50779 0.508378L9.40765 4.22947L13.6684 4.82801C14.052 4.88133 14.2008 5.37496 13.9137 5.64499L10.8499 8.53105L11.572 12.5987C11.6398 12.9797 11.2588 13.2781 10.923 13.1078L7.10328 11.1677L3.28617 13.1078Z"
                fill="#F5841F"/>
            </svg>
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg"
                 class="mr-0.5">
              <path
                d="M3.28617 13.1078C2.95039 13.2781 2.56937 12.9797 2.63722 12.5987L3.35924 8.53105L0.294585 5.64499C0.00838807 5.37496 0.157141 4.88133 0.540766 4.82801L4.80153 4.22947L6.70139 0.508378C6.87276 0.172989 7.33642 0.172989 7.50779 0.508378L9.40765 4.22947L13.6684 4.82801C14.052 4.88133 14.2008 5.37496 13.9137 5.64499L10.8499 8.53105L11.572 12.5987C11.6398 12.9797 11.2588 13.2781 10.923 13.1078L7.10328 11.1677L3.28617 13.1078Z"
                fill="#F5841F"/>
            </svg>
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path
                d="M3.28617 13.1078C2.95039 13.2781 2.56937 12.9797 2.63722 12.5987L3.35924 8.53105L0.294585 5.64499C0.00838807 5.37496 0.157141 4.88133 0.540766 4.82801L4.80153 4.22947L6.70139 0.508378C6.87276 0.172989 7.33642 0.172989 7.50779 0.508378L9.40765 4.22947L13.6684 4.82801C14.052 4.88133 14.2008 5.37496 13.9137 5.64499L10.8499 8.53105L11.572 12.5987C11.6398 12.9797 11.2588 13.2781 10.923 13.1078L7.10328 11.1677L3.28617 13.1078Z"
                fill="#F5841F"/>
            </svg>
          </div>
          <a :href="doctor.url"
             class="flex items-center gap-x-2.5 py-1 px-3 border-[0.5px] border-interactive rounded-md">
            <svg width="14" height="18" viewBox="0 0 14 18" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path
                d="M10.3191 8.28562C10.3191 9.14562 9.96974 9.9704 9.34791 10.5785C8.72608 11.1866 7.88269 11.5283 7.00329 11.5283C6.12389 11.5283 5.2805 11.1866 4.65867 10.5785C4.03684 9.9704 3.6875 9.14562 3.6875 8.28562C3.6875 7.42561 4.03684 6.60083 4.65867 5.99272C5.2805 5.3846 6.12389 5.04297 7.00329 5.04297C7.88269 5.04297 8.72608 5.3846 9.34791 5.99272C9.96974 6.60083 10.3191 7.42561 10.3191 8.28562Z"
                fill="#1F3462"/>
              <path
                d="M14 15.1324V4.86397L8.75 0H2.33333C1.71449 0 1.121 0.227757 0.683417 0.633166C0.245833 1.03858 0 1.58843 0 2.16176V15.1324C0 15.7057 0.245833 16.2555 0.683417 16.6609C1.121 17.0664 1.71449 17.2941 2.33333 17.2941H11.6667C12.2855 17.2941 12.879 17.0664 13.3166 16.6609C13.7542 16.2555 14 15.7057 14 15.1324ZM8.75 3.24265C8.75 3.67265 8.93437 4.08504 9.26256 4.3891C9.59075 4.69315 10.0359 4.86397 10.5 4.86397H12.8333V14.8675C12.8333 14.8675 11.6667 12.9706 7 12.9706C2.33333 12.9706 1.16667 14.8675 1.16667 14.8675V2.16176C1.16667 1.8751 1.28958 1.60017 1.50838 1.39747C1.72717 1.19476 2.02391 1.08088 2.33333 1.08088H8.75V3.24265Z"
                fill="#1F3462"/>
            </svg>
            <p class="text-es"><b>Карточка</b> врача</p>
          </a>
        </div>
        <p class="doctor__card-header--name text-2xl leading-6 mt-2">
          <span class="font-bold">{{ doctor.surname }}</span>
          <br>{{ doctor.name }}</p>
      </div>
      <div class="flex flex-col mt-4 relative z-0">
        <ul class="flex gap-2.5 flex-col">
          <li
            class="bg-blue-superlite py-3 pr-3 pl-6 rounded-md relative before:absolute before:h-full before:w-3 before:blue-gradient-nohover before:top-0 before:left-0 before:rounded">
            <p class="text-es font-bold">Специальность:</p>
            <p class="text-xs leading-4">{{ doctor.speciality }}</p>
          </li>
          <li class="flex gap-4">
            <span class="text-[8px] max-w-20 text-interactive/60 self-center">100% пациентов рекомендуют врача</span>
            <span
              class="py-3 pr-3 pl-6 w-full rounded-md bg-blue-superlite relative before:absolute before:h-full before:w-3 before:orange-gr-nohover before:top-0 before:left-0 before:rounded">
                        <p class="text-es font-bold">Врачебный стаж:</p>
                        <p class="text-xs leading-4">{{ doctor.extra.seniority }}</p>
                    </span>
          </li>
          <li
            class="bg-blue-superlite py-3 pr-3 pl-6 rounded-md relative before:absolute before:h-full before:w-3 before:superlight-blue-gradient before:top-0 before:left-0 before:rounded">
            <p class="text-es font-bold">Ведёт приём:</p>
            <p class="text-xs leading-4">{{ doctor.extra.receives }}</p>
          </li>
        </ul>
        <div class="flex w-full gap-2 relative mt-2.5">
          <div class="absolute max-w-40 bottom-5 right-0">
            <img :src="doctor.avatar" alt="">
          </div>

          <button
            v-if="doctor.video_url"
            @click="showVideoModal(doctor.video_url)"
            class="absolute -bottom-4 right-0 flex justify-center items-center w-40 h-10 rounded-lg text-sm z-10 blue-gradient-nohover">
                        <span
                          class="flex justify-center items-center w-5 h-5 rounded-full bg-white mr-2">
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              width="12"
                              height="12"
                              viewBox="0 0 24 24"
                              fill="#3981F1"
                              stroke="none"
                              stroke-width="2"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              class="lucide lucide-play ml-0.5"
                            >
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                          </svg>
                        </span>
            <span class="text-es font-semibold text-white">видео-визитка</span>
          </button>

          <div
            v-else
            class="absolute -bottom-4 right-0 bg-surface-subdued flex justify-center gap-1.5 items-center z-10 w-40 h-10 rounded-lg text-sm">
                            <span class="text-icon-subdued">
                                <svg viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                     class="w-5 fill-current">
                                    <path
                                      d="M12.0527 24C18.6802 24 24.0527 18.6274 24.0527 12C24.0527 5.37258 18.6802 0 12.0527 0C5.42532 0 0.0527344 5.37258 0.0527344 12C0.0527344 18.6274 5.42532 24 12.0527 24Z"/>
                                    <path
                                      d="M10.1174 15.9307L15.6535 12.6743C16.1858 12.3374 16.1858 11.6637 15.6535 11.3268L10.1174 8.07035C9.69152 7.84576 9.05273 8.18264 9.05273 8.74409V15.1447C9.05273 15.8184 9.58505 16.1553 10.1174 15.9307Z"
                                      fill="white"/>
                                </svg>
                            </span>
            <span
              class="text-es font-semibold">видео-визитка</span>
          </div>
        </div>

      </div>
      <div class="mt-auto relative z-10">
        <button
          @click="showCallbackModal(null, 'otpravka-formy')"
          class="w-full py-3 px-6 btn-gradient font-semibold text-white rounded-xl font-bold mt-7 py-3.5 text-lg md:px-0 blue-gradient text-white rounded-lg">
          Записаться на приём
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import {eventBus} from '../../eventBus';

export default {
  name: 'DoctorCard',
  props: {
    doctor: {
      type: Object,
      required: true
    }
  },
  methods: {
    showCallbackModal(event, goal) {
      eventBus.$emit('showCallbackModal', event, goal);
    },
    showVideoModal(url) {
      eventBus.$emit('showVideoModal', url);
    }
  }
}
</script>
