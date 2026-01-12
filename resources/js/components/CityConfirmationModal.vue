<template>
    <div v-if="showModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl p-6 md:p-8 text-center max-w-md w-full">
            <template v-if="step === 'confirm'">
                <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">Ваш город — {{ detectedCity.name }}?</h3>
                <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                    <button @click="confirmCity" class="w-full sm:w-auto btn btn-primary px-6 py-3">
                        Да, верно
                    </button>
                    <button @click="rejectCity" class="w-full sm:w-auto btn btn-secondary-outline px-6 py-3">
                        Выбрать другой
                    </button>
                </div>
            </template>

            <template v-if="step === 'select'">
                <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">Выберите ваш город</h3>
                <ul class="text-left max-h-60 overflow-y-auto">
                    <li v-for="city in cities" :key="city.id">
                        <a :href="city.url" @click.prevent="selectCity(city)"
                           class="block py-2 px-3 hover:bg-gray-100 rounded-md text-lg">
                            {{ city.name }}
                        </a>
                    </li>
                </ul>
            </template>
        </div>
    </div>
</template>

<script>
import Cookies from 'js-cookie';

export default {
    name: "CityConfirmationModal",

    data() {
        return {
            showModal: false,
            step: 'confirm', // 'confirm' or 'select'
            detectedCity: null,
            cities: [],
        };
    },

    mounted() {
        this.detectedCity = window.config.detectedCity;
        this.cities = window.config.cities;
        const cityConfirmed = Cookies.get('city_confirmed');

        if (this.detectedCity && !cityConfirmed) {
            this.showModal = true;
        }
    },

    methods: {
        setCookie() {
            Cookies.set('city_confirmed', 'true', { expires: 365 });
        },

        confirmCity() {
            this.setCookie();
            window.location.href = this.detectedCity.url;
        },

        rejectCity() {
            this.step = 'select';
        },

        selectCity(city) {
            this.setCookie();
            window.location.href = city.url;
        }
    }
};
</script>
