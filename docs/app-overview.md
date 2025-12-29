# Zrenie Clinic — контекст и архитектура приложения

## Назначение
- Медицинский сайт клиники: контентные страницы, услуги, блог, отзывы, профиль пользователя.
- Админ-панель на Filament 3 для управления контентом.
- Интеграция с UNF API для записи, обратных звонков, цен и расписаний.

## Технологии
- Laravel, Eloquent, Spatie Media Library, Spatie Laravel Settings.
- Filament 3 (+ Shield, Navigation).
- Vue 2.7 + Vite, TailwindCSS.
- Кеширование через `Cache`.

## Точки входа
- Web-маршруты: `routes/web.php:20–76`
  - Поиск: `GET /search`, `GET /live-search` → `app/Http/Controllers/SearchController.php:8–30`.
  - Профиль (`auth:web`): `GET/PUT /profile/*` → `app/Http/Controllers/Profile/*`.
  - Контент: `GET /{category}/{handle?}`, `GET /{handle?}` → `app/Http/Controllers/PageController.php:20–39`.
  - Роботы/сайтмап/YML: `robots.txt` (`app/Http/Controllers/RobotsTxtController.php:12–17`), `sitemap.html` (`app/Http/Controllers/SitemapHtmlController.php:13–31`), `yml-feed/*`.
- API-маршруты: `routes/api.php:13–23`
  - Расписание: `GET /api/schedule` → `app/Http/Controllers/ScheduleController.php:15–23`.
  - Запись: `POST /api/making-an-appointment` → `app/Http/Controllers/MakingAnAppointmentController.php:13–41`.
  - Callback: `POST /api/callback` → `app/Http/Controllers/CallbackController.php:13–31`.
  - Отзыв: `POST /api/review` → `app/Http/Controllers/Review/ReviewController.php:12–22`.
  - Врач по ULID: `GET /api/doctors/{doctor:ulid}` → `app/Http/Controllers/Api/DoctorController.php:13–18`.

## Контроллеры
- Страница: выбирает `pages.show` или `posts.show` по типу страницы, собирает данные врачей/цен, SEO из сервиса:
  - `app/Http/Controllers/PageController.php:20–39`, `128–177`.
  - SEO/кеш страниц: `app/Services/PageService.php:96–110`.
- Врач: `app/Http/Controllers/DoctorController.php:15–22`.
- Расписание: `app/Http/Controllers/ScheduleController.php:15–23` (данные из `app/Services/ScheduleService.php:9–40`).
- Запись: `app/Http/Controllers/MakingAnAppointmentController.php:13–41`.
- Callback: `app/Http/Controllers/CallbackController.php:13–31`.
- Блог/каталог: `app/Http/Controllers/Api/PostController.php:15–55`.
- Отзывы: `app/Http/Controllers/Api/ReviewController.php:15–48`.
- Поиск: `app/Http/Controllers/SearchController.php:8–30`.

## Модели
- `Page` (контент, тип, SEO, блоки): `app/Models/Page.php`.
- `Block`, `Tag`, `Category`.
- `Service` (услуга, media, цены через внешний API).
- `Doctor` (ULID, media, SEO, `fullName`, `url`): `app/Models/Doctor.php`.
- `Review`, `User`, `Staff` (для Filament guard).

## Сервисы и интеграции
- UNF API: `app/Clinic.php`, конфиг: `config/zrenie-clinic.php:3–20`.
  - Методы: запись, callback, форма, профиль, расписание, цены.
  - Клиент: `Http::baseUrl(...)->withHeaders(...)`.
- Кеш цен: `Clinic::prices()` (30 дней): `app/Clinic.php:49–55`.
- Расписание: сопоставление UNF с локальными врачами по `uuid`:
  - `app/Services/ScheduleService.php:9–40`.
- Альтернативный сервис цен: `app/Services/PriceService.php`.

## Vue фронтенд
- Vite конфиг: `vite.config.js` (Vue2 plugin, alias `@ -> /resources/js`, chunking).
- Вход: `resources/js/app.js` — регистрация компонентов, lazy imports, плагины (`vue-yandex-maps`, `vue-the-mask`, `v-calendar`, `vue-lazyload`, `vue-observe-visibility`).
- Ключевые компоненты:
  - Онлайн запись: `resources/js/components/OnlineAppointmentForm/OnlineAppointmentForm.vue` — `GET /api/schedule` → выбор → `POST /api/making-an-appointment`.
  - Callback: `resources/js/components/CallbackForm/CallbackForm.vue` — `POST /api/callback`.
  - Карта: `resources/js/components/Map/Map.vue` — Yandex API (`window.YANDEX_API_KEY`).
- Интеграция в Blade: через `@vite` и `inline-template`.

## Макеты/Blade
- Главный макет: `resources/views/layouts/app.blade.php:37` (подключение `@vite`), `64–67` (`window.config`), `71–90` (шапка), `255–265` (модалки).
- Страницы:
  - `resources/views/pages/show.blade.php:1–17` (canonical/noindex, header scripts), `37–42` (schema), блоки.
  - `resources/views/posts/show.blade.php:1–19` (canonical/noindex), `38–40` (schema), хлебные крошки и блоки.

## Админка (Filament)
- Провайдер: `app/Providers/Filament/AdminPanelProvider.php:19–63` (`id('admin')`, `path('admin')`, `authGuard('staff')`, плагины).
- Guard/модель: `config/auth.php:38–48`, `providers.staff:73–76`; модель `app/Models/Staff.php:32–36`.
- Ресурсы: `app/Filament/Resources/*` (Pages, RelationManagers).
- Настройки: `ManageGeneralSettings.php`, `ManageSeoSettings.php` → `app/Settings/*`.

## Кеширование
- Врачи: `Cache::remember('doctors', 3600)` в `ScheduleService`.
- Страницы/блоки: `PageService::findPageWithBlocks` кеш `page_{category}_{handle|index}`.
- Цены/услуги: `services-with-prices` (30 дней) — `PageController`, `PriceService`.
- Инвалидизация:
  - Врачи: `app/Models/Doctor.php:74–81`.
  - Страницы: `PageService::clearPageCache()` — точечные ключи.

## SEO/Schema.org
- Настройки: `SeoSettings`, scripts/header_scripts.
- Генератор: `app/Services/Schema/Schema.php` — `localBusiness`, `medicalOrganization`, `medicalScholarlyArticle`, `physician`, `offer`, `review`.
- Вью добавляет canonical/noindex и схемы (см. раздел Макеты/Blade).

## API справочник
- `GET /api/schedule` → `{ doctors: [ { id, uuid, name, speciality, avatar_image, cells, receives, seniority } ] }`.
- `POST /api/making-an-appointment` → `doctorId`, `date`, `time`, ФИО, `phone` → UNF `events?action=newrecord`.
- `POST /api/callback` → `name`, `phone`, цель метрики → UNF `events?action=callrequest`.
- `POST /api/review` → `name`, `body`, `rating` → создает `Review`.
- `GET /api/doctors/{doctor:ulid}` → `DoctorResource`.

## Типовые сценарии
- Добавить страницу:
  1) В Filament `PageResource` создать `Page` с SEO/тип/категория/блоки.
  2) Доступно по `/{category}/{handle}` или `/{handle}` через `PageController`.
- Добавить услугу с ценами:
  1) Создать `Service`, привязать media.
  2) Внешний массив цен (`Clinic::prices()`) должен содержать `uid` услуги и `items` → блок цен отрендерится.
- Меню: `FilamentNavigation` с типами пунктов (“Страница”, “Врачи”).
- Расписание: источник UNF (`Clinic::schedule()`), сопоставление по `Doctor.uuid`.

## Переменные окружения
- `CLINIC_UUID`, `UNF_BASE_URL`, `LO_TOKEN` — `config/zrenie-clinic.php`.
- `SMS_AERO_USER_LOGIN`, `SMS_AERO_API_KEY` — при необходимости для SMS.

## Сборка/команды
- Front: `npm run dev`, `npm run build` (`package.json`).
- Планировщик: генерация sitemap ежедневно (`app/Console/Kernel.php:12–16`).
- Vite алиасы и чанки: `vite.config.js`.

## Нюансы
- Поиск: сгруппировать `orWhere` чтобы не размывать `active=1` (`app/Http/Controllers/SearchController.php:8–30`).
- Кеш: избегать глобального `Cache::flush()`, использовать точечные ключи (`PageService::clearPageCache()`).
