### Introduction
Laravel crud is a simple package to create CRUD operations creating table migrations. <br/>
This is for Laravel 5.2

### Installation
1. Get the package using composer 
    ```
    composer require deangite/laravel-crud
    ```

2. Add the service provider to **/config/app.php**.
    ```php
    'providers' => [
        ...

        Deangite\LaravelCrud\LaracrudServiceProvider::class,
    ],
    ```

### Usage
1. To create migrations, models, controllers and routes, run
	```
	php artisan laravel:crud
	```
2. After creating migration files you can simply run
	```
	php artisan migrate
	```
3. It will create routes based on table name.
