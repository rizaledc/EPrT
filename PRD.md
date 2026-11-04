# PRD PerumahanInggris

Nov 4, 2026 · @Rizal

## 1. Ringkasan produk

PerumahanInggris adalah website latihan tes kemampuan bahasa Inggris berformat PBT (Listening, Structure & Written Expression, Reading) dengan kurikulum 14 hari untuk mencapai skor perkiraan 500+. Website ini dibangun dengan PHP 8 + MySQL di hosting Hostinger milik pemilik.

**Masalah yang diselesaikan.** Mahasiswa tingkat akhir butuh skor minimum (misalnya 500) untuk sidang, tetapi kesulitan memahami istilah grammar berbahasa Inggris. Mereka juga tidak punya tempat latihan terstruktur yang menjelaskan *mengapa* sebuah jawaban salah.

**Pembeda utama.**

- Penjelasan setiap pilihan jawaban (benar dan salah) dalam Bahasa Indonesia dan Inggris, lengkap dengan perumpamaan sehari-hari.
- Istilah grammar selalu disertai arti Indonesianya dalam kurung, contoh: verb (kata kerja).
- Tema "perumahan": 4 **Blok** (level) berisi 14 **Rumah** (hari), dibuka sesuai kecepatan pengguna.
- Riwayat dan progres tersimpan di akun, jadi pengguna tidak perlu mengulang.

**Metrik sukses (3 bulan pertama).**

| Metrik | Target |
| --- | --- |
| Pengguna terdaftar | 50 |
| Pengguna yang menyelesaikan Blok A | 60% dari yang mendaftar |
| Pengguna yang menyelesaikan seluruh 14 Rumah | 25% |
| Waktu muat landing page (4G) | < 2,5 detik |
| Skor Lighthouse Performance & Accessibility | ≥ 90 |

## 2. Pengguna dan peran

Ada tiga peran: tamu, member, dan admin. Tamu hanya melihat landing page dan 1 Rumah contoh, sedangkan latihan penuh memerlukan akun.

| Peran | Siapa | Bisa melakukan |
| --- | --- | --- |
| Tamu | Pengunjung tanpa akun | Melihat landing page, mencoba **Rumah Contoh** (10 soal campuran, hasil tidak disimpan), membuka Kamus Istilah, register, login |
| Member | Pemilik, teman, dan umum | Semua latihan 14 Rumah, simulasi, riwayat, catatan kesalahan, unduh ringkasan per Blok, perkiraan skor akhir, ganti bahasa, ganti password |
| Admin | Pemilik website | Panel admin: mengelola pengguna dan soal, melihat progres setiap pengguna, statistik |

**Persona utama.** Mahasiswa S1 semester akhir, 21–24 tahun, belum pernah ikut tes kemampuan bahasa Inggris. Listening sedang, tetapi grammar lemah dan belum paham istilah seperti noun, auxiliary, atau clause. Belajar 1 jam per hari, kebanyakan dari HP. Butuh skor 500 dalam 2 minggu untuk syarat sidang.

**Model bisnis.** Gratis di tahap ini. Struktur data harus siap untuk fitur berbayar nanti, yaitu kolom `plan` di tabel users (nilai awal `free`). Paywall belum dibuat.

## 3. Ruang lingkup

Versi 1 mencakup landing page, akun, 14 Rumah + 2 simulasi, admin, dan dua bahasa. Pembayaran dan aplikasi mobile belum termasuk.

**Termasuk (v1)**

- Landing page, halaman Latihan (peta Blok/Rumah), halaman sesi soal, hasil, riwayat, Kamus Istilah, profil.
- Register dan login dengan username + password kuat.
- 4 Blok, 14 Rumah, minimal 30 soal per Rumah dengan ketiga jenis soal; 2 simulasi penuh berformat 50/40/50.
- Audio Listening berupa file MP3 yang dibuat dengan Edge TTS.
- Unduh ringkasan hasil per Blok (PDF), perkiraan skor akhir 310–677.
- Panel admin: pengguna, progres, soal (CRUD), upload audio, statistik.
- Dua bahasa antarmuka dan penjelasan: Indonesia (default) dan Inggris.

**Di luar lingkup (v1)**

- Pembayaran, kupon, langganan.
- Login dengan Google atau email, serta reset password lewat email. Reset dilakukan oleh admin (lihat bagian 13).
- Aplikasi Android/iOS. Website wajib responsif untuk HP.
- Speaking dan writing.

**Batasan nama dan hak cipta (wajib dipatuhi)**

- Jangan memakai kata "EPrT", "TOEFL", "Telkom University", atau logo lembaga mana pun di nama, judul halaman, logo, maupun meta tag. Gunakan istilah umum: "tes kemampuan bahasa Inggris format PBT".
- Seluruh soal, teks reading, dan naskah audio harus **buatan orisinal**. Contoh soal resmi hanya boleh menjadi acuan format, tidak boleh disalin.
- Tampilkan disclaimer di footer: *"PerumahanInggris adalah platform latihan independen dan tidak berafiliasi dengan lembaga penyelenggara tes mana pun. Skor di website ini adalah perkiraan, bukan skor resmi."*
- Perkiraan skor selalu diberi label "Perkiraan" / "Estimated".

## 4. Arsitektur teknis

Stack: **PHP 8.2 native + MySQL + HTML/CSS/JavaScript vanilla, tanpa build step**. Stack ini berjalan di semua paket Hostinger, cukup di-upload lewat File Manager atau Git, dan tetap bisa tampil semewah referensi karena tampilan ditentukan oleh CSS, bukan oleh framework.

&#91;embedded content: arsitektur · browser, PHP, MySQL, file audio\]

Browser hanya berbicara dengan satu pintu (`index.php`). Kode aplikasi dan konfigurasi database disimpan di luar `public_html`, sehingga tidak bisa diakses dari internet.

**Keputusan teknis**

| Area | Pilihan | Alasan |
| --- | --- | --- |
| Bahasa server | PHP 8.2+, tanpa framework | Didukung semua paket Hostinger, tanpa Node |
| Database | MySQL 8 / MariaDB 10.6 via PDO, prepared statements | Disediakan Hostinger, aman dari SQL injection |
| Routing | Front controller `index.php` + `.htaccess` rewrite | URL bersih: `/latihan/blok-a/rumah-1` |
| Template | File `.php` dengan fungsi `e()` untuk escape HTML | Sederhana, aman dari XSS |
| CSS | Satu `app.css` dengan CSS custom properties (token di bagian 5) | Tanpa Tailwind/build |
| JS | ES modules vanilla di `/assets/js/` | Pemutar soal, timer, autosave |
| PDF ringkasan | Dompdf (dipasang lokal via Composer, folder `vendor/` ikut di-upload) | PDF rapi dari server |
| Audio | MP3 statis yang dibuat dengan `edge-tts` di komputer developer | Hosting tidak perlu menjalankan TTS |
| Font | Google Fonts | Lihat bagian 5 |
| Bahasa | File `lang/id.php` dan `lang/en.php` + kolom `_id` / `_en` di DB | Lihat bagian 14 |

**Struktur folder** (di Hostinger: `domains/<domain>/`)

```
app/
  config.php            # kredensial DB, BASE_URL, APP_SECRET (tidak di-commit)
  config.example.php
  bootstrap.php         # session, autoload, helper
  routes.php
  controllers/          # HomeController, AuthController, PracticeController, ApiController, AdminController
  models/               # User, Question, Attempt, Progress
  views/                # layout.php, landing.php, practice/*.php, admin/*.php, partials/
  lang/                 # id.php, en.php
  services/             # Scoring.php, ScoreEstimator.php, PdfReport.php
  database/
    schema.sql
    seed/               # blok-a.json … blok-d.json, simulasi-1.json, simulasi-2.json, glossary.json
    seed.php            # mengimpor JSON ke DB
    create_admin.php    # CLI sekali jalan, lihat bagian 13
  vendor/               # dompdf
tools/
  make_audio.py         # membuat MP3 dari naskah listening
  requirements.txt      # edge-tts
public_html/
  index.php
  .htaccess
  assets/css/app.css
  assets/js/*.js
  assets/img/           # SVG grafis, favicon, og-image
  audio/                # mp3 hasil tools/make_audio.py
```

## 5. Sistem desain

Arah visual: **terang, elegan, dan berani**. Dasarnya putih kemerahan, aksen utama pink "Jambu", judul besar yang rapat, dan grafis rumah geometris tanpa foto. Referensi yang diambil: pola judul tiga kata dengan kata terakhir berwarna ("Play. Connect. Belong."), tombol pill dengan lingkaran panah, kartu membulat besar (Finovate), dan rasa ramah ala kursus psikologi. Mode gelap tidak termasuk v1.

**Palet warna (CSS custom properties di `:root`)**

| Token | Hex | Nama | Dipakai untuk |
| --- | --- | --- | --- |
| `--pink-500` | #E8336F | Jambu | Warna utama: tombol primer, kata sorotan di judul, progres |
| `--pink-700` | #B3194F | Jambu Tua | Teks link dan teks pink di atas putih (kontras ≥ 4.5:1), hover tombol |
| `--pink-100` | #FFE3EC | Jambu Muda | Latar kartu aktif, chip, highlight |
| `--blush-50` | #FFF8FA | Kapur | Latar halaman |
| `--white` | #FFFFFF | Putih | Permukaan kartu |
| `--ink-900` | #24101C | Plum Gelap | Judul dan teks utama (pengganti hitam) |
| `--ink-500` | #76606C | Abu Plum | Teks sekunder |
| `--line` | #F0DFE6 | Garis | Border dan pemisah |
| `--plum-600` | #6B2FD6 | Ungu | Warna seksi **Structure** |
| `--teal-600` | #0E9A86 | Toska | Warna seksi **Reading** |
| `--lime-300` | #D6F35B | Daun | Sorotan kecil saja: stabilo di bawah kata, badge "Baru" (maksimal 1 per layar) |
| `--good` | #1A9A5C | Hijau | Jawaban benar |
| `--bad` | #D9482B | Bata | Jawaban salah (selalu disertai ikon ✕, jangan hanya mengandalkan warna) |

Warna seksi: **Listening = Jambu, Structure = Ungu, Reading = Toska**, konsisten di chip, ikon, progress bar, dan PDF.

**Tipografi (Google Fonts)**

| Peran | Font | Pengaturan |
| --- | --- | --- |
| Display (H1–H2, angka besar) | Bricolage Grotesque 700/800 | letter-spacing -0.03em, line-height 0.95 di H1 |
| Body, UI | Plus Jakarta Sans 400/500/600/700 | 16px dasar, line-height 1.6, lebar teks maks 68ch |
| Angka & timer | JetBrains Mono 500 | `font-variant-numeric: tabular-nums` |

Skala: 14 / 16 / 18 / 22 / 28 / 40 / 56 / `clamp(48px, 9vw, 112px)` untuk H1 landing. Label eyebrow memakai huruf kapital 12px dengan letter-spacing 0.14em, seperti "PADEL CLUB & MORE" di referensi.

**Bahasa grafis (tanpa foto)**

- **Rumah geometris**: badan persegi membulat dengan atap segitiga, dibuat sebagai SVG inline. Dipakai di hero, peta Latihan, dan ikon Rumah. Rumah terkunci (outline), sedang dikerjakan (isi Jambu Muda), selesai (isi Jambu + tanda centang).
- **Pola titik** 8×8 (seperti referensi kedua) sebagai tekstur di sudut kartu.
- **Angka raksasa** sebagai grafis: "14" hari, "500" target, nomor Rumah.
- **Garis stabilo** Daun di bawah satu kata kunci per judul.
- Ilustrasi kelas kata: kalimat sebagai **mobil** (subject = sopir, verb = mesin) digambar sebagai SVG garis sederhana, dipakai di Kamus Istilah.
- Semua ikon memakai satu set outline SVG inline (stroke 1.75, sudut membulat). Dilarang memakai emoji sebagai ikon.

**Komponen**

- Tombol primer: pill tinggi 52px, latar Jambu, teks putih, di kanan ada lingkaran putih berisi panah (→) yang bergeser 4px saat hover. Tombol sekunder: outline Jambu Tua.
- Kartu: radius 24px, border 1px `--line`, tanpa bayangan. Kartu yang disorot memakai latar Jambu Muda.
- Chip: pill 28px, huruf 12px kapital.
- Navbar: sticky, latar putih 85% + `backdrop-filter: blur(12px)`, tinggi 72px.
- Radius: 8 (input), 16 (kartu kecil), 24 (kartu), 999 (pill).
- Spasi: kelipatan 8. Jarak antar-section landing 120px di desktop, 72px di HP.
- Gerak: masuk pelan saat scroll (opacity 0.6 → 1, translate 16px, 400ms) dan hanya sekali. Hormati `prefers-reduced-motion`.
- Responsif: breakpoint 640 / 960 / 1200px. Target utama HP 390px.

## 6. Peta halaman dan alur pengguna

Website memiliki 14 rute halaman dan satu grup API. Pengunjung masuk lewat landing page, lalu tombol "Mulai Latihan" membawanya ke halaman Latihan. Jika belum login, ia diarahkan ke login dulu, lalu kembali ke halaman yang dituju.

| Rute | Halaman | Akses |
| --- | --- | --- |
| `/` | Landing page | Semua |
| `/coba` | Rumah Contoh (10 soal, tidak disimpan) | Semua |
| `/kamus` | Kamus Istilah Grammar | Semua |
| `/daftar`, `/masuk`, `/keluar` | Register, login, logout | Tamu / member |
| `/latihan` | Peta Perumahan: 4 Blok, 14 Rumah, 2 Simulasi, progres | Member |
| `/latihan/{blok}/{rumah}` | Halaman pembuka Rumah: materi singkat + tombol mulai per seksi | Member |
| `/latihan/{blok}/{rumah}/{seksi}` | Sesi soal (listening / structure / reading) | Member |
| `/hasil/{attemptId}` | Hasil sesi + pembahasan lengkap | Member (pemilik) |
| `/simulasi/{1\|2}` | Simulasi penuh 140 soal dengan timer resmi | Member |
| `/riwayat` | Semua percobaan + Buku Kesalahan | Member |
| `/rapor/{blok}.pdf` | Unduh ringkasan Blok (PDF) | Member, Blok selesai |
| `/rapor/akhir` | Perkiraan skor akhir + PDF | Member, semua Blok selesai |
| `/akun` | Profil, ganti password, bahasa | Member |
| `/admin/*` | Panel admin (bagian 13) | Admin |
| `/api/*` | Endpoint JSON (simpan jawaban, autosave, progres) | Sesuai halaman |

**Alur utama member baru**

1. Landing page → klik "Mulai Latihan".
2. Register (username + password kuat) → otomatis login.
3. Halaman Latihan menampilkan sapaan + kartu "Lanjutkan Rumah 1".
4. Rumah 1: baca materi 3 menit → kerjakan Listening → Structure → Reading (urutan bebas).
5. Setiap jawaban disimpan otomatis. Bila tab tertutup, sesi bisa dilanjutkan dari soal terakhir.
6. Selesai satu seksi → halaman Hasil dengan pembahasan setiap soal.
7. Setelah 30 soal di Rumah itu dijawab, Rumah ditandai **Selesai** dan rumahnya berubah warna di peta.
8. Semua Rumah di satu Blok selesai → tombol "Unduh Rapor Blok" aktif.
9. Keempat Blok selesai (dan disarankan minimal 1 Simulasi) → halaman Perkiraan Skor Akhir.

## 7. Landing page

Landing page terdiri dari 12 section berurutan. Tujuannya satu: membuat pengunjung menekan "Mulai Latihan". Teks di bawah ini adalah copy final versi Indonesia. Versi Inggris ada di file `lang/en.php` dan diterjemahkan setara.

| # | Section | Isi dan copy | Catatan desain |
| --- | --- | --- | --- |
| 1 | Navbar | Logo (ikon rumah + wordmark **Perumahan**Inggris, kata "Inggris" berwarna Jambu) · Kurikulum · Cara Kerja · Kamus · FAQ · toggle **ID / EN** · Masuk · tombol "Mulai Latihan →" | Sticky + blur. Di HP: logo, tombol, dan menu hamburger |
| 2 | Hero | Eyebrow: "PERSIAPAN TES BAHASA INGGRIS · 14 HARI". H1: "Dengar. Pahami. **Lulus.**" (EN: "Listen. Learn. Pass."). Sub: "Latihan Listening, Structure, dan Reading dengan pembahasan berbahasa Indonesia, lengkap dengan perumpamaan. Target 500, dari rumah." CTA: "Mulai Latihan Gratis" + link "Coba 10 soal dulu" | H1 raksasa di kiri, kata "Lulus." berwarna Jambu. Di kanan, ilustrasi SVG kompleks perumahan (4 rumah bertingkat = 4 Blok) dengan stabilo Daun. Di tepi kanan ada daftar vertikal kecil "LISTENING / STRUCTURE / READING" seperti referensi 1 |
| 3 | Pita marquee | "LISTENING • STRUCTURE • READING • PEMBAHASAN DETAIL • 14 RUMAH •" berjalan pelan | Latar Plum Gelap, teks putih. Berhenti saat `prefers-reduced-motion` |
| 4 | Statistik | 4 angka besar: **14** Rumah · **420+** soal latihan · **2** simulasi penuh · **2** bahasa | Angka Bricolage 56px, label eyebrow |
| 5 | Kenapa kami | Judul: "Lebih dari sekadar **bank soal**". 4 kolom ber-ikon dengan garis pemisah vertikal (seperti referensi 1): Pembahasan detail ("Tahu kenapa salah, bukan cuma jawabannya") · Istilah ada artinya ("verb (kata kerja), noun (kata benda), selalu dijelaskan") · Audio mirip tes ("Suara natural pria dan wanita, diputar sekali") · Progres tersimpan ("Lanjutkan kapan saja, dari HP mana saja") | Ikon outline Jambu |
| 6 | Kurikulum | Judul: "4 Blok, 14 Rumah, 1 tujuan". 4 kartu Blok yang sedikit miring (rotate ±1.5deg) dengan "paku pin" SVG di atasnya (terinspirasi kartu berpenjepit di referensi 4). Setiap kartu memuat nama Blok, rentang Rumah, 3 topik utama, dan chip jumlah soal | Warna kartu: A putih, B Jambu Muda, C putih, D Jambu |
| 7 | Contoh pembahasan | Judul: "Salah itu wajar. **Paham itu wajib.**" Satu soal demo interaktif (Error Recognition "has retain"). Pengunjung bisa memilih jawaban dan langsung melihat kartu pembahasan lengkap dengan perumpamaan | Demo berjalan di sisi klien, tanpa login |
| 8 | Format tes | Tabel 3 baris: Listening 50 soal / 45 menit · Structure 40 soal / 25 menit · Reading 50 soal / 55 menit · Rentang skor 310–677 | Setiap baris diberi warna seksi |
| 9 | Cara kerja | 3 langkah: Daftar 30 detik → Kerjakan 30 soal per Rumah → Unduh rapor & lihat perkiraan skor | Nomor besar 01/02/03 (memang berurutan) |
| 10 | FAQ | 6 pertanyaan dalam accordion: Apakah gratis? · Apakah ini tes resmi? (tidak, perkiraan) · Bisa dikerjakan dalam 1 hari? (bisa, semua Rumah terbuka) · Lupa password? (hubungi admin) · Bisa dari HP? · Audionya bisa diulang? (di mode Latihan bisa, di Simulasi tidak) | `<details>` yang bisa diakses keyboard |
| 11 | Didukung oleh | Lihat spesifikasi sponsor di bawah |  |
| 12 | CTA penutup + footer | Pita Jambu besar: "Rumah pertamamu sudah menunggu." + tombol putih "Mulai Latihan →". Footer: logo, link halaman, toggle bahasa, disclaimer (bagian 3), "© 2026 PerumahanInggris" |  |

**Spesifikasi section "Didukung oleh" (sponsor)**

- Judul eyebrow: "DIDUKUNG OLEH" / "SUPPORTED BY".
- **orbitani.com = sponsor utama (dipin)**: kartu lebar penuh di urutan pertama, latar Jambu Muda, chip "Sponsor Utama", wordmark besar, deskripsi 1 baris, tombol "Kunjungi orbitani.com ↗".
- **kodinginaja.biz.id**: kartu di bawahnya, lebih kecil, latar putih + border, wordmark, deskripsi 1 baris, link "Kunjungi ↗".
- Link memakai `target="_blank" rel="noopener sponsored"`.
- Data sponsor disimpan di tabel `sponsors` (nama, url, deskripsi\_id, deskripsi\_en, logo\_path, is\_pinned, sort\_order) agar bisa diubah dari admin. Jika `logo_path` kosong, tampilkan wordmark teks.
- Section yang sama juga tampil versi ringkas (satu baris) di footer halaman Latihan.
- *Perlu diisi pemilik:* deskripsi 1 baris untuk masing-masing sponsor dan file logo SVG/PNG (opsional).

**SEO dasar:** title "PerumahanInggris — Latihan Tes Bahasa Inggris 14 Hari", meta description, Open Graph image 1200×630 bergrafis rumah, `lang` sesuai bahasa aktif, `hreflang` id/en.

## 8. Register dan login

Register hanya butuh username dan password kuat, tanpa email. Karena tidak ada email, setiap pengguna menerima **kode pemulihan** sekali saat mendaftar. Admin juga bisa mereset password.

**Form register** (`/daftar`): Username · Password · Ulangi password · Pilihan bahasa (default mengikuti toggle) · Centang "Saya paham skor di sini hanya perkiraan".

| Aturan | Username | Password |
| --- | --- | --- |
| Panjang | 4–20 karakter | Minimal 10 karakter, maksimal 72 |
| Karakter | Huruf, angka, garis bawah (`_`). Tidak boleh diawali angka | Wajib: 1 huruf besar, 1 huruf kecil, 1 angka, 1 simbol (`!@#$%^&*` dll) |
| Larangan | Unik (tidak peka huruf besar/kecil). Kata cadangan ditolak: admin, mimin, root, perumahaninggris | Tidak boleh mengandung username. Tidak boleh ada di daftar 1.000 password paling umum (file `app/data/common-passwords.txt`) |

**Panduan password langsung di bawah input (live, sambil mengetik):**

- Checklist 5 baris yang berubah dari abu-abu ○ ke hijau ✓: "Minimal 10 karakter" · "Ada huruf besar (A–Z)" · "Ada huruf kecil (a–z)" · "Ada angka (0–9)" · "Ada simbol (!@#$…)".
- Meter kekuatan 4 segmen: Lemah / Cukup / Kuat / Sangat kuat.
- Contoh pola yang disarankan: "Gabungkan 3 kata + angka + simbol, misalnya `Rumah!Biru7Kopi`". Contoh ini tidak boleh dipakai persis (validasi menolaknya).
- Tombol mata untuk melihat atau menyembunyikan password.
- Tombol "Daftar" nonaktif sampai semua syarat terpenuhi. Validasi yang sama **wajib diulang di server**.
- Pesan error spesifik, misalnya "Username sudah dipakai. Coba rina\_belajar atau rina2026."

**Kode pemulihan:** setelah berhasil daftar, tampilkan layar "Simpan kode ini" berisi 12 karakter acak (format `XXXX-XXXX-XXXX`), tombol Salin, dan centang "Saya sudah menyimpannya". Kode disimpan dalam bentuk hash. Halaman `/lupa-password` meminta username + kode pemulihan + password baru, lalu menerbitkan kode pemulihan baru.

**Login** (`/masuk`): username + password + centang "Ingat saya (30 hari)". Setelah login, pengguna diarahkan ke halaman yang tadi dituju, atau ke `/latihan`.

**Aturan sesi dan keamanan login**

- Hash: `password_hash()` dengan `PASSWORD_ARGON2ID` (fallback `PASSWORD_BCRYPT` jika server tidak mendukung).
- `session_regenerate_id(true)` setelah login. Cookie: `HttpOnly`, `Secure`, `SameSite=Lax`.
- "Ingat saya" memakai token acak 32 byte di tabel `remember_tokens` (hash SHA-256), bukan password di cookie.
- Batas percobaan: 5 kali gagal per username atau per IP dalam 15 menit → kunci 15 menit, dengan pesan "Terlalu banyak percobaan. Coba lagi pukul 14.32."
- Pesan gagal login tidak membedakan "username salah" dan "password salah".
- Halaman `/akun`: ganti password (wajib memasukkan password lama), ganti bahasa, buat kode pemulihan baru, hapus akun (ketik username untuk konfirmasi).

## 9. Kurikulum: 4 Blok, 14 Rumah

Setiap Rumah = 1 hari belajar berisi **30 soal: 10 Listening + 10 Structure + 10 Reading** (2 teks × 5 soal), ditambah materi singkat 3–5 menit. Totalnya 420 soal latihan + 280 soal simulasi + 10 soal Rumah Contoh = **710 soal**. Semua Rumah dan Simulasi terbuka sejak awal, sehingga pengguna bebas melompat atau menyelesaikan semuanya dalam sehari. Peta Latihan tetap menyarankan urutan Rumah 1 → 14.

**Komposisi Structure per Rumah:** 4 Sentence Completion + 6 Error Recognition (rasio mirip tes asli 15:25).

| Rumah | Blok | Structure (fokus grammar) | Listening (fokus) | Reading (fokus) |
| --- | --- | --- | --- | --- |
| 1 | A · Fondasi | Kelas kata (noun, verb, adjective, adverb) + kalimat wajib punya subject + verb ("sopir & mesin") | Short dialogue: jawaban ada di kalimat orang kedua; bentuk saran (*Why don't you…*) | Main idea dari kalimat pertama paragraf |
| 2 | A · Fondasi | Subject–verb agreement (frasa *of*, *each/every*, *there is/are*) | Idiom set 1 (*tell me about it, went over my head*) | Detail: scanning kata kunci |
| 3 | A · Fondasi | V1/V2/V3, simple present/past/future | Ekspresi setuju/tidak setuju (*So do I, Neither do I, I couldn't agree more*) | Vocabulary in context |
| 4 | A · Fondasi | Perfect tense (have/has/had + V3), modal + V1 | Jebakan bunyi mirip (*sound-alike*) vs sinonim | Reference (*it, they, this*) |
| 5 | B · Menengah | Passive voice (be + V3) | Long dialogue: topik + detail | Main idea teks panjang |
| 6 | B · Menengah | Countable/uncountable, *many/much/few/little*, article | Idiom set 2 + inference | Inference (makna tersirat) |
| 7 | B · Menengah | Word form: akhiran -tion, -ment, -ly, -ous, -ive | Lecture: mencatat topik, angka, urutan | NOT / EXCEPT questions |
| 8 | B · Menengah | Parallel structure, comparative & superlative | Long dialogue: tujuan dan saran | Tujuan penulis dan nada (*purpose, tone*) |
| 9 | C · Lanjut | Conjunction vs preposition (*although/despite, because/because of*), adverb clause | Conditional dan *wish* (*If I had known…*) | Organisasi paragraf |
| 10 | C · Lanjut | Relative clause (*who/which/whose/that*), reduced clause | Lecture panjang: proses dan sebab-akibat | Parafrase kalimat |
| 11 | C · Lanjut | Noun clause, inversi (*Not only, Never, Rarely*), correlative (*neither…nor*) | Makna tersirat dari intonasi dan kejutan (*You did?*) | Teks akademik sains |
| 12 | D · Pemantapan | Campuran semua aturan, timer ketat 37 detik per soal | Campuran 3 part, kecepatan normal | Campuran, 1 menit per soal |
| 13 | D · Pemantapan | 10 kesalahan paling umum di tes | Set sulit (dialog cepat, aksen UK + US) | Teks panjang 450 kata |
| 14 | D · Pemantapan | **Rumah adaptif**: 30 soal diambil dari Buku Kesalahan pengguna (soal yang pernah salah + soal baru dengan tag aturan yang sama). Jika kurang dari 30, ditambah dari bank Blok D | sama | sama |
| Simulasi 1 | D | 40 soal / 25 menit | 50 soal / 45 menit | 5 teks × 10 soal / 55 menit |
| Simulasi 2 | D | Paket berbeda, tingkat kesulitan sama |  |  |

**Saran urutan di peta Latihan:** Rumah 1–11 → Simulasi 1 → Rumah 12–14 → Simulasi 2. Ditampilkan sebagai badge "Disarankan berikutnya", bukan kunci.

**Materi singkat setiap Rumah** (halaman pembuka sebelum soal):

- 1 kartu konsep per seksi: aturan dalam 2–3 kalimat, **perumpamaan sehari-hari**, rumus (misalnya `have/has + V3`), 3 contoh benar ✓ dan 2 contoh salah ✕.
- Setiap istilah grammar memakai komponen `<term>`: tampil sebagai "verb (kata kerja)" dan menampilkan tooltip definisi Kamus saat disentuh.
- Tombol "Mulai Listening / Structure / Reading", masing-masing dengan status: belum, sedang (x/10), selesai (skor).

**Rumah selesai** = minimal 30 soal di Rumah itu sudah dijawab (berapa pun skornya). **Blok selesai** = semua Rumah di Blok itu selesai. Skor terbaik dan skor terakhir sama-sama disimpan.

**Kamus Istilah** (`/kamus`) memuat ≥ 40 istilah dalam 5 grup: pelaku dan mesin kalimat, kelas kata, bagian kalimat, bentuk kata kerja dan waktu, istilah aturan. Setiap istilah berisi nama Inggris, arti Indonesia, contoh dengan bagian bergaris bawah, dan perumpamaan. Perumpamaan inti: **kalimat = mobil** (subject = sopir, verb = mesin, auxiliary = kernet, adjective = cat, adverb = gaya menyetir, preposition = rambu, conjunction = gandengan). Kamus juga menyediakan pencarian dan filter grup.

## 10. Mesin latihan

Satu mesin soal melayani 3 seksi, 7 tipe soal, dan 2 mode: **Latihan** (Rumah, pembahasan langsung muncul setelah menjawab) dan **Simulasi** (persis seperti tes, pembahasan baru muncul di akhir).

**Tipe soal**

| Kode `type` | Seksi | Tampilan |
| --- | --- | --- |
| `listening_short` | Listening Part A | Audio dialog 2 baris + pertanyaan dibacakan narator. Di layar hanya ada 4 pilihan, tanpa teks pertanyaan (sama seperti tes) |
| `listening_long` | Listening Part B | Audio percakapan 6–12 baris, 3–4 pertanyaan berurutan |
| `listening_lecture` | Listening Part C | Audio monolog 150–250 kata, 3–4 pertanyaan |
| `structure_completion` | Structure | Kalimat dengan rumpang `______` + 4 pilihan |
| `structure_error` | Written Expression | Kalimat dengan 4 bagian bergaris bawah berlabel A–D. Pengguna memilih bagian yang salah |
| `reading` | Reading | Teks 250–450 kata di kiri (desktop) atau di atas, bisa dilipat (HP), soal di kanan. Kata yang ditanyakan dicetak **tebal** di teks |
| `reading` subtype | Reading | `main_idea`, `detail`, `vocabulary`, `reference`, `inference`, `not_except`, `purpose`, `organization` |

**Mode Latihan vs Simulasi**

| Aspek | Latihan (Rumah) | Simulasi |
| --- | --- | --- |
| Audio | Diputar 1 kali otomatis. Setelah soal dijawab, muncul tombol "Putar ulang" + transkrip | 1 kali, tanpa pause, tanpa ulang, pindah otomatis seperti tes |
| Kecepatan audio | Pilihan 0.85× / 1.0× / 1.15× (`playbackRate`, `preservesPitch`) | Terkunci 1.0× |
| Timer | Opsional (default menyala), memakai tempo tes: Structure 37 dtk/soal, Reading 66 dtk/soal. Hanya pengingat, tidak menghentikan sesi | Ketat: 45 / 25 / 55 menit, auto-submit saat habis |
| Pembahasan | Langsung setelah tiap jawaban | Setelah seluruh seksi selesai |
| Ubah jawaban | Tidak bisa setelah pembahasan muncul | Bisa, selama waktu seksi belum habis (kecuali Listening) |

**Tampilan sesi soal**

- Header: nama Rumah + chip seksi berwarna, progres "7 / 10", timer (JetBrains Mono), tombol "Keluar & simpan".
- Pilihan jawaban berupa baris besar (min. tinggi 56px) berlabel A–D. Pintasan keyboard: tombol `A`–`D` atau `1`–`4` untuk memilih, `Enter` untuk lanjut.
- Tombol "Tandai ragu" + grid navigasi nomor soal (hijau = benar, bata = salah, garis = belum, titik = ragu).
- Konfirmasi selesai memakai dialog di dalam halaman, bukan `confirm()`.
- **Autosave**: setiap jawaban langsung dikirim ke `POST /api/answer`. Saat dibuka kembali, sesi berlanjut dari soal pertama yang belum dijawab, dan sisa timer Simulasi tersimpan di server.

**Format pembahasan (wajib untuk setiap soal, dalam 2 bahasa)**

Setiap soal menampilkan kartu pembahasan dengan urutan berikut. Untuk pengguna yang **benar**, kartu tetap lengkap tetapi dimulai dengan "Benar, ini alasannya". Untuk pengguna yang **salah**, kartu dimulai dengan penjelasan mengapa pilihan *mereka* keliru.

1. **Verdict**: "Benar" (hijau, ✓) atau "Belum tepat, jawabannya C" (bata, ✕).
2. **Kenapa jawaban ini benar**: 2–4 kalimat, langkah demi langkah ("Langkah 1: cari sopir… Langkah 2: cari mesin…").
3. **Kenapa pilihan lain salah**: satu baris untuk **setiap** pilihan salah (A, B, D), dengan pilihan yang dipilih pengguna disorot.
4. **Perumpamaan**: 1–2 kalimat analogi sehari-hari (mobil, rumah, sepatu, kelereng vs air, dll).
5. **Aturan**: chip tag aturan (misalnya `S-AGREE-OF`) yang menautkan ke kartu Kamus/materi.
6. **Trik cepat saat tes**: 1 kalimat.
7. Khusus Listening: transkrip dengan **kalimat kunci disorot** + terjemahan Indonesia kalimat kunci itu + catatan bunyi sambung bila relevan (misalnya "*want to* terdengar *wanna*").
8. Khusus Reading: kutipan kalimat bukti dari teks, disorot di dalam teks saat kartu dibuka.

Istilah grammar di dalam pembahasan **selalu** ditulis "istilah (arti)" pada kemunculan pertama di setiap kartu, contoh: "auxiliary (kata kerja bantu)".

**Buku Kesalahan** (`/riwayat#kesalahan`): semua soal yang pernah salah, dikelompokkan per tag aturan dan diurutkan dari yang paling sering salah. Ada tombol "Latih ulang 10 soal dari aturan ini" (soal yang sama, diacak, dan ditambah soal lain bertag sama). Soal keluar dari Buku Kesalahan setelah dijawab benar 2 kali berturut-turut.

## 11. Audio Listening

Semua audio adalah MP3 yang dibuat **sekali** di komputer developer dengan skrip `tools/make_audio.py` (Python + `edge-tts` + `ffmpeg`), lalu di-upload ke `public_html/audio/`. Hosting tidak menjalankan TTS sama sekali.

**Agar terdengar seperti tes asli**

- **Narator tetap**: satu suara pria US yang tenang untuk semua directions, nomor soal, dan pertanyaan: `en-US-ChristopherNeural`.
- **Pembicara bergiliran** agar telinga terbiasa dengan banyak suara. Pria: `en-US-AndrewNeural`, `en-US-EricNeural`, `en-US-GuyNeural`, `en-GB-RyanNeural`. Wanita: `en-US-JennyNeural`, `en-US-AriaNeural`, `en-US-MichelleNeural`, `en-GB-SoniaNeural`. Dosen (lecture): `en-US-BrianNeural` atau `en-US-EmmaNeural`. Suara UK maksimal 20% (lebih banyak di Rumah 13).
- Kecepatan bicara alami (`rate=+0%`, sekitar 150 kata/menit). Jangan diperlambat di file. Perlambatan dilakukan pemutar lewat `playbackRate`.
- Jeda: 450 ms antar-giliran bicara, 900 ms sebelum pertanyaan narator, 300 ms di awal file.
- Naskah ditulis seperti percakapan nyata: pakai kontraksi (*I'm, didn't, it's*), interjeksi (*Oh, Well, Hmm*), dan idiom. Ejaan slang seperti "wanna" tidak dipakai; tulis "want to" dan biarkan TTS menyambungnya secara alami.
- Directions setiap Part dibacakan narator dengan teks **orisinal** buatan sendiri (jangan menyalin directions resmi tes mana pun).
- Normalisasi loudness ke -16 LUFS (`ffmpeg loudnorm`). Format MP3 mono 24 kHz 64 kbps (sekitar 0,5 MB per menit).

**Struktur file**

| File | Isi | Contoh nama |
| --- | --- | --- |
| Dialog / lecture | Semua giliran bicara satu grup soal | `audio/r01/L-r01-g03-talk.mp3` |
| Pertanyaan | Narator membaca 1 pertanyaan | `audio/r01/L-r01-g03-q1.mp3` |
| Directions | Pengantar Part A / B / C | `audio/common/dir-part-a.mp3` |
| Nomor soal (Simulasi) | "Number one." … "Number fifty." | `audio/common/num-01.mp3` |

Pemutar memutar file berurutan: (directions di awal Part) → talk → jeda 900 ms → q1 → waktu menjawab → q2 → … Di Simulasi, waktu menjawab tiap pertanyaan adalah **12 detik** dengan hitung mundur di layar, lalu otomatis lanjut.

**Skrip `tools/make_audio.py`**

1. Membaca semua grup listening dari `app/database/seed/*.json` (field `script`: daftar `{speaker: "M1"|"W2"|"N"|"LEC", text}`).
2. Memetakan kode speaker ke voice (konfigurasi `voices.json`), lalu membuat satu potongan MP3 per giliran dengan edge-tts.
3. Menggabungkan potongan + jeda dengan ffmpeg, melakukan normalisasi, dan menyimpan file final.
4. Cache berdasarkan hash (teks + voice), sehingga hanya naskah yang berubah yang dibuat ulang.
5. Menulis `audio-manifest.json` (path, durasi ms, hash) yang diimpor `seed.php` ke tabel `audio_files`.
6. Opsi CLI: `--only r05`, `--force`, `--dry-run`.

**Cadangan:** Edge TTS adalah layanan tidak resmi dan bisa berubah sewaktu-waktu. Jika berhenti bekerja, skrip harus bisa beralih ke Azure Speech (voice sama, kuota gratis 500 ribu karakter/bulan) cukup dengan mengganti satu pengaturan `TTS_PROVIDER`. Admin juga bisa mengganti file MP3 mana pun secara manual dari panel admin.

**Fallback di browser:** jika file MP3 tidak ditemukan (404), pemutar menampilkan pesan "Audio belum tersedia" dan tombol "Baca transkrip". Soal tetap bisa dijawab dan ditandai `audio_missing` di laporan admin.

## 12. Penilaian, perkiraan skor, dan rapor

Skor akhir dihitung seperti tes berformat PBT: setiap seksi dikonversi ke skala 31–68 (Reading 31–67), lalu **total = (L + S + R) × 10 / 3**, sehingga rentangnya 310–677. Skor 500 kira-kira setara dengan 60% jawaban benar.

**Tabel konversi (perkiraan, disimpan di `ScoreEstimator.php`, nilai di antara titik diinterpolasi linear lalu dibulatkan)**

| Benar (raw) | Listening (dari 50) | Structure (dari 40) | Reading (dari 50) |
| --- | --- | --- | --- |
| 0 | 31 | 31 | 31 |
| 5 | 32 | 31 | 31 |
| 10 | 37 | 36 | 34 |
| 15 | 41 | 41 | 39 |
| 20 | 44 | 46 | 43 |
| 25 | 47 | 50 | 46 |
| 30 | 50 | 55 | 49 |
| 35 | 53 | 60 | 52 |
| 40 | 57 | 68 | 56 |
| 45 | 61 | – | 60 |
| 50 | 68 | – | 67 |

Tabel ini adalah kalibrasi kasar, bukan tabel resmi. Admin dapat mengubahnya di Pengaturan (disimpan sebagai JSON di tabel `settings`).

**Perkiraan skor dari latihan (bukan simulasi)**

1. Untuk setiap seksi, hitung persentase benar tertimbang: Simulasi 60%, Rumah Blok D 25%, Rumah Blok A–C 15%. Jika belum ada simulasi, bobotnya dibagi ulang secara proporsional ke yang tersedia.
2. Ubah persentase ke raw setara tes: % × 50 (L), % × 40 (S), % × 50 (R), lalu konversi dengan tabel di atas.
3. Tampilkan total sebagai angka utama + rentang ± 20 ("Perkiraan: 503, kisaran 483–523"). Jika jumlah jawaban < 150, beri label "Data masih sedikit".

**Halaman Perkiraan Skor Akhir** (`/rapor/akhir`, aktif setelah 4 Blok selesai)

- Angka besar total + rentang, dibandingkan dengan garis target **500** (target bisa diubah di `/akun`, default 500).
- Status: < 450 "Perlu latihan lagi" · 450–499 "Hampir sampai" · 500–549 "Siap tes" · ≥ 550 "Sangat siap".
- 3 kartu seksi berisi skor skala dan % benar, berwarna seksi.
- "3 aturan yang paling menaikkan skormu": tag aturan dengan kesalahan terbanyak + tombol latih ulang.
- Tombol "Unduh Rapor Akhir (PDF)".

**Rapor Blok (PDF, `/rapor/{blok}.pdf`, aktif setelah Blok selesai)**

- Dibuat dengan Dompdf, ukuran A4 potret, 1–2 halaman, font Plus Jakarta Sans (di-embed), warna sesuai sistem desain.
- Isi: header (logo, "Rapor Blok A · Fondasi", username, tanggal) → tabel per Rumah (skor L / S / R, total, waktu pengerjaan) → batang % benar per seksi (dibuat dengan div HTML, bukan chart JS) → 5 aturan terlemah + 1 kalimat saran untuk masing-masing → perkiraan skor sementara → baris sponsor kecil (orbitani.com · kodinginaja.biz.id) → disclaimer.
- Bahasa PDF mengikuti bahasa aktif pengguna.
- Nama file: `PerumahanInggris-Rapor-BlokA-{username}-{YYYY-MM-DD}.pdf`.

**Halaman Hasil sesi** (`/hasil/{id}`): skor x/10, waktu, grafik kecil benar/salah per nomor, daftar soal yang bisa dibuka untuk melihat pembahasan, tombol "Ulangi soal yang salah" dan "Lanjut ke seksi berikutnya".

## 13. Halaman admin

Panel admin di `/admin` dipakai untuk mengelola pengguna, melihat progres lengkap setiap pengguna, mengelola soal dan audio, serta mengatur sponsor dan konversi skor. Tampilannya mengikuti sistem desain yang sama, dengan sidebar kiri dan latar putih.

**Akun admin**

- Username admin: **MiminEPrT2026** (role `admin`).
- Password awal **tidak boleh ditulis di kode, repo, atau dokumen ini**. Pemilik mengisinya sendiri di `app/config.php` (`ADMIN_INITIAL_PASSWORD`), lalu menjalankan `php app/database/create_admin.php` sekali. Skrip ini membuat akun dengan password ter-hash, lalu menampilkan pesan "Hapus ADMIN\_INITIAL\_PASSWORD dari config.php sekarang".
- Jika hosting tidak punya akses SSH: sediakan `/install` sekali pakai yang menjalankan langkah yang sama, lalu mengunci dirinya sendiri (membuat file `app/installed.lock`).
- Login admin memakai halaman `/masuk` yang sama. Rute `/admin/*` mengecek `role = admin`. Sesi admin kedaluwarsa setelah 2 jam tanpa aktivitas.
- Halaman `/admin` tidak ditautkan dari menu publik, dan meta `noindex`.

**Menu dan fitur**

| Menu | Fitur |
| --- | --- |
| Dashboard | Kartu: total pengguna, pendaftar 7 hari terakhir, pengguna aktif 7 hari, sesi hari ini. Corong penyelesaian Rumah 1→14 (jumlah pengguna yang menyelesaikan tiap Rumah). Rata-rata % benar per seksi. 10 soal tersulit (% benar terendah). Jumlah audio hilang |
| Pengguna | Tabel semua pendaftar: username, tanggal daftar, terakhir aktif, Rumah selesai (x/14), Blok selesai, Simulasi (skor), perkiraan skor, bahasa, status. Pencarian, sortir, filter (aktif/nonaktif, Blok), export CSV |
| Detail pengguna | Peta progres persis seperti yang dilihat pengguna, semua percobaan beserta skor dan durasi, jawaban per soal (pilihan + benar/salah + waktu), Buku Kesalahan per tag, riwayat login (tanggal, IP disamarkan). Aksi: **reset password** (membuat password sementara yang tampil sekali; pengguna wajib menggantinya saat login berikutnya), buat kode pemulihan baru, nonaktifkan/aktifkan, ganti `plan`, hapus (ketik username untuk konfirmasi) |
| Soal | Daftar dengan filter Blok / Rumah / seksi / tipe / tag / status (draft, terbit). Form tambah/edit dengan semua field dua bahasa (bagian 16), pratinjau seperti tampilan pengguna, duplikat, nonaktifkan. Statistik per soal: dijawab berapa kali, % benar, pilihan yang paling sering dipilih |
| Grup Listening & Teks Reading | CRUD naskah dialog/lecture (per giliran bicara + speaker) dan teks reading. Upload/ganti MP3 per file (validasi: hanya `audio/mpeg`, maks 5 MB), putar langsung, status audio |
| Impor / Ekspor | Impor JSON (format bagian 16) dengan pratinjau validasi sebelum disimpan, ekspor per Rumah |
| Kamus | CRUD istilah grammar (dua bahasa) |
| Sponsor | CRUD sponsor: nama, URL, deskripsi ID/EN, logo, pin, urutan |
| Pengaturan | Tabel konversi skor (JSON), target default, buka/tutup pendaftaran, pesan pengumuman di halaman Latihan |
| Log admin | Siapa melakukan apa dan kapan (reset password, hapus, edit soal) |

## 14. Dua bahasa (ID / EN)

Bahasa Indonesia adalah default. Toggle **ID / EN** di navbar mengganti seluruh antarmuka, materi, Kamus, pembahasan, dan PDF. Soal ujiannya sendiri (dialog, kalimat, teks reading, pilihan jawaban) selalu dalam bahasa Inggris.

- Pilihan bahasa disimpan di `users.lang` (member) atau cookie `lang` (tamu). URL tetap sama; atribut `<html lang>` ikut berubah.
- Teks antarmuka ada di `app/lang/id.php` dan `app/lang/en.php` (array key → teks), dipanggil lewat `t('practice.start')`. Key yang hilang menampilkan versi Indonesia dan dicatat di log.
- Konten di database memakai kolom berpasangan `_id` / `_en`: `explanation_correct_id`, `explanation_correct_en`, `analogy_id`, `analogy_en`, dll. Jika `_en` kosong, tampilkan `_id` + chip kecil "ID".
- **Di mode EN, istilah grammar ditulis biasa** ("auxiliary verb"). **Di mode ID**, istilah ditulis dengan arti dalam kurung pada kemunculan pertama ("auxiliary (kata kerja bantu)").
- Perumpamaan versi Inggris menyesuaikan budaya (mobil, rumah, sepatu tetap bisa dipakai), bukan terjemahan kata per kata.
- Format angka dan tanggal mengikuti bahasa: ID "5 Nov 2026, 14.30", EN "Nov 5, 2026, 2:30 PM". Zona waktu default Asia/Jakarta.

## 15. Skema database

Skema terdiri dari 17 tabel InnoDB `utf8mb4_unicode_ci` dengan foreign key. Ini rancangan minimum. Claude Code boleh menambah indeks atau kolom teknis, tetapi tidak boleh mengubah nama konsep.

```sql
users            (id PK, username UNIQUE, password_hash, role ENUM('member','admin') DEFAULT 'member',
                  lang ENUM('id','en') DEFAULT 'id', plan VARCHAR(20) DEFAULT 'free', target_score SMALLINT DEFAULT 500,
                  recovery_hash, must_change_password BOOL DEFAULT 0, status ENUM('active','disabled') DEFAULT 'active',
                  created_at, last_active_at)
remember_tokens  (id PK, user_id FK, token_hash CHAR(64), expires_at)
login_attempts   (id PK, username, ip_hash CHAR(64), success BOOL, created_at)            -- rate limit + riwayat login

bloks            (id PK, code CHAR(1) UNIQUE, name_id, name_en, sort)                      -- A, B, C, D
rumahs           (id PK, blok_id FK NULL, number TINYINT, slug UNIQUE,                     -- r01..r14, sim1, sim2, contoh
                  kind ENUM('practice','adaptive','simulation','sample'),
                  title_id, title_en, material_id MEDIUMTEXT, material_en MEDIUMTEXT, sort)
passages         (id PK, rumah_id FK, section ENUM('listening','reading'), part ENUM('A','B','C') NULL,
                  title, body MEDIUMTEXT NULL,                                             -- teks reading
                  script JSON NULL,                                                        -- [{speaker:'W1', text:'...'}]
                  audio_talk_id FK NULL, sort)
questions        (id PK, rumah_id FK, passage_id FK NULL, section ENUM('listening','structure','reading'),
                  type VARCHAR(30), subtype VARCHAR(30) NULL, number SMALLINT,
                  prompt TEXT,                                                             -- listening: teks pertanyaan narator
                  options JSON,                                                            -- ["..","..","..",".."]
                  underlines JSON NULL,                                                    -- structure_error: posisi A-D
                  correct_index TINYINT,                                                   -- 0..3
                  why_correct_id TEXT, why_correct_en TEXT,
                  why_wrong_id JSON, why_wrong_en JSON,                                    -- {"0":"..","2":"..","3":".."}
                  analogy_id TEXT, analogy_en TEXT, tip_id TEXT, tip_en TEXT,
                  key_sentence TEXT NULL, key_translation_id TEXT NULL, evidence_quote TEXT NULL,
                  rule_tags JSON, difficulty TINYINT,                                      -- 1 mudah .. 3 sulit
                  audio_question_id FK NULL, status ENUM('draft','published') DEFAULT 'published',
                  created_at, updated_at)
rule_tags        (code PK, section, name_id, name_en, glossary_slug NULL)                  -- S-AGREE-OF, L-IDIOM, R-REF ...
audio_files      (id PK, path UNIQUE, duration_ms, hash, uploaded_by NULL, created_at)

attempts         (id PK, user_id FK, rumah_id FK, section, mode ENUM('practice','simulation','review'),
                  started_at, finished_at NULL, time_spent_s, correct, total, remaining_s NULL)
answers          (id PK, attempt_id FK, question_id FK, user_id FK, chosen_index TINYINT NULL,
                  is_correct BOOL, time_ms INT, flagged BOOL, created_at, UNIQUE(attempt_id, question_id))
progress         (user_id FK, rumah_id FK, answered_distinct SMALLINT, best_pct, last_pct,
                  completed_at NULL, PRIMARY KEY(user_id, rumah_id))
mistakes         (user_id FK, question_id FK, wrong_count, correct_streak, last_seen_at,
                  PRIMARY KEY(user_id, question_id))                                       -- Buku Kesalahan

glossary         (id PK, slug UNIQUE, grp, term_en, meaning_id, def_id, def_en, example, analogy_id, analogy_en, sort)
sponsors         (id PK, name, url, desc_id, desc_en, logo_path NULL, is_pinned BOOL, sort_order)
settings         (`key` PK, value JSON)                                                   -- score_table, registration_open, announcement
admin_log        (id PK, admin_id FK, action, target, meta JSON, created_at)
```

**Aturan data**

- `progress.answered_distinct` menghitung soal unik yang pernah dijawab di Rumah itu, lintas seksi. Jika ≥ 30, isi `completed_at`.
- Jawaban yang sama untuk soal yang sama di percobaan berikutnya tetap dicatat (untuk statistik), tetapi `answered_distinct` tidak bertambah.
- `mistakes`: salah → `wrong_count + 1`, `correct_streak = 0`; benar → `correct_streak + 1`. Tampil di Buku Kesalahan jika `correct_streak < 2`.
- Simpan `ip_hash = sha256(ip + APP_SECRET)`, jangan IP mentah.

## 16. Format data soal dan contoh

Semua soal ditulis sebagai file JSON di `app/database/seed/` (satu file per Rumah, misalnya `r01.json`), lalu diimpor dengan `php app/database/seed.php`. Format yang sama dipakai oleh fitur Impor di admin.

**Contoh 1: Error Recognition**

```json
{
  "rumah": "r04", "section": "structure", "type": "structure_error", "number": 7, "difficulty": 1,
  "prompt": "Although {A}numerous{/A} renovations were made, the old library {B}has retain{/B} its {C}classically{/C} simple {D}style{/D}.",
  "options": ["numerous", "has retain", "classically", "style"],
  "correct_index": 1,
  "rule_tags": ["S-PERFECT-V3"],
  "why_correct_id": "Setelah auxiliary (kata kerja bantu) have/has, main verb (kata kerja utama) wajib berbentuk V3 (bentuk ketiga). Jadi 'has retain' harus menjadi 'has retained'.",
  "why_correct_en": "After the auxiliary have/has, the main verb must be in its past participle (V3) form, so 'has retain' should be 'has retained'.",
  "why_wrong_id": {
    "0": "'numerous' (banyak) adalah adjective (kata sifat) yang menerangkan noun (kata benda) 'renovations'. Sudah benar.",
    "2": "'classically' adalah adverb (kata keterangan) yang menerangkan adjective (kata sifat) 'simple'. Adverb boleh menerangkan adjective, jadi benar.",
    "3": "'style' adalah noun (kata benda) setelah adjective 'simple'. Polanya adjective + noun, sudah benar."
  },
  "why_wrong_en": { "0": "...", "2": "...", "3": "..." },
  "analogy_id": "have/has itu seperti kernet yang hanya mau naik bus jurusan V3. Kalau sopirnya V1 (retain), kernetnya menolak ikut.",
  "analogy_en": "Think of have/has as a passenger who only rides the 'V3 bus'...",
  "tip_id": "Lihat have/has/had? Langsung cek kata kerja sesudahnya, apakah sudah V3.",
  "tip_en": "Spot have/has/had? Check that the next verb is V3."
}
```

**Contoh 2: Listening Part A** (satu grup = satu `passage` + pertanyaan)

```json
{
  "rumah": "r02", "section": "listening", "part": "A",
  "script": [
    {"speaker": "W1", "text": "Did you understand the professor's explanation of the formula?"},
    {"speaker": "M2", "text": "Not really. It went right over my head."}
  ],
  "questions": [{
    "type": "listening_short", "number": 3,
    "prompt": "What does the man mean?",
    "options": ["He did not understand the explanation.", "The professor spoke too loudly.", "He forgot his notes.", "The formula was easy for him."],
    "correct_index": 0, "rule_tags": ["L-IDIOM"],
    "key_sentence": "It went right over my head.",
    "key_translation_id": "Itu sama sekali tidak masuk ke kepala saya (saya tidak paham).",
    "why_correct_id": "...", "why_wrong_id": {"1": "...", "2": "...", "3": "..."},
    "analogy_id": "Seperti bola yang dilempar terlalu tinggi, lewat di atas kepala dan tidak tertangkap.",
    "tip_id": "Jawaban Part A biasanya ada di kalimat orang kedua."
  }]
}
```

Reading memakai pola yang sama: `passage.body` berisi teks dengan penanda `**kata**` untuk kata yang ditanyakan, dan setiap soal punya `evidence_quote` (kalimat bukti dari teks).

**Aturan menulis soal (untuk Claude Code saat membuat 710 soal)**

- 100% orisinal, dengan topik kampus, sains populer, sejarah, lingkungan, teknologi, dan kehidupan mahasiswa. Hindari topik sensitif (politik, agama, SARA).
- Pengecoh harus masuk akal: salah karena tata bahasa atau makna, bukan karena konyol. Listening Part A wajib punya 1 pengecoh "kata yang sama dengan audio".
- Posisi jawaban benar tersebar merata: setiap huruf A–D muncul 20–30% per Rumah.
- Sebaran kesulitan per Rumah: Blok A 60/30/10 (mudah/sedang/sulit), B 40/40/20, C 25/45/30, D dan Simulasi 20/50/30.
- Field yang wajib terisi untuk setiap soal: `why_correct`, `why_wrong` untuk ketiga pilihan salah, `analogy`, dan `tip`, masing-masing dalam ID dan EN. Seeder menolak soal yang field-nya kosong.
- Teks reading 250–450 kata. Lecture 150–250 kata. Long dialogue 6–12 giliran.
- Simulasi 1 dan 2 tidak boleh memakai soal yang sama dengan Rumah.
- Tag aturan memakai daftar tetap di `rule_tags` (≈ 40 kode). Setiap soal punya 1–2 tag.
- Pembuatan konten dilakukan bertahap per Rumah (lihat bagian 18) dan setiap file divalidasi dengan `php app/database/seed.php --validate`.

## 17. Keamanan

Semua data pengguna dan kunci jawaban dilindungi di server. Browser tidak pernah menerima `correct_index` sebelum pengguna menjawab.

- **Kunci jawaban**: API mengirim soal tanpa jawaban dan pembahasan. Setelah `POST /api/answer`, server membalas hasil + pembahasan. Di mode Simulasi, pembahasan baru dikirim setelah seksi selesai.
- **SQL**: hanya PDO prepared statements. `PDO::ATTR_EMULATE_PREPARES = false`.
- **XSS**: semua output melalui `e()` (`htmlspecialchars` ENT\_QUOTES, UTF-8). Konten dari admin yang memakai markdown dirender dengan parser yang membuang HTML mentah.
- **CSRF**: token per sesi di setiap form dan header `X-CSRF-Token` untuk fetch.
- **Header**: `Content-Security-Policy` (self + fonts.googleapis.com + fonts.gstatic.com), `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: DENY`, HSTS setelah SSL aktif (Hostinger menyediakan SSL gratis).
- **Upload**: hanya MP3 (cek MIME + ekstensi + ukuran ≤ 5 MB), nama file dibuat ulang oleh server. Logo sponsor: hanya PNG/SVG ≤ 500 KB, SVG disanitasi.
- **Rahasia**: `app/config.php` di luar `public_html`, masuk `.gitignore`. `.htaccess` menolak akses ke file `.json`, `.sql`, `.lock`, `.env`.
- **Rate limit**: login (bagian 8), register 5 per IP per jam, API jawaban 120 per menit per pengguna.
- **Admin**: password awal dari config, dihapus setelah instalasi. Sesi 2 jam. Semua aksi tercatat di `admin_log`.
- **Error**: `display_errors = Off` di produksi. Error dicatat ke `app/storage/logs/` dan ditampilkan sebagai halaman 404/500 yang bergaya sama dengan website.
- **Backup**: aktifkan backup harian Hostinger. Sediakan juga tombol "Ekspor semua data (SQL)" di admin.

## 18. Tahapan build dan kriteria penerimaan

Build dilakukan dalam 7 tahap. Setiap tahap harus bisa dicoba di localhost (`php -S localhost:8000 -t public_html`) sebelum lanjut, dan konten soal dibuat bertahap supaya kualitasnya bisa dicek.

1. **Fondasi**: struktur folder, router, config, `schema.sql`, layout + `app.css` (semua token bagian 5), komponen tombol/kartu/chip, halaman 404/500, i18n `t()`.
2. **Landing page**: 12 section lengkap (ID + EN), demo soal interaktif, sponsor dari DB, responsif 390px–1440px.
3. **Akun**: register dengan panduan password live, kode pemulihan, login, ingat saya, rate limit, `/akun`, `/lupa-password`.
4. **Mesin latihan**: peta Latihan, halaman Rumah, sesi soal 7 tipe, autosave, pembahasan, hasil, progres, Buku Kesalahan. Uji dengan konten **Rumah 1 + Rumah Contoh** saja dulu.
5. **Audio**: `tools/make_audio.py`, pemutar berurutan, kecepatan, mode Simulasi (12 detik, tanpa ulang).
6. **Konten**: Rumah 2–14 lalu Simulasi 1–2, ditulis 1 Rumah per langkah + `seed.php --validate` + audio. Kamus ≥ 40 istilah.
7. **Admin, rapor, dan rilis**: panel admin lengkap, PDF Blok + Rapor Akhir, perkiraan skor, header keamanan, panduan deploy ke Hostinger (`DEPLOY.md`).

**Kriteria penerimaan**

- [ ] Landing page tampil benar di HP 390px dan desktop 1440px, tanpa scroll horizontal, Lighthouse Performance dan Accessibility ≥ 90.
- [ ] Toggle ID/EN mengganti semua teks antarmuka, materi, Kamus, pembahasan, dan PDF.
- [ ] Register menolak password lemah di klien **dan** server. Checklist berubah hijau sambil mengetik.
- [ ] Login gagal 5× mengunci selama 15 menit.
- [ ] Pengguna bisa membuka Rumah mana pun tanpa menyelesaikan Rumah sebelumnya.
- [ ] Rumah berstatus Selesai setelah 30 soal unik dijawab, dan peta berubah warna.
- [ ] Menutup tab di tengah sesi lalu membuka lagi melanjutkan dari soal yang sama (termasuk sisa timer Simulasi).
- [ ] Setiap soal menampilkan pembahasan lengkap: alasan benar, alasan untuk **setiap** pilihan salah, perumpamaan, dan trik, dalam bahasa aktif.
- [ ] Istilah grammar di mode ID selalu disertai arti dalam kurung pada kemunculan pertama.
- [ ] Audio Simulasi tidak bisa di-pause atau diulang dan berlanjut otomatis.
- [ ] `correct_index` tidak pernah muncul di response API sebelum soal dijawab (cek di DevTools).
- [ ] Rapor Blok PDF terunduh dengan nama file yang benar setelah Blok selesai.
- [ ] Perkiraan skor akhir muncul setelah 4 Blok selesai, dengan rentang, status, dan 3 aturan terlemah.
- [ ] Admin bisa melihat semua pendaftar, progres per Rumah, jawaban per soal, dan bisa mereset password.
- [ ] Admin bisa menambah, mengedit, dan menerbitkan soal + upload MP3 tanpa menyentuh kode.
- [ ] orbitani.com tampil sebagai sponsor utama (dipin) di atas kodinginaja.biz.id.
- [ ] Tidak ada kata "EPrT", "TOEFL", atau nama lembaga di halaman publik dan meta tag.

## 19. Instruksi untuk Claude Code

Simpan dokumen ini sebagai `PRD.md` di root folder proyek, lalu tempel prompt berikut di Claude Code untuk memulai.

```markdown
Baca PRD.md sampai habis. Bangun website PerumahanInggris sesuai PRD itu.

Aturan kerja:
- Stack wajib: PHP 8.2 native + MySQL (PDO) + HTML/CSS/JS vanilla, tanpa framework dan tanpa build step.
  Harus berjalan di shared hosting Hostinger.
- Kerjakan per tahap sesuai bagian 18. Di akhir setiap tahap: jalankan di localhost, cek kriteria
  penerimaan yang relevan, lalu berhenti dan laporkan ke saya sebelum lanjut.
- Ikuti sistem desain bagian 5 persis (token warna, font, radius, komponen). Tampilan harus terang,
  elegan, dan tidak terlihat seperti template.
- Jangan pernah menulis password admin di kode atau repo. Ikuti alur create_admin.php di bagian 13.
- Semua soal harus orisinal dan setiap soal wajib punya pembahasan lengkap dua bahasa (bagian 10 & 16).
  Buat konten satu Rumah per langkah, validasi dengan seed.php --validate.
- Jangan gunakan kata EPrT/TOEFL/nama lembaga di halaman publik.
- Buat DEPLOY.md: langkah upload ke Hostinger (buat database di hPanel, isi config.php,
  import schema.sql, jalankan seed, create_admin, set document root, aktifkan SSL).

Mulai dari Tahap 1.
```

**Yang perlu disiapkan pemilik sebelum Tahap 7**

- Nama domain yang dipakai (untuk `BASE_URL`, Open Graph, dan sitemap).
- Deskripsi 1 baris (ID + EN) untuk orbitani.com dan kodinginaja.biz.id, plus logo SVG/PNG bila ada.
- Akses hPanel Hostinger: nama database, user, dan password database (diisi sendiri ke `config.php`).
- Password admin awal (diisi sendiri ke `config.php`, lalu dihapus setelah instalasi).
- Komputer dengan Python 3.10+ dan ffmpeg untuk menjalankan `tools/make_audio.py`.
