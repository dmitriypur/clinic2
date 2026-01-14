# Booking Widget V2 - Новый виджет онлайн-записи

## 📋 Содержание

- [Обзор](#обзор)
- [Архитектура](#архитектура)
- [Установка и настройка](#установка-и-настройка)
- [Использование](#использование)
- [Разработка](#разработка)
- [Тестирование](#тестирование)
- [API](#api)

## 🎯 Обзор

`BookingWidgetV2` - это новый компонент формы онлайн-записи на прием к врачу, который решает проблемы старой версии:

### Проблемы старой версии:
- ❌ Медленная загрузка (прямые запросы к 1С)
- ❌ Нет поддержки мультигорода
- ❌ Риск овербукинга

### Преимущества новой версии:
- ✅ Быстрая загрузка (данные кешируются на промежуточном API)
- ✅ Поддержка мультигорода
- ✅ Проверка доступности слотов перед записью
- ✅ Улучшенный UX с пошаговой формой
- ✅ Адаптивный дизайн

## 🏗️ Архитектура

```
┌─────────────────┐
│  Пользователь   │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────┐
│  BookingWidgetV2.vue        │
│  (Главный компонент)        │
└─────────┬───────────────────┘
          │
          ├──► DoctorSelectionStep.vue  (Шаг 1: Выбор врача)
          ├──► DateSelectionStep.vue    (Шаг 2: Выбор даты)
          ├──► TimeSelectionStep.vue    (Шаг 3: Выбор времени)
          ├──► PatientFormStep.vue      (Шаг 4: Форма пациента)
          └──► ConfirmationStep.vue     (Шаг 5: Подтверждение)
                    │
                    ▼
          ┌──────────────────┐
          │  bookingApi.js   │
          │  (API Service)   │
          └────────┬─────────┘
                   │
                   ▼
    ┌──────────────────────────────┐
    │  adminzrenie.ru/api/v1       │
    │  (Промежуточный API)         │
    └──────────────┬───────────────┘
                   │
                   ▼
            ┌────────────┐
            │    1C      │
            └────────────┘
```

## 🚀 Установка и настройка

### 1. Установка зависимостей

Все необходимые зависимости уже установлены:
- Vue 2.7
- v-calendar (для выбора даты)
- vue-the-mask (для маски телефона)
- axios (для HTTP-запросов)

### 2. Переменные окружения

В `.env` можно настроить URL API (опционально):

```env
# Production API
VITE_BOOKING_API_URL=https://adminzrenie.ru/api/v1

# Development/Testing API
# VITE_BOOKING_API_URL=https://app.fondzrenie.ru/api/v1
```

По умолчанию:
- **Development**: используется `https://app.fondzrenie.ru/api/v1`
- **Production**: используется `https://adminzrenie.ru/api/v1`

### 3. Сборка проекта

```bash
# Разработка
npm run dev

# Production
npm run build
```

## 📘 Использование

### Базовый пример

```blade
<div id="app">
    <!-- Кнопка открытия виджета -->
    <button @click="bookingWidgetV2Active = true">
        Записаться на приём
    </button>

    <!-- Виджет -->
    <booking-widget-v2
        :open="bookingWidgetV2Active"
        :city-id="{{ $city->id }}"
        @close="bookingWidgetV2Active = false"
    />
</div>
```

### Пример с метрикой

```blade
<booking-widget-v2
    :open="bookingWidgetV2Active"
    :city-id="{{ $city->id }}"
    target="booking_completed"
    @close="bookingWidgetV2Active = false"
/>
```

### Props

| Prop | Тип | Обязателен | По умолчанию | Описание |
|------|-----|------------|--------------|----------|
| `open` | Boolean | Нет | `false` | Открыт ли виджет |
| `cityId` | Number/String | Нет | `null` | ID города для загрузки врачей |
| `target` | String | Нет | `null` | Цель для Яндекс.Метрики |

### Events

| Event | Параметры | Описание |
|-------|-----------|----------|
| `close` | - | Виджет закрыт пользователем |

## 🛠️ Разработка

### Структура файлов

```
resources/js/
├── services/
│   └── bookingApi.js                      # API сервис
└── components/
    └── BookingWidgetV2/
        ├── BookingWidgetV2.vue            # Главный компонент
        └── components/
            ├── DoctorSelectionStep.vue    # Шаг 1
            ├── DateSelectionStep.vue      # Шаг 2
            ├── TimeSelectionStep.vue      # Шаг 3
            ├── PatientFormStep.vue        # Шаг 4
            └── ConfirmationStep.vue       # Шаг 5
```

### Добавление нового шага

1. Создайте новый компонент в `components/BookingWidgetV2/components/`
2. Зарегистрируйте его в `BookingWidgetV2.vue`
3. Добавьте логику переключения шагов

Пример:
```vue
<!-- NewStep.vue -->
<template>
  <div class="p-6">
    <h2>Новый шаг</h2>
    <button @click="$emit('next')">Далее</button>
  </div>
</template>

<script>
export default {
  name: 'NewStep',
  props: {
    // ваши props
  }
}
</script>
```

### Модификация API сервиса

Все API запросы централизованы в `services/bookingApi.js`. Для добавления нового метода:

```javascript
// services/bookingApi.js
async getNewData(params) {
  try {
    const response = await this.client.get('/new-endpoint', { params });
    return response.data;
  } catch (error) {
    throw this.handleError(error);
  }
}
```

## 🧪 Тестирование

### Локальное тестирование

1. Запустите dev-сервер:
```bash
npm run dev
```

2. Откройте тестовую страницу:
```
http://localhost/booking-widget-v2-demo
```

3. Пройдите все шаги записи:
   - Выберите врача
   - Выберите дату
   - Выберите время
   - Заполните форму
   - Проверьте подтверждение

### Проверка API

#### 1. Проверка списка врачей
```bash
curl https://app.fondzrenie.ru/api/v1/cities/1/doctors
```

#### 2. Проверка слотов врача
```bash
curl "https://app.fondzrenie.ru/api/v1/doctors/{doctor_id}/slots?date=2024-01-15"
```

#### 3. Создание тестовой заявки
```bash
curl -X POST https://app.fondzrenie.ru/api/v1/applications \
  -H "Content-Type: application/json" \
  -d '{
    "city_id": 1,
    "doctor_id": 123,
    "full_name": "Тестов Тест Тестович",
    "phone": "79991234567",
    "birth_date": "1990-01-01",
    ...
  }'
```

### Отладка

Включите Vue DevTools для просмотра состояния компонента:

1. Установите [Vue DevTools](https://chrome.google.com/webstore/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
2. Откройте DevTools → Vue
3. Выберите компонент `BookingWidgetV2`
4. Просмотрите `data`, `props`, `computed`

### Console Logging

Все ошибки API логируются в консоль браузера:

```javascript
// Пример вывода
console.error('Booking API Error:', error);
```

## 📡 API

### Endpoints

Виджет использует следующие endpoints:

#### 1. Получить города
```
GET /api/v1/cities
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Москва",
      "status": "active"
    }
  ]
}
```

#### 2. Получить врачей по городу
```
GET /api/v1/cities/{city_id}/doctors
```

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "full_name": "Иванов Иван Иванович",
      "specialization": "Офтальмолог",
      "experience_years": 15,
      "avatar_url": "https://..."
    }
  ]
}
```

#### 3. Получить слоты врача
```
GET /api/v1/doctors/{doctor_id}/slots?date=YYYY-MM-DD
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "datetime": "2024-01-15 14:30:00",
      "time": "14:30",
      "clinic_id": 1,
      "clinic_name": "Клиника Зрение",
      "branch_id": 2,
      "branch_name": "Филиал на Ленина",
      "cabinet_id": 5,
      "is_available": true,
      "is_occupied": false,
      "is_past": false,
      "onec_slot_id": "uuid-here"
    }
  ]
}
```

#### 4. Проверить слот (опционально)
```
POST /api/v1/applications/check-slot
```

**Request:**
```json
{
  "clinic_id": 1,
  "branch_id": 2,
  "doctor_id": 123,
  "onec_slot_id": "uuid-here"
}
```

**Response:**
```json
{
  "available": true
}
```

#### 5. Создать заявку
```
POST /api/v1/applications
```

**Request:**
```json
{
  "city_id": 1,
  "clinic_id": 1,
  "branch_id": 2,
  "doctor_id": 123,
  "cabinet_id": 5,
  "appointment_datetime": "2024-01-15 14:30:00",
  "onec_slot_id": "uuid-here",
  "full_name": "Иванов Иван Иванович",
  "full_name_parent": "Иванов Петр Сергеевич",
  "birth_date": "1990-05-15",
  "phone": "79991234567",
  "promo_code": "PROMO2024",
  "comment": "Комментарий",
  "appointment_source": "site"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "status": "pending"
  }
}
```

### Обработка ошибок

API возвращает ошибки в формате:

```json
{
  "message": "Validation failed",
  "errors": {
    "phone": ["Некорректный формат телефона"],
    "birth_date": ["Дата рождения обязательна"]
  }
}
```

## 📚 Дополнительная документация

- [Документация API](./booking-widget-external-site.md)
- [Руководство по использованию](./booking-widget-v2-usage.md)
- [План миграции](./IMPLEMENTATION_PLAN_EXTERNAL_SITE_INTEGRATION.md)

## 🤝 Поддержка

При возникновении проблем:
1. Проверьте консоль браузера
2. Проверьте Network tab в DevTools
3. Убедитесь, что API доступен
4. Проверьте документацию выше

## 📝 Changelog

### v2.0.0 (2024-01-14)
- ✨ Первый релиз нового виджета
- ✅ Поддержка мультигорода
- ✅ Интеграция с adminzrenie.ru API
- ✅ Пошаговая форма записи
- ✅ Проверка доступности слотов
- ✅ Адаптивный дизайн
