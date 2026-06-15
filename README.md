# RSUD Gayo Lues SIMRS Revamp

Aplikasi ini adalah revamp bertahap untuk ekosistem SIMRS RSUD Gayo Lues. Aplikasi baru tetap memakai database SIMRS legacy yang juga digunakan oleh aplikasi lama, sehingga pengembangan harus menjaga kompatibilitas data dan workflow rumah sakit yang sudah berjalan.

## Tech Stack

- PHP 8.4
- Laravel 13
- Inertia.js 3
- Vue 3
- TypeScript
- Tailwind CSS 4
- Shadcn/Vue dan Reka UI
- Laravel Fortify
- Laravel Wayfinder
- Pest PHP
- MySQL atau MariaDB untuk database SIMRS legacy
- Redis untuk session/cache
- DomPDF untuk cetakan PDF

## Kebutuhan Lokal

Pastikan environment lokal sudah memiliki:

- PHP 8.4 dengan extension umum Laravel
- Composer
- Node.js dan npm
- MySQL atau MariaDB
- Redis
- Git

Untuk Windows, project ini biasanya dijalankan lewat Laragon.

## Setup Awal

Clone repository, lalu masuk ke folder project:

```bash
git clone <repository-url>
cd rsud_gayo_lues_revamp
```

Install dependency PHP dan JavaScript:

```bash
composer install
npm install
```

Salin file environment dan generate application key:

```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan `.env` dengan environment lokal. Minimal konfigurasi yang perlu dicek:

```env
APP_NAME="RSUD Gayo Lues"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_simrs
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=database

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Database yang digunakan adalah database SIMRS legacy. Jangan menjalankan migration ke database produksi atau database legacy bersama tanpa memahami migration yang akan dieksekusi.

Untuk environment lokal baru, jalankan migration setelah database sudah benar:

```bash
php artisan migrate
```

Generate route/action TypeScript Wayfinder jika diperlukan:

```bash
php artisan wayfinder:generate
```

## Menjalankan Aplikasi

Mode development lengkap:

```bash
composer run dev
```

Command tersebut menjalankan Laravel server di port `8080`, queue listener, dan Vite dev server.

Alternatif menjalankan service secara terpisah:

```bash
php artisan serve --port=8080
npm run dev
php artisan queue:listen --tries=1
```

Buka aplikasi di:

```text
http://localhost:8080
```

## Build Production

Build asset frontend:

```bash
npm run build
```

Optimasi Laravel untuk deployment:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Quality Check

Jalankan pengecekan sebelum membuat pull request:

```bash
vendor/bin/pint --dirty
npm run types:check
npm run lint:check
php artisan test --compact
```

Build final:

```bash
npm run build
```

## Struktur Utama

- `app/` berisi kode backend Laravel.
- `app/Modules/` berisi modul domain SIMRS.
- `resources/js/pages/` berisi halaman Inertia.
- `resources/js/components/` berisi komponen Vue.
- `resources/js/composables/` berisi composable Vue shared.
- `routes/` berisi definisi route Laravel.
- `database/migrations/` berisi migration baru yang dibutuhkan revamp.
- `tests/` berisi test Pest.

## Catatan Pengembangan

- Aplikasi ini bukan greenfield. Selalu cek struktur database legacy sebelum menulis query atau migration baru.
- Jangan mengubah aplikasi legacy dari repository ini.
- Jangan commit `.env`, file konfigurasi lokal, atau file konteks AI.
- File `AGENTS.md`, `.agents/`, dan `.codex/` berisi konteks internal pengembangan dan harus tetap lokal.
- Gunakan nama domain berbahasa Indonesia untuk modul SIMRS.
- Gunakan Wayfinder untuk route/action frontend.
- Gunakan service layer untuk business logic module yang besar.

## Troubleshooting

Jika halaman tidak berubah setelah edit frontend:

```bash
npm run dev
```

atau build ulang:

```bash
npm run build
```

Jika session/cache bermasalah, pastikan Redis berjalan lalu bersihkan cache:

```bash
php artisan optimize:clear
```

Jika route/action TypeScript tidak ditemukan:

```bash
php artisan wayfinder:generate
```
