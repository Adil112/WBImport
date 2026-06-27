# WB Import

Laravel-приложение для импорта данных из тестового API.

Проект поддерживает:

- импорт продаж (Sales);
- импорт заказов (Orders);
- импорт поставок (Incomes);
- импорт складских остатков (Stocks);
- работу с несколькими компаниями и аккаунтами;
- хранение нескольких типов API-токенов;
- автоматическое обновление данных по расписанию;
- повторные попытки при ошибке `429 Too Many Requests`.

---

## Используемое тестовое API

Описание API:

https://github.com/cy322666/wb-api/blob/master/README.md

Коллекция Postman:

https://www.postman.com/cy322666/app-api-test/overview?sideView=agentMode

---

## Доступ к API

```env
WB_API_BASE_URL=http://109.73.206.144:6969
WB_API_KEY=E6kUTYrYwZq2tN4QEtyzsbEBk3ie
```

---

## Стек

- PHP 8.x
- Laravel
- MySQL
- Docker Compose

---

## Запуск проекта

### 1. Клонировать репозиторий

```bash
git clone <repository-url>
cd <project>
```

### 2. Создать файл окружения

```bash
cp .env.example .env
```

Заполнить параметры подключения к БД и API.

### 3. Запустить Docker

```bash
docker compose up -d --build
```

### 4. Установить зависимости

```bash
docker compose exec php composer install
```

### 5. Сгенерировать ключ приложения

```bash
docker compose exec php php artisan key:generate
```

### 6. Выполнить миграции

```bash
docker compose exec php php artisan migrate
```

---

## Структура данных

Проект поддерживает следующую структуру:

```
Company
    └── Account
            └── ApiToken
                    ├── ApiService
                    └── TokenType
```

Каждый аккаунт хранит собственные данные импорта.

Все импортируемые записи содержат поле `account_id`, что предотвращает перезапись данных разных аккаунтов.

---

## Поддерживаемые типы данных

- Sales
- Orders
- Incomes
- Stocks

---

## Команды создания справочников

### Добавить компанию

```bash
php artisan company:add
```

### Добавить аккаунт

```bash
php artisan account:add
```

### Добавить API сервис

```bash
php artisan api-service:add
```

### Добавить тип токена

```bash
php artisan token-type:add
```

### Связать сервис с типом токена

```bash
php artisan api-service:attach-token-type
```

### Добавить API токен

```bash
php artisan api-token:add
```

### Сделать токен используемым по умолчанию

```bash
php artisan api-token:set-default
```

---

## Импорт данных

### Продажи

```bash
php artisan wb:import-sales {account_id} {dateFrom} {dateTo}
```

### Заказы

```bash
php artisan wb:import-orders {account_id} {dateFrom} {dateTo}
```

### Поставки

```bash
php artisan wb:import-incomes {account_id} {dateFrom} {dateTo}
```

### Остатки

```bash
php artisan wb:import-stocks {account_id}
```

### Импорт всех сущностей

```bash
php artisan wb:import-all
```

---

## Особенности импорта

Во время импорта реализованы:

- постраничная загрузка данных;
- получение только новых записей при повторном запуске;
- защита от дублирования данных;
- защита от перезаписи данных разных аккаунтов;
- удаление отсутствующих остатков после успешной синхронизации;
- пропуск некорректных записей;
- подробный вывод информации в консоль.

---

## Обработка ошибок

Поддерживается автоматическая обработка ответа:

```
429 Too Many Requests
```

Используются:

- повторные попытки;
- экспоненциальная задержка (Exponential Backoff);
- поддержка заголовка `Retry-After`.

---

## Планировщик

Автоматический импорт запускается два раза в день.

Для работы расписания необходимо запустить:

```bash
php artisan schedule:work
```

или добавить cron:

```cron
* * * * * php artisan schedule:run
```

---

## Логи импорта

Каждый запуск сохраняет информацию:

- статус выполнения;
- период импорта;
- количество обработанных записей;
- количество созданных записей;
- количество обновленных записей;
- количество неизмененных записей;
- ошибки при выполнении.

---

## Особенности реализации

Проект построен с использованием:

- сервисного слоя;
- нормализаторов данных;
- централизованного клиента API;
- универсального базового сервиса импорта;
- моделей Eloquent с отношениями;
- консольных команд Laravel;
- планировщика Laravel Scheduler.
