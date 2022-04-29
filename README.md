# App Manager

You must run the following command to install the dependencies:
```
composer update
```

For generate a .env file copy the file .env.example and rename it to .env<br>
Fill the .env file with your database credentials.

To generate app keys, run the following command:
```
php artisan key:generate
```

For starting the application, you must run the following command:
```
php artisan serve
```

You can get the database with the following command, or you can import with the sql file in the root folder.
```
php artisan migrate
```
