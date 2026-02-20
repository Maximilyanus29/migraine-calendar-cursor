# Migraine Calendar

Небольшое веб‑приложение для ведения календаря приступов мигрени.

## Стек

- **Backend**: PHP (сессии), JSON API
- **Frontend**: Vue 3 + Vite (SPA)
- **DB**: MySQL

## Быстрый старт (локально)

### 1) База данных

#### Вариант A: MySQL (как в требованиях)

Создайте базу и пользователя (пример):

```sql
CREATE DATABASE migraine_calendar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'migraine'@'localhost' IDENTIFIED BY 'migraine';
GRANT ALL PRIVILEGES ON migraine_calendar.* TO 'migraine'@'localhost';
FLUSH PRIVILEGES;
```

Импортируйте схему:

```bash
mysql -u migraine -p migraine_calendar < db/schema.sql
```

#### Вариант B: SQLite (dev fallback)

Если в системе нет `pdo_mysql`, можно запуститься на SQLite:

```bash
sqlite3 db/app.sqlite < db/schema.sqlite.sql
```

и в `.env` поставьте:

```env
DB_DRIVER=sqlite
DB_SQLITE_PATH=/полный/путь/до/db/app.sqlite
```

### 2) Переменные окружения

Скопируйте пример и отредактируйте:

```bash
cp .env.example .env
```

### 3) Создание пользователя

Создайте пользователя:

```bash
php backend/bin/create_user.php demo@example.com demo
```

### 4) Установка зависимостей

Frontend:

```bash
cd frontend
npm install
```

### 5) Запуск в dev

Соберите фронтенд (либо запускайте vite отдельно):

```bash
cd frontend
npm run dev
```

API (PHP built-in server):

```bash
cd backend
php -S 127.0.0.1:8080 -t public
```

По умолчанию фронтенд в dev ожидает API на `http://127.0.0.1:8080`.

## Что реализовано

- `/` — страница логина
- `/calendar` — календарь месяца и навигация
- клик по дню → `/attack/YYYY-MM-DD` — создание/редактирование приступа
- если на день приступа нет — форма предзаполняется данными предыдущего приступа пользователя

# migraine-calendar-cursor
