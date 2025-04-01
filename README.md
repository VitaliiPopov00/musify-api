## about the project and the best settings
Symfony Version 6.4
<br>
PHP Version 8.3
<br>
MYSQL Version 8.0

# Быстрый старт

### Склонируйте репозиторий
```bash
    git clone https://github.com/VitaliiPopov00/musify-api.git
```
перейдите в папку с проектом
```bash
    cd musify-api
```

### Установите зависимости Composer
``` bash
    composer install
```

### Настройте файл окружения .env
Скопируйте файл .env и настройте параметры:
```bash
    cp .env .env.local
```
Параметры .env для соединения к БД (обязательны к заполнению), лучше всего заполнять их в файле .env.local, созданном ранее:
* DB_USER
* DB_PASSWORD
* DB_HOST
* DB_PORT
* DB_NAME

### Создайте базу данных, которую вы указали в переменной DB_NAME
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
*Если база данных была создана вами ранее и она не пуста, то для корректной работы удалите базу данных и выполните пункт заново*

### Выполните миграции
```bash
    php bin/console doctrine:migrations:migrate
```

### Запустите встроенный сервер
```bash
    symfony server:start -d
```