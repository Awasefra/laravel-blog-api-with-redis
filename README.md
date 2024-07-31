# Description

This is a api to manage personal blog. Using Laravel, Redis and Oauth be authenticator

# Requirement Install Project

-   Laravel ^11.0.0
-   PHP ^8.2.4

# Recommend Required Extension on VS Code

-   PHP Intelphense
-   PHP Debug
-   Prettier
-   Git Lens
-   Laravel Artisan
-   Laravel Blade Formatter
-   Laravel Blade Snippets
-   Laravel Blade Spacer
-   Laravel Goto View
-   Laravel Intellisense
-   Laravel Snippets
-   Laravel Blade

# Installations

## Clone repositories

```
git clone https://github.com/Awasefra/laravel-blog-api-with-redis.git
```

## Install resourse laravel

```
composer install
```

## Create & update env File

```
cp .env.example .env
```

###

## Generate key

```
php artisan key:generate
```



## Migrate & Seed

```
php artisan migrate --seed
```

### Generate personal access token client

```
php artisan passport:client --personal
```

## Launch Apps

```
php artisan serve
```
