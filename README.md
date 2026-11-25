# MML web app (Laravel 12)

Public website, staff console (`/staff`), customer portal (`/portal`) and the Android app's API (`/api/v1`).

Setup, demo logins and how everything fits together: see `../README.md`.

```bash
composer install && cp .env.example .env && php artisan key:generate
php artisan migrate --seed && php artisan storage:link && php artisan serve
php artisan test
```
