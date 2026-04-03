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
- 2026-04-02: публичная страница врача временно переведена на новый шаблон [resources/views/doctors/show-2.blade.php](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/doctors/show-2.blade.php), при этом старый [resources/views/doctors/show.blade.php](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/doctors/show.blade.php) оставлен в репозитории без удаления. [app/Http/Controllers/DoctorController.php](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Controllers/DoctorController.php) снова сделан тонким: подготовка view-данных страницы врача вынесена в [app/ViewData/DoctorPageViewData.php](/Applications/MAMP/htdocs/zrenie.clinic-7/app/ViewData/DoctorPageViewData.php), чтобы не держать разбор `doctor.extra`, review-label/media и mobile-section mapping внутри контроллера или Blade. В новом шаблоне изменён hero-блок врача: для mobile добавлена отдельная последовательность `рейтинг → имя → факты → видеовизитка → отзывы → CTA`, а desktop/right-column структура сохранена отдельно. Desktop-секции `Образование / Повышение квалификации / Профессиональные навыки / Документы` доведены ближе к Figma-паттерну с плоской структурой и миниатюрами документов, а mobile-аккордеон этих секций переведён на более плоскую структуру без внутренних карточек, с закрытым стартовым состоянием и сеткой документов 3 колонки через уже существующие Vue-компоненты `faq/item`. Для скрытых accordion-секций добавлен frontend-refresh `glightbox`: при открытии `faq/item` lightbox переинициализируется, поэтому документы внутри mobile-аккордеона открываются в той же модалке, что и на desktop.
- 2026-04-01: мультигородный сценарий выбора города приведён к новому контракту. `force_city` и прямые URL с префиксом `/{city}` теперь открывают выбранный город без popup-подтверждения, но сохранённый `selected_city` всё равно имеет приоритет: если пользователь уже запомнил свой город, даже прямой заход на URL другого города редиректит его обратно на сохранённый вариант, пока он сам не переключит город через выбор/`force_city`. На первом заходе на корень без параметров `CityDetectionController` и [App\Services\GeoIpService](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/GeoIpService.php) могут вернуть и default-город тоже, поэтому Москва теперь тоже показывается в popup как подтверждённый GEO-вариант. Если GEO не сматчился ни с одним активным городом, фронтовая модалка [resources/js/components/CityConfirmationModal.vue](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/js/components/CityConfirmationModal.vue) сразу открывает шаг выбора города. Для production GeoIP теперь ходит в SypexGeo по `https` и предпочитает `CF-Connecting-IP` перед обычным `REMOTE_ADDR`; прямой заход на `/{city}` дополнительно очищает висящий `detected_city`, чтобы явный URL не спорил с popup. Подтверждение/переключение города больше не зависит от `js-cookie`: frontend читает серверный флаг `window.config.cityConfirmed`, а сами cookies города остаются под управлением backend через `force_city` и middleware, что убирает цикл popup на default-городе из-за `HttpOnly` cookies. Обновлены тесты [tests/Unit/CityDetectionControllerTest.php](/Applications/MAMP/htdocs/zrenie.clinic-7/tests/Unit/CityDetectionControllerTest.php) и [tests/Unit/SetCityMiddlewareTest.php](/Applications/MAMP/htdocs/zrenie.clinic-7/tests/Unit/SetCityMiddlewareTest.php).
- 2026-04-01: в `UTM Трекере` таблицы переведены на icon-first UI: на табе `Основной` колонка `Статус` использует зелёную галочку, красный крестик и янтарные часы, действия `Стоп / Возобновить / Удалить` заменены на компактные иконки, а также добавлена колонка `Ссылка` с полным URL вида `APP_URL + путь города + utm_source + utm_medium` и кнопкой открытия. В табах `Источники` и `Телефоны` текстовое `Удалить` тоже заменено на красную корзину, а у занятого телефона корзина показывается серой disabled-версией. Цвета иконок зафиксированы прямо в SVG, чтобы не зависеть от темы Filament, в [resources/views/filament/forms/components/utm-tracker-manager.blade.php](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/filament/forms/components/utm-tracker-manager.blade.php).
- 2026-04-01: для UTM-трекера ужесточено правило уникальности телефонов: сервис [app/Services/UtmTrackerService.php](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/UtmTrackerService.php) теперь автоматически снимает дефолтный телефон у `source`, если тот же номер уже используется в `medium` этого же города, а остальные повторы телефонов между `source/medium` блокируются валидацией. Для уже мигрированных данных добавлена cleanup-миграция [database/migrations/2026_04_01_180000_remove_source_phone_duplicates_from_utm_tracker.php](/Applications/MAMP/htdocs/zrenie.clinic-7/database/migrations/2026_04_01_180000_remove_source_phone_duplicates_from_utm_tracker.php), а сервисные тесты в [tests/Unit/Services/UtmTrackerServiceTest.php](/Applications/MAMP/htdocs/zrenie.clinic-7/tests/Unit/Services/UtmTrackerServiceTest.php) покрывают и auto-clean source, и запрет повторов между medium-правилами.
- 2026-04-01: UTM-трекинг в админке города переведён на нормализованную схему с тремя табами: в [app/Filament/Resources/CityResource.php](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Filament/Resources/CityResource.php) секция `UTM Трекер` теперь использует кастомный field [app/Filament/Forms/Components/UtmTrackerManager.php](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Filament/Forms/Components/UtmTrackerManager.php) и view [resources/views/filament/forms/components/utm-tracker-manager.blade.php](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/filament/forms/components/utm-tracker-manager.blade.php); данные хранятся в таблицах `city_utm_phones`, `city_utm_sources`, `city_utm_mediums`, где `Основной` таб редактирует medium-правила с колонками `Начало / Конец / Статус` и действиями `Стоп / Возобновить`, `Источники` держит справочник `utm_source`, а `Телефоны` даёт общий пул номеров без повторного использования. Синхронизацию и mirror обратно в legacy `cities.utm_phones` выполняет [app/Services/UtmTrackerService.php](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/UtmTrackerService.php); в legacy JSON попадают только активные medium-правила, поэтому публичная подмена телефонов и UTM-aware redirect продолжают работать без переписывания фронта. Добавлены миграции [database/migrations/2026_04_01_160000_create_city_utm_tracker_tables.php](/Applications/MAMP/htdocs/zrenie.clinic-7/database/migrations/2026_04_01_160000_create_city_utm_tracker_tables.php) и [database/migrations/2026_04_01_170000_add_schedule_fields_to_city_utm_mediums_table.php](/Applications/MAMP/htdocs/zrenie.clinic-7/database/migrations/2026_04_01_170000_add_schedule_fields_to_city_utm_mediums_table.php), а также unit-тест [tests/Unit/Services/UtmTrackerServiceTest.php](/Applications/MAMP/htdocs/zrenie.clinic-7/tests/Unit/Services/UtmTrackerServiceTest.php).
- 2026-04-01: в мультигороде добавлен UTM-aware redirect по городу: в [app/Http/Middleware/SetCityMiddleware.php](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Middleware/SetCityMiddleware.php) при входе на не-глобальный публичный URL с `utm_source` система пытается однозначно определить город по `City.utm_phones`; если найден ровно один лучший матч, пользователь редиректится на URL этого города с сохранением query-параметров, а город запоминается в cookies. При неоднозначном совпадении редирект не делается. Добавлен unit-тест [tests/Unit/SetCityMiddlewareUtmRedirectTest.php](/Applications/MAMP/htdocs/zrenie.clinic-7/tests/Unit/SetCityMiddlewareUtmRedirectTest.php).
- 2026-04-01: исправлена UTM-подмена телефона на публичном сайте: в [app/View/Components/AppLayout.php](/Applications/MAMP/htdocs/zrenie.clinic-7/app/View/Components/AppLayout.php) `utm_medium` больше не “залипает” в сессии, если пользователь открывает URL с `utm_source`, но уже без `utm_medium`; в таком сценарии medium явно сбрасывается и сайт возвращается к телефону уровня source. Заодно убрана ошибочная проверка, где `utm_medium` сравнивался с переменной `utmSource`.
- 2026-03-31: исправлена ошибка при массовой замене блоков в админке: в [app/Models/Page.php](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Models/Page.php) `clearCache()` больше не пытается лениво грузить `category` у `Page`, а безопасно берёт `category.handle` через уже загруженную связь или отдельный запрос; это устраняет падение `Attempted to lazy load [category] on model [App\Models\Page] but lazy loading is disabled`.
- 2026-03-31: в списке блоков Filament добавлен точечный bulk action только для роли `super_admin`: в [app/Filament/Resources/BlockResource.php](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Filament/Resources/BlockResource.php) действие `replaceCallToActionWithSpecialistBanner` массово переводит выбранные блоки типа `BlockType::CALL_TO_ACTION` в `BlockType::BANNER_SPECIALIST_CALLBACK` и очищает их `payload`; bulk action не виден обычным пользователям и не пытается быть универсальной заменой любых блоков на любые.
- 2026-03-31: новый banner-block `BlockType::BANNER_SPECIALIST_CALLBACK` сделан полностью статичным: в `resources/views/components/banner/specialist-callback.blade.php` заголовок, подзаголовок и графика зафиксированы в шаблоне, причём графика подключается как единый цельный арт через `<picture>` (`public/images/specialist-callback-desktop.webp` и `public/images/specialist-callback-mobile.webp`), а в `app/Filament/Resources/BlockResource.php` для него не показываются редактируемые hero-поля и загрузки `bg/pic`; кнопки жёстко открывают два существующих сценария: `openBookingWidgetV3('otpravka-formy')` для записи и `showCallbackFormNew(null, 'otpravka-formy')` для обычной формы.
- 2026-03-30: для безопасного пошагового рефакторинга блоковой системы создан отдельный рабочий документ [docs/block-refactor-plan.md](/Applications/MAMP/htdocs/zrenie.clinic-7/docs/block-refactor-plan.md) и отдельная ветка `codex/block-refactor-plan`; в документе зафиксированы этапы, ограничения, критерии безопасности и рекомендуемый порядок улучшений без ломки существующего контента.
- 2026-03-30: добавлен блок `BlockType::APPARATUS_CONTRAINDICATIONS` для секции “Противопоказания для аппаратного лечения зрения”: он переиспользует уже существующую админскую схему `title + body_html + payload.items[]`, как и `APPARATUS_DISEASES`, но рендерится отдельным шаблоном `resources/views/components/block/apparatus-contraindications.blade.php` с warning-карточками в две колонки на desktop и одной колонкой на mobile.
- 2026-03-30: добавлен блок `BlockType::APPARATUS_METHODS` для секции “Методики аппаратного лечения глаз у детей”: в админке он переиспользует общие поля `title`, `body_html`, `payload.btn_text` и `payload.items[]`, где у каждого элемента есть `title + body_html + image`; на frontend `resources/views/components/block/apparatus-methods.blade.php` рендерит desktop-карточки в раскрытом виде и mobile-аккордеон через существующий inline Vue-паттерн `faq/item`, но без автоматически открытого первого элемента.
- 2026-03-30: у блока `BlockType::APPARATUS_DISEASES` скорректирована композиция по брейкпоинтам: на desktop в `resources/views/components/block/apparatus-diseases.blade.php` левая колонка собирается независимым стеком `заголовок + текст + изображение`, а на mobile изображение вынесено отдельным рендером в самый низ блока, после сетки карточек.
- 2026-03-30: добавлен новый контентный блок `BlockType::APPARATUS_DISEASES` для секции “При каких заболеваниях эффективны аппаратные процедуры?”: он использует стандартные поля `title` и `body_html`, хранит карточки в `payload.items`, берёт одно изображение из media-коллекции `default` и рендерится в `resources/views/components/block/apparatus-diseases.blade.php` с разной desktop/mobile композицией внутри одного шаблона.
- 2026-03-30: внесены два точечных фикса в блоковую систему без смены архитектуры: в `app/Models/Block.php` accessor `getElementsAttribute()` больше не срабатывает из-за всегда-truthy `BlockType::ELEMENTS_ITEM_ROW`, а в `app/Filament/Resources/BlockResource.php` поле `payload.service` снова корректно обязательно именно для `BlockType::PRICE_LIST`.
- 2026-03-30: добавлен блок `BlockType::APPARATUS_TASKS` для секции “Ключевые задачи этой терапии”: в админке он хранит заголовок блока, список `payload.tasks` и текст нижней плашки `payload.note_text`; изображение корги жёстко подключено из `public/images/corgy/new-corgy.webp`/`.png`, а фронтенд-рендер вынесен в `resources/views/components/block/apparatus-tasks.blade.php`.
- 2026-03-30: добавлен отдельный hero-баннер `BlockType::BANNER_APPARATUS_HERO` для страницы аппаратного лечения: `resources/views/components/banner/apparatus-hero.blade.php` рендерит desktop/mobile-сценарии раздельно, использует media-коллекции `bg` и `pic` и `hero` webp-conversion для `<picture>`-источников; в `BlockResource` добавлены поля заголовка, текста и кнопки.
- 2026-03-30: добавлен новый тип контентного блока `BlockType::APPARATUS_TREATMENT` под Figma-макет про аппаратное лечение зрения: `app/Enums/BlockType.php`, `app/Models/Block.php`, `app/Filament/Resources/BlockResource.php` и `resources/views/components/block/apparatus-treatment.blade.php` дают отдельный адаптивный Blade-рендер и две фиксированные секции в админке (`заголовок + текст + изображение` у каждой).
- 2026-03-26: переключение с сохранённого Кирова обратно на дефолтную Москву теперь работает с любой публичной страницы: `app/Clinic.php` и `resources/views/components/city-switcher.blade.php` собирают URL дефолтного города через `force_city` не только на `/`, но и на внутренних не-глобальных страницах, а `resources/js/components/CitySwitcher/CitySwitcher.vue` больше не полагается на обычный `<a>`-переход и сначала синхронно пишет cookies, потом делает `window.location.href`. Это закрывает сценарий, когда на странице врача/услуги выбор Москвы тут же отбрасывался обратно в Киров.
- 2026-03-26: сохранённый non-default город теперь восстанавливается не только на корневой `/`, но и на обычных публичных страницах без префикса (`pages.show`, `posts.show`, `doctor.show`, `review.index`, `call-request`, `sitemap.html`): `app/Http/Middleware/SetCityMiddleware.php` при наличии cookie `selected_city` редиректит на `/{slug}/...`, поэтому повторный заход без префикса на страницу врача, услуги или обычную страницу больше не открывает дефолтную Москву.
- 2026-03-26: GeoIP-автоопределение города ужато до non-default сценария: `app/Http/Controllers/CityDetectionController.php` теперь возвращает `detectedCity` только для non-default города, а default city и любой нерелевантный IP оставляет на дефолтной Москве без popup; `app/Clinic.php` дополнительно чистит старый `detected_city` из сессии, если там оказался дефолтный город. При текущей конфигурации из двух городов это означает: только Киров может автоопределиться, всё остальное остаётся на Москве.
- 2026-03-26: добита логика корневой главной `/` в мультигороде: `app/Http/Middleware/SetCityMiddleware.php` теперь возвращает пользователя на ранее подтверждённый non-default город по cookie `selected_city`, а ссылки выбора дефолтной Москвы на главной собираются с `force_city`, чтобы явное переключение на дефолтный город гарантированно перебивало сохранённый Киров и не зацикливалось на редиректе обратно.
- 2026-03-26: для админских загрузок через Livewire поднят лимит временных файлов с `100 MB` до `200 MB` и `max_upload_time` с `5` до `10` минут в `config/livewire.php`, чтобы загрузка крупных видео в `BlockResource` не упиралась в приложение раньше лимита `spatie/laravel-media-library`; комментарий в `config/media-library.php` синхронизирован с фактическим лимитом `200 MB`.
- 2026-03-26: исправлен production-сбой загрузки видео в админке Filament у блоков `BlockType::VIDEO_NEW` и других video-коллекций `Block`: `app/Models/Block.php` больше не запускает Spatie media conversions для не-изображений, поэтому загрузка `.mov/.mp4` не зависит от наличия `ffmpeg/ffprobe` на сервере; image-conversions для картинок блоков сохранены.
- 2026-03-25: усилена интеграция админской синхронизации филиалов с booking API: `app/Services/BookingWidgetApiService.php` теперь валидирует `BOOKING_API_BASE_URL`, логирует ошибки с контекстом, делает ограниченный retry для временных HTTP/connection-сбоев и выбрасывает доменное исключение `App\Exceptions\BookingWidgetApiException` вместо сырых ошибок HTTP-клиента; внешний контракт методов и формат payload не менялись.
- 2026-03-25: `app/Services/PageService.php` переведён с глобального `Cache::flush()` на точечную инвалидацию page-кешей; при изменении `Page`/`Block` теперь очищаются только связанные ключи страниц, legacy page keys и нужные listing-кеши (`active_doctors`, `active_services`, `doctors-page-*`, `posts_filter`, `blog_posts_for_slider`) без полного сброса кеша приложения.
- 2026-03-24: callback-формы (`CallbackForm.vue`, `CallbackFormNew.vue`) теперь сами отправляют текущий город сайта в поле `city`, используя общий frontend util `resources/js/utilities/currentCity.js`; в `CallbackController` это поле имеет приоритет, а cookie `selected_city` и default city остались только fallback-веткой для API.
- 2026-03-24: для `BookingWidgetV3` приведена в порядок архитектура doctor-flow: `resources/js/services/bookingApi.js` теперь отвечает только за transport/fetch, order-map вынесены в `resources/js/services/bookingOrdering.js`, а doctor merge/sort и возрастной парсинг собраны в widget utils (`doctorUtils.js`, `doctorAgeUtils.js`); итоговое поведение прежнее — список врачей получает вторичную сортировку по числу из `extra.receives`, а на последнем шаге поле `Дата рождения` блокирует запись, если возраст пациента меньше минимального возраста выбранного врача.
- 2026-03-23: убрано мигание стартового шага `BookingWidgetV3` при первом открытии с принудительным режимом (`mode = doctor|clinic`): компонент теперь показывает внутренний loading-state до завершения `initCities()` и `applyInitialMode()`, поэтому в doctor-flow больше не появляется промежуточный первый экран.
- 2026-03-23: для `BookingWidgetV3` добавлен управляемый старт сценария через `bookingStartMode` в триггерах открытия: по умолчанию виджет остаётся на первом экране, но для блока `doctors-alt`, карточек врачей в mega-menu, публичной страницы списка врачей и страницы отдельного врача теперь сразу открывается ветка `Выбрать врача` (пропуск стартового шага).
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
- UTM tracker по городам нормализован в три таблицы:
  - `city_utm_phones` — справочник телефонов города;
  - `city_utm_sources` — справочник `utm_source` с дефолтным телефоном;
  - `city_utm_mediums` — правила `utm_medium` c привязкой к source, телефону, `start_date` и `end_date`;
  - legacy JSON `cities.utm_phones` остаётся как mirror для публичной части и собирается из активных правил сервисом `UtmTrackerService`.
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
  - на не-глобальных публичных страницах умеет до основного city-resolution определить целевой город по `utm_source/utm_medium` через `City.utm_phones`; если найден единственный лучший матч, делает redirect на URL этого города и запоминает его в cookies;
  - делает 301-редирект с дефолтного города на URL без префикса;
  - на публичных не-глобальных маршрутах без префикса (`/`, страницы, услуги, врачи, отзывы и часть служебных страниц контента) делает 302-редирект на `/{selected_city}/...`, если в cookie сохранён подтверждённый non-default город;
  - если пользователь уже сохранил город, прямой заход на URL другого города тоже редиректит на сохранённый вариант той же страницы; исключение — явный ручной override через `force_city`;
  - явное переключение на дефолтный город с публичных не-глобальных страниц идёт через `force_city`, чтобы override сработал раньше редиректа по сохранённой cookie;
  - запоминает выбранный город в cookies;
  - для глобальных путей хранит город в cookie и переключает его через `force_city`;
  - при первом заходе на корень без подтверждения города может определить город по IP и передать его в popup, включая default-город;
  - прямой URL с `/{city}` считается явным выбором и очищает pending `detected_city`, поэтому confirmation popup на префиксных URL не всплывает;
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
- Внешний сервис: `https://api.sypexgeo.net/json/{ip}`
- Локальные IP (`127.0.0.1`, `::1`) игнорируются.
- В production для запроса к GeoIP приоритетно используется `CF-Connecting-IP`, затем обычный IP запроса.
- Popup/`detectedCity` теперь может формироваться и для default-города; если GeoIP ничего не сматчил с активным городом, фронт открывает шаг выбора города вручную.

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
  - публичная страница врача;
  - временно рендерит новый Blade-шаблон `doctors.show-2`, старый `doctors.show` сохранён как legacy-версия для отката/сравнения.
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
- `CallbackController` при `Clinic::callback(...)` теперь дополнительно передаёт текущий сайтовый город в поле `city`; основное значение приходит с фронтенда из callback-форм, а для API callback на бэке оставлен fallback через cookie `selected_city` или default city.

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
  - cached-client к внешнему booking API (`adminzrenie.ru/api/v1`) для синхронизации филиалов в админке;
  - коротко кеширует ответы (`booking-widget-api:*`, TTL 60 сек);
  - валидирует `BOOKING_API_BASE_URL`;
  - на временных сбоях делает ограниченный retry;
  - логирует ошибки с контекстом и выбрасывает `BookingWidgetApiException`, не прокидывая наружу сырые исключения HTTP-клиента.
- `BookingWidgetOrderingService`
  - собирает `doctorSortOrders` и `branchSortOrders` для текущего города;
  - order-map прокидывается в `window.config.booking` через `Clinic::scriptVariables()`.
- `BookingWidgetBranchSyncService`
  - подтягивает филиалы из booking API в локальную таблицу `booking_widget_branch_orders` для выбранного города;
  - фильтрует клиники по `BOOKING_ALLOWED_CLINIC_IDS`;
  - вызывается из админской страницы `Настройки виджета` автоматически после первого рендера;
  - забирает ветки клиник параллельно и обновляет локальную таблицу через `upsert`, без серии `updateOrCreate`.
- `UtmTrackerService`
  - адаптер между новыми таблицами `city_utm_*` и legacy JSON `cities.utm_phones`;
  - собирает state для Filament-редактора города;
  - синхронизирует справочник телефонов, sources и medium-правила;
  - при сборке legacy JSON публикует только активные medium-правила, чтобы `Стоп / Возобновить` и даты действия влияли на публичную подмену телефона и UTM-city redirect.
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
- Управление стартовым шагом вынесено в родительский слой (`resources/js/app.js`): `showCallbackModal(...)` поддерживает необязательный `options.bookingStartMode`, который прокидывается в `<booking-widget-v3 :mode="...">`.
- Если `bookingStartMode` не передан, `mode = null` и виджет открывается с первого экрана.
- `resources/js/services/bookingApi.js` для doctor-flow теперь работает как transport-layer без doctor-бизнес-логики: `getDoctorsByCity()` и `getClinicDoctors()` только получают данные внешнего booking API, а сортировка и merge выполняются выше по слою.
- Backend order-map текущего города читаются через `resources/js/services/bookingOrdering.js`.
- `resources/js/components/BookingWidgetV3/utils/doctorUtils.js` — единая точка для doctor merge, дедупликации и итоговой сортировки врачей.
- После обогащения врачей локальными данными сайта `BookingWidgetV3.vue` применяет единый util sort: backend order-map остаётся первичным, а число из `doctor.extra.receives` используется только как вторичный tie-breaker.
- На шаге `resources/js/components/BookingWidgetV3/components/PatientFormStep.vue` дата рождения теперь валидируется и против минимального возраста, распарсенного из `selectedDoctor.extra.receives`; если пациент младше, виджет не отправляет заявку и показывает inline-предупреждение у поля даты.

### Blade-макет
- Основной layout: `resources/views/layouts/app.blade.php`
- Что делает:
  - подключает `@vite`;
  - формирует `window.config` через `Clinic::scriptVariables()`;
  - вставляет глобальные и city-specific scripts;
  - подключает header/footer;
  - монтирует Vue-компоненты модалок и форм;
  - одновременно поддерживает legacy-форму записи, новый `BookingWidgetV3` (в т.ч. `mode` для управляемого старта ветки) и отдельную callback-модалку на `CallbackFormNew`.
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
- В `BlockResource` загрузки через Spatie Media Library используют модель `App\Models\Block`; её media conversions теперь применяются только к изображениям, поэтому видео-коллекции (`video`, `videos`) не должны падать из-за отсутствия серверного video-thumbnail pipeline.
- Для контентных страниц доступен кастомный тип `BlockType::APPARATUS_TREATMENT`: в `BlockResource` он хранит две фиксированные секции `payload.sections`, где у каждой есть свой заголовок, rich-text и изображение через отдельную media collection.
  - `ServiceResource`
  - `CityResource`
    - секция `UTM Трекер` редактируется кастомным field `UtmTrackerManager`: три таба (`Основной`, `Источники`, `Телефоны`) работают поверх нормализованных таблиц, а сервис `UtmTrackerService` синхронизирует изменения обратно в legacy `utm_phones` для публичной логики.
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
- `PageService::clearPageCache()` больше не делает глобальный `Cache::flush()`:
  - очищает city-aware page keys `page-{citySlug}-{handle}` и `page-{citySlug}-{category}/{handle}`;
  - очищает legacy page keys `page-{handle}`, `page_{category}_{handle}`, `page_{handle}_index`;
  - дополнительно сбрасывает связанные listing-кеши для страниц врачей и постов.

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
  - `BookingWidgetApiServiceTest`
  - `PageServiceTest`

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
- Публичная UTM-логика всё ещё читает legacy `cities.utm_phones`, но это теперь зеркало, которое собирает `UtmTrackerService`; если менять UTM-правила, нельзя обновлять только новые таблицы и забывать о mirror-синхронизации.
- В реальных UTM-данных города уже могут существовать повторные использования одного и того же номера в нескольких `source/medium`; редактор должен их корректно показывать и сохранять, даже если для новых назначений UI помечает занятые номера.
- Для UTM-трекера действует правило приоритета: если один и тот же телефон одновременно выбран у `source` и у `medium`, телефон остаётся только у `medium`, а `source.default_phone` автоматически очищается; после этого любые оставшиеся дубли телефонов между `source/medium` считаются ошибкой валидации.
- Для порядка врачей в `BookingWidgetV3` backend/admin order-map остаётся первичным источником, transport-слой не содержит doctor-бизнес-логики, а публичный frontend добавляет вторичную сортировку по числу, распарсенному из `doctor.extra.receives`, и использует тот же парсинг для клиентской возрастной валидации на последнем шаге.
- Принудительный старт ветки `Выбрать врача` включается только явным `bookingStartMode: doctor` в конкретном UI-триггере; без этого виджет всегда стартует с первого шага.
- Для списка врачей в виджете теперь поддерживаются два независимых порядка по городу: отдельный для doctor-flow и отдельный для clinic-flow; если `clinic_sort_order` не задан, clinic-flow использует fallback на doctor-flow order-map, но любой явный `clinic_sort_order` имеет приоритет над fallback-значениями.
- Ручная сортировка публичной страницы специалистов хранится отдельно в `doctors.page_sort_order` и не влияет на порядок врачей в виджете записи.
- В админке `Настройки виджета` филиалы могут обновляться фоновым Livewire-запросом сразу после открытия/смены города; первый рендер приходит из локальной БД без блокировки внешним booking API.
- `BookingWidgetApiService` теперь предполагает корректно заданный `BOOKING_API_BASE_URL`; пустой или невалидный URL считается конфигурационной ошибкой и приводит к `BookingWidgetApiException` с логированием контекста.
- Инвалидация page-кеша стала точечной; при доработках `PageService`, `PageObserver` и `BlockObserver` нельзя возвращаться к полному `Cache::flush()`, иначе это снова будет сбрасывать несвязанные кеши по всему приложению.
- На production nginx перехватывает любые URL с расширением `.js` как статические файлы (`try_files $uri =404`), поэтому route-based Livewire assets под `/livewire/*.js` там неработоспособны; для Filament/Livewire нужно использовать опубликованные assets в `public/vendor/livewire`, и деплой обязан их публиковать каждый релиз.

## Журнал изменений
- 2026-04-02: подготовка данных публичной страницы врача вынесена из `DoctorController` в `app/ViewData/DoctorPageViewData.php`; контроллер снова оставлен тонким, а разбор `doctor.extra`, mapping отзывов/media и формирование mobile sections больше не живут ни в controller-method soup, ни в Blade.
- 2026-04-02: `DoctorController` временно переключён на `resources/views/doctors/show-2.blade.php`; в новом шаблоне полностью переразмечена внутренняя часть публичной страницы врача без изменения header/footer и маршрута, а мобильные секции `Образование / Повышение квалификации / Профессиональные навыки / Документы` переведены на сворачиваемый accordion через существующие `faq/item`.
- 2026-04-01: для UTM-трекера введено правило приоритета `medium > source`: если номер совпадает, `source.default_phone` автоматически очищается, а оставшиеся дубли телефонов между `source/medium` блокируются валидацией. Для уже мигрированных данных добавлена cleanup-миграция `2026_04_01_180000_remove_source_phone_duplicates_from_utm_tracker.php`; в `UtmTrackerServiceTest` добавлены проверки на auto-clean source и на ошибку при дублировании номера между medium-правилами.
- 2026-04-01: UTM-трекер города переведён с JSON-редактора на нормализованные таблицы `city_utm_phones`, `city_utm_sources`, `city_utm_mediums`; в `CityResource` добавлен кастомный `UtmTrackerManager` с тремя табами, а `UtmTrackerService` синхронизирует всё обратно в `cities.utm_phones`. Для medium-правил добавлены `start_date`, `end_date`, вычисляемый статус и действия `Стоп / Возобновить`; в legacy JSON попадают только активные правила. Добавлен unit-тест `tests/Unit/Services/UtmTrackerServiceTest.php`.
- 2026-04-01: в `SetCityMiddleware` добавлен redirect по UTM-городам: система ищет лучший city match по `City.utm_phones`, уводит пользователя на соответствующий городской URL только при однозначном совпадении и сохраняет выбор города в cookies; добавлен unit-тест на medium-specific redirect и на отсутствие redirect при неоднозначном `utm_source`.
- 2026-04-01: в `AppLayout` исправлена логика чтения UTM из query/session: при явном `utm_source` без `utm_medium` старый medium теперь удаляется из сессии, поэтому телефон корректно откатывается к правилу source; также исправлена опечатка в условии обновления medium.
- 2026-03-30: добавлен тип блока `APPARATUS_TREATMENT` с отдельным Blade-шаблоном и двумя управляемыми через Filament секциями `title + body_html + image`; `App\Models\Block` теперь готовит для него responsive images по тем же правилам, что и для других media-driven блоков.
- 2026-03-26: для дефолтной Москвы URL переключения теперь везде на не-глобальных публичных страницах собирается с `force_city`: обновлены `app/Clinic.php` и `resources/views/components/city-switcher.blade.php`. Сам `resources/js/components/CitySwitcher/CitySwitcher.vue` переведён на `@click.prevent` + явный `window.location.href` после записи cookies, чтобы браузер не успевал отправить старый `selected_city` и не возвращал пользователя обратно в Киров. В `tests/Unit/SetCityMiddlewareTest.php` добавлен сценарий для страницы врача `?force_city=moscow`.
- 2026-03-26: в `app/Http/Middleware/SetCityMiddleware.php` расширен remembered-city redirect: при наличии cookie `selected_city` non-default город теперь восстанавливается не только на `/`, но и на обычных публичных маршрутах без префикса (`pages.show`, `posts.show`, `doctor.show`, `review.index`, `call-request`, `sitemap.html`) через `302` на `/{slug}/...`. Добавлен unit-тест `tests/Unit/SetCityMiddlewareTest.php` на страницу врача без префикса.
- 2026-03-26: `app/Http/Controllers/CityDetectionController.php` теперь отдает `detectedCity` только для non-default города, поэтому при текущих двух городах popup автоопределения появляется только для Кирова, а Москва и все нерелевантные/неопознанные IP остаются на дефолтном сайте без popup. В `app/Clinic.php` добавлена зачистка старого session `detected_city`, если там оказался default city. Добавлен unit-тест `tests/Unit/CityDetectionControllerTest.php`.
- 2026-03-26: в `app/Http/Middleware/SetCityMiddleware.php` добавлен отдельный redirect-сценарий для корневой страницы `pages.show` без `{city}`: при наличии cookie `selected_city` с non-default городом `/` теперь отвечает `302` на `/{slug}` вместо молчаливого возврата к дефолтной Москве. Чтобы явный переход обратно на дефолтный город не залипал на этом редиректе, `app/Clinic.php` и `resources/views/components/city-switcher.blade.php` для дефолтного города на главной теперь генерируют URL с `force_city`. Добавлены проверки в `tests/Feature/MultiCityTest.php` и `tests/Unit/SetCityMiddlewareTest.php`.
- 2026-03-25: в `app/Services/BookingWidgetApiService.php` добавлены валидация `BOOKING_API_BASE_URL`, ограниченный retry для временных HTTP/connection-сбоев, структурированное логирование ошибок и доменное исключение `app/Exceptions/BookingWidgetApiException.php`; при batch-запросах филиалов сервис теперь использует pool для успешных ответов и безопасно дозапрашивает упавшие клиники через fallback `performGet()`, сохраняя прежний формат ответа методов. Добавлен unit-тест `tests/Unit/Services/BookingWidgetApiServiceTest.php` на retry и конфигурационную ошибку base URL.
- 2026-03-25: в `app/Services/PageService.php` удалён глобальный `Cache::flush()` из `clearPageCache()`; сервис теперь адресно очищает city-aware page keys, legacy page keys и связанные listing-кеши для страниц врачей и постов, включая сценарии со сменой `handle` и `category_id`. Добавлен unit-тест `tests/Unit/Services/PageServiceTest.php` на точечную инвалидацию без затрагивания несвязанных кешей.
- 2026-03-24: в `resources/js/components/CallbackForm/CallbackForm.vue`, `resources/js/components/CallbackForm/CallbackFormNew.vue` и `resources/js/utilities/currentCity.js` добавлена явная отправка текущего города сайта в поле `city`; `app/Http/Requests/CallbackRequest.php` валидирует новое поле, а `app/Http/Controllers/CallbackController.php` использует его с приоритетом над backend fallback по cookie `selected_city` и default city. Нормализация телефона переведена на injected `PhoneService` без `resolve(...)` внутри метода.
- 2026-03-24: doctor-flow `BookingWidgetV3` отрефакторен по слоям: в `resources/js/services/bookingApi.js` удалена doctor-сортировка, order-map вынесены в `resources/js/services/bookingOrdering.js`, в `resources/js/components/BookingWidgetV3/utils/doctorUtils.js` собраны дедупликация, merge локальных doctor-данных и итоговая сортировка, а `resources/js/components/BookingWidgetV3/utils/doctorAgeUtils.js` даёт единый парсер минимального возраста из `doctor.extra.receives`; `resources/js/components/BookingWidgetV3/components/PatientFormStep.vue` использует тот же парсер для блокировки отправки формы при слишком маленьком возрасте пациента.
- 2026-03-23: в `resources/js/components/BookingWidgetV3/BookingWidgetV3.vue` добавлен флаг `isPreparingInitialStep`: при открытии с `mode = doctor|clinic` виджет сначала рендерит loading-state и только потом нужный шаг, чтобы исключить визуальное мигание стартового экрана на «холодном» первом открытии.
- 2026-03-23: в `resources/js/app.js` добавлен необязательный `bookingStartMode` для `showCallbackModal(...)` и проброс `bookingWidgetV3Mode` в layout; в `resources/views/components/doctor-card-alt.blade.php`, `resources/views/components/page/partials/doctor-card.blade.php`, `resources/views/doctors/show.blade.php`, `resources/js/components/InfiniteDoctorsList/index.js`, `resources/js/components/DoctorCard/DoctorCard.vue` и `resources/views/components/mega-menu/doctor-card.blade.php` триггеры кнопок врачей передают режим `doctor`, поэтому только в этих местах `BookingWidgetV3` открывается сразу на шаге выбора врача.
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
