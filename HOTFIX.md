# 🔧 Hotfix: Исправлена ошибка с именем pivot-таблицы

**Дата:** Январь 2025  
**Коммит:** `64145af`  
**Статус:** ✅ Исправлено

---

## 🐛 Проблема

При запуске сайта локально возникала ошибка:

```
SQLSTATE[42S02]: Base table or view not found: 1146 
Table 'clinic3.city_doctors' doesn't exist
```

---

## 🔍 Причина

В оптимизированном `HasCityScope` trait метод `getCityPivotTableName()` формировал неправильное имя таблицы:

**Было:**
```php
protected static function getCityPivotTableName(string $table): string
{
    return 'city_' . $table;  // Возвращало: city_doctors
}
```

**Реальные таблицы в БД:**
- `city_doctor` (не `city_doctors`) ✅
- `city_page` (не `city_pages`) ✅
- `city_service` (не `city_services`) ✅
- `city_promotion` (не `city_promotions`) ✅
- `city_block` (не `city_blocks`) ✅

---

## ✅ Решение

Исправлен метод для использования единственного числа:

```php
protected static function getCityPivotTableName(string $table): string
{
    // Для всех таблиц: city_doctor, city_page, city_service (единственное число)
    // Убираем 's' в конце, если есть
    $singular = str_ends_with($table, 's') ? substr($table, 0, -1) : $table;
    return 'city_' . $singular;
}
```

**Результат:**
- `doctors` → `city_doctor` ✅
- `pages` → `city_page` ✅
- `services` → `city_service` ✅
- `promotions` → `city_promotion` ✅
- `blocks` → `city_block` ✅

---

## 🧪 Проверка

```bash
# Очистить кеш
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Проверить работу
php artisan tinker
>>> $cityService = app(\App\Services\CityService::class);
>>> $cities = $cityService->getActiveCities();
>>> $cities->count(); // Должно вернуть количество городов

>>> $kirov = $cityService->getCityBySlug('kirov');
>>> $cityService->setCurrentCity($kirov);
>>> \App\Models\Doctor::count(); // Должно вернуть количество врачей
```

---

## 📝 Измененные файлы

- `app/Models/Traits/HasCityScope.php` - исправлен метод `getCityPivotTableName()`

---

## ✅ Статус

**Исправление применено и протестировано.**

Сайт запускается без ошибок, фильтрация по городам работает корректно.

---

## 🚀 Применение

Исправление уже включено в ветку `feature/multicity-optimization`.

```bash
# Убедитесь, что вы на правильной ветке
git checkout feature/multicity-optimization

# Обновите код (если нужно)
git pull origin feature/multicity-optimization

# Очистите кеш
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

**Версия:** 1.1  
**Автор:** AI Assistant  
**Коммит:** `64145af`
