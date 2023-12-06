# Обменник валют на Laravel

Микросервис для сайта обменника валют, реализующий парсер курсов с BestChange и REST API для взаимодействия с основным сайтом.

## Основные компоненты системы

- `App\Console\Commands\ParseBestChangeCommand`: Команда для запуска парсера курсов с BestChange.
- `App\Http\Services\CurrencyRateService`: Cервисный слой, отвечающий за бизнес-логику, связанную с валютными курсами.
- `App\Http\Requests\CurrencyRateIndexRequest`: Запрос для валидации параметров API метода получения курсов.
- `App\Http\Controllers\CurrencyRateController`: Контроллер, обеспечивающий обработку API запросов.
- `database\migrations`: Миграции базы данных для создания таблиц в БД.
- `routes\app`: Созданные роуты для API
- `database\factories`: Фабрика для тестов
- `tests\Unit\Http\Servise\CurrencyRateServiceTest.php`: Юнит тесты бизнес логики сервисного слоя

## Функциональные возможности

1. **Парсер курсов с BestChange**
   - Загрузка файла `info.zip` с сайта BestChange.
   - Парсинг файла `bm_rates.dat` с целью извлечения курсов валют.
   - Сохранение наиболее выгодных курсов для каждой пары валют в базу данных.

2. **REST API**

   - `GET /courses`: Получение массива всех курсов с возможностью фильтрации по отправляемой и получаемой валюте.
   - `GET /course/{send_currency}/{receive_currency}`: Получение курса для конкретной пары валют.
   - Аутентификация через Bearer Token.

## Запуск микросервиса

### Предварительные требования

- PHP >= 7.3
- Composer
- Laravel Framework
- База данных - MySQL

### Установка

1. Клонируйте репозиторий и перейдите в папку проекта.
   
```bash
git clone <url-repository>
cd <project-folder>
```

2. Установите зависимости.
   
```bash
composer install
```

3. Настройте переменные окружения в файле `.env`. Пример в `.env.example`

4. Запустите миграции для создания таблиц в базе данных.
   
```bash
php artisan migrate
```

### Запуск парсера BestChange

Запуск с использованием планировщика задач(заданно вренмя между запусками 30 минут):
```bash
php artisan schedule:run
```

Запуск парсера вручную:
```bash
php artisan app:parse-best-change
```

### Тесмтирование
Запуск тестов 
```bash
php artisan test
```
