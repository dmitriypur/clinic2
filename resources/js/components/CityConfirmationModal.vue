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
        this.cities = window.config.cities;
        this.detectedCity = this.resolveDetectedCity(window.config.detectedCity);
        const currentCity = this.getCurrentCity();
        const cityConfirmed = Cookies.get('city_confirmed') || Cookies.get('selected_city');
        const hasDetectedCityMismatch = this.detectedCity
            && (!currentCity || this.detectedCity.slug !== currentCity.slug);

        if (hasDetectedCityMismatch) {
            this.showModal = true;
            this.step = 'confirm';
            return;
        }

        if (currentCity && currentCity.is_default === false) {
            return;
        }

        if (this.detectedCity && !cityConfirmed) {
            this.showModal = true;
            return;
        }

        if (!cityConfirmed && this.cities.length > 1) {
            this.fetchDetectedCity();
        }
    },

    methods: {
        getCurrentCity() {
            return this.cities.find((city) => city?.is_current) || null;
        },
        resolveDetectedCity(city) {
            if (!city) {
                return null;
            }

            const cityUrl = this.cities.find((item) => item.slug === city.slug)?.url || city.url;

            return {
                ...city,
                url: cityUrl,
            };
        },
        async fetchDetectedCity() {
            try {
                const params = new URLSearchParams(window.location.search);
                const testCity = params.get('test_city');
                const url = new URL('/city-detection', window.location.origin);

                if (testCity) {
                    url.searchParams.set('test_city', testCity);
                }

                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                this.detectedCity = this.resolveDetectedCity(payload?.detectedCity || null);
                window.config.detectedCity = this.detectedCity;

                if (this.detectedCity && !Cookies.get('city_confirmed') && !Cookies.get('selected_city')) {
                    this.showModal = true;
                    this.step = 'confirm';
                }
            } catch (e) {
                // Detection failure should not block the page.
            }
        },
        setCityCookies(citySlug = null) {
            const options = {
                expires: 365,
                path: '/',
            };

            Cookies.set('city_confirmed', 'true', options);

            if (citySlug) {
                Cookies.set('selected_city', citySlug, options);
            }
        },

        confirmCity() {
            this.showModal = false;
            this.setCityCookies(this.detectedCity?.slug || null);
            window.location.href = this.detectedCity.url;
        },

        rejectCity() {
            this.step = 'select';
        },

        selectCity(city) {
            this.showModal = false;
            this.setCityCookies(city?.slug || null);
            window.location.href = city.url;
        }
    }
};
</script>
