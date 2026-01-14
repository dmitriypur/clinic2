# 🔀 Инструкция по мерджу ветки feature/booking-widget-v2

## Текущее состояние

### Ветки
- **main** - основная ветка (старый функционал)
- **feature/booking-widget-v2** - новый виджет записи (✅ готово)

### Коммиты в feature/booking-widget-v2
```
be922a6 docs: Add comprehensive testing and summary documentation
1612c61 feat: Add BookingWidgetV2 - new booking form with adminzrenie.ru API
```

## 📋 Чеклист перед мерджем

### 1. Тестирование ✅
- [ ] Все 5 шагов виджета работают корректно
- [ ] API запросы успешны
- [ ] Валидация формы работает
- [ ] Нет ошибок в консоли
- [ ] Адаптивность проверена
- [ ] Протестировано в Chrome, Firefox, Safari

### 2. Code Review ⏳
- [ ] Код проверен другим разработчиком
- [ ] Замечания исправлены
- [ ] Соблюдены code style guidelines

### 3. Документация ✅
- [x] README создан
- [x] Usage guide создан
- [x] Testing guide создан
- [x] API документация обновлена

### 4. Безопасность ✅
- [x] Нет хардкода API ключей
- [x] Валидация данных на клиенте
- [x] Безопасная обработка ошибок

## 🚀 Процесс мерджа

### Вариант 1: Через Pull Request (рекомендуется)

```bash
# 1. Пушим ветку на origin
git push origin feature/booking-widget-v2

# 2. Создаем Pull Request на GitHub/GitLab
# - Заголовок: "feat: Add BookingWidgetV2 with adminzrenie.ru API integration"
# - Описание: см. BOOKING_WIDGET_V2_SUMMARY.md
# - Reviewers: добавить ответственных

# 3. После approve:
# Переходим в main
git checkout main

# 4. Мержим
git merge feature/booking-widget-v2

# 5. Пушим в main
git push origin main
```

### Вариант 2: Прямой мердж (для небольших команд)

```bash
# 1. Переходим в main
git checkout main

# 2. Подтягиваем последние изменения
git pull origin main

# 3. Мержим feature-ветку
git merge feature/booking-widget-v2

# 4. Решаем конфликты (если есть)
# Проверяем файлы с конфликтами
git status

# Разрешаем конфликты вручную
# Затем:
git add .
git commit -m "merge: Resolve conflicts"

# 5. Пушим в main
git push origin main

# 6. (Опционально) Удаляем feature-ветку
git branch -d feature/booking-widget-v2
git push origin --delete feature/booking-widget-v2
```

## 🔍 Проверка после мерджа

### 1. Проверка файлов
```bash
# Проверяем что все файлы на месте
ls -la resources/js/components/BookingWidgetV2/
ls -la resources/js/services/
ls -la docs/ | grep -i booking
```

### 2. Проверка сборки
```bash
# Собираем проект
npm run build

# Проверяем что нет ошибок
echo $?  # Должно быть 0
```

### 3. Проверка в браузере
```bash
# Запускаем dev-сервер
npm run dev

# Открываем:
# http://localhost/booking-widget-v2-demo
```

### 4. Проверка Git истории
```bash
# Смотрим последние коммиты
git log --oneline -10

# Должны быть видны оба коммита:
# - feat: Add BookingWidgetV2...
# - docs: Add comprehensive testing...
```

## ⚠️ Возможные конфликты

### 1. Конфликт в resources/js/app.js

**Причина**: Кто-то добавил другой компонент

**Решение**:
```javascript
// Оставить ОБА импорта и ОБА компонента
const OnlineAppointmentForm = () => import("...");
const BookingWidgetV2 = () => import("...");  // Новый

components: {
  OnlineAppointmentForm,
  BookingWidgetV2,  // Новый
  // ... остальные
}
```

### 2. Конфликт в routes/web.php

**Причина**: Добавлены новые роуты

**Решение**:
```php
// Оставить все роуты, добавить новый в конец
// ... существующие роуты

// Новый роут для виджета
if (config('app.env') !== 'production') {
    Route::get('/booking-widget-v2-demo', function () {
        $city = \App\Models\City::first();
        return view('booking-widget-v2-demo', compact('city'));
    })->name('booking.widget.v2.demo');
}
```

### 3. Конфликт в package.json / package-lock.json

**Решение**:
```bash
# Оставляем версию из main
git checkout --theirs package-lock.json

# Переустанавливаем зависимости
npm install
```

## 🎯 После успешного мерджа

### 1. Обновить документацию
- [ ] Обновить CHANGELOG.md
- [ ] Обновить версию в package.json (если нужно)

### 2. Уведомить команду
```markdown
✅ Мердж feature/booking-widget-v2 в main выполнен успешно!

Что нового:
- Новый виджет онлайн-записи BookingWidgetV2
- Интеграция с API adminzrenie.ru
- Поддержка мультигорода
- Улучшенный UX

Тестовая страница:
http://localhost/booking-widget-v2-demo

Документация:
- docs/BOOKING_WIDGET_V2_README.md
- docs/booking-widget-v2-usage.md
- TESTING_BOOKING_WIDGET_V2.md
```

### 3. Создать tag (опционально)
```bash
git tag -a v2.0.0-booking-widget -m "Release BookingWidgetV2"
git push origin v2.0.0-booking-widget
```

### 4. Деплой
- [ ] Задеплоить на staging
- [ ] Протестировать на staging
- [ ] Задеплоить на production
- [ ] Мониторить ошибки

## 🔄 Откат (если что-то пошло не так)

### Откат мерджа
```bash
# Находим хеш коммита перед мерджем
git log --oneline

# Откатываемся (ОСТОРОЖНО!)
git reset --hard <commit-hash-before-merge>

# Форс-пуш (если уже запушили)
git push origin main --force

# Или создаем revert commit (безопаснее)
git revert -m 1 <merge-commit-hash>
git push origin main
```

## 📞 Контакты

При проблемах с мерджем:
1. Проверьте документацию выше
2. Проверьте BOOKING_WIDGET_V2_SUMMARY.md
3. Создайте issue с описанием проблемы
4. Свяжитесь с автором ветки

---

## ✅ Финальный чеклист

Перед тем как мержить, убедитесь:

- [ ] Все тесты пройдены
- [ ] Code review завершен
- [ ] Документация актуальна
- [ ] Нет конфликтов с main
- [ ] Команда уведомлена
- [ ] Есть план отката

**Готовы к мерджу? Вперед! 🚀**
