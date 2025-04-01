## about the project and the best settings
Symfony Version 6.4
<br>
PHP Version 8.3
<br>
MYSQL Version 8.0

# Быстрый старт

### 1. Склонируйте репозиторий
```bash
git clone https://github.com/VitaliiPopov00/musify-api.git
```
1.1 перейдите в папку с проектом
```bash
cd musify-api
```

### 2. Установите зависимости Composer
``` bash
composer install
```

### 3. Настройте файл окружения .env
3.1 Скопируйте файл .env и настройте параметры:
```bash
cp .env .env.local
```
Параметры .env для соединения к БД (обязательны к заполнению), лучше всего заполнять их в файле .env.local, созданном ранее:
* DB_USER
* DB_PASSWORD
* DB_HOST
* DB_PORT
* DB_NAME

### 4. Создайте базу данных, которую вы указали в переменной DB_NAME
```bash
php bin/console doctrine:database:create
```
Если появляется ошибка:
```SQL
SQLSTATE[HY000]: General error: 1007 Can`t create database `DB_NAME`; database exists
```
то всё хорошо, значит база данных уже создана
<br>
<br>
*Если база данных была создана вами ранее и она не пуста, то для корректной работы удалите базу данных и выполните пункт заново (4 пункт)*

### 5. Выполните миграции
```bash
php bin/console doctrine:migrations:migrate
```

### 6. Запустите встроенный сервер
```bash
symfony server:start -d
```

---
<p align="center" style="font-style: italic; color: #8e8e8e">
    vpopov
    <br>
    and by javalets team
</p>
