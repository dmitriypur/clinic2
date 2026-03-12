# API для ИИ по услугам

Это интеграционное API предназначено для внешнего ИИ-агента, который сначала читает текущее дерево услуг, а затем вносит точечные изменения.

## Авторизация

Укажите токен в `.env`:

```dotenv
SERVICES_INTEGRATION_TOKEN=change-me
SERVICES_INTEGRATION_ALLOWED_IPS=127.0.0.1
SERVICES_INTEGRATION_DEFAULT_CITY_SLUG=moskva
```

Передавайте его как `Bearer token`:

```bash
curl -H "Authorization: Bearer change-me" \
  http://localhost/api/integrations/services/tree
```

## Методы чтения

- `GET /api/integrations/services/tree`
- `GET /api/integrations/services/parents`
- `GET /api/integrations/services/search?q=лазер`
- `GET /api/integrations/services/{uuid}/children`
- `GET /api/integrations/services/{uuid}`

Все методы чтения и записи могут принимать `city_slug` в query string.

Правило по городам:

- если у услуги или подуслуги `city_slugs = []`, значит она доступна во всех городах;
- если у цены `city_slug = null`, значит эта цена действует во всех городах;
- в ответах API это дополнительно отмечается флагом `available_in_all_cities = true`.

Примеры:

- `GET /api/integrations/services/search?q=приём&city_slug=moskva`
- `GET /api/integrations/services/children-by-title?q=Приём детского офтальмолога&city_slug=kirov`
- `POST /api/integrations/services/preview?city_slug=moskva`

## Метод записи

- `POST /api/integrations/services/preview`
- `POST /api/integrations/services/apply`

Поддерживаемые типы операций:

- `create_service`
- `update_service`
- `delete_service`
- `upsert_price`
- `delete_price`

`create_service` создаёт родительскую услугу, если родитель не передан. Чтобы создать подуслугу, передайте `parent_uuid` или `parent_ref`.

Поле `ref` позволяет ссылаться на только что созданную услугу позже в рамках того же запроса.

Пример:

```json
{
  "dry_run": true,
  "operations": [
    {
      "type": "create_service",
      "ref": "laser_root",
      "title": "Лазерная коррекция",
      "sort_order": 10
    },
    {
      "type": "create_service",
      "ref": "smile_child",
      "parent_ref": "laser_root",
      "title": "SMILE",
      "sort_order": 20
    },
    {
      "type": "upsert_price",
      "service_ref": "smile_child",
      "price": 25000,
      "old_price": 30000,
      "price_from": false
    }
  ]
}
```

## Локальное тестирование через Ollama

`llama3.1:8b` можно использовать для локальных проверок, но только если дать модели инструменты.

На практике это означает:

1. Поднять Laravel локально.
2. Запустить `ollama serve`.
3. Подключить это API к небольшой агентной обвязке или UI, который умеет вызывать HTTP-инструменты.
4. Дать модели список инструментов:
   - `get_service_parents`
   - `search_services`
   - `get_service_children`
   - `get_service`
   - `preview_service_changes`
   - `apply_service_changes`

Модель должна сначала читать данные, затем вызывать `preview`, и только после этого отправлять финальный запрос на изменение через `apply`.

Если пользователь пишет "для Москвы" или "для Кирова", модель должна передавать соответствующий `city_slug` во все запросы чтения, `preview` и `apply`.

Если у услуги, подуслуги или цены нет привязки к городу, модель должна считать её общей для всех городов, а не отсутствующей.

## Быстрая локальная проверка

Если нужно проверить сценарий "написал задание текстом и получил изменения", используйте artisan-команду:

```bash
php artisan app:services-ai-agent "Добавь к услуге Лазерная коррекция подуслугу SMILE и поставь цену 25000, старую цену 30000"
```

Команда:

1. Заберёт текущий каталог услуг.
2. Отправит ваше текстовое задание в `Ollama`.
3. Получит от модели JSON-операции.
4. Покажет `dry-run`, не меняя базу.

Если результат вас устраивает, можно реально применить изменения:

```bash
php artisan app:services-ai-agent "Добавь к услуге Лазерная коррекция подуслугу SMILE и поставь цену 25000, старую цену 30000" --apply
```

Перед реальной записью команда попросит подтверждение.
