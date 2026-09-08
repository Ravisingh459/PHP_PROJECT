# FreelanceHub Installation Guide

## Prerequisites

- **PHP 8.0+** with extensions: `pdo_mysql`, `mbstring`, `json`, `openssl`
- **MySQL 8.0+** or MariaDB 10.5+
- **Composer** (for PHPMailer)
- **Web Server**: Apache 2.4+ or Nginx (or PHP built-in server for development)

## Step 1: Clone / Copy Project

Copy the `freelancehub` folder to your web server document root:

```bash
# Apache (macOS Homebrew)
cp -r freelancehub /usr/local/var/www/freelancehub

# Or XAMPP
cp -r freelancehub /Applications/XAMPP/htdocs/freelancehub

# Or keep in place and configure virtual host
```

## Step 2: Create Database

```bash
mysql -u root -p
```

```sql
SOURCE /path/to/freelancehub/database/freelancehub.sql;
```

Or via command line:

```bash
mysql -u root -p < database/freelancehub.sql
```

This creates the `freelancehub` database with all tables and sample data.

## Step 3: Configure Application

Edit `includes/config.php`:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'freelancehub');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');

// Application URL (no trailing slash)
define('APP_URL', 'http://localhost/freelancehub');

// Email (PHPMailer - Gmail example)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_FROM', 'noreply@freelancehub.com');
```

## Step 4: Install PHP Dependencies

```bash
cd freelancehub
composer install
```

This installs PHPMailer for email verification and password reset.

## Step 5: Set File Permissions

```bash
chmod -R 755 assets/uploads/
chmod -R 755 assets/uploads/profiles/
chmod -R 755 assets/uploads/portfolios/
chmod -R 755 assets/uploads/projects/
chmod -R 755 assets/uploads/messages/
chmod -R 755 assets/uploads/resumes/
chmod -R 755 assets/uploads/certificates/
```

## Step 6: Web Server Configuration

### Apache

Ensure `mod_rewrite` is enabled. The included `.htaccess` handles routing and security headers.

```apache
<VirtualHost *:80>
    DocumentRoot "/path/to/freelancehub"
    ServerName freelancehub.local

    <Directory "/path/to/freelancehub">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name freelancehub.local;
    root /path/to/freelancehub;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### PHP Built-in Server (Development)

```bash
cd freelancehub
php -S localhost:8000
```

Update `APP_URL` to `http://localhost:8000`.

## Step 7: Verify Installation

1. Open `http://localhost/freelancehub` in your browser
2. You should see the FreelanceHub landing page
3. Login with demo account: `admin@freelancehub.com` / `password`
4. Test registration, project posting, and bidding

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Database connection failed | Check `config.php` credentials and ensure MySQL is running |
| 404 on pages | Verify `APP_URL` matches your actual URL; check Apache `AllowOverride` |
| Uploads fail | Check folder permissions on `assets/uploads/` |
| Emails not sending | Run `composer install`; configure SMTP in `config.php` |
| Blank page | Enable `display_errors` in `config.php`; check PHP error log |
| CSRF errors | Clear browser cookies; ensure sessions are working |

## Production Checklist

- [ ] Set `display_errors` to `0` in `config.php`
- [ ] Use HTTPS and update `APP_URL`
- [ ] Configure real SMTP credentials
- [ ] Set strong database password
- [ ] Restrict `assets/uploads/` from executing PHP
- [ ] Enable regular database backups
- [ ] Review and update default admin password

## Sample Data

The SQL file includes:
- 1 admin, 2 clients, 4 freelancers
- 5 projects with bids and contracts
- Messages, reviews, notifications, portfolios
- Skills, achievements, and skill tests

All demo users use password: `password`
