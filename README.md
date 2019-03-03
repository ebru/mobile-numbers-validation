# Mobile Numbers Validation

A REST API application validates South African mobile numbers from a CSV file. It allows you to make POST requests to validate numbers and attempt to fix them if it is applicable. https://ebrukye.github.io/mobile-numbers-validation/

**Technologies used;**
- Laravel 5.7.27 as framework
- Composer for dependency management
- MySQL 8.0.15 for database management
- Laravel Excel for processing CSV files
- Laravel Passport for authentication

## Installation
* Clone the repository and go to project directory.

`git clone https://github.com/ebrukye/mobile-numbers-validation.git`

`cd mobile-numbers-validation`

* Create the .env file as a copy from the example file provided.

`cp .env.example .env`

* Connect to MySQL and create a database. You can find the sample terminal command below.

`mysql -u root -p`

mysql> ``create database `mobile-numbers-validation`; ``


* Update the .env file with database connection details.

```
DB_DATABASE=mobile-numbers-validation
DB_USERNAME={username}
DB_PASSWORD={password}
```

* After setting the environment, run the build script.

`./build.sh`

or the equivalent commands below.

```
composer install
php artisan key:generate
php artisan migrate
php artisan passport:install
php artisan storage:link
php artisan serve
```

...and you're all done! You have started the server on http://localhost:8000
