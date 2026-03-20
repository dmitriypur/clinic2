# APP_CONTEXT

Актуальный контекст проекта для быстрого входа в работу без полного повторного анализа кода.

## Что это за проект
- Медицинский сайт клиники `zrenie.clinic`.
- Основные пользовательские сценарии:
  - просмотр контентных страниц и страниц услуг;
  - просмотр врачей и отзывов;
  - поиск по сайту;
  - онлайн-запись на приём;
  - заказ обратного звонка;
  - работа с пользовательским профилем;
  - просмотр/генерация служебных фидов и sitemap.
- Отдельно есть админ-панель на Filament для управления контентом, услугами, врачами, городами, отзывами, меню и настройками.

## Что изменилось (последнее)
- 2026-03-20: устранён регресс первого открытия doctor-flow в `BookingWidgetV3`: второй шаг больше не может открыться пустым из-за гонки между модалкой и загрузкой списка городов booking API; `initCities()` теперь дедуплицирует in-flight запрос, а `openDoctorFlow()`, `loadDoctorsByCity()`, `loadClinics()` и `loadCityBranches()` всегда дожидаются готовности городов перед вычислением `currentCityId`.
- 2026-03-20: у специалистов добавлено отдельное поле `doctors.page_sort_order` для публичной страницы списка врачей; в админке `Специалисты` оно доступно и в форме, и inline в таблице, а `PageController::getDoctorsForPage()` теперь сортирует врачей как `page_sort_order ASC NULLS LAST`, затем по `id`, сохраняя порядок по умолчанию для записей без ручной сортировки.
- 2026-03-20: для врачей в `Настройках виджета` добавлен раздельный порядок по двум сценариям: `Выбрать врача` и `Выбрать клинику`; в `city_doctor` теперь используются `sort_order` для второго шага doctor-flow и `clinic_sort_order` для третьего шага clinic-flow, а `BookingWidgetOrderingService`, `Clinic::scriptVariables()` и `resources/js/services/bookingApi.js` отдают/применяют две отдельные order-map; в clinic-flow врачи с явным `clinic_sort_order` всегда идут выше fallback-порядка из doctor-flow.
- 2026-03-20: исправлен production-регресс админки Filament: nginx в проде отдаёт любые пути `*.js` как статические и не пропускает route-based Livewire asset `/livewire/livewire.min.js` в Laravel, из-за чего login-страница деградировала до обычного `POST /admin/login` и падала `405 Method Not Allowed`; временно на сервере опубликованы `public/vendor/livewire` assets, а в `Envoy.blade.php` деплой теперь всегда выполняет `php artisan livewire:publish --assets --no-interaction`, чтобы Filament использовал статический `vendor/livewire/*.js`.
- 2026-03-20: ускорена админская страница `Настройки виджета`: таблица филиалов больше не блокирует первый рендер синхронным вызовом booking API в `mount()`, а запускает sync через `wire:init` уже после показа локальных данных; сама синхронизация филиалов теперь забирает ветки клиник параллельно и пишет их в БД через batch `upsert`, а таблица врачей выбирает только нужные поля вместо `doctors.*`.
- 2026-03-20: добит цикл popup выбора города после подтверждения/переключения: `App\Clinic::scriptVariables()` теперь не прокидывает stale `detectedCity` при наличии `city_confirmed`/`selected_city`, очищает session `detected_city` и убирает `test_city` из сгенерированных городских URL; `CityDetectionController` тоже забывает `detected_city`, если город уже подтверждён.
- 2026-03-20: исправлен цикл popup выбора города при переключении с дефолтной Москвы на другой город: `App\Clinic::scriptVariables()` теперь передаёт в `window.config.cities` признак `is_default`, а `CityConfirmationModal` закрывает popup только для явно non-default текущего города; `CitySwitcher` и `CityConfirmationModal` до навигации ставят `city_confirmed` и `selected_city`, поэтому после выбора города popup не должен открываться повторно; при отсутствии geo-detection сайт остаётся в дефолтной Москве без fallback-popup.
- 2026-03-20: для `BookingWidgetV3` добавлена минимальная сортировка без изменения логики компонента: врачи сортируются по `city_doctor.sort_order` отдельно по городу, филиалы — по локальной таблице `booking_widget_branch_orders`, а order-map текущего города прокидывается из backend в `window.config.booking`, после чего `resources/js/services/bookingApi.js` применяет стабильную сортировку к уже привычным ответам внешнего booking API.
- 2026-03-20: в Filament добавлена отдельная страница `Настройки виджета` (`/admin/booking-widget-settings`) с выбором города и двумя inline-таблицами: порядок врачей и порядок филиалов; список филиалов подтягивается из booking API автоматически, без ручной синхронизации кнопкой.
- 2026-03-20: city SEO-переменные теперь резолвятся не только у `Page`, но и централизованно у связанных `Block`: подстановка работает в `title`, `body_html` и строковых значениях `payload` на публичных страницах/постах без изменения данных в БД.
- 2026-03-20: исправлен рендер `target` и `download` в шаблонах footer-меню; нижнее меню теперь корректно применяет `target = _blank` и скачивание файлов как для верхних, так и для дочерних пунктов.
- 2026-03-20: исправлен рендер `target` в Blade-шаблонах меню; теперь для пунктов типа `Ссылка` значение `target` применяется не только к верхнему уровню, но и к dropdown/mega-menu ссылкам.
- 2026-03-20: в админском конструкторе меню (`Filament Navigation`) добавлен отдельный тип пункта `Файл`; файл скачивается через отдельный маршрут `GET /menu-files/download/{encodedPath}` с ответом `attachment`, а не открывается прямым URL из `/storage`.
- 2026-03-19: создана базовая AI-документация для текущего проекта:
  - `AGENTS.md`
  - `README_AI_TASK.md`
  - `docs/APP_CONTEXT.md`
- 2026-03-19: ссылка «Перезвоните мне» в новом header (`x-phone-new`) переведена на отдельную модалку с `CallbackFormNew`, без зависимости от `booking.formVariant`.

## Технологии
- Backend: `PHP 8.1`, `Laravel 10.49.1`
- Frontend: `Blade`, `Vue 2.7`, `Vite 5`, `TailwindCSS 3`
- Admin: `Filament 3.3.43`
- Пакеты и подсистемы:
  - `spatie/laravel-media-library`
  - `spatie/laravel-settings`
  - `spatie/laravel-sitemap`
  - `bezhansalleh/filament-shield`
  - `ryangjchandler/filament-navigation`
  - `sirodiaz/laravel-redirection`
- Карты во frontend: `vue-yandex-maps`

## Архитектурный срез
- Монолитное Laravel-приложение без Inertia.
- HTML рендерится через Blade-шаблоны.
- Интерактивные части сайта подключаются через Vue 2-компоненты внутри общего frontend-бандла.
- Есть мультигород:
  - город определяется из URL-префикса, cookies или GeoIP;
  - часть маршрутов помечена как глобальная и работает без городского префикса;
  - контент фильтруется по текущему городу через `HasCityScope`.
- Есть внешняя интеграция с UNF API через класс `App\Clinic`.
- Есть отдельное integration API для чтения/изменения дерева услуг.

## Структура домена
### Контент
- `Page`:
  - контентные страницы;
  - тип страницы (`PageType`);
  - SEO-поля;
  - связь с `Category`, `Block`, `Tag`, `Review`, `City`.
- `Block`:
  - строительные блоки страницы;
  - тип блока (`BlockType`);
  - payload/settings;
  - связь с `Page` и `City`;
  - может подтягивать услуги/цены и врачей.
- `Category`:
  - категории страниц;
  - используются в URL вида `/{category}/{handle}`.
- `Tag`:
  - теги для страниц/статей.

### Медицинский каталог
- `Doctor`:
  - врач с ULID;
  - media;
  - SEO;
  - city scope;
  - `page_sort_order` для ручного порядка на публичной странице специалистов;
  - используется в публичных страницах, расписании, отзывах и YML-фиде.
- `BookingWidgetBranchOrder`:
  - локальная таблица порядка филиалов для нового виджета записи;
  - хранит `city_id + clinic_id + branch_id + clinic_name + title + sort_order`;
  - используется только в админке и proxy-эндпоинтах `booking-widget`.
- `Review`:
  - отзывы;
  - могут быть связаны с врачом, страницами и городами;
  - поддерживают фильтрацию.
- `Service`:
  - древовидная структура услуг;
  - родительские категории и дочерние услуги;
  - может быть привязана к городам.
- `ServicePrice`:
  - цена услуги;
  - цена либо глобальная (`city_id = null`), либо городская.

### Пользователи и админка
- `User`:
  - обычный пользователь сайта;
  - профиль, телефон, email, дата рождения, согласия.
- `Staff`:
  - пользователь админки Filament;
  - guard `staff`;
  - роли и права через Shield.

### Мультигород
- `City`:
  - список городов;
  - один город может быть `is_default`;
  - содержит контакты, адреса, соцсети, branches, SEO cases, scripts.
- Pivot-таблицы:
  - `city_page`
  - `city_doctor`
  - `city_service`
  - `city_promotion`
  - `city_block`
  - `city_review`
- В `city_doctor` для виджета записи добавлен nullable `sort_order`.
- В `city_doctor` для виджета записи добавлены nullable-поля:
  - `sort_order` — порядок ветки `Выбрать врача`
  - `clinic_sort_order` — порядок списка врачей на шаге ветки `Выбрать клинику`

## Мультигород
### Основная логика
- `App\Http\Middleware\SetCityMiddleware`:
  - определяет город из `{city}` в роуте;
  - делает 301-редирект с дефолтного города на URL без префикса;
  - запоминает выбранный город в cookies;
  - для глобальных путей хранит город в cookie и переключает его через `force_city`;
  - при первом заходе без подтверждения города может определить город по IP;
  - шарит в Blade `currentCity` и список `cities`.

### Сервис
- `App\Services\CityService`:
  - хранит текущий город в рамках запроса;
  - кеширует:
    - `default_city`
    - `active_cities`
    - `city_by_slug_{slug}`
  - добавляет городской префикс в URL через `addCityPrefix()`;
  - знает список глобальных путей:
    - `stati`
    - `directory`
    - `tags`
    - `search`
    - `live-search`

### Автоопределение города
- `App\Services\GeoIpService`
- Внешний сервис: `http://api.sypexgeo.net/json/{ip}`
- Локальные IP (`127.0.0.1`, `::1`) игнорируются.

### Автоматическая фильтрация данных
- Трейт `App\Models\Traits\HasCityScope`:
  - на публичных web-запросах ограничивает выдачу текущим городом;
  - если запись не привязана ни к одному городу, она считается глобальной;
  - в админке и консольных командах scope не применяется;
  - если текущий город не определён, остаются только глобальные записи.

## Маршруты
### Web
Файл: `routes/web.php`

#### Глобальные маршруты
- `GET /clear-price`
- домен `form.{APP_HOST}`:
  - `GET /robots.txt`
  - `GET /`
  - `POST /`
- `GET /robots.txt`
- `POST /login`
- `POST /logout`
- `GET /menu-files/download/{encodedPath}`
- профиль под `auth`:
  - `GET /profile`
  - `PUT /profile`
  - `GET /profile/bonuses`
  - `GET /profile/history`
  - `GET /profile/notifications`
- YML:
  - `POST /admin/yml-feed/generate`
  - `GET /admin/yml-feed/download/{filename}`
  - `GET /yml-feed/doctors`

#### Контентные маршруты
Один и тот же набор работает:
- с префиксом `/{city}` для активных городов;
- и без префикса для дефолтного/глобального контекста.

Публичные маршруты:
- `GET /search`
- `GET /live-search`
- `GET /reviews`
- `GET /stati`
- `GET /directory`
- `GET /tags`
- `GET /tags/{handle?}`
- `GET /sitemap.xml`
- `GET /sitemap.html`
- `GET /call-request`
- `GET /doctors/{handle}`
- `GET /{category}/{handle?}`
- `GET /{handle?}`

### API
Файл: `routes/api.php`

- `GET /api/review-filter`
- `GET /api/doctors/{doctor:ulid}`
- `GET /api/booking/doctors`
- `GET /api/schedule`
- `POST /api/making-an-appointment`
- `POST /api/callback`
- `POST /api/review`
- `POST /api/send-verification-code`
- `PUT /api/user`
- `PUT /api/user/reset-password`

#### Integration API по услугам
Префикс: `/api/integrations/services`

Middleware:
- `services.integration`
- `services.integration.city`

Методы:
- `GET /tree`
- `GET /parents`
- `GET /search`
- `GET /children-by-title`
- `POST /preview`
- `GET /{uuid}/children`
- `POST /apply`
- `GET /{uuid}`

## Контроллеры и сценарии
### Контент
- `PageController`
  - ищет страницу по handle;
  - кеширует страницу с блоками;
  - определяет view:
    - `pages.show`
    - `posts.show`
  - добавляет SEO-данные;
  - подгружает врачей для страниц типа doctors;
  - подгружает прайс для страниц с `is_price_page = true`.
- `DoctorController`
  - публичная страница врача.
- `SearchController`
  - полнотекстовый поиск по `Page.title`, `Page.body_html`, `Block.body_html`, `Block.payload`;
  - есть обычный и live-search endpoint.

### Формы и интеграции
- `MakingAnAppointmentController`
  - принимает форму записи;
  - отправляет данные в UNF через `Clinic::makingAnAppointment()`.
- `CallbackController`
  - создаёт/обновляет пользователя по телефону;
  - отправляет callback в UNF через `Clinic::callback()`.
- `FormController`
  - отдельная форма на поддомене `form.*`.
- `ScheduleController`
  - возвращает JSON с расписанием врачей.

### Отзывы
- `App\Http\Controllers\Api\ReviewController`
  - рендерит страницу отзывов или JSON-пагинацию;
  - использует `ReviewFilter`.
- `App\Http\Controllers\Review\ReviewController`
  - создаёт отзыв из формы.

### YML / robots / sitemap
- `YmlFeedController`
- `RobotsTxtController`
- `FormRobotsTxtController`
- `SitemapHtmlController`

## Сервисы и интеграции
### UNF API
- Класс: `App\Clinic`
- Конфиг: `config/zrenie-clinic.php`
- Используемые методы:
  - `prices()`
  - `schedule()`
  - `makingAnAppointment()`
  - `callback()`
  - `sendForm()`
  - `getUser()`
- Базовый URL: `UNF_BASE_URL`
- Заголовок авторизации: `X-LO-Token`

### Логика услуг и цен
- `ServicePriceService`
  - основной сервис для работы с услугами и ценами из БД;
  - возвращает дерево услуг с учётом текущего города;
  - выбирает сначала городскую цену, затем глобальную;
  - кеш: `services-with-prices-{slug}`.
- `PriceService`
  - legacy-слой для старого источника цен через `Clinic::prices()`;
  - кеш:
    - `services-with-prices`
    - `clinic-prices`.
- `ServiceIntegrationService`
  - даёт внешнему агенту API для чтения и изменения услуг;
  - поддерживает операции:
    - `create_service`
    - `update_service`
    - `delete_service`
    - `upsert_price`
    - `delete_price`
  - умеет работать в `dry_run`.

### Прочие сервисы
- `BookingWidgetApiService`
  - тонкий cached-client к внешнему booking API (`adminzrenie.ru/api/v1`) для синхронизации филиалов в админке.
- `BookingWidgetOrderingService`
  - собирает `doctorSortOrders` и `branchSortOrders` для текущего города;
  - order-map прокидывается в `window.config.booking` через `Clinic::scriptVariables()`.
- `BookingWidgetBranchSyncService`
  - подтягивает филиалы из booking API в локальную таблицу `booking_widget_branch_orders` для выбранного города;
  - фильтрует клиники по `BOOKING_ALLOWED_CLINIC_IDS`;
  - вызывается из админской страницы `Настройки виджета` автоматически после первого рендера;
  - забирает ветки клиник параллельно и обновляет локальную таблицу через `upsert`, без серии `updateOrCreate`.
- `MenuService`
  - фильтрует и готовит меню под текущий город;
  - добавляет городские URL;
  - умеет собирать doctor-grid элементы.
- `ScheduleService`
  - формирует API-ответ по расписанию врачей.
- `GeoIpService`
- `PageService`
- `PhoneService`
- `SmsAeroService`
- `YmlFeedService`
- `ArticleImportService` и связанные классы для импорта статей из Google Docs.

## Frontend
### Основной стек
- `resources/js/app.js`
- `Vue 2`
- Vite через `@vitejs/plugin-vue2`

### Подключённые библиотеки
- `swiper`
- `glightbox`
- `vue-image-lightbox`
- `vue-the-mask`
- `v-calendar`
- `vue-yandex-maps`
- `vue-lazyload`
- `vue-observe-visibility`

### Ключевые Vue-компоненты
- `OnlineAppointmentForm`
- `BookingWidgetV3`
- `CallbackForm`
- `CallbackFormNew`
- `CallbackModal`
- `CallbackModalNew`
- `LoginModal`
- `DoctorModal`
- `CitySwitcher`
- `CityConfirmationModal`
- `SearchLive`
- `InfiniteDoctorsList`
- `AccessibilityToggle`

### BookingWidgetV3
- Логика `resources/js/components/BookingWidgetV3/BookingWidgetV3.vue` сохранена как на `main`.
- Из frontend-слоя изменён только `resources/js/services/bookingApi.js`:
  - `getDoctorsByCity()`
  - `getClinicBranches()`
  - `getClinicDoctors()`
- Эти методы по-прежнему ходят напрямую во внешний booking API, но перед возвратом применяют сортировку по backend order-map текущего города.

### Blade-макет
- Основной layout: `resources/views/layouts/app.blade.php`
- Что делает:
  - подключает `@vite`;
  - формирует `window.config` через `Clinic::scriptVariables()`;
  - вставляет глобальные и city-specific scripts;
  - подключает header/footer;
  - монтирует Vue-компоненты модалок и форм;
  - одновременно поддерживает legacy-форму записи, новый `BookingWidgetV3` и отдельную callback-модалку на `CallbackFormNew`.
- Для публичного рендера блоков city-переменные (`{city}`, `{city_phone}` и др.) теперь подставляются централизованно в модели `Page`/`Block`, поэтому Blade-шаблоны блоков получают уже обработанные `title`, `body_html` и строковый `payload`.

## Админка
- Провайдер: `App\Providers\Filament\AdminPanelProvider`
- Путь: `/admin`
- Guard: `staff`
- Плагины:
  - `Filament Shield`
  - `Filament Navigation`
    - кастомные типы пунктов меню: `Страница`, `Врачи`, `Файл`, `JS`
- Основные ресурсы:
  - `PageResource`
  - `PagePostResource`
  - `PageServiceResource`
  - `BlockResource`
  - `DoctorResource`
  - `ServiceResource`
  - `CityResource`
  - `ReviewResource`
  - `CategoryResource`
  - `TagResource`
  - `PromotionResource`
  - `NavigationResource`
  - `StaffResource`
  - `ElementResource`
- Страницы настроек:
  - `ManageGeneralSettings`
  - `ManageSeoSettings`
  - `PublicFileManager`
  - `BookingWidgetSettings`

## Настройки и env
### Основные env/config
- `config/zrenie-clinic.php`
  - `CLINIC_UUID`
  - `UNF_BASE_URL`
  - `LO_TOKEN`
  - `BOOKING_API_BASE_URL`
  - `BOOKING_ALLOWED_CLINIC_IDS`
  - `SMS_AERO_USER_LOGIN`
  - `SMS_AERO_API_KEY`
- `config/services-integration.php`
  - `SERVICES_INTEGRATION_TOKEN`
  - `SERVICES_INTEGRATION_DEFAULT_CITY_SLUG`
  - `SERVICES_INTEGRATION_ALLOWED_IPS`
- `config/settings.php`
  - зарегистрированы `GeneralSettings` и `SeoSettings`

### Что прокидывается во frontend
`Clinic::scriptVariables()` формирует:
- `csrfToken`
- `env`
- `baseUrl`
- `state`
- `detectedCity`
- `cities`
- `booking.allowedClinicIds`
- `booking.formVariant`
- `booking.doctorSortOrders`
- `booking.branchSortOrders`

`booking.formVariant` влияет на сценарий `showCallbackModal()` для кнопок записи, но ссылка «Перезвоните мне» в `resources/views/components/phone-new.blade.php` открывает отдельную модалку через событие `showCallbackFormNew`.

## Кеширование
### Город
- `default_city`
- `active_cities`
- `city_by_slug_{slug}`
- `route_city_slugs`

### Страницы и контент
- `page-{citySlug}-{handle}`
- `page-{citySlug}-{category}/{handle}`
- `doctors-page-{citySlug}-{page}`
- `services_with_media_and_prices`

### Услуги и цены
- `services-with-prices-{slug}`
- legacy:
  - `services-with-prices`
  - `clinic-prices`
  - `prices`

### Прочее
- кеши врачей по городам;
- кеши отзывов по городам.
- короткий кеш ответов `BookingWidgetApiService` для синхронизации филиалов в админке (`booking-widget-api:*`, TTL 60 сек).

### Инвалидация
- `City`, `Page`, `Block`, `Doctor`, `Service`, `ServicePrice`, `Review` очищают связанные кеши в model events/observers.
- В проекте есть и точечная инвалидация, и места с широким сбросом:
  - например `PageService::clearPageCache()` содержит `Cache::flush()`.

## Middleware
### Группа `web`
- `UtmParameters`
- `RedirectRequests`
- `EncryptCookies`
- `AddQueuedCookiesToResponse`
- `StartSession`
- `ShareErrorsFromSession`
- `VerifyCsrfToken`
- `SubstituteBindings`
- `InterceptSource`
- `SetCityMiddleware`

### Алиасы
- `city`
- `services.integration`
- `services.integration.city`
- стандартные `auth`, `guest`, `signed`, `throttle` и т.д.

## Планировщик и команды
### Scheduler
- `app/Console/Kernel.php`
- По расписанию:
  - `sitemap:generate` ежедневно

### Кастомные команды
- `yml-feed:generate`
- `app:add-ulid-to-all-doctors`
- `app:services-ai-agent`
- `app:import-services-legacy`
- `app:import-doctors-from-booking-api`
- `app:import-services`
- `sitemap:generate`
- `cache:clear-doctors`

## Деплой
- Файл: `Envoy.blade.php`
- Основной сценарий:
  - pull/clone release;
  - `composer install --no-dev`;
  - `npm ci`;
  - `npm run build`;
  - symlink storage и `.env`;
  - `php artisan livewire:publish --assets --no-interaction`;
  - `php artisan migrate --force`;
  - cache clear/cache build;
  - restart `php8.1-fpm` и `supervisor`.
- Есть macro:
  - `deploy`
  - `deploy-code`
  - `rollback`

## Тесты
- PHPUnit
- Есть feature и unit тесты, в том числе:
  - `MultiCityTest`
  - `ServiceIntegrationApiTest`
  - `RunServicesAiAgentCommandTest`
  - `YmlFeedTest`
  - `ArticleContentParserTest`

## Связанные документы
- `docs/app-overview.md` — обзор архитектуры проекта
- `docs/multicity.md` — документация по мультигороду
- `docs/multicity-optimization.md` — история оптимизаций мультигорода
- `docs/services-ai-api.md` — contract integration API по услугам
- `docs/article-import-format.md` — формат Google Docs для импорта статьи
- `YML_FEED_README.md` — генерация YML-фида врачей
- `SUMMARY.md` — итоговая сводка по отдельной задаче оптимизации мультигорода

## Ограничения и замечания
- Проект сейчас не на Inertia и не на Vue 3; документацию и изменения нужно писать под фактический стек.
- В поиске (`SearchController`) есть запросы с `where ... orWhere ...`, которые важно учитывать при доработках, чтобы не изменить фактическую выдачу случайно.
- В проекте сосуществуют новая схема цен в БД и legacy-источник через UNF.
- Часть URL и поведения зависит от города, а часть маршрутов специально глобальная.
- В layout и city-настройках есть поддержка `header_scripts` и `body_scripts`, поэтому любые изменения в SEO/scripts нужно проверять и в глобальных настройках, и в `City`.
- Подстановка city-переменных в блоках выполняется только при публичном рендере через `withResolvedCitySeoVariables()`; исходные значения `blocks.title`, `blocks.body_html` и `blocks.payload` в БД и админке не переписываются.
- Для нового порядка виджета не менялась логика `BookingWidgetV3.vue`; источник истины для порядка находится в backend/admin, а публичный frontend применяет только готовые order-map без дополнительной бизнес-логики шагов.
- Для списка врачей в виджете теперь поддерживаются два независимых порядка по городу: отдельный для doctor-flow и отдельный для clinic-flow; если `clinic_sort_order` не задан, clinic-flow использует fallback на doctor-flow order-map, но любой явный `clinic_sort_order` имеет приоритет над fallback-значениями.
- Ручная сортировка публичной страницы специалистов хранится отдельно в `doctors.page_sort_order` и не влияет на порядок врачей в виджете записи.
- В админке `Настройки виджета` филиалы могут обновляться фоновым Livewire-запросом сразу после открытия/смены города; первый рендер приходит из локальной БД без блокировки внешним booking API.
- На production nginx перехватывает любые URL с расширением `.js` как статические файлы (`try_files $uri =404`), поэтому route-based Livewire assets под `/livewire/*.js` там неработоспособны; для Filament/Livewire нужно использовать опубликованные assets в `public/vendor/livewire`, и деплой обязан их публиковать каждый релиз.

## Журнал изменений
- 2026-03-20: в `resources/js/components/BookingWidgetV3/BookingWidgetV3.vue` устранена гонка первого открытия ветки `Выбрать врача`: `initCities()` кеширует in-flight запрос по городам, а загрузка врачей/клиник/филиалов не стартует, пока не готов `currentCityId`.
- 2026-03-20: добавлена ручная сортировка публичной страницы специалистов: миграция `2026_03_20_170000_add_page_sort_order_to_doctors_table.php` добавляет `doctors.page_sort_order`, `DoctorResource` показывает его в форме и inline в таблице `Специалисты`, а `Doctor::scopeOrderedForPublicIndex()` используется в `PageController` для выдачи списка врачей на странице типа `Doctors`.
- 2026-03-20: добавлена двойная сортировка врачей виджета по городу: новая миграция `2026_03_20_160000_add_clinic_sort_order_to_city_doctor_for_booking_widget.php` добавляет `city_doctor.clinic_sort_order`, таблица `BookingWidgetDoctorsTable` показывает две inline-колонки (`Выбрать врача`, `Выбрать клинику`), `BookingWidgetOrderingService` отдаёт две doctor order-map, а `resources/js/services/bookingApi.js` применяет разные карты к `getDoctorsByCity()` и `getClinicDoctors()`; в clinic-flow первыми идут врачи с явным `clinic_sort_order`, затем fallback из основного порядка.
- 2026-03-20: в `Envoy.blade.php` добавлен обязательный шаг `php artisan livewire:publish --assets --no-interaction` для полного деплоя и `deploy-code`; причина в production nginx-конфиге, который не пропускает `/livewire/*.js` в Laravel и без опубликованных assets ломает Filament login (`POST /admin/login -> 405`).
- 2026-03-20: в `App\Models\Block` добавлен централизованный резолв city SEO-переменных для `title`, `body_html` и строковых значений `payload`; `App\Models\Page::withResolvedCitySeoVariables()` теперь применяет его ко всем загруженным блокам, а unit-тест `tests/Unit/PageCitySeoVariablesTest.php` покрывает рекурсивную подстановку.
- 2026-03-20: шаблоны `resources/views/parts/footer.blade.php` и `resources/views/parts/footer-new.blade.php` обновлены: `target` и `download` теперь пробрасываются и в верхние, и в дочерние ссылки footer-меню.
- 2026-03-20: в Blade-компонентах меню добавлен вывод `target` для дочерних и mega-menu ссылок, чтобы тип пункта `Ссылка` корректно открывался в новой вкладке при `target = _blank`.
- 2026-03-20: в `App\Providers\Filament\AdminPanelProvider` добавлен тип пункта меню `Файл`; для него добавлен публичный контроллер скачивания `MenuFileDownloadController` и маршрут `menu-files.download`, чтобы браузер всегда получал файл как вложение (`attachment`).
- 2026-03-19: собран и зафиксирован стартовый актуальный контекст проекта по коду, конфигурации и существующим документам.
- 2026-03-19: для `x-phone-new` добавлена отдельная callback-модалка на `CallbackFormNew`, чтобы ссылка «Перезвоните мне» не зависела от глобального варианта формы записи.
