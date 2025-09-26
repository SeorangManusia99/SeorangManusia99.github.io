# MyAquarium

Aplikasi web ringan untuk membantu mengelola kebutuhan akuarium Anda. MyAquarium menyediakan dashboard interaktif, penjadwalan dengan pengingat, pencatatan perlengkapan dan pembelian, rencana belanja, hingga ringkasan keuangan.

## Fitur Utama
- **Dashboard** dengan ringkasan jadwal, perlengkapan, pembelian, dan tips perawatan.
- **Penjadwalan** aktivitas (pakan, ganti air, maintenance, dll.) lengkap dengan frekuensi fleksibel, catatan, serta alarm pengingat berbasis browser.
- **Catatan Perlengkapan & Pembelian** dengan dukungan CRUD, pencarian, dan perhitungan otomatis total pengeluaran.
- **Catatan Pemeliharaan** untuk memantau ikan yang mati atau beranak, terintegrasi dengan perhitungan total ikan saat ini.
- **Rencana Pembelian** yang dapat dipindahkan langsung ke catatan perlengkapan atau pembelian setelah terealisasi.
- **Ringkasan Keuangan** yang merangkum pengeluaran aktual dan estimasi rencana belanja.

## Teknologi
- PHP 8+
- Tailwind CSS via CDN
- JavaScript (vanilla) untuk interaktivitas dan alarm

## Cara Menjalankan
1. Pastikan PHP telah terpasang di perangkat Anda.
2. Jalankan server pengembangan lokal dari root proyek:
   ```bash
   php -S localhost:8000
   ```
3. Buka `http://localhost:8000/index.php` di browser.

Semua data tersimpan sebagai berkas JSON di folder `data/`, sehingga mudah dicadangkan ataupun dipindahkan.
