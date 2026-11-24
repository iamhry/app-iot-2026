# Monitoring Suhu dan Kelembapan Ruangan

Aplikasi pembelajaran untuk memantau suhu dan kelembapan ruangan. ESP32 mengirim pengukuran ke REST API PHP. Administrator melihat dashboard, grafik, dan riwayat di peramban.

Aplikasi ini prototipe pendidikan. Bukan sistem klinis yang tervalidasi dan bukan pengendali alat medis.

## Arsitektur

```text
ESP32 + DHT22 / Postman
        | HTTP POST + Bearer Token + JSON
        v
REST API PHP Native
        | Autentikasi, validasi, transaksi
        v
MariaDB/MySQL: devices + sensor_readings
        |
        v
Dashboard PHP + Fetch API + Chart.js
```

Waktu disimpan dalam UTC dan ditampilkan sebagai WIB (Asia/Jakarta).

## Kebutuhan

- PHP 8.2 atau lebih baru, dengan ekstensi PDO MySQL
- MariaDB 10.11 atau MySQL 8
- Peramban modern
- Untuk perangkat: Arduino IDE, board ESP32, sensor DHT22, dan pustaka DHT dari Adafruit

## Struktur

Hanya direktori `public/` yang boleh menjadi document root. Kredensial database dibaca dari `config.php`, dan dapat ditimpa oleh environment atau file `.env`.

```text
app/            konfigurasi, helper, model, tampilan
public/         halaman admin, AJAX, dan REST API
database/       schema.sql
scripts/        pembuatan administrator
postman/        collection dan environment
examples/       contoh sketch ESP32
```

## Konfigurasi database

Salin pola di `.env.example` bila ingin menimpa `config.php`. Aplikasi benar-benar membaca environment, lalu `.env`, lalu variabel di `config.php`.

Nilai yang dipakai mesin ini ada di `config.php`:

- host `localhost` (aplikasi menghubungi `127.0.0.1:3306`)
- database `app-iot-2026`
- user `app-iot-2026`

Host `localhost` dialihkan ke `127.0.0.1` karena socket bawaan PHP tidak menunjuk `/tmp/mysql.sock`. Untuk socket khusus, set `DB_SOCKET`.

Impor tabel:

```bash
mysql --skip-ssl -h 127.0.0.1 -u app-iot-2026 -p app-iot-2026 < database/schema.sql
```

Gunakan akun MySQL yang hanya berhak pada database aplikasi ini.

## Administrator pertama

Kata sandi diminta di terminal dan tidak ditulis di kode.

```bash
php scripts/create-admin.php
```

Untuk otomasi lokal, boleh memakai argumen `--name`, `--username`, dan `--password`. Jangan memasukkan kata sandi itu ke repository.

## Menjalankan

```bash
php -S localhost:8000 -t public
```

Buka `http://127.0.0.1:8000`. PHP Development Server jangan diekspos ke internet.

Agar ESP32 di jaringan praktikum tepercaya dapat menjangkau server:

```bash
php -S 0.0.0.0:8000 -t public
```

Gunakan IP LAN komputer, misalnya `http://192.168.1.10:8000/api/v1/readings.php`. Untuk pemakaian nyata, layani `public/` lewat HTTPS.

### Apache

Document root mengarah ke `public/`. `public/.htaccess` meneruskan header `Authorization`. Pada PHP-FPM tambahkan `CGIPassAuth On` bila header itu kosong.

### Nginx

```nginx
root /path/ke/aplikasi/public;
location / {
    try_files $uri $uri/ =404;
}
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param HTTP_AUTHORIZATION $http_authorization;
    fastcgi_pass unix:/run/php/php-fpm.sock;
}
```

## Perangkat dan token

1. Masuk sebagai administrator.
2. Buka Perangkat Sensor, lalu Tambah perangkat.
3. Simpan Device ID dan token dari halaman kredensial. Token asli hanya muncul sekali dan tidak disimpan di database.
4. Perangkat mengirim:

```bash
curl -X POST "$BASE_URL/api/v1/readings.php" \
  -H "Authorization: Bearer $DEVICE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"device_id":"DEV-A82F91C4","temperature":26.5,"humidity":65.2}'
```

Rotasi token ada di halaman detail perangkat. Token lama langsung ditolak. Metadata dapat diubah tanpa mengganti token. Perangkat yang sudah punya riwayat tidak dihapus permanen; gunakan nonaktifkan.

Status koneksi:

- nonaktif bila perangkat dimatikan
- belum terhubung bila belum pernah mengirim data
- online bila selisih waktu server dan `last_seen_at` tidak lebih dari 3 kali interval kirim
- selain itu offline

Status suhu (`below_min`, `normal`, `above_max`) terpisah dari status koneksi. Angka dari perangkat offline ditandai sebagai data terakhir, bukan kondisi saat ini.

## Postman

Impor `postman/IoT-Monitoring.postman_collection.json` dan `postman/Local.postman_environment.json`. Isi `base_url`, `device_id`, dan `device_token` di environment. Untuk uji perangkat nonaktif, isi `inactive_device_token`. Uji rate limit sengaja mengirim banyak permintaan; jalankan terpisah.

## ESP32 dan DHT22

Sketch: `examples/esp32-dht22/esp32-dht22.ino`.

1. Pasang library DHT sensor dan Adafruit Unified Sensor.
2. Isi SSID, kata sandi Wi-Fi, URL API, Device ID, dan token.
3. Hubungkan VCC ke 3V3, GND ke GND, dan DATA ke GPIO 4. Sensor tanpa modul perlu pull-up 10k ohm.
4. Unggah, lalu lihat kode HTTP di Serial Monitor. Token tidak dicetak.

Interval bawaan sketch adalah 30 detik. Untuk produksi gunakan HTTPS dengan verifikasi sertifikat, bukan `setInsecure()`.

## Keamanan

- PDO prepared statement
- kata sandi administrator di-hash
- token perangkat disimpan sebagai SHA-256, hanya awalan yang ditampilkan ulang
- CSRF pada aksi ubah data, termasuk logout
- cookie sesi HttpOnly dan SameSite, serta Secure pada HTTPS
- batas ukuran permintaan, metode HTTP, dan laju permintaan
- header `Authorization` tidak dicatat

Batas API: 30 permintaan per 60 detik untuk tiap perangkat, dan 20 kegagalan token per 60 detik untuk tiap alamat IP. Login administrator dibatasi 5 kegagalan per 15 menit.
