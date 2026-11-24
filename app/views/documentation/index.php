<?php

declare(strict_types=1);
?>
<article class="docs">
    <p>Perangkat mengirim pengukuran ke server dengan REST API. Administrator memakai sesi login. Perangkat memakai Bearer Token yang hanya ditampilkan sekali saat pendaftaran.</p>

    <h2>Konsep singkat</h2>
    <ul>
        <li>Setiap permintaan perangkat mencantumkan header <code>Authorization: Bearer &lt;token&gt;</code>.</li>
        <li>Pengukuran dikirim sebagai JSON dengan <code>Content-Type: application/json</code>.</li>
        <li>Server mengenali perangkat dari hash token, bukan dari <code>device_id</code> di badan permintaan.</li>
        <li><code>device_id</code> pada JSON harus sama dengan perangkat pemilik token.</li>
    </ul>

    <h2>Endpoint</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr><th>Metode</th><th>Path</th><th>Fungsi</th></tr>
            </thead>
            <tbody>
            <tr><td>POST</td><td><code>/api/v1/readings.php</code></td><td>Menyimpan suhu dan kelembapan</td></tr>
            <tr><td>GET</td><td><code>/api/v1/device.php</code></td><td>Identitas dan konfigurasi perangkat sendiri</td></tr>
            <tr><td>GET</td><td><code>/api/v1/latest.php</code></td><td>Pengukuran terakhir perangkat sendiri</td></tr>
            </tbody>
        </table>
    </div>

    <h2>Mengirim pengukuran</h2>
    <pre>POST /api/v1/readings.php
Authorization: Bearer $DEVICE_TOKEN
Content-Type: application/json

{
  "device_id": "DEV-A82F91C4",
  "temperature": 26.5,
  "humidity": 65.2
}</pre>
    <p>Respons sukses memakai HTTP 201:</p>
    <pre>{
  "success": true,
  "message": "Data sensor berhasil disimpan",
  "data": {
    "device_id": "DEV-A82F91C4",
    "temperature": 26.5,
    "humidity": 65.2,
    "status": "normal"
  }
}</pre>
    <p><code>recorded_at</code> boleh ditambahkan dalam format ISO 8601 beserta zona waktu, misalnya <code>2026-11-24T08:00:00+07:00</code>. Jika tidak dikirim, server memakai waktu penerimaan. <code>status</code> bernilai <code>below_min</code>, <code>normal</code>, atau <code>above_max</code> menurut ambang perangkat.</p>

    <h2>cURL</h2>
    <pre>curl -X POST "$BASE_URL/api/v1/readings.php" \
  -H "Authorization: Bearer $DEVICE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"device_id":"DEV-A82F91C4","temperature":26.5,"humidity":65.2}'</pre>

    <h2>Status HTTP</h2>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Kode</th><th>Kondisi</th></tr></thead>
            <tbody>
            <tr><td>200</td><td>GET berhasil</td></tr>
            <tr><td>201</td><td>Pengukuran tersimpan</td></tr>
            <tr><td>400</td><td>JSON rusak atau ukuran permintaan terlalu besar</td></tr>
            <tr><td>401</td><td>Token tidak ada atau salah</td></tr>
            <tr><td>403</td><td>Perangkat nonaktif atau Device ID tidak sesuai token</td></tr>
            <tr><td>404</td><td>Pengukuran belum tersedia</td></tr>
            <tr><td>405</td><td>Metode HTTP salah</td></tr>
            <tr><td>415</td><td>Content-Type bukan application/json</td></tr>
            <tr><td>422</td><td>Isi field tidak lolos validasi</td></tr>
            <tr><td>429</td><td>Terlalu banyak permintaan</td></tr>
            <tr><td>500</td><td>Kesalahan server</td></tr>
            </tbody>
        </table>
    </div>

    <h2>Validasi sensor</h2>
    <p>DHT22 menerima suhu -40 sampai 80 °C dan kelembapan 0 sampai 100%. DHT11 menerima suhu 0 sampai 50 °C dan kelembapan 20 sampai 90%. Angka harus bertipe number pada JSON, bukan string.</p>

    <h2>Postman dan ESP32</h2>
    <p>Impor <code>postman/IoT-Monitoring.postman_collection.json</code> dan <code>postman/Local.postman_environment.json</code>. Isi <code>base_url</code>, <code>device_id</code>, dan <code>device_token</code> dari halaman kredensial. Jangan menyimpan token asli di repository.</p>
    <p>Contoh firmware ada di <code>examples/esp32-dht22/esp32-dht22.ino</code>. Ganti Wi-Fi, URL, Device ID, dan token sebelum unggah. Untuk pemakaian nyata gunakan HTTPS dengan verifikasi sertifikat.</p>
</article>
