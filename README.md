# Aksa Framework

Framework MVC PHP yang ringan dan powerful, terinspirasi dari Laravel dengan fokus pada kesederhanaan dan performa tinggi.

## ✨ Fitur

- 🚀 **Routing** - Sistem routing yang simple dan powerful seperti Laravel
- 🎨 **Aksa template engine (.aksa.php)** - Sintaks template yang familiar dan mudah digunakan
- 💾 **Eloquent-like ORM** - Query builder dan model dengan sintaks yang elegan
- 🔧 **MVC Pattern** - Struktur kode yang terorganisir dengan baik
- 🛡️ **Middleware Support** - Sistem middleware untuk filtering HTTP requests
- 📦 **Service Container** - Dependency injection container untuk manajemen dependencies
- 🔍 **Request & Response** - Object-oriented HTTP handling
- ⚡ **Lightweight** - Ukuran kecil dan performa tinggi

## 📋 Requirements

- PHP >= 7.4
- Composer
- MySQL/MariaDB (atau database lain yang didukung PDO)
- Apache/Nginx dengan mod_rewrite enabled

## 🚀 Instalasi

1. Clone atau download framework ini
2. Install dependencies:
```bash
composer install
```


3. Copy file `.env.example` ke `.env`:
```bash
cp .env.example .env
```

4. Konfigurasi database di file `.env`:
```
DB_DRIVER=mysql
DB_HOST=localhost
DB_DATABASE=aksa
DB_USERNAME=root
DB_PASSWORD=
```

5. Buat database sesuai konfigurasi


## 🎯 Penggunaan

### Menjalankan server pengembangan

Gunakan perintah angkasa `serve` untuk menjalankan built-in PHP server (mirip Laravel):

```bash
# default: http://127.0.0.1:8000
php angkasa serve

# custom host/port
php angkasa serve --host=0.0.0.0 --port=8080
```


## 📁 Struktur Folder

```
aksa/
├── app/
│   ├── Controllers/       # Controllers
│   ├── Models/           # Models (Eloquent-like)
│   └── Middleware/       # Middleware
├── bootstrap/
│   └── app.php          # Bootstrap file
├── config/
│   └── database.php     # Database configuration
├── public/
│   ├── index.php        # Entry point
│   └── .htaccess        # Apache rewrite rules
├── resources/
│   └── views/           # Aksa views (.aksa.php)
├── routes/
│   └── web.php          # Route definitions
├── src/                 # Framework core
│   ├── Database/
│   ├── Http/
│   ├── Routing/
│   ├── Support/
│   └── View/
└── composer.json
```
