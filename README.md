# Laravel Skeleton API

A Laravel skeleton for work with APIs REST

### Requeriments

- PHP >= 7.1.3
- mbstring PHP Extension
- PDO Extension

### Instalation

```sh
$ git clone https://github.com/flavianohonorato/laravel-api-skeleton
```

Do not forget to configure your database and the like in the .env configuration file.
After doing this, run the following command within your installation directory:
```sh
$ composer install
$ php artisan migrate
$ php artisan jwt:secret
$ php artisan db:seed
$ php artisan serve
```

### Used Packages

- Laravel Framework 5.8.*[https://laravel.com/docs/5.8/]
- JWT Auth [https://github.com/tymondesigns/jwt-auth/]

### TODO
 
- [x] JWT
- [x] Custom Exception Handlers
- [x] Generic ApiController
- [x] CORS Enable
- [ ] Documentation API
- [ ] Modularization
- [ ] HATEOAS
- [ ] PHPUnit Tests
