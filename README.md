# Grohabitate Backend API with Laravel

## Prerequiries

- PHP 7
- Composer
- NodeJs
- MySQL

## Installation

- Clone the repository
- Installer back dependencies with `composer install`
- Install front dependencies with `npm i`
- Copy file `.env.example` in `.env` and add following informations:
    - Database credentials (`DB_HOST`, `DB_PORT`, ...)
    - Application url (`APP_URL`). Either virtual host address if you configure one, either address form the command `php artisan serve`
- Generate application key with `php artisan key:generate`
- Generate JWT key with `php artisan jwt:secret`
- Launch migrations with `php artisan migrate --seed`.
- Build front with `npm run dev`

If you did'nt set a virtual host, launch application with the `php artisan serve` command. By default, application will be served at `http://127.0.0.1:8000`

## Configuration

You can change the length of time (in minutes) that the token will be valid for by changin the `JWT_TTL` value in the `.env` file.

