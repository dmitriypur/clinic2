# Статический аудит безопасности сайта

Дата: 3 ноября 2026 года. Репозиторий: `zrenie.clinic-7`, ветка `main`, HEAD `74b36a3353e33e1526ede8d2001e9c924a970645`.

## Вывод

В коде обнаружены существенные риски: выполнение JavaScript через поисковую ссылку, постоянный четырёхзначный код входа вместо одноразового, истощение ресурсов через календарь записи и SSRF при импорте изображений. Также найдены ошибки разграничения прав и публичные операции, которыми можно злоупотреблять для изменения данных и нагрузки на интеграцию.

В зависимостях есть опубликованные уязвимости. Особенно существенны доступный до авторизации разбор HTTP-заголовков Symfony и временная загрузка файлов через страницу входа Filament. Часть остальных предупреждений относится к средствам разработки либо требует функций, которые здесь не используются.

Это аудит исходников, а не подтверждение взлома сайта. Эксплуатация не выполнялась. Код приложения не изменялся.

## Объём и метод

Проверены маршруты и middleware, контроллеры и FormRequest, вход и личный кабинет, интеграции UNF и услуг, календарь и API записи, импорт статей, загрузка и скачивание файлов, политики и пользовательские действия Filament/Livewire, SQL-конструкции, Blade/Vue и DOM-операции, сериализация данных для браузера, конфигурация сессий, хранения и развёртывания.

Выполнены только чтение и статический анализ, а также проверки метаданных зависимостей:

```text
composer audit --locked --no-interaction --format=json
npm audit --package-lock-only --ignore-scripts --json
```

Тесты не писались и не запускались. Приложение, сборка, миграции, браузер, SSH и запросы к рабочему сайту не запускались. Содержимое производственной БД, `.env`, веб-сервер и CDN не проверялись. Для JavaScript использованы рекомендации навыка `security-best-practices`; отдельного PHP/Laravel-справочника в нём нет, поэтому выводы по backend основаны на коде и официальных первоисточниках.

«Подтверждено статически» означает, что прослежена цепочка в коде; это не означает, что выполнен проверочный запрос. Реальное воздействие может уменьшаться ограничениями инфраструктуры, которых нет в репозитории.

## Приоритеты

| ID | Уровень | Проблема | Условия |
|---|---|---|---|
| SEC-01 | Высокий | XSS через компиляцию поискового запроса в Vue | Посетитель открывает подготовленную ссылку |
| SEC-02 | Высокий | Четырёхзначный код остаётся постоянным паролем | Существующая учётная запись с выданным кодом |
| SEC-03 | Высокий | Неограниченный диапазон календаря | Публичный API, без входа |
| SEC-04 | Высокий | SSRF в импорте изображений | Право импорта либо импорт подменённого внешнего документа |
| SEC-05 | Высокий | ReDoS в установленном Symfony HttpFoundation | Публичный HTTP-запрос; опубликованное advisory |
| SEC-06 | Средний | Временные загрузки без входа в Filament | Доступная страница входа; опубликованное advisory |
| SEC-07 | Средний | Генерация фидов доступна обычному пользователю | Вход в кабинет пациента |
| SEC-08 | Средний | Callback меняет имя чужой учётной записи | Знание её телефона, без входа |
| SEC-09 | Средний | Любой web-запрос может создать задание в UNF | Параметры `source` и `place`, без входа |
| SEC-10 | Средний | Публичный GET очищает рабочие кэши | Без входа |
| SEC-11 | Средний | Импорт врачей не требует права создания | Сотрудник с доступом к списку врачей, кроме `demo` |

Условные риски и общая задолженность по зависимостям приведены отдельно: они не считаются доказанными способами взлома production.

## Высокий приоритет

### SEC-01. Поисковый запрос становится исполняемым шаблоном Vue

**Статус:** подтверждено статически. **Класс:** CWE-79, VUE-XSS-002/003.

**Места:** [app/Http/Requests/SiteSearchRequest.php:17](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Requests/SiteSearchRequest.php:17), [app/Http/Controllers/SearchController.php:22](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Controllers/SearchController.php:22), [resources/views/search/results.blade.php:20](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/search/results.blade.php:20), [resources/views/search/results.blade.php:80](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/search/results.blade.php:80), [resources/views/layouts/app.blade.php:60](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/layouts/app.blade.php:60), [resources/views/layouts/app.blade.php:95](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/layouts/app.blade.php:95), [resources/js/app.js:889](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/js/app.js:889), [vite.config.js:40](/Applications/MAMP/htdocs/zrenie.clinic-7/vite.config.js:40).

Параметр `q` проверяется только как строка длиной до 100 символов. Blade выводит его через `{{ $search }}` в текст страницы. Вся страница находится внутри `#app`, на который монтируется Vue без заранее заданного render-шаблона. В сборке выбран `vue/dist/vue.esm.js`, содержащий компилятор. Поэтому последовательность с двойными фигурными скобками, пришедшая в `q`, интерпретируется повторно уже как выражение Vue.

HTML-экранирование здесь недостаточно: скобки сохраняются, а закодированные HTML-сущности браузер преобразует обратно в символы до компиляции. Для иллюстрации механизма достаточно `q={{7*7}}`: это данные запроса, которые могут превратиться в вычисляемое выражение. Никакие такие ссылки при аудите не открывались. В установленном компиляторе цепочка видна в [node_modules/vue/dist/vue.esm.js:11843](/Applications/MAMP/htdocs/zrenie.clinic-7/node_modules/vue/dist/vue.esm.js:11843) и [node_modules/vue/dist/vue.esm.js:11659](/Applications/MAMP/htdocs/zrenie.clinic-7/node_modules/vue/dist/vue.esm.js:11659).

**Последствия:** злоумышленник может подготовить ссылку, при открытии которой браузер выполнит JavaScript с происхождением сайта. Это позволяет читать доступную странице информацию и выполнять доступные пользователю действия; HttpOnly не предотвращает действия через его браузер.

**Исправление:** исключить недоверенный серверный текст из области компиляции. Для поисковой страницы можно точечно использовать `v-pre` на текстовых элементах с запросом; системное решение — изолированные точки монтирования и заранее скомпилированные компоненты. Проверить также CMS-контент и другие динамические текстовые узлы внутри `#app`. Простое добавление HTML-санитайзера не устраняет шаблонную инъекцию.

**Граница вывода:** строгая CSP без разрешения динамической компиляции могла бы блокировать цепочку, но тогда используемый способ монтирования Vue также перестал бы работать. Такая CSP в исходниках не задана. Проблему прямо описывает [официальное руководство Vue 2](https://v2.vuejs.org/v2/guide/security.html#What-Vue-Does-to-Protect-You).

### SEC-02. Код входа не одноразовый и не имеет срока действия

**Статус:** подтверждено статически. **Класс:** CWE-307/CWE-613.

**Места:** [app/Http/Controllers/UserPasswordController.php:19](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Controllers/UserPasswordController.php:19), [app/Http/Controllers/Auth/LoginController.php:47](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Controllers/Auth/LoginController.php:47), [app/Http/Controllers/Auth/LoginController.php:54](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Controllers/Auth/LoginController.php:54), [app/Models/User.php:33](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Models/User.php:33), [vendor/laravel/ui/auth-backend/ThrottlesLogins.php:91](/Applications/MAMP/htdocs/zrenie.clinic-7/vendor/laravel/ui/auth-backend/ThrottlesLogins.php:91).

Код создаётся как `rand(1000, 9999)` и его bcrypt-хеш сохраняется прямо в `users.password`. Вход использует стандартную проверку пароля. Нет отдельной OTP-записи, срока годности, счётчика попыток кода или удаления после успешного входа. Следовательно, выданный код продолжает работать до следующей замены пароля.

Защита от перебора **присутствует**: стандартный Laravel UI ограничивает попытки по сочетанию телефон + IP. Однако пространство составляет только 9000 значений, срок перебора не ограничен временем жизни кода, а распределённые попытки с разных IP не объединяются по аккаунту. При небезопасной конфигурации proxy риск усиливается, см. COND-01. `rand()` также не предназначен для генерации криптографических секретов.

**Последствия:** подбор либо повторное использование однажды узнанного кода даёт доступ в кабинет, включая историю приёмов, которую загружает [app/Http/Controllers/Profile/HistoryController.php:16](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Controllers/Profile/HistoryController.php:16).

**Исправление:** отдельные короткоживущие OTP, криптографический генератор, достаточная длина, небольшой лимит попыток на телефон/код, ограничение выдачи, атомарное погашение после успеха. После перехода аннулировать старые четырёхзначные пароли. Оставить ограничения по IP как дополнительный слой.

### SEC-03. Календарь допускает истощение памяти и CPU одним диапазоном дат

**Статус:** подтверждено статически. **Класс:** CWE-400.

**Места:** [routes/api.php:32](/Applications/MAMP/htdocs/zrenie.clinic-7/routes/api.php:32), [app/Http/Controllers/Api/BookingCityDoctorsByDateCalendarController.php:22](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Controllers/Api/BookingCityDoctorsByDateCalendarController.php:22), [app/Services/BookingDoctorsByDateService.php:76](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/BookingDoctorsByDateService.php:76), [app/Services/BookingDoctorsByDateService.php:252](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/BookingDoctorsByDateService.php:252).

`date_from` и `date_to` проверяются только на формат. `buildDateRange()` затем создаёт массив для каждого дня в цикле `while ($cursor->lte($end))`. Позже из него строится дополнительная коллекция записей календаря. Ограничения на число дней, близость к текущему времени и допустимый горизонт записи нет. Очень длинный корректный диапазон достигает этого цикла даже до обращения к внешнему API.

**Последствия:** публичный запрос может занять PHP-worker, исчерпать память или создать большой ответ и запись кэша. Общий лимит 60 запросов в минуту на IP не ограничивает стоимость одного запроса.

**Исправление:** проверять порядок дат, максимальную длительность и допустимый горизонт календаря до вызова сервиса; повторить ограничение на уровне сервиса. Выбрать предел по фактическому интерфейсу, например один-два месяца. Нагрузочные запросы при аудите не отправлялись.

### SEC-04. Импорт изображения допускает запрос к произвольному внутреннему адресу

**Статус:** подтверждено статически. **Класс:** CWE-918.

**Места:** [app/Services/ArticleImport/ArticleContentParser.php:620](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/ArticleImport/ArticleContentParser.php:620), [app/Services/ArticleImport/ArticleContentParser.php:628](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/ArticleImport/ArticleContentParser.php:628), [app/Services/ArticleImport/GoogleDriveImageImporter.php:64](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/ArticleImport/GoogleDriveImageImporter.php:64), [app/Services/ArticleImport/GoogleDriveImageImporter.php:83](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/ArticleImport/GoogleDriveImageImporter.php:83), [app/Services/ArticleImport/ArticleImportService.php:355](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/ArticleImport/ArticleImportService.php:355), [app/Filament/Resources/PagePostResource/Pages/ListPagePosts.php:31](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Filament/Resources/PagePostResource/Pages/ListPagePosts.php:31).

Парсер принимает ссылку изображения по наличию подстроки `drive.google.com/file/d/`, не проверяя hostname. Загрузчик преобразует её в доверенный Google URL только если regex дополнительно нашёл идентификатор файла. Иначе он возвращает исходную ссылку, которую затем передаёт в `Http::get()`.

Например, URL с внутренним хостом и параметром `marker=drive.google.com/file/d/` удовлетворяет проверке парсера, но без идентификатора не проходит преобразование загрузчика. Сервер обращается к исходному хосту. Это анализ условий, никакие внутренние адреса не запрашивались.

**Последствия:** сотрудник с правом импорта или автор подменённого документа может заставить сервер обращаться к локальным/внутренним HTTP-сервисам. Проверка изображения после скачивания не предотвращает сам запрос. Чтение секретов или выполнение кода через конкретный внутренний сервис этим аудитом не доказано.

**Исправление:** извлекать и валидировать именно Google Drive ID из URL с точным разрешённым хостом; полностью удалить fallback к произвольному URL. Контролировать redirects и адрес назначения каждого перехода. Добавить сетевой запрет внутренних диапазонов, ограничение размера скачивания и проверку MIME/содержимого.

### SEC-05. Symfony HttpFoundation содержит доступный без входа ReDoS

**Статус:** установленная версия и цепочка вызовов подтверждены; эксплуатация не выполнялась. **Класс:** CWE-1333/CWE-400.

**Места:** [composer.lock:8316](/Applications/MAMP/htdocs/zrenie.clinic-7/composer.lock:8316), [vendor/symfony/http-foundation/HeaderBag.php:157](/Applications/MAMP/htdocs/zrenie.clinic-7/vendor/symfony/http-foundation/HeaderBag.php:157), [vendor/symfony/http-foundation/HeaderBag.php:286](/Applications/MAMP/htdocs/zrenie.clinic-7/vendor/symfony/http-foundation/HeaderBag.php:286), [vendor/symfony/http-foundation/HeaderUtils.php:45](/Applications/MAMP/htdocs/zrenie.clinic-7/vendor/symfony/http-foundation/HeaderUtils.php:45).

Зафиксирована версия `symfony/http-foundation v6.4.28`. При создании запроса `HeaderBag` разбирает `Cache-Control` через уязвимый `HeaderUtils::split()`. Опубликованное [CVE-2026-108082 от разработчиков Symfony](https://symfony.com/cve-2026-108082) описывает чрезмерные затраты CPU на специально сформированном заголовке. Аутентификация и бизнес-маршрут для достижения парсера не требуются.

**Последствия:** увеличение нагрузки на PHP до проверок контроллера и его ограничений запросов. Возможность и масштаб воздействия зависят также от обработки заголовков на reverse proxy.

**Исправление:** обновить совместимую ветку HttpFoundation с учётом всех advisory. Для указанного CVE исправление вышло в `6.4.46`, а корректировка поведения парсера — в `6.4.48`; одного обновления до `6.4.29`, закрывающего старое CVE-2025-64500, сейчас недостаточно. Проверить ограничения заголовков на сервере. Остальные зависимости перечислены ниже.

## Средний приоритет

### SEC-06. Filament позволяет временно загружать файлы без авторизации

**Места:** [composer.json:16](/Applications/MAMP/htdocs/zrenie.clinic-7/composer.json:16), [app/Providers/Filament/AdminPanelProvider.php:50](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Providers/Filament/AdminPanelProvider.php:50), [config/livewire.php:68](/Applications/MAMP/htdocs/zrenie.clinic-7/config/livewire.php:68), [vendor/filament/forms/src/Concerns/InteractsWithForms.php:25](/Applications/MAMP/htdocs/zrenie.clinic-7/vendor/filament/forms/src/Concerns/InteractsWithForms.php:25).

Зафиксирован `filament/filament 3.3.43`, включена страница `login()`. В этой версии компоненты форм наследуют `WithFileUploads`, в том числе формы входа без поля загрузки. Уязвимость описана в [GHSA-44wp-g8f4-f4v5](https://github.com/filamentphp/filament/security/advisories/GHSA-44wp-g8f4-f4v5), исправление ветки v3 — `3.3.52`. В проекте глобальный лимит временного файла повышен до 200 MB, что увеличивает потенциальный ущерб. Фактический HTTP-лимит PHP/nginx неизвестен.

**Последствия:** заполнение временного хранилища и отказ в обслуживании без входа. Исполнение загруженного PHP этим не доказано: речь о временных файлах, а не об их выполнении.

**Исправление:** согласованно обновить пакеты Filament; понизить лимит временных загрузок до необходимого, ограничить частоту и объём. Из-за отдельного advisory RichEditor обновление только до `3.3.52` недостаточно для всего набора проблем, см. DEP-01.

### SEC-07. Операция администрирования использует guard пациента

**Места:** [routes/web.php:65](/Applications/MAMP/htdocs/zrenie.clinic-7/routes/web.php:65), [app/Http/Controllers/YmlFeedController.php:19](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Controllers/YmlFeedController.php:19), [app/Services/YmlFeedService.php:330](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/YmlFeedService.php:330), [config/auth.php:17](/Applications/MAMP/htdocs/zrenie.clinic-7/config/auth.php:17).

`POST /admin/yml-feed/generate` защищён `auth`, то есть guard `web`, а не `staff`. Политики/права внутри контроллера нет. Обычный пользователь кабинета может запустить генерацию фидов всех активных городов и их запись в public storage. На операции установлен `set_time_limit(120)`, отдельного throttling нет.

**Последствия:** запуск административной операции и повторная тяжёлая обработка с правами пациента. CSRF защищает от сторонней формы, но не от самого вошедшего пользователя.

**Исправление:** `auth:staff` вместе с явным правом генерации; предпочтительно перенести операцию в защищённое действие Filament. Добавить ограничение повторного запуска/блокировку параллельной генерации.

### SEC-08. Публичная заявка перезаписывает имя существующего пациента

**Места:** [routes/api.php:39](/Applications/MAMP/htdocs/zrenie.clinic-7/routes/api.php:39), [app/Http/Requests/CallbackRequest.php:50](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Requests/CallbackRequest.php:50), [app/Http/Controllers/CallbackController.php:29](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Controllers/CallbackController.php:29).

`User::updateOrCreate(['phone' => ...], ['name' => $request->name])` выполняется без проверки владения телефоном. Любой посетитель может указать номер другого пользователя и новое имя. Изменение сохраняется **до** обращения в UNF и разбора ответа, поэтому даже отказ внешней системы не откатывает его.

**Последствия:** изменение персональных данных чужой учётной записи и загрязнение данных интеграции. Само по себе это не доказывает захват аккаунта.

**Исправление:** хранить имя заявителя в записи заявки, существующего пользователя находить без изменения его профиля. Обновлять профиль только из авторизованного сценария.

### SEC-09. GET-параметры позволяют без ограничений ставить задания в UNF

**Места:** [app/Http/Kernel.php:42](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Kernel.php:42), [app/Http/Middleware/InterceptSource.php:19](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Middleware/InterceptSource.php:19), [app/Jobs/SendSourceToUnf.php:29](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Jobs/SendSourceToUnf.php:29).

В web-группе middleware наличие любых `source` и `place` запускает `SendSourceToUnf::dispatch()`. Нет проверки метода, формата/длины полей, отдельного лимита, дедупликации или проверки успешности основного маршрута. Задание добавляется до вызова следующего обработчика, в том числе перед возможным 404. Внешний запрос отправляется с серверным интеграционным токеном на фиксированный UNF endpoint.

**Последствия:** заполнение очереди и исходящие запросы от имени сайта. При синхронной очереди нагрузка ложится прямо на web-workers; при асинхронной задерживает другие задания. Токен этим путём пользователю не раскрывается.

**Исправление:** отдельная ограниченная точка приёма аналитики, валидация строк и размера, лимиты, дедупликация/агрегация, отдельная очередь. Добавить таймауты внешнего вызова.

### SEC-10. Очистка ценовых кэшей доступна любому посетителю

**Место:** [routes/web.php:26](/Applications/MAMP/htdocs/zrenie.clinic-7/routes/web.php:26).

`GET /clear-price` без авторизации удаляет кэш цен, услуг и промоблоков. Это изменяющая состояние служебная операция в публичном GET без ограничения частоты.

**Последствия:** злоумышленник может регулярно сбрасывать кэш и вынуждать сайт повторять дорогие запросы к БД/UNF. Изменение самих цен не выполняется. Фактическая тяжесть зависит от посещаемости и стоимости восстановления кэша.

**Исправление:** убрать публичный маршрут; выполнять очистку из консольной команды либо действия админки с правом, CSRF и ограничением частоты.

### SEC-11. Импорт врачей обходит право создания записей

**Места:** [app/Filament/Resources/DoctorResource/Pages/ListDoctors.php:70](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Filament/Resources/DoctorResource/Pages/ListDoctors.php:70), [app/Policies/DoctorPolicy.php:17](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Policies/DoctorPolicy.php:17), [app/Policies/DoctorPolicy.php:33](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Policies/DoctorPolicy.php:33), [app/Services/DoctorImportFromBookingApiService.php:29](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/DoctorImportFromBookingApiService.php:29).

Пользовательское действие `import_doctors_from_api` проверяет только отсутствие роли `demo`. Оно вызывает сервис импорта непосредственно, без `DoctorResource::canCreate()` или отдельного разрешения. Между тем политика отдельно разграничивает `view_any_doctor` и `create_doctor`.

**Последствия:** сотрудник с правом просмотра списка, не имеющий права создания врачей, может создавать записи через импорт. Стандартная защита кнопки CreateAction на пользовательское действие не распространяется.

**Исправление:** авторизовать действие по `create_doctor`/отдельному разрешению импорта и проверять право непосредственно перед запуском. Аналогично ограничить генерацию фидов в этой странице. Наличие в production именно такой ограниченной роли не проверялось; дефект проявляется при её использовании.

## Зависимости и срок поддержки

### DEP-01. Lock-файлы содержат версии с опубликованными уязвимостями

`composer audit` вернул **70 записей advisory для 23 пакетов**, включая dev-зависимости. Это не 70 доказанных векторов атаки: в выдаче есть, в частности, две записи одного GHSA для Laravel email rule. `npm audit` вернул **23 позиции пакетов**: 2 critical, 13 high, 7 moderate, 1 low. Эти числа относятся к базе advisory, а не к оценке уязвимости сайта.

Приоритеты применимости:

- **HttpFoundation 6.4.28:** публичный разбор заголовков достижим, см. SEC-05. Также версия затронута [CVE-2025-64500](https://symfony.com/cve-2025-64500), но конкретный обход авторизации через PATH_INFO в этом проекте не доказан.
- **Filament 3.3.43:** публичная временная загрузка — SEC-06; [disabled RichEditor XSS](https://github.com/filamentphp/filament/security/advisories/GHSA-m9cv-24rx-8mv7) исправлена в `filament/forms 3.3.53`. Отключаемые RichEditor присутствуют в формах проекта; второму случаю нужен атакующий, способный записать опасное HTML-содержимое, и пользователь, открывший отключённое поле. Есть также advisory AttachAction/AssociateAction, его конкретная эксплуатация не доказана.
- **spatie/schema-org 3.23.1:** в установленном [vendor/spatie/schema-org/src/BaseType.php:163](/Applications/MAMP/htdocs/zrenie.clinic-7/vendor/spatie/schema-org/src/BaseType.php:163) JSON-LD выводится с `JSON_UNESCAPED_SLASHES`, без защиты от закрывающего `</script>`. Публичные вызовы есть в [resources/views/layouts/app.blade.php:159](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/layouts/app.blade.php:159) и [resources/views/posts/show.blade.php:64](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/posts/show.blade.php:64). Доступ к записи соответствующего CMS-поля позволяет выйти из JSON-LD в HTML. Исправление для ветки v3 опубликовано в [3.23.2](https://github.com/spatie/schema-org/releases/tag/3.23.2). Публичного анонимного пути записи такого поля не найдено.
- **symfony/html-sanitizer 6.4.28:** используется `sanitizeHtml()` и `HtmlCardSanitizer`; есть несколько advisory обхода URL-фильтрации. Применимость каждого зависит от разрешённых элементов/атрибутов; нельзя считать все обходы достижимыми при `allowSafeElements()`. Обновить совместимую ветку.
- **league/commonmark 2.7.1:** используется для импорта. Актуальны риски затрат на обработку недоверенного Markdown; ряд предупреждений требует расширений, которые в данном парсере не включены. Автоматически переносить все перечисленные XSS на этот импорт неверно.
- **MediaLibrary/Guzzle:** используются на сервере; обновить совместимые версии. SSRF приложения в SEC-04 существует независимо от advisory этих библиотек. Отдельный `addMediaFromUrl()` с анонимным вводом не обнаружен.
- **Laravel TokenGuard critical:** в `config/auth.php` настроены session-guards, TokenGuard не используется. Это предупреждение о версии пакета, **не доказанный критический обход входа на сайте**.
- **npm form-data critical и Axios Node HTTP-adapter:** сайт использует Axios в браузере, серверного JS-приложения не обнаружено. Например, [CVE-2025-58754](https://github.com/axios/axios/security/advisories/GHSA-4hjh-wcwx-xvwj) относится к Node-адаптеру; его нельзя объявлять удалённым DoS PHP-сайта.
- **Swiper critical:** версия `11.2.10` входит в диапазон prototype pollution advisory; в просмотренном коде настройки слайдеров формируются приложением. Передача полностью контролируемого атакующим объекта настроек не установлена.
- **Vite/esbuild/PostCSS/Rollup и другие build-инструменты:** риск главным образом в разработке/сборке. В Envoy используется production build; публичный dev server не обнаружен.
- **PHPUnit/PsySH/Browsershot:** предупреждения требуют своих сценариев использования. PHPUnit — dev-зависимость; Envoy использует Composer `--no-dev`. Публичный вызов Browsershot из контроллеров не найден.

Пакет `ryangjchandler/filament-navigation` отмечен Composer как abandoned. Это риск сопровождения, не самостоятельная доказанная уязвимость.

**Действия:** обновлять зависимости согласованными группами, начинать с достижимых HTTP/Filament-проблем. Точные pins в composer.json/package.json требуют осознанного изменения ограничений. Не применять `npm audit fix --force` автоматически: предложенная миграция Vue 2 → Vue 3 меняет стек и запрещена инструкциями проекта без отдельной задачи.

Полные машинные результаты и компактная ведомость доступны здесь: [ведомость зависимостей](/Applications/MAMP/htdocs/zrenie.clinic-7/output/security-audit-2026-11-03/dependency-inventory.md), [Composer JSON](/Applications/MAMP/htdocs/zrenie.clinic-7/output/security-audit-2026-11-03/composer-audit.json) и [npm JSON](/Applications/MAMP/htdocs/zrenie.clinic-7/output/security-audit-2026-11-03/npm-audit.json). Они содержат метаданные пакетов, а не секреты приложения.

### DEP-02. Основные версии вышли из обычной поддержки безопасности

**Места:** [composer.json:20](/Applications/MAMP/htdocs/zrenie.clinic-7/composer.json:20), [composer.json:91](/Applications/MAMP/htdocs/zrenie.clinic-7/composer.json:91), [package.json:22](/Applications/MAMP/htdocs/zrenie.clinic-7/package.json:22), [Envoy.blade.php:77](/Applications/MAMP/htdocs/zrenie.clinic-7/Envoy.blade.php:77), [Envoy.blade.php:146](/Applications/MAMP/htdocs/zrenie.clinic-7/Envoy.blade.php:146).

- Laravel 10: обычная поддержка безопасности закончилась 4 февраля 2025 года, согласно [политике Laravel](https://laravel.com/docs/10.x/releases#support-policy).
- Vue 2: обычная поддержка закончилась 31 декабря 2023 года; официальный сайт указывает на возможность отдельной расширенной поддержки, см. [Vue 2 EOL](https://v2.vuejs.org/eol/).
- PHP 8.1: поддержка закончилась 31 декабря 2025 года, согласно [PHP EOL](https://www.php.net/eol.php). Скрипт развёртывания прямо запускает `php8.1` и перезапускает `php8.1-fpm`. Фактическая версия работающего сервера по SSH не проверялась.

Это системный риск получения исправлений, а не доказательство уже совершённой атаки. План обновления/расширенной поддержки нужно согласовать отдельно; стек при аудите не изменялся.

## Условные риски и дополнительные замечания

### COND-01. Доверие всем reverse proxy может обнулить IP-защиту

[app/Http/Middleware/TrustProxies.php:15](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Middleware/TrustProxies.php:15) задаёт `'*'` и доверяет X-Forwarded-For/Host/Proto. [app/Providers/RouteServiceProvider.php:45](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Providers/RouteServiceProvider.php:45) ограничивает API по `request()->ip()`. Стандартный вход также использует IP в ключе. Проверка разрешённых IP интеграции использует этот же адрес в [app/Http/Middleware/EnsureServicesIntegrationToken.php:31](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Middleware/EnsureServicesIntegrationToken.php:31).

**Условие:** origin принимает прямые запросы либо внешний proxy не заменяет недоверенные forwarded-заголовки. Тогда атакующий может менять эффективный IP и обходить лимиты; при уже известном токене — также IP-allowlist интеграции. Host/proto могут влиять на генерируемые ссылки. `TrustHosts` в Kernel отключён. Токен интеграции подмена IP сама по себе не заменяет.

**Действие:** доверять конкретным proxy/CIDR и проверять, что origin доступен только через них; нормализовать заголовки на границе. До чтения реальной nginx/CDN-конфигурации считать риск условным. Рекомендации: [Laravel trusted proxies/hosts](https://laravel.com/docs/10.x/requests#configuring-trusted-proxies).

### COND-02. Проверка UNF-токена не запрещает пустую конфигурацию

[app/Http/Requests/UserUpdateRequest.php:18](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Requests/UserUpdateRequest.php:18) и [app/Http/Requests/UserPasswordRequest.php:17](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Requests/UserPasswordRequest.php:17) используют прямое строгое сравнение заголовка с конфигурацией. В [config/zrenie-clinic.php:20](/Applications/MAMP/htdocs/zrenie.clinic-7/config/zrenie-clinic.php:20) default — пустая строка. Отдельного запрета пустого/отсутствующего серверного секрета нет, в отличие от нового `EnsureServicesIntegrationToken`.

**Условие:** серверный секрет отсутствует/пуст и HTTP-стек сохраняет присланное пустое значение заголовка как пустую строку; либо обе стороны оказываются `null`. Тогда пустое значение совпадёт. При обычном отсутствии заголовка (`null`) и default `''` строгое сравнение **не проходит** — анонимный доступ только на этом основании не утверждается.

**Действие:** возвращать 503 при ненастроенном непустом секрете; проверять тип и непустоту обоих значений, затем `hash_equals`. Производственное значение токена не читалось.

### COND-03. Полный UTM-экспорт опубликован без авторизации

[routes/api.php:47](/Applications/MAMP/htdocs/zrenie.clinic-7/routes/api.php:47), [app/Services/UtmTrackerIntegrationExportService.php:73](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/UtmTrackerIntegrationExportService.php:73), [app/Services/UtmTrackerIntegrationExportService.php:101](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/UtmTrackerIntegrationExportService.php:101): endpoint возвращает активные и архивные правила, названия кампаний, телефоны, тип кабинета и даты. Наличие CORS `'*'` упрощает чтение из другого сайта, но доступ и без него публичный.

**Условие:** история рекламных кампаний и внутренние названия не предназначены для общего доступа. Отсутствие входа подтверждено; конфиденциальность этих бизнес-данных требует решения владельца. Данных пациентов в экспортируемых полях не обнаружено.

**Действие:** отделить минимальную публичную конфигурацию подмены телефонов от полного серверного интеграционного экспорта; для полного экспорта использовать токен/разрешения.

### COND-04. Телефон входа меняется без подтверждения владения

[app/Http/Controllers/Profile/ProfileController.php:32](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Controllers/Profile/ProfileController.php:32), [app/Http/Requests/Profile/ProfileUpdateRequest.php:33](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Requests/Profile/ProfileUpdateRequest.php:33): пользователь может привязать любой ещё не занятый номер, проверяется формат и отсутствие другого аккаунта, но нет SMS-подтверждения нового номера или повторной аутентификации.

Это допускает регистрацию чужого незанятого номера на собственный профиль и ошибочную адресацию сообщений/связей. Захват уже существующего аккаунта этим путём не доказан: занятые номера проверяются. Нужен отдельный подтверждённый сценарий смены номера и аннулирование старых кодов/сеансов по выбранной политике.

### COND-05. Старая форма записи передаёт дату и время без валидации

[app/Http/Requests/MakingAnAppointmentRequest.php:37](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Requests/MakingAnAppointmentRequest.php:37) не содержит правил для `date`/`time`, хотя [app/Http/Controllers/MakingAnAppointmentController.php:24](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Controllers/MakingAnAppointmentController.php:24) использует их для `dt_start` и отправляет в UNF. По формату UUID также нельзя установить, что врач/слот действительно доступны. Валидация согласия допускает отсутствие `privacy`.

**Последствия:** некорректные запросы и попытки записи на неподходящие даты/слоты доходят до UNF. Проверяет ли их UNF, неизвестно; успешная недопустимая запись не утверждается. Добавить явную проверку даты, времени и доступности слота на стороне, которая подтверждает запись. `privacy` сделать обязательным, если это требование продукта; правовая оценка согласия в аудит не входила.

### COND-06. Rich HTML и URL из CMS требуют чёткой границы доверия

Примеры: [resources/views/components/review-card.blade.php:51](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/components/review-card.blade.php:51), [app/Http/Resources/ReviewResource.php:27](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Http/Resources/ReviewResource.php:27), [resources/js/components/AppFilter/components/Card/Card.vue:44](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/js/components/AppFilter/components/Card/Card.vue:44), [resources/js/components/DoctorModal/components/DoctorMarkup/DoctorMarkup.vue:57](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/js/components/DoctorModal/components/DoctorMarkup/DoctorMarkup.vue:57), [resources/views/components/block/faq.blade.php:30](/Applications/MAMP/htdocs/zrenie.clinic-7/resources/views/components/block/faq.blade.php:30).

В части шаблонов HTML идёт без очистки в `{!! !!}`/`v-html`; у ссылок `link_resource` не проверяется безопасная схема. Импортный `sanitizeImportedHtml()` в [app/Services/ArticleImport/ArticleContentParser.php:440](/Applications/MAMP/htdocs/zrenie.clinic-7/app/Services/ArticleImport/ArticleContentParser.php:440) удаляет несколько атрибутов regex-ами, но не является полноценным санитайзером. Кроме обычного HTML внутри общего `#app` опасны и Vue-выражения, см. SEC-01.

Здесь источником в основном является CMS/импорт, а не анонимный пользователь. Поля намеренной вставки скриптов в настройках сами по себе не объявляются уязвимостью: они предоставляют такое право по назначению. Если контент-редакторы не должны иметь право исполнять JavaScript, необходимо централизованное очищение rich text, allowlist схем URL, изоляция от компилятора Vue и отдельные права на скрипты. Публичную отправку отзыва нельзя автоматически считать готовым stored XSS: контроллер пишет `body`, тогда как модель допускает `body_html`, и миграция требует непустое поле; полноценная цепочка записи HTML через этот endpoint не подтверждена.

### COND-07. Защиты HTTP-уровня в репозитории не видны

В коде и `public/.htaccess` нет CSP, frame-ancestors/X-Frame-Options, nosniff и Referrer-Policy; они могут задаваться nginx/CDN. [config/session.php:171](/Applications/MAMP/htdocs/zrenie.clinic-7/config/session.php:171) берёт Secure из окружения; HttpOnly и SameSite=lax включены. Значение production Secure неизвестно. Локальная `.env.example` с `APP_DEBUG=true` не доказывает debug на рабочем сайте.

Проверить фактические заголовки, cookie, закрытие `.env`/`.git`, document root на `public`, запрет выполнения скриптов в публичных загрузках, доступность старых debug-интерфейсов. Это перечень неохваченных настроек, **не подтверждённый список утечек**. Строгую CSP вводить с учётом существующей компиляции Vue и намеренных скриптов CMS, без маскировки проблемы через широкое `unsafe-eval`.

## Что проверено и не признано уязвимостью

- Найденные `whereRaw`/`selectRaw` используют константный SQL и параметры; подтверждённой SQL-инъекции в прослеженных публичных цепочках нет.
- Публичного пути к `eval`, shell-командам или `unserialize` в коде приложения не найдено. Это не утверждение об отсутствии всех возможных RCE в зависимостях/сервере.
- В web-группе включён CSRF без пользовательских исключений; штатный login регенерирует session ID. Публичные API-формы без cookie-auth не объявляются CSRF автоматически.
- Новая интеграция услуг запрещает работу без токена, использует `hash_equals`; массовое присваивание произвольного тела запроса в просмотренных контроллерах не обнаружено.
- Файловый менеджер ограничен public-диском, для изменений есть серверные проверки прав и роли demo. Публичные загрузчики ограничивают MIME и расширения, SVG/HTML/PHP не входят в разрешённый набор. Это не закрывает временную загрузку SEC-06.
- `menu-files/../...` может нормализоваться в другой путь того же public-диска, но Flysystem ограничивает корень; доступ к `.env`/private storage из этого не следует. Содержимое этого диска и так публикуется через `public/storage`.
- Middleware `CachePageResponse` потенциально опасен для персональных страниц, но не подключён к маршрутам/Kernel; утечка кабинетов через него не заявляется. Контентные кэши учитывают город, персональное состояние формируется отдельно.
- Для публикаций `ArticleOrderingService` через стратегию добавляет `active=true` и тип статьи; отсутствие этих условий непосредственно в PostController не означает выдачу черновиков.
- `@json` в layout использует стандартное экранирование Laravel; подтверждённой инъекции закрывающего script через `window.config` не найдено. Отдельная проблема JSON-LD относится к Spatie, см. DEP-01.
- В просмотренных отслеживаемых исходниках по сигнатурам секретов явных приватных ключей/реальных токенов не обнаружено. `.env` не отслеживается Git. История Git и фактические секреты окружения в этот вывод не входят.
- Код внешнего сервиса записи `adminzrenie.ru` отсутствует в репозитории; проверки создания заявки, возраста, цены, телефона и антиспама на его стороне не оценивались.

## Порядок исправлений

1. Закрыть SEC-01, ограничить календарь SEC-03 и обновить достижимые Symfony/Filament-проблемы SEC-05/06.
2. Исправить модель OTP и проверить proxy-доверие; затем убрать возможность подмены профиля через callback.
3. Закрыть SSRF импорта, публичную очистку кэшей и неограниченную постановку заданий UNF.
4. Исправить guard/permissions административных операций, решить публичность UTM-экспорта и подтверждение телефона.
5. Обновить остальные зависимости, согласовать поддержку PHP/Laravel/Vue и централизованную границу доверия HTML.

Исправления, commit, merge и push в рамках аудита не выполнялись.

**Фраза для рабочего отчёта:** «Проведён подробный анализ безопасности сайта, выявлены риски доступа к личным кабинетам, выполнения постороннего кода и перегрузки сервера, подготовлены приоритетные рекомендации по их устранению».
