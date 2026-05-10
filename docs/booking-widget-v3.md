# BookingWidgetV3: технический handbook

Этот документ описывает текущую реализацию виджета онлайн-записи `BookingWidgetV3`: как он открывается, какие сценарии поддерживает, какие файлы и API затрагивает, как устроены данные, фильтрация, цены, кэши и безопасная доработка.

Источником истины для этого документа является текущий код. Файлы `docs/booking-widget-birthdate-step-plan.md` и `docs/booking-widget-date-selection-plan.md` являются историческими планами разработки отдельных фич; они полезны для понимания решений, но не заменяют этот handbook.

## Быстрая карта

Основной frontend:

- `resources/js/components/BookingWidgetV3/BookingWidgetV3.vue` — root-компонент виджета, хранит flow-state, выбранные сущности, загрузки, кэши и навигацию между шагами.
- `resources/js/components/BookingWidgetV3/components/*` — визуальные шаги: старт, ДР, выбор врача/филиала, расписание, форма, успех, age-blocker.
- `resources/js/components/BookingWidgetV3/utils/*` — локальные helpers виджета: дата рождения, слоты, календарь, сортировка/выбор, возрастной blocker.
- `resources/js/services/bookingApi.js` — единый frontend API-service для виджета.
- `resources/js/utilities/bookingLaunchContext.js` — чтение и нормализация `data-*` launch context.
- `resources/js/utilities/doctorAge.js` — возрастные поля, форматирование возраста, расчет возраста по ДР.
- `resources/js/utilities/doctorPrice.js` — выбор отображаемой цены врача/филиала.

Backend сайта:

- `routes/api.php` — локальные proxy/enrichment endpoints `/api/booking/*`.
- `app/Http/Controllers/Api/Booking*Controller.php` — API controllers для данных виджета.
- `app/Services/BookingWidgetApiService.php` — прокси к `adminzrenie.ru/api/v1`, timeout/retry/cache.
- `app/Services/BookingDoctorLaunchService.php` — загрузка одного врача для direct-launch.
- `app/Services/BookingDoctorsByDateService.php` — агрегированный flow выбора по дате.
- `app/Services/BookingBranchEnrichmentService.php` — обогащение филиалов локальными данными сайта, включая `address`, `metro`, `price`.
- `app/Services/BookingSiteDoctorsService.php` — локальные данные видимых врачей сайта по UUID.

Тесты:

- `tests/Feature/BookingDoctorsByDateApiTest.php` — doctor launch, doctors-by-date, calendar, branches availability.
- `tests/Feature/BookingWidgetSortingTest.php` — сортировки и enrichment филиалов.
- `tests/Unit/Services/BookingWidgetApiServiceTest.php` — retry/config внешнего booking API.

## Назначение и подключение

`BookingWidgetV3` — новый Vue 2.7 виджет онлайн-записи. Он заменяет старую форму записи, если включен новый вариант формы.

Глобальное подключение находится в `resources/views/layouts/app.blade.php`:

```blade
<booking-widget-v3
    :open="bookingWidgetV3Active"
    :mode="bookingWidgetV3Mode"
    :launch-context="bookingWidgetV3LaunchContext"
    :callback-target="bookingWidgetV3Target"
    @close="closeBookingWidgetV3"
></booking-widget-v3>
```

Исключение: на route `booking.widget.v3.demo` глобальный экземпляр не рендерится.

Основной Vue root находится в `resources/js/app.js`. Там регистрируется `BookingWidgetV3`, хранится состояние открытия модалки и выбирается, открыть старую форму или новый виджет.

Ключевой переключатель:

- `window.config.booking.formVariant === "new"` — `showCallbackModal()` открывает `BookingWidgetV3`.
- иначе открывается legacy `OnlineAppointmentForm`.

## Точки открытия

Есть три основных способа открыть виджет.

1. Обычный вызов:

```js
showCallbackModal(null, "otpravka-formy")
```

Если включен новый вариант формы, вызов попадет в `openBookingWidgetV3(target, options)` и откроет виджет без launch context. Пользователь увидит стартовый шаг выбора сценария.

2. Прямой вызов:

```js
openBookingWidgetV3("otpravka-formy", options)
```

`options` может содержать launch context или mode.

3. Клик по элементу с `data-*`:

`resources/js/app.js` слушает `document.click`, ищет ближайший элемент по `BOOKING_LAUNCH_SELECTOR` и строит launch context через `buildBookingLaunchContextFromElement()`.

Поддерживаемые атрибуты:

```html
data-appointment-entry="doctor|clinic"
data-doctor-id="..."
data-branch-id="..."
data-clinic-id="..."
```

`data-clinic-id` сейчас нормализуется как `branchId` для совместимости с будущими/старыми кнопками, фактически direct clinic flow ожидает идентификатор филиала.

Автооткрытие:

- если URL hash равен `#appointment-form`, `autoOpenBookingWidgetV3FromUrl()` открывает обычный виджет.

EventBus:

- `eventBus.$emit("showCallbackModal", phone, target, options)` также проходит через root `showCallbackModal()`.

4. Прямая ссылка с GET-параметрами:

`autoOpenBookingWidgetV3FromUrl()` читает URL query string через `buildBookingLaunchContextFromSearchParams()`. Если в ссылке есть валидный booking launch context, виджет откроется автоматически без необходимости добавлять `#appointment-form`.

Рекомендуемые форматы:

```text
/?appointment=doctor&doctor_id=<uuid-врача>
/?appointment=clinic&branch_id=<external_id-филиала>
```

Короткие форматы:

```text
/?booking_doctor=<uuid-врача>
/?booking_branch=<external_id-филиала>
```

Совместимые aliases:

- entry: `appointment`, `appointment_entry`, `booking_entry`, `booking_mode`; значения `doctor`, `clinic`, `branch`, где `branch` нормализуется в `clinic`;
- doctor id: `doctor_id`, `doctor`, `doctor_uuid`, `booking_doctor`, `booking_doctor_id`;
- branch id: `branch_id`, `branch`, `clinic_id`, `clinic`, `booking_branch`, `booking_branch_id`, `booking_clinic`, `booking_clinic_id`.

Безопасное правило: bare `doctor_id` или `branch_id` сами по себе не открывают виджет. Для них нужен явный `appointment=doctor|clinic`. Автооткрытие без `appointment` разрешено только для prefixed параметров `booking_doctor*` и `booking_branch*`.

## Launch context

Launch context нормализуется в `resources/js/utilities/bookingLaunchContext.js`.

Selector:

```js
"[data-appointment-entry], [data-doctor-id], [data-branch-id], [data-clinic-id]"
```

Нормализованный объект:

```js
{
  entry: "doctor" | "clinic",
  doctorId: string | null,
  branchId: string | null
}
```

Источники launch context:

- HTML data-атрибуты через `buildBookingLaunchContextFromElement()`;
- GET-параметры через `buildBookingLaunchContextFromSearchParams()`;
- прямой JS-вызов `openBookingWidgetV3(target, options)`.

Правила:

- если нет `entry`, но есть только `doctorId`, entry становится `doctor`;
- если нет `entry`, но есть только `branchId`, entry становится `clinic`;
- если одновременно переданы doctor и branch без entry, context считается неоднозначным и сбрасывается в `null`;
- без валидного context виджет открывается как обычный.

Direct doctor:

- кнопка должна передать `data-appointment-entry="doctor"` и `data-doctor-id`;
- первый шаг пропускается;
- пользователь сразу попадает на шаг ДР;
- данные врача начинают грузиться заранее;
- после ДР виджет проверяет возраст и либо ведет на расписание врача, либо показывает age-blocker.

Direct clinic:

- кнопка должна передать `data-appointment-entry="clinic"` и `data-branch-id`;
- первый шаг пропускается;
- пользователь вводит ДР;
- выбранный филиал пропускает шаг выбора филиала;
- после ДР виджет идет сразу на расписание филиала.

## Основные сценарии

Обычный flow:

1. `start`
2. пользователь выбирает `doctor`, `clinic` или `date`
3. `birth-date`
4. дальнейшие шаги зависят от режима

Flow выбора врача:

1. `birth-date`
2. `doctor-select`
3. `doctor-schedule`
4. `form`
5. `success`

Flow выбора филиала:

1. `birth-date`
2. `clinic-select`, если филиалов больше одного
3. `clinic-schedule`
4. `form`
5. `success`

Если филиал один или direct clinic передал филиал, `clinic-select` может быть пропущен.

Flow выбора по дате:

1. `birth-date`
2. `date-select`
3. `form`
4. `success`

На `date-select` календарь определяет выбранную дату, а правая колонка показывает врачей, у которых есть прием в выбранный день и нужном городе.

Direct doctor:

1. открыть из карточки врача
2. `birth-date`
3. если возраст подходит: `doctor-schedule`
4. если возраст не подходит: `doctor-age-blocked`

## Шаги виджета

`start`

- Компонент: `StartStep.vue`.
- Выбор сценария записи.
- События: `select-mode`, `leave-request`, `close`.

`birth-date`

- Компонент: `BirthDateStep.vue`.
- Обязательный ввод ДР пациента.
- Валидная дата отправляется наверх как `{ display, iso }`.
- При изменении валидной ДР direct doctor flow запускает прогрев данных расписания.
- В форме финального шага ДР уже подставляется и readonly.

`doctor-select`

- Компонент: `DoctorSelectStep.vue`.
- Показывает список врачей, отфильтрованный по ДР.
- Цена и возрастная строка берутся из локальных utilities.

`clinic-select`

- Компонент: `ClinicSelectStep.vue`.
- Показывает филиалы города.
- После выбора филиала грузит врачей клиники.

`doctor-schedule`

- Компонент: `DoctorScheduleStep.vue`.
- Используется в flow выбора врача и direct doctor.
- Показывает выбранного врача, филиалы врача, календарь, подсветку дат и слоты.
- При смене даты обновляет филиалы/слоты; список филиалов старается не мигать за счет `keepVisibleBranches`.

`clinic-schedule`

- Компонент: `ClinicScheduleStep.vue`.
- Используется в flow выбора филиала.
- Показывает врачей выбранного филиала, календарь и слоты выбранного врача.

`date-select`

- Тоже использует `ClinicScheduleStep.vue`, но с `flowMode="date"`.
- Визуально похож на clinic schedule, но список врачей зависит от выбранной даты.
- На мобильных этот flow должен начинаться с календаря, а затем показывать врачей.

`form`

- Компонент: `PatientFormStep.vue`.
- Собирает ФИО, телефон, промокод, комментарий и ДР.
- ДР приходит из шага `birth-date`.

`doctor-age-blocked`

- Компонент: `DoctorAgeBlockedStep.vue`.
- Используется только в direct doctor scenario.
- Показывает текст вида `Данный врач принимает пациентов с ... до ...`.
- Ссылка `Пожалуйста выберите другого специалиста` переводит в обычный список врачей с уже введенной ДР.

`success`

- Компонент: `SuccessStep.vue`.
- Показывает успешную запись.

`leave-request`

- Компонент: `CallbackFormNew`.
- Альтернативная форма заявки, если пользователь не пошел по онлайн-расписанию.

## Root state

Ключевые поля `BookingWidgetV3.vue`:

- `currentStep` — текущий шаг.
- `selectedMode` — выбранный сценарий: `doctor`, `clinic`, `date`.
- `selectedDoctor`, `selectedClinic`, `selectedBranch` — выбранные сущности.
- `patientBirthDateDisplay`, `patientBirthDateIso` — ДР пациента.
- `selectedDate` — дата приема, по умолчанию текущая дата.
- `selectedSlot` — выбранный слот.
- `clinics`, `cityBranches`, `doctors`, `clinicDoctors`, `dateFlowDoctors` — списки для сценариев.
- `doctorFlowBranches` — филиалы выбранного врача на шаге расписания врача.
- `doctorFlowHighlightedDates`, `clinicFlowHighlightedDates`, `dateFlowHighlightedDates` — подсветка календарей.
- `doctorFlowLastAvailableDate`, `clinicFlowLastAvailableDate`, `dateFlowLastAvailableDate` — последняя доступная дата для сообщений.
- `loadingDoctors`, `loadingSlots`, `loadingDoctorFlowBranches`, `loadingDateFlowDoctors` — loading-флаги.
- `ageBlockedDoctor` — врач для direct doctor blocker.
- `directDoctorLaunchPreload`, `directDoctorScheduleWarmup` — фоновые preload-promises для direct doctor.
- `formSourceStep` — откуда пришли на форму, чтобы корректно работала кнопка назад.

Основные reset-методы:

- `resetState()` — полный сброс при закрытии/новом открытии.
- `resetFlowSelections()` — сброс выбора внутри текущего открытия после ввода новой ДР.

## Возрастная логика

Источник возраста врача:

- `age_min_months`
- `age_max_months`
- fallback в `doctor.extra.age_min_months`, `doctor.extra.age_max_months`

Функции:

- `resources/js/utilities/doctorAge.js`
- `resources/js/components/BookingWidgetV3/utils/doctorAgeBlocker.js`
- `filterDoctorsByBirthDate()` в root виджета

Возраст пациента считается в месяцах по `patientBirthDateIso`.

Правила:

- если `minAgeMonths` задан и пациент младше — врач не подходит;
- если `maxAgeMonths` задан и пациент старше — врач не подходит;
- если граница не задана — она не ограничивает;
- при невалидной/отсутствующей ДР фильтр не применяется.

Обычные списки врачей фильтруются так, чтобы неподходящие врачи не показывались.

Direct doctor отличается:

- конкретный врач грузится даже без фильтра по ДР (`ignoreBirthDate: true`);
- после ввода ДР проверяется локально;
- если возраст не подходит, показывается `doctor-age-blocked`, а не расписание.

Возрастная строка приема:

- строится через `getDoctorReceivesDisplay()`;
- учитывает `receives_display`, `receives_text`, `age_min_months`, `age_max_months`;
- `formatAgeMonths(0)` возвращает `рождения`, но дефолтные фразы могут отдельно выводить `0`, если так задано текущей логикой.

Важно: frontend age-check нужен для UX. Если нужно строго запретить запись неподходящего возраста, финальная защита должна быть также на backend/API создания заявки.

## Цены

Цена отображается через `resources/js/utilities/doctorPrice.js`.

Источники:

- взрослая цена врача: `doctor.extra.price`;
- детская цена врача: `doctor.extra.price_child`;
- акционная цена филиала: `branch.price`, приходит через branch enrichment из локальных данных города.

Текущее правило:

1. если у врача есть взрослая и детская цена, выбирается по возрасту пациента;
2. если у врача указана только одна из цен, показывается она;
3. если у врача нет цены, используется `branch.price`;
4. если нет ни цены врача, ни цены филиала, цена не показывается.

Граница детской цены:

- пациент считается ребенком, если возраст меньше `18 * 12` месяцев.

Где отображается:

- `DoctorSelectStep.vue` — карточки врачей в выборе врача;
- `DoctorScheduleStep.vue` — выбранный врач в расписании врача;
- `ClinicScheduleStep.vue` — карточки врачей в flow филиала и даты.

Важный нюанс: branch promo price корректно применима только там, где известен филиал. В первом списке выбора врача филиал еще не выбран, поэтому branch-specific цену невозможно определить точно без дополнительной логики/API.

## Frontend API

`resources/js/services/bookingApi.js` использует два типа запросов:

- прямые запросы к `https://adminzrenie.ru/api/v1`;
- локальные `/api/booking/*` endpoints сайта, когда нужно обогащение локальными врачами/филиалами или оптимизированная агрегация.

Основные методы:

- `getCities()` — внешнее `/cities`.
- `getDoctorsByCity(cityId, birthDate)` — внешнее `/cities/{city}/doctors`.
- `getClinicsByCity(cityId)` — внешнее `/cities/{city}/clinics`.
- `getClinicDoctors(clinicId, birthDate, branchId)` — внешнее `/clinics/{clinic}/doctors`.
- `getDoctorSlots(doctorId, date, clinicId, branchId)` — внешнее `/doctors/{doctor}/slots`.
- `getCalendarAvailability({ doctorId, dateFrom, dateTo, clinicId, branchId })` — внешнее `/booking/calendar-availability`.
- `createApplication(applicationData)` — внешнее `/applications`.
- `getClinicBranches(clinicId, cityId)` — локальное `/api/booking/clinics/{clinic}/branches`.
- `getDoctorBranchesAvailability(doctorId, date, clinicId, cityId)` — локальное `/api/booking/doctors/{doctor}/branches-availability`.
- `getDoctorsByDate(cityId, date, birthDate)` — локальное `/api/booking/cities/{city}/doctors-by-date`.
- `getDoctorsByDateCalendarAvailability(cityId, dateFrom, dateTo, birthDate)` — локальное `/api/booking/cities/{city}/doctors-by-date/calendar`.
- `getSiteDoctorsByUuids(uuids)` — локальное `/api/booking/doctors`.
- `getDoctorLaunchPayload(doctorUuid, bookingCityId, birthDate)` — локальное `/api/booking/doctors/{uuid}/launch`.

Локальные запросы автоматически добавляют `site_city_id` через `window.config.booking.siteCityId`, если он есть.

## Backend/API сайта

Локальные маршруты в `routes/api.php`:

```php
GET api/booking/doctors
GET api/booking/doctors/{doctor}/launch
GET api/booking/doctors/{doctor}/branches-availability
GET api/booking/cities/{city}/doctors-by-date
GET api/booking/cities/{city}/doctors-by-date/calendar
GET api/booking/clinics/{clinic}/branches
```

Зачем нужны локальные endpoints:

- скрыть часть сложности внешнего API;
- добавить локальные данные сайта по врачам;
- отфильтровать врачей по видимости на сайте;
- обогатить филиалы локальными `address`, `metro`, `price`;
- ускорить тяжелые flow через cache/aggregation;
- учитывать `site_city_id`.

`BookingWidgetApiService`:

- ходит во внешний booking API;
- base URL берется из `config('zrenie-clinic.booking_api_base_url')`;
- timeout: connect 5s, request 15s;
- retry: 2 попытки, delay 200ms;
- retryable statuses: `408`, `429`, `500`, `502`, `503`, `504`;
- cache TTL: 60 секунд.

`BookingDoctorLaunchService`:

- принимает UUID врача сайта и booking city id;
- проверяет, что локальный врач видим;
- ищет соответствующего врача во внешнем списке по UUID;
- мержит внешние данные с локальными данными врача;
- cache TTL: 60 секунд.

`BookingDoctorsByDateService`:

- обслуживает flow выбора по дате;
- возвращает врачей, доступных на дату;
- возвращает календарную доступность диапазона дат;
- учитывает видимых врачей сайта;
- обогащает карточки врача и филиала;
- cache TTL: 60 секунд.

`BookingBranchEnrichmentService`:

- сопоставляет филиалы внешнего API с локальными филиалами города;
- matching по `external_id`, адресу, названию, телефону, email, координатам;
- локальные поля `address`, `metro`, `price` могут переопределить API payload.

## Кэши и preload

Frontend cache в `BookingWidgetV3.vue`:

- `clinicsCacheByCity` — клиники города, TTL 60s.
- `cityBranchesCacheByCity` — филиалы города, TTL 60s.
- `doctorsCacheByCity` — врачи города с учетом ДР, TTL 60s.
- `doctorLaunchCacheByQuery` — один врач для direct launch, TTL 60s.
- `dateFlowDoctorsCacheByQuery` — врачи на выбранную дату, TTL 60s.
- `dateFlowCalendarCacheByQuery` — календарь flow выбора по дате, TTL 60s.
- `siteDoctorsCacheByUuids` — локальные данные врачей сайта, TTL 60s.
- `slotsCacheByQuery` — слоты врача, TTL 30s.
- `doctorFlowBranchesAvailabilityCacheByQuery` — филиалы врача с доступностью, TTL 30s.

Direct doctor preload:

- при открытии direct doctor сразу вызывается `preloadDirectDoctorLaunch()`;
- при вводе ДР дополнительно подталкивается загрузка врача;
- когда ДР становится валидной, `preloadDirectDoctorSchedule()` прогревает doctor launch, clinics, branches availability и первые слоты;
- после кнопки `Далее` глобальный overlay для direct doctor не показывается, возможная догрузка остается внутри schedule step.

Цель preload — убрать промежуточный экран загрузки между ДР и расписанием, не меняя API-контракт.

Backend cache:

- `BookingWidgetApiService` кеширует GET-запросы к внешнему API на 60s;
- `BookingDoctorLaunchService` кеширует direct doctor payload на 60s;
- `BookingDoctorsByDateService` кеширует day/calendar payload на 60s.

## Blade/Vue integration

Direct doctor кнопки находятся, например, в:

- `resources/views/doctors/show.blade.php`
- `resources/views/doctors/show-2.blade.php`
- `resources/views/components/doctor-card-alt.blade.php`
- `resources/views/components/page/partials/doctor-card.blade.php`
- `resources/views/components/mega-menu/doctor-card.blade.php`
- `resources/js/components/DoctorCard/DoctorCard.vue`

Минимальный direct doctor markup:

```html
data-appointment-entry="doctor"
data-doctor-id="uuid-врача"
```

Direct clinic кнопки находятся, например, в:

- `resources/views/components/block/branch.blade.php`

Минимальный direct clinic markup:

```html
data-appointment-entry="clinic"
data-branch-id="external_id-филиала"
```

Обычные кнопки без `data-*` продолжают открывать стандартный flow через `showCallbackModal(null, "otpravka-formy")`.

Не привязывать новый launch behavior к CSS-классам, тексту кнопки или DOM-позиции. Источник истины для direct-сценариев — только data-атрибуты.

## Мультигород

Frontend определяет текущий booking city id через список городов внешнего API и кандидатов:

- `window.currentCity?.name`;
- текущий city из `window.config.cities`;
- `window.config.state.currentCity?.name`.

Сначала ищется точное совпадение имени после нормализации, затем мягкое совпадение. Если город не найден, fallback — Москва, затем первый город из API.

Site city:

- `bookingApi.getSiteCityId()` берет `window.config.booking.siteCityId`;
- локальные `/api/booking/*` получают `site_city_id`;
- backend использует `site_city_id` для видимых врачей и enrichment филиалов.

Allowed clinics:

- `window.config.booking.allowedClinicIds` ограничивает клиники/филиалы на фронте;
- backend branch sync/sorting также учитывает `config('zrenie-clinic.booking_allowed_clinic_ids')`.

## Создание заявки

Форма отправляется из `handleFormSubmit()` через `bookingApi.createApplication()`.

Payload:

```js
{
  city_id,
  clinic_id,
  branch_id,
  doctor_id,
  cabinet_id,
  appointment_datetime,
  onec_slot_id,
  full_name,
  full_name_parent,
  birth_date,
  phone,
  promo_code,
  comment,
  appointment_source: "site",
  ...utm
}
```

Источники:

- `city_id` — `currentCityId`;
- `clinic_id`, `branch_id`, `doctor_id` — сначала из selected slot, затем из выбранных сущностей;
- `appointment_datetime` — из slot или собирается из `selectedDate + selectedSlot.time`;
- `birth_date` — из финальной формы, туда подставляется ДР со шага `birth-date`;
- UTM — из query string или `window.config.utm`.

После успешной заявки:

- вызывается цель Яндекс.Метрики `ym(94302729, "reachGoal", "bloki-otpravka-formy")`;
- шаг меняется на `success`.

Ошибки:

- `422` прокидывается в `PatientFormStep` как field errors;
- остальные ошибки показываются как general error.

## Правила безопасной доработки

Перед изменениями:

- проверить текущий flow в `BookingWidgetV3.vue`;
- проверить, не затрагивает ли изменение direct doctor/direct clinic/date flow;
- проверить, нужен ли локальный `/api/booking/*` endpoint или достаточно прямого `bookingApi.js`;
- проверить, есть ли уже utility в `utils/*` или `resources/js/utilities/*`.

Добавление нового шага:

- добавить компонент в `components/`;
- добавить `currentStep` branch в template root;
- явно описать переходы вперед/назад;
- обновить `formSourceStep`, если шаг ведет на форму;
- проверить mobile ordering, если шаг использует schedule layout.

Добавление нового direct-сценария:

- расширять `bookingLaunchContext.js`, а не искать кнопки по CSS/тексту;
- обеспечить safe fallback при невалидных атрибутах;
- сбрасывать старый context при повторном открытии;
- проверить, не нужен ли preload, чтобы не вернуть долгий промежуточный overlay.

Изменение age-логики:

- менять `doctorAge.js`/`doctorAgeBlocker.js` и проверять все три flow;
- помнить, что frontend age-check не является финальной защитой от записи;
- если запись должна быть строго запрещена, дублировать проверку на backend/API заявки.

Изменение цен:

- использовать `doctorPrice.js`;
- проверить карточку выбора врача, schedule врача, schedule филиала и date flow;
- помнить, что branch promo price доступен только когда известен филиал.

Изменение API:

- для тяжелых агрегированных данных предпочитать локальный `/api/booking/*` endpoint с backend cache/enrichment;
- не ломать прямые внешние endpoints в `bookingApi.js`, если они используются другими flow;
- учитывать `site_city_id`, `booking_city_id`, `birth_date`, `clinic_id`, `branch_id`.

Минимальный ручной чеклист после изменений:

- обычное открытие без data-атрибутов;
- flow выбора врача;
- flow выбора филиала;
- flow выбора по дате;
- direct doctor с подходящей ДР;
- direct doctor с неподходящей ДР и `doctor-age-blocked`;
- direct clinic с предвыбранным филиалом;
- кнопка назад из schedule/form;
- цена взрослого/ребенка/филиала;
- подсветка дат и выбор слота;
- создание заявки.

Релевантные проверки:

```bash
php artisan test tests/Feature/BookingDoctorsByDateApiTest.php
php artisan test tests/Feature/BookingWidgetSortingTest.php
npm run build
```

Для documentation-only изменений сборка и тесты не обязательны.
