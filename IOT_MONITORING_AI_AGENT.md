# AI Agent — IoT Room Temperature Monitoring System

## 1. Peran dan tujuan
Bertindak sebagai Senior Full Stack Developer, IoT Engineer, Database Engineer, REST API Developer, dan UI/UX Designer. Implementasikan aplikasi monitoring suhu dan kelembapan ruangan sebagai media pembelajaran D4 Teknologi Rekayasa Elektromedis. Kerjakan langsung di workspace, bukan sekadar memberikan contoh kode. Analisis kode yang sudah ada sebelum melakukan perubahan.

## 2. Technology stack
- PHP Native 8.2+, MySQL 8, PDO prepared statements.
- HTML5, CSS3, Bootstrap 5, Bootstrap Icons, JavaScript Native, Fetch API, Chart.js.
- REST API JSON, Bearer Token perangkat, PHP Session administrator.
- Postman, ESP32 dan DHT22 (DHT11 opsional).
- Jangan menggunakan Laravel, CodeIgniter, React, Vue atau framework backend lainnya.

## 3. Arsitektur
```text
ESP32 + DHT22 / Postman
        | HTTP POST + Bearer Token + JSON
        v
REST API PHP Native
        | Autentikasi, validasi, transaksi
        v
MySQL: devices + sensor_readings
        |
        v
Dashboard PHP + Fetch API + Chart.js
```
Administrator mendaftarkan perangkat melalui CRUD. Sistem menghasilkan Device ID dan token unik. Perangkat menggunakan kredensial tersebut untuk mengirim data. Server mengidentifikasi perangkat berdasarkan token, menyimpan pengukuran, dan memperbarui status koneksi. Dashboard melakukan polling setiap 10 detik.

## 4. Struktur proyek
```text
iot-monitoring/
├── app/
│   ├── config/{app.php,database.php}
│   ├── helpers/{auth.php,csrf.php,response.php,validation.php,device-auth.php}
│   ├── models/{User.php,Device.php,SensorReading.php}
│   └── views/layouts/{header.php,sidebar.php,footer.php}
├── public/
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── devices/{index.php,create.php,store.php,edit.php,update.php,show.php,delete.php,regenerate-token.php}
│   ├── monitoring/{index.php,detail.php,history.php}
│   ├── ajax/{dashboard.php,chart.php}
│   ├── api/v1/{readings.php,device.php,latest.php}
│   ├── documentation/index.php
│   └── assets/{css,js}/
├── database/schema.sql
├── scripts/create-admin.php
├── postman/
│   ├── IoT-Monitoring.postman_collection.json
│   └── Local.postman_environment.json
├── examples/esp32-dht22/esp32-dht22.ino
├── .env.example
├── .gitignore
└── README.md
```
Hanya direktori public boleh menjadi document root. Gunakan konfigurasi environment yang benar-benar dibaca oleh aplikasi; dokumentasikan cara mengaturnya.

## 5. Database
Buat `db_iot_monitoring` menggunakan InnoDB dan utf8mb4.

### users
| Kolom | Tipe / aturan |
|---|---|
| id | BIGINT UNSIGNED PK AUTO_INCREMENT |
| name | VARCHAR(100) NOT NULL |
| username | VARCHAR(100) UNIQUE NOT NULL |
| password | VARCHAR(255) NOT NULL, hash |
| created_at, updated_at | DATETIME |

### devices
| Kolom | Tipe / aturan |
|---|---|
| id | BIGINT UNSIGNED PK AUTO_INCREMENT |
| device_id | VARCHAR(50) UNIQUE NOT NULL |
| name | VARCHAR(100) NOT NULL |
| location | VARCHAR(150) NOT NULL |
| sensor_type | VARCHAR(50) NOT NULL |
| token_hash | CHAR(64) UNIQUE NOT NULL |
| token_prefix | VARCHAR(20) NOT NULL |
| min_temperature, max_temperature | DECIMAL(5,2) NOT NULL |
| reporting_interval | INT UNSIGNED NOT NULL, default 30 |
| is_active | BOOLEAN NOT NULL DEFAULT TRUE |
| last_seen_at | DATETIME NULL |
| created_at, updated_at | DATETIME |

### sensor_readings
| Kolom | Tipe / aturan |
|---|---|
| id | BIGINT UNSIGNED PK AUTO_INCREMENT |
| device_id | BIGINT UNSIGNED FK devices.id |
| temperature | DECIMAL(5,2) NOT NULL |
| humidity | DECIMAL(5,2) NOT NULL |
| recorded_at | DATETIME NOT NULL |
| received_at | DATETIME NOT NULL |

Relasi devices 1:N sensor_readings. Gunakan foreign key ON DELETE RESTRICT, indeks gabungan (device_id, recorded_at), dan indeks yang mendukung pencarian data terbaru. Simpan waktu UTC, tampilkan Asia/Jakarta. Tambahkan tabel pendukung rate limiting bila diperlukan.

## 6. Autentikasi administrator
Implementasikan login/logout, password_hash(), password_verify(), regenerasi session ID setelah login, CSRF pada semua operasi mutasi, cookie HttpOnly dan SameSite, serta Secure pada HTTPS. Lindungi seluruh halaman admin dan endpoint AJAX. Buat CLI untuk membuat administrator pertama tanpa password hardcode. Logout melalui POST dengan CSRF.

## 7. CRUD perangkat sensor
Form: nama, lokasi, jenis sensor, batas minimum/maksimum suhu, interval pengiriman, status aktif. Validasi panjang dan tipe seluruh field. Minimum harus lebih kecil dari maksimum; interval harus positif.

Saat registrasi, buat kredensial secara otomatis:
```php
$deviceId = 'DEV-' . strtoupper(bin2hex(random_bytes(8)));
$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$tokenPrefix = substr($token, 0, 8);
```
Simpan hanya hash dan prefix token. Tangani benturan unique constraint. Tampilkan Device ID, token asli, endpoint, contoh header dan JSON, serta tombol salin. Token asli ditampilkan sekali melalui halaman kredensial dengan Cache-Control: no-store; jangan masukkan token ke URL atau log. Berikan peringatan untuk menyimpan token dengan aman.

Sediakan regenerate token dengan konfirmasi dan CSRF. Token lama langsung tidak berlaku. Edit metadata tidak mengubah token. Perangkat yang memiliki histori tidak boleh dihapus permanen; gunakan nonaktifkan. Device ID tetap stabil.

## 8. REST API
| Method | Endpoint | Fungsi |
|---|---|---|
| POST | /api/v1/readings.php | Mengirim pengukuran |
| GET | /api/v1/device.php | Identitas dan konfigurasi perangkat sendiri |
| GET | /api/v1/latest.php | Pengukuran terakhir perangkat sendiri |

Semua endpoint perangkat menggunakan Bearer Token. CRUD admin menggunakan session, bukan token perangkat.

### POST readings
```http
POST /api/v1/readings.php
Authorization: Bearer {{device_token}}
Content-Type: application/json
```
```json
{
  "device_id": "DEV-A82F91C4",
  "temperature": 26.5,
  "humidity": 65.2
}
```
Respons 201:
```json
{
  "success": true,
  "message": "Data sensor berhasil disimpan",
  "data": {
    "device_id": "DEV-A82F91C4",
    "temperature": 26.5,
    "humidity": 65.2,
    "status": "normal"
  }
}
```

### Alur pemrosesan
1. Validasi metode HTTP dan Content-Type; batasi ukuran request.
2. Ambil Authorization header dan ekstrak Bearer Token.
3. Hash SHA-256 dan cari perangkat berdasarkan token_hash.
4. Tolak token tidak valid atau perangkat nonaktif.
5. Parse JSON dan validasi field.
6. Cocokkan Device ID payload dengan perangkat pemilik token. Jangan gunakan identitas dari payload sebagai sumber otorisasi.
7. Dalam transaksi database, simpan pengukuran dengan FK perangkat terautentikasi dan perbarui last_seen_at. Pastikan rotasi token dan penonaktifan tidak dapat dilewati oleh request konkuren.
8. Tentukan status suhu: below_min, normal, above_max.
9. Kembalikan JSON konsisten dan status HTTP yang tepat.

### Validasi
- Device ID wajib berupa string yang valid.
- Temperature dan humidity wajib berupa angka JSON finite, bukan string atau null.
- DHT22: suhu -40 sampai 80 °C; kelembapan 0–100%.
- Untuk sensor lain, gunakan rentang sesuai spesifikasi model.
- recorded_at opsional, format ISO 8601 dengan zona waktu; gunakan waktu server jika tidak dikirim. received_at selalu ditentukan server.
- Jangan menampilkan stack trace, query SQL, password atau token dalam respons.

### Status HTTP
| Kode | Kondisi |
|---|---|
| 200 | GET berhasil |
| 201 | Pengukuran tersimpan |
| 400 | JSON rusak |
| 401 | Token tidak ada/salah; sertakan WWW-Authenticate: Bearer |
| 403 | Perangkat nonaktif atau Device ID berbeda |
| 404 | Pengukuran belum tersedia |
| 405 | Metode salah; sertakan Allow |
| 415 | Content-Type tidak didukung |
| 422 | Validasi field gagal |
| 429 | Rate limit terlampaui |
| 500 | Kesalahan internal |

## 9. Dashboard dan UI/UX
Gunakan Bootstrap 5, warna navy, teal, putih dan abu-abu muda. Desain responsif, bersih, human-centered, tipografi jelas, whitespace proporsional, navigasi konsisten, feedback dan konfirmasi tindakan destruktif. Hindari dekorasi dan animasi berlebihan. Gunakan bahasa Indonesia yang baik.

Sidebar: Dashboard, Perangkat Sensor, Monitoring, Riwayat Pengukuran, Dokumentasi API, Logout.

Summary cards: total perangkat, online, offline, peringatan suhu. Tabel perangkat: nama, Device ID, lokasi, suhu terakhir, kelembapan terakhir, status suhu, status koneksi, waktu pengiriman terakhir.

Status koneksi: nonaktif jika is_active false; belum terhubung jika last_seen_at null; online jika selisih waktu server dengan last_seen_at <= 3 × reporting_interval; selain itu offline. Bedakan status suhu dari status koneksi; data lama tidak boleh ditampilkan seolah-olah terkini. Gunakan badge hijau untuk normal/online, biru untuk suhu rendah, merah untuk suhu tinggi, abu-abu untuk offline/nonaktif.

## 10. Monitoring dan grafik
Gunakan Chart.js untuk suhu dan kelembapan, periode 1 jam, 6 jam, 24 jam, 7 hari. Fetch API setiap 10 detik tanpa reload halaman. Hindari request bertumpuk. Batasi jumlah titik atau gunakan agregasi server untuk periode panjang. Tampilkan waktu pembaruan, satuan, empty state dan pesan kegagalan jaringan.

## 11. Riwayat
Filter perangkat, lokasi, tanggal awal/akhir; pencarian, pagination server-side dan export CSV. Kolom: nomor, Device ID, nama, lokasi, suhu, kelembapan, waktu pengukuran dan waktu diterima. Amankan CSV dari formula injection. Batasi ekspor agar tidak menghabiskan memori server.

## 12. Dokumentasi API
Buat halaman dokumentasi di aplikasi: konsep REST API, endpoint, HTTP method, Bearer Token, Content-Type, JSON request/response, status HTTP, Postman, cURL dan ESP32. Gunakan placeholder kredensial.
```bash
curl -X POST "$BASE_URL/api/v1/readings.php" \
  -H "Authorization: Bearer $DEVICE_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"device_id":"DEV-A82F91C4","temperature":26.5,"humidity":65.2}'
```

## 13. Postman Collection
Buat collection dan environment yang dapat diimpor. Variabel: base_url, device_id, device_token. Gunakan token lokal yang diisi pengguna; jangan menyertakan token asli di repository. Tambahkan test scripts untuk status HTTP dan struktur respons.

| Skenario | Ekspektasi |
|---|---|
| Token valid dan data valid | 201, data tersimpan |
| Tanpa token | 401 |
| Token salah | 401 |
| Device ID perangkat lain | 403 |
| Perangkat nonaktif | 403 |
| Suhu di luar rentang | 422 |
| Kelembapan 150% | 422 |
| Field wajib hilang | 422 |
| JSON rusak | 400 |
| Content-Type salah | 415 |
| Metode salah | 405 |
| GET device | 200, hanya identitas sendiri |
| GET latest | 200 atau 404 jika kosong |
| Rotasi token | Token lama 401, token baru berhasil |
| Rate limit | 429 |

Pastikan request yang ditolak tidak menambah data pengukuran atau memperbarui last_seen_at.

## 14. Integrasi ESP32 dan DHT22
Buat contoh Arduino IDE dengan konfigurasi berikut:
```cpp
const char* WIFI_SSID = "YOUR_WIFI";
const char* WIFI_PASSWORD = "YOUR_PASSWORD";
const char* API_URL = "YOUR_API_ENDPOINT";
const char* DEVICE_ID = "YOUR_DEVICE_ID";
const char* DEVICE_TOKEN = "YOUR_DEVICE_TOKEN";
```
Implementasikan koneksi WiFi, pembacaan DHT22, validasi hasil sensor, JSON payload, HTTP POST dengan Authorization Bearer, timeout, reconnect WiFi, interval 30 detik, dan penanganan kegagalan. Tampilkan status HTTP di Serial Monitor tanpa token. Dokumentasikan library, pin, wiring sesuai modul, dan cara konfigurasi. Untuk produksi gunakan HTTPS dengan verifikasi sertifikat, bukan setInsecure().

## 15. Keamanan
PDO prepared statements, password hashing, token hashing, CSRF, output escaping, validasi input, session authentication, pembatasan metode dan ukuran request, rate limiting per perangkat dan percobaan autentikasi, environment variables, HTTPS. Jangan log Authorization header, token atau password. Gunakan akun MySQL dengan hak akses minimum. Pastikan Authorization header diteruskan oleh Apache/Nginx ke PHP. Aplikasi adalah prototipe pendidikan, bukan sistem klinis tervalidasi atau pengendali alat medis.

## 16. Instalasi dan README
Dokumentasikan arsitektur, kebutuhan software, struktur proyek, impor schema.sql, konfigurasi database, pembuatan admin, cara menjalankan, registrasi perangkat, penggunaan token, Postman, ESP32 dan keamanan. Sertakan konfigurasi web server bila diperlukan.
```bash
php -S localhost:8000 -t public
```
Untuk ESP32 di jaringan lokal, dokumentasikan penggunaan IP LAN server dan binding `0.0.0.0:8000` hanya pada jaringan praktikum tepercaya. Jangan mengekspos PHP Development Server ke internet. Gunakan HTTPS untuk penggunaan nyata.

## 17. Acceptance criteria
- [ ] Login/logout admin dan proteksi halaman berfungsi.
- [ ] CRUD perangkat dan penonaktifan berfungsi.
- [ ] Device ID unik dan token aman dihasilkan otomatis.
- [ ] Token asli tidak tersimpan dalam database/repository/log.
- [ ] Rotasi token membatalkan token lama.
- [ ] API valid menyimpan data; token salah, perangkat nonaktif dan identitas berbeda ditolak.
- [ ] Validasi suhu, kelembapan dan JSON berjalan.
- [ ] Dashboard, grafik, polling dan status koneksi berjalan.
- [ ] Riwayat, filter, pagination dan CSV berjalan.
- [ ] Dokumentasi API, Postman Collection, environment dan contoh ESP32 tersedia.
- [ ] Seluruh file PHP lolos php -l.
- [ ] Tidak ada kredensial sensitif di repository.

## 18. Tahapan eksekusi AI Agent
1. Audit workspace dan susun implementation plan.
2. Buat struktur proyek, schema dan konfigurasi.
3. Implementasikan autentikasi admin.
4. Implementasikan CRUD perangkat dan kredensial.
5. Implementasikan REST API dan pengujian positif/negatif.
6. Implementasikan dashboard, monitoring, grafik dan riwayat.
7. Buat dokumentasi, Postman Collection dan contoh ESP32.
8. Jalankan lint PHP, pengujian database, API dan UI yang tersedia.
9. Perbaiki kesalahan yang ditemukan.
10. Laporkan file yang dibuat, fitur, hasil pengujian aktual, kendala dan langkah menjalankan aplikasi.

Jangan hanya membuat mockup atau data dummy. Pastikan fitur terintegrasi dengan MySQL. Jangan mengklaim pengujian berhasil jika belum dijalankan. Jika perangkat fisik atau database tidak tersedia, jelaskan batas pengujian secara eksplisit.
