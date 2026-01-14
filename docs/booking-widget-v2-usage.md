# Использование нового виджета онлайн-записи (BookingWidgetV2)

## Описание

`BookingWidgetV2` - это новый компонент формы онлайн-записи, который использует API `adminzrenie.ru` для получения данных врачей и расписания, а также отправки заявок.

## Преимущества

- ⚡ **Быстрая загрузка** - данные кешируются на сервере adminzrenie.ru
- 🌍 **Поддержка мультигорода** - выбор врачей по городам
- 🛡️ **Надежность** - проверка доступности слотов перед записью
- 📱 **Адаптивность** - корректная работа на всех устройствах

## Использование в Blade-шаблонах

### Базовый пример

```blade
<div id="app">
    <!-- Кнопка для открытия виджета -->
    <button @click="bookingWidgetV2Active = true">
        Записаться на приём
    </button>

    <!-- Компонент виджета -->
    <booking-widget-v2
        :open="bookingWidgetV2Active"
        :city-id="currentCityId"
        @close="bookingWidgetV2Active = false"
    />
</div>

<script>
    // Передаем ID текущего города в window для доступа из Vue
    window.currentCity = {
        id: {{ $city->id ?? 1 }}
    };
</script>
```

### Пример с метрикой для Яндекс.Метрики

```blade
<booking-widget-v2
    :open="bookingWidgetV2Active"
    :city-id="{{ $city->id ?? 1 }}"
    target="appointment_submitted"
    @close="bookingWidgetV2Active = false"
/>
```

### Пример в существующем компоненте

```vue
<template>
    <div>
        <button @click="openBookingWidget">
            Записаться онлайн
        </button>
    </div>
</template>

<script>
export default {
    methods: {
        openBookingWidget() {
            this.$root.bookingWidgetV2Active = true;
        }
    }
}
</script>
```

## Props

| Prop | Тип | По умолчанию | Описание |
|------|-----|--------------|----------|
| `open` | Boolean | `false` | Открыт ли виджет |
| `cityId` | String/Number | `null` | ID города для загрузки врачей |
| `target` | String | `null` | Цель для Яндекс.Метрики |

## Events

| Event | Параметры | Описание |
|-------|-----------|----------|
| `close` | - | Виджет был закрыт |

## API Endpoints

Виджет использует следующие endpoints из `adminzrenie.ru/api/v1/`:

1. `GET /cities/{city_id}/doctors` - список врачей по городу
2. `GET /doctors/{doctor_id}/slots?date=YYYY-MM-DD` - слоты врача
3. `POST /applications/check-slot` - проверка доступности слота (опционально)
4. `POST /applications` - создание заявки

## Настройка окружения

В файле `.env` можно настроить URL API:

```env
VITE_BOOKING_API_URL=https://adminzrenie.ru/api/v1
```

Для разработки используется тестовый API:
```
https://app.fondzrenie.ru/api/v1
```

## Структура данных заявки

При отправке формы создается заявка со следующими полями:

```javascript
{
  city_id: 1,
  clinic_id: 123,
  branch_id: 456,
  doctor_id: 789,
  cabinet_id: 12,
  appointment_datetime: "2024-01-15 14:30:00",
  onec_slot_id: "uuid-slot-id",
  full_name: "Иванов Иван Иванович",
  full_name_parent: "Иванов Петр Сергеевич", // для детей
  birth_date: "1990-05-15",
  phone: "79991234567",
  promo_code: "PROMO2024", // опционально
  comment: "Дополнительная информация", // опционально
  appointment_source: "site"
}
```

## Тестирование

### Локальное тестирование

1. Запустите dev-сервер:
```bash
npm run dev
```

2. Откройте страницу с виджетом
3. Попробуйте пройти все шаги записи

### Проверка работы с API

Виджет автоматически использует тестовый API в режиме разработки.

Для переключения на production API установите:
```javascript
process.env.NODE_ENV = 'production'
```

## Troubleshooting

### Виджет не открывается

Проверьте:
1. Зарегистрирован ли компонент в `app.js`
2. Правильно ли передан prop `:open="bookingWidgetV2Active"`
3. Есть ли ошибки в консоли браузера

### Не загружаются врачи

Проверьте:
1. Передан ли корректный `city-id`
2. Доступен ли API endpoint
3. Есть ли ошибки в консоли (Network tab)

### Ошибка при отправке формы

Проверьте:
1. Заполнены ли все обязательные поля
2. Корректен ли формат данных (телефон, дата рождения)
3. Доступен ли слот (не занят ли другим пользователем)

## Миграция со старого виджета

Старый виджет `OnlineAppointmentForm` продолжит работать. Для миграции:

1. Замените компонент в шаблоне:
```blade
<!-- Старый -->
<online-appointment-form ... />

<!-- Новый -->
<booking-widget-v2 ... />
```

2. Обновите переменную состояния:
```javascript
// Старый
onlineAppointmentFormActive

// Новый
bookingWidgetV2Active
```

3. Обновите передаваемые props согласно документации выше

## Поддержка

При возникновении проблем:
1. Проверьте консоль браузера на наличие ошибок
2. Убедитесь, что API доступен и возвращает корректные данные
3. Проверьте документацию API: `/docs/booking-widget-external-site.md`
