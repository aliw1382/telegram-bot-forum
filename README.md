<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>


# Step 1 ( Download & Installation )


<p>Download From Github</p>

```bash
git clone https://github.com/aliw1382/telegram-bot-forum.git
```

<p>Install Packages Vendors Laravel</p>

```bash
composer install
```

# Step 2 ( Config Database )

<p>Copy <code>.env</code> file</p>

```bash
cp .env.example .env
copy .env.example .env
```

<p>Make Key Application</p>

```bash
php artisan key:generate
```

<p>Config Database <code>.env</code> file</p>

```dotenv
DB_CONNECTION=
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

<p>Create Tables</p>

```bash
php artisan migrate
```

# Step 3 ( Config Source )

<p>Config Application Telegram Bot</p>

```markdown

|    Variable    |             Description              |
| -------------- | ------------------------------------ |
| TOKEN_API      | Token received from Botfather        |
| ADMIN_LOG      | Numeric ID of the main admin         |
| GP_SUPPORT     | Group ID to manage support messages  |
| CHANNEL_ID     | Numeric ID of the channel            |
| CHANNEL_LOG    | Numerical ID of the logs channel     |
| MERCHANT_ID    | Received token from Zarin Pal portal |
```

```dotenv
TOKEN_API=
ADMIN_LOG=
GP_SUPPORT=
CHANNEL_ID=
CHANNEL_LOG=
MERCHANT_ID=
```

# Step 4 ( SetWebHook )

<p>Set Web Hook On This Url</p>

```url
https://domain.com/bot/index.php
```

# Step 5 ( Schedule Or Cron Job )

<p>
Add This Command To Your Cron Job
</p>

```bash
php /PATH_OF_SOURCE/'artisan' schedule:run >> /dev/null 2>&1
```

# Step 6 [Optional] ( Cached Application )

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
```

# Security

If you discover any security related issues, please email aliw1382@gmail.com instead of using the issue tracker.

# License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
