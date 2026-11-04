<?php
declare(strict_types=1);
/**
 * Isi halaman legal (ID + EN). Setiap halaman: title, eyebrow, h1, lead, sections[].
 * Section: h (judul), p (paragraf[]), list (butir[], opsional).
 * {email}, {wa}, {hours} diganti dari config di view.
 * Aturan teks: tanpa tanda kutip penanda dan tanpa garis panjang.
 */
return [
    'id' => [
        'updated' => 'Terakhir diperbarui: 4 November 2026',
        'terms' => [
            'title' => 'Syarat & Ketentuan',
            'eyebrow' => 'LEGAL',
            'h1' => 'Syarat & Ketentuan',
            'lead' => 'Dengan membuat akun atau memakai PerumahanInggris, kamu setuju dengan ketentuan di bawah ini. Bacanya cuma butuh beberapa menit.',
            'sections' => [
                ['h' => '1. Tentang layanan', 'p' => [
                    'PerumahanInggris adalah platform latihan tes kemampuan bahasa Inggris format PBT (Listening, Structure, dan Reading). Kami bukan penyelenggara tes dan tidak berafiliasi dengan lembaga penyelenggara tes mana pun.',
                ]],
                ['h' => '2. Akses gratis dan berbayar', 'p' => [
                    'Level A (Hari 1 sampai 4) dan Latihan Contoh bisa dipakai gratis.',
                    'Level B sampai D beserta Simulasi penuh termasuk paket Premium. Paket Premium dibayar sekali, tanpa langganan otomatis, dan aktif selama 1 tahun sejak pembayaran berhasil. Harga yang berlaku tercantum di halaman Harga.',
                    'Selama pembayaran belum dibuka, fitur yang nantinya berbayar bisa saja masih terbuka untuk semua pengguna. Kami akan memberi tahu lewat website sebelum pembatasan berlaku.',
                ]],
                ['h' => '3. Akun', 'list' => [
                    'Satu akun untuk satu orang. Jangan bagikan username dan password kepada orang lain.',
                    'Simpan kode pemulihan dengan aman, karena kode itulah cara mengatur ulang password.',
                    'Kamu bertanggung jawab atas semua aktivitas yang terjadi lewat akunmu.',
                ]],
                ['h' => '4. Larangan', 'p' => ['Kamu tidak boleh:'], 'list' => [
                    'menyalin, menyebarkan, mengunggah ulang, atau menjual ulang soal, pembahasan, audio, dan materi dari website ini, baik sebagian maupun seluruhnya;',
                    'mengambil konten secara otomatis (scraping) atau mencoba membobol sistem;',
                    'memakai akun untuk tujuan komersial tanpa izin tertulis dari kami.',
                ]],
                ['h' => '5. Penonaktifan akun', 'p' => [
                    'Kami dapat menonaktifkan akun yang melanggar ketentuan ini, terutama penyebaran atau penjualan ulang soal. Akun yang dinonaktifkan karena pelanggaran tidak berhak atas pengembalian dana.',
                ]],
                ['h' => '6. Skor', 'p' => [
                    'Semua skor di website ini adalah perkiraan berdasarkan hasil latihanmu. Skor tersebut bukan skor resmi dan tidak bisa dipakai sebagai sertifikat atau bukti kemampuan bahasa.',
                ]],
                ['h' => '7. Hak cipta', 'p' => [
                    'Soal, pembahasan, audio, ilustrasi, dan desain di website ini adalah milik PerumahanInggris dan dilindungi hukum hak cipta.',
                ]],
                ['h' => '8. Perubahan ketentuan', 'p' => [
                    'Ketentuan ini bisa diperbarui sewaktu-waktu. Tanggal pembaruan terakhir tercantum di halaman ini. Dengan tetap memakai layanan setelah perubahan, kamu dianggap setuju dengan ketentuan yang baru.',
                ]],
                ['h' => '9. Kontak', 'p' => ['Ada pertanyaan soal ketentuan ini? Hubungi kami lewat halaman Kontak.']],
            ],
        ],
        'privacy' => [
            'title' => 'Kebijakan Privasi',
            'eyebrow' => 'LEGAL',
            'h1' => 'Kebijakan Privasi',
            'lead' => 'Kami menyimpan data sesedikit mungkin, hanya yang dibutuhkan supaya latihanmu tersimpan dan akunmu aman.',
            'sections' => [
                ['h' => '1. Data yang kami simpan', 'list' => [
                    'Username yang kamu pilih saat mendaftar. Kami tidak meminta nama asli, email, atau nomor telepon untuk membuat akun.',
                    'Password dalam bentuk hash (diacak satu arah), sehingga kami sendiri tidak bisa membacanya. Kode pemulihan juga disimpan dalam bentuk hash.',
                    'Jawaban soal, waktu pengerjaan, progres per Hari, hasil simulasi, dan Buku Kesalahan.',
                    'Pengaturan akun, misalnya bahasa dan target skor.',
                    'Alamat IP dalam bentuk hash, hanya untuk membatasi percobaan login dan mencegah penyalahgunaan.',
                ]],
                ['h' => '2. Cara kami memakai data', 'list' => [
                    'Menyimpan progres latihan dan menampilkan pembahasan, riwayat, dan rapor.',
                    'Menghitung perkiraan skor.',
                    'Menjaga keamanan akun, misalnya mengunci sementara setelah terlalu banyak percobaan login yang gagal.',
                    'Membuat statistik gabungan tanpa identitas, misalnya soal mana yang paling sering salah, untuk memperbaiki materi.',
                ]],
                ['h' => '3. Kami tidak menjual datamu', 'p' => [
                    'Data pengguna tidak dijual, disewakan, atau dibagikan kepada pihak lain untuk iklan. Data hanya dibuka bila diwajibkan oleh hukum.',
                    'Saat pembayaran dibuka nanti, proses pembayaran ditangani oleh penyedia payment gateway. Kami tidak menyimpan nomor kartu atau data rekening.',
                ]],
                ['h' => '4. Cookie', 'p' => [
                    'Kami hanya memakai cookie yang dibutuhkan agar website berjalan: cookie sesi login, cookie Ingat saya (bila kamu mencentangnya), dan cookie pilihan bahasa. Tidak ada cookie iklan.',
                ]],
                ['h' => '5. Menghapus akun', 'p' => [
                    'Kamu bisa menghapus akun kapan saja di halaman Akun. Setelah dihapus, username, jawaban, progres, dan riwayatmu ikut terhapus dan tidak bisa dikembalikan.',
                ]],
                ['h' => '6. Keamanan', 'p' => [
                    'Koneksi ke website memakai HTTPS, password disimpan dalam bentuk hash, dan akses panel admin dibatasi.',
                ]],
                ['h' => '7. Perubahan kebijakan', 'p' => [
                    'Kebijakan ini bisa diperbarui. Tanggal pembaruan terakhir tercantum di halaman ini.',
                ]],
                ['h' => '8. Kontak', 'p' => ['Pertanyaan soal privasi bisa dikirim lewat halaman Kontak.']],
            ],
        ],
        'refund' => [
            'title' => 'Kebijakan Pengembalian Dana',
            'eyebrow' => 'LEGAL',
            'h1' => 'Kebijakan Pengembalian Dana',
            'lead' => 'Paket Premium adalah produk digital yang langsung bisa dipakai setelah pembayaran berhasil, jadi pengembalian dana hanya berlaku untuk kondisi tertentu.',
            'sections' => [
                ['h' => '1. Kapan dana bisa dikembalikan', 'p' => ['Pengembalian dana hanya diberikan jika:'], 'list' => [
                    'pembayaran sudah berhasil, tetapi akses Premium belum aktif dalam 1x24 jam; atau',
                    'terjadi pembayaran ganda untuk paket yang sama.',
                ]],
                ['h' => '2. Kapan dana tidak bisa dikembalikan', 'list' => [
                    'Berubah pikiran setelah akses Premium aktif.',
                    'Sudah memakai materi atau soal di Level B sampai D.',
                    'Akun dinonaktifkan karena melanggar Syarat & Ketentuan.',
                    'Masa aktif 1 tahun sudah berjalan atau sudah habis.',
                ]],
                ['h' => '3. Cara mengajukan', 'list' => [
                    'Ajukan paling lambat 7 hari sejak tanggal pembayaran.',
                    'Hubungi kami lewat {channels} di halaman Kontak.',
                    'Sertakan username, tanggal pembayaran, nominal, dan bukti pembayaran.',
                ]],
                ['h' => '4. Proses', 'p' => [
                    'Kami memeriksa pengajuan dalam 3 hari kerja. Jika disetujui, dana dikembalikan lewat metode pembayaran yang sama atau rekening yang kamu sebutkan, dalam 7 hari kerja setelah persetujuan. Lama dana masuk juga bergantung pada bank atau penyedia pembayaran.',
                ]],
            ],
        ],
        'contact' => [
            'title' => 'Kontak',
            'eyebrow' => 'KONTAK',
            'h1' => 'Hubungi kami',
            'lead' => 'Ada pertanyaan soal akun, pembayaran, atau materi? Kirim pesan, kami bantu.',
            'email' => 'Email',
            'wa' => 'WhatsApp',
            'hours_h' => 'Jam respons',
            'hours' => 'Senin sampai Jumat, 09.00 sampai 17.00 WIB. Pesan biasanya dibalas dalam 1x24 jam kerja.',
            'tips_h' => 'Biar cepat dibantu',
            'copy' => 'Salin', 'copied' => 'Email disalin', 'write' => 'Tulis email',
            'channels_email' => 'email', 'channels_both' => 'email atau WhatsApp',
            'tips' => [
                'Sebutkan username kamu, tapi jangan pernah kirim password.',
                'Untuk urusan pembayaran, lampirkan tanggal, nominal, dan bukti pembayaran.',
                'Untuk laporan soal yang keliru, sebutkan Hari, seksi, dan nomor soalnya.',
            ],
        ],
        'pricing' => [
            'title' => 'Harga',
            'eyebrow' => 'HARGA',
            'h1' => 'Mulai gratis, lanjut kalau cocok',
            'lead' => 'Level A bisa dipakai gratis selamanya. Paket Premium dibayar sekali dan aktif 1 tahun, tanpa langganan otomatis.',
            'free_name' => 'Gratis',
            'free_price' => 'Rp0',
            'free_period' => 'selamanya',
            'free_items' => ['Level A: Hari 1 sampai 4 (120 soal)', 'Latihan Contoh 10 soal', 'Pembahasan lengkap dan Kamus', 'Progres tersimpan'],
            'free_cta' => 'Mulai gratis',
            'premium_name' => 'Premium',
            'premium_period' => 'sekali bayar, aktif 1 tahun',
            'premium_items' => ['Semua isi paket Gratis', 'Level B sampai D: Hari 5 sampai 14', '2 Simulasi penuh dengan waktu resmi', 'Rapor PDF per Level dan perkiraan skor akhir'],
            'premium_cta' => 'Segera hadir',
            'premium_note' => 'Pembayaran belum dibuka. Tombol beli akan aktif segera.',
            'tiers_h' => 'Harga untuk pembeli awal',
            'tier' => 'Pembeli ke-{from} sampai {to}',
            'tier_after' => 'Setelah pembeli ke-300',
            'tier_now' => 'Berlaku sekarang',
            'popular' => 'Paling hemat',
            'notes' => [
                'Harga sudah termasuk semua biaya. Tidak ada langganan otomatis.',
                'Kebijakan pengembalian dana ada di halaman Pengembalian Dana.',
            ],
        ],
    ],
    'en' => [
        'updated' => 'Last updated: November 4, 2026',
        'terms' => [
            'title' => 'Terms & Conditions',
            'eyebrow' => 'LEGAL',
            'h1' => 'Terms & Conditions',
            'lead' => 'By creating an account or using PerumahanInggris, you agree to the terms below. They only take a few minutes to read.',
            'sections' => [
                ['h' => '1. About the service', 'p' => [
                    'PerumahanInggris is a practice platform for the PBT-format English proficiency test (Listening, Structure, and Reading). We do not run any test and are not affiliated with any testing organization.',
                ]],
                ['h' => '2. Free and paid access', 'p' => [
                    'Level A (Days 1 to 4) and the Sample Practice are free.',
                    'Levels B to D and the full Simulations belong to the Premium plan. Premium is a one-time payment with no automatic renewal, and it stays active for 1 year from the successful payment. Current prices are listed on the Pricing page.',
                    'Until payments open, features that will later be paid may remain open to everyone. We will announce it on the website before any limits apply.',
                ]],
                ['h' => '3. Accounts', 'list' => [
                    'One account per person. Do not share your username and password.',
                    'Keep your recovery code safe, because it is how you reset your password.',
                    'You are responsible for all activity on your account.',
                ]],
                ['h' => '4. Prohibited use', 'p' => ['You may not:'], 'list' => [
                    'copy, share, re-upload, or resell questions, explanations, audio, or lessons from this website, in part or in full;',
                    'scrape content automatically or try to break into the system;',
                    'use an account for commercial purposes without our written permission.',
                ]],
                ['h' => '5. Account suspension', 'p' => [
                    'We may deactivate accounts that break these terms, especially by sharing or reselling questions. Accounts deactivated for a violation are not eligible for a refund.',
                ]],
                ['h' => '6. Scores', 'p' => [
                    'All scores on this website are estimates based on your practice results. They are not official scores and cannot be used as a certificate or proof of language ability.',
                ]],
                ['h' => '7. Copyright', 'p' => [
                    'The questions, explanations, audio, illustrations, and design on this website belong to PerumahanInggris and are protected by copyright law.',
                ]],
                ['h' => '8. Changes', 'p' => [
                    'These terms may be updated at any time. The date of the latest update is shown on this page. By continuing to use the service after a change, you accept the new terms.',
                ]],
                ['h' => '9. Contact', 'p' => ['Questions about these terms? Reach us through the Contact page.']],
            ],
        ],
        'privacy' => [
            'title' => 'Privacy Policy',
            'eyebrow' => 'LEGAL',
            'h1' => 'Privacy Policy',
            'lead' => 'We keep as little data as possible: only what is needed to save your practice and keep your account safe.',
            'sections' => [
                ['h' => '1. Data we store', 'list' => [
                    'The username you choose when signing up. We do not ask for your real name, email, or phone number to create an account.',
                    'Your password as a one-way hash, so even we cannot read it. Recovery codes are also stored as hashes.',
                    'Your answers, time spent, progress per Day, simulation results, and Mistake Book.',
                    'Account settings such as language and target score.',
                    'Your IP address as a hash, only to limit login attempts and prevent abuse.',
                ]],
                ['h' => '2. How we use data', 'list' => [
                    'To save your practice progress and show explanations, history, and reports.',
                    'To calculate estimated scores.',
                    'To keep accounts secure, for example by locking login for a while after too many failed attempts.',
                    'To build anonymous combined statistics, such as which questions are missed most often, to improve the lessons.',
                ]],
                ['h' => '3. We do not sell your data', 'p' => [
                    'User data is not sold, rented, or shared with third parties for advertising. Data is only disclosed when required by law.',
                    'When payments open, they will be handled by a payment gateway provider. We do not store card numbers or bank details.',
                ]],
                ['h' => '4. Cookies', 'p' => [
                    'We only use cookies needed for the website to work: the login session cookie, the Remember me cookie (if you tick it), and the language cookie. There are no advertising cookies.',
                ]],
                ['h' => '5. Deleting your account', 'p' => [
                    'You can delete your account at any time on the Account page. Once deleted, your username, answers, progress, and history are removed and cannot be restored.',
                ]],
                ['h' => '6. Security', 'p' => [
                    'The website uses HTTPS, passwords are stored as hashes, and access to the admin panel is restricted.',
                ]],
                ['h' => '7. Changes', 'p' => [
                    'This policy may be updated. The date of the latest update is shown on this page.',
                ]],
                ['h' => '8. Contact', 'p' => ['Privacy questions can be sent through the Contact page.']],
            ],
        ],
        'refund' => [
            'title' => 'Refund Policy',
            'eyebrow' => 'LEGAL',
            'h1' => 'Refund Policy',
            'lead' => 'The Premium plan is a digital product that can be used as soon as payment succeeds, so refunds only apply in specific cases.',
            'sections' => [
                ['h' => '1. When we refund', 'p' => ['A refund is only given if:'], 'list' => [
                    'your payment succeeded but Premium access was not activated within 24 hours; or',
                    'you were charged twice for the same plan.',
                ]],
                ['h' => '2. When we do not refund', 'list' => [
                    'You change your mind after Premium access is active.',
                    'You have already used lessons or questions in Levels B to D.',
                    'Your account was deactivated for breaking the Terms & Conditions.',
                    'The 1-year access period has started running or has ended.',
                ]],
                ['h' => '3. How to request', 'list' => [
                    'Submit your request within 7 days of the payment date.',
                    'Contact us by {channels} on the Contact page.',
                    'Include your username, payment date, amount, and proof of payment.',
                ]],
                ['h' => '4. Process', 'p' => [
                    'We review requests within 3 working days. If approved, the money is returned through the same payment method or to the account you provide, within 7 working days of approval. Arrival time also depends on your bank or payment provider.',
                ]],
            ],
        ],
        'contact' => [
            'title' => 'Contact',
            'eyebrow' => 'CONTACT',
            'h1' => 'Get in touch',
            'lead' => 'Questions about your account, payments, or lessons? Send us a message and we will help.',
            'email' => 'Email',
            'wa' => 'WhatsApp',
            'hours_h' => 'Response hours',
            'hours' => 'Monday to Friday, 09:00 to 17:00 Western Indonesia Time (UTC+7). We usually reply within one working day.',
            'tips_h' => 'To get help faster',
            'copy' => 'Copy', 'copied' => 'Email copied', 'write' => 'Write an email',
            'channels_email' => 'email', 'channels_both' => 'email or WhatsApp',
            'tips' => [
                'Mention your username, but never send your password.',
                'For payment issues, include the date, amount, and proof of payment.',
                'To report a wrong question, mention the Day, section, and question number.',
            ],
        ],
        'pricing' => [
            'title' => 'Pricing',
            'eyebrow' => 'PRICING',
            'h1' => 'Start free, upgrade if it fits',
            'lead' => 'Level A is free forever. Premium is a one-time payment that stays active for 1 year, with no automatic renewal.',
            'free_name' => 'Free',
            'free_price' => 'Rp0',
            'free_period' => 'forever',
            'free_items' => ['Level A: Days 1 to 4 (120 questions)', 'Sample Practice, 10 questions', 'Full explanations and Glossary', 'Saved progress'],
            'free_cta' => 'Start free',
            'premium_name' => 'Premium',
            'premium_period' => 'one-time payment, active for 1 year',
            'premium_items' => ['Everything in Free', 'Levels B to D: Days 5 to 14', '2 full Simulations with official timing', 'PDF report per Level and final estimated score'],
            'premium_cta' => 'Coming soon',
            'premium_note' => 'Payments are not open yet. The buy button will be active soon.',
            'tiers_h' => 'Early buyer prices',
            'tier' => 'Buyers {from} to {to}',
            'tier_after' => 'After buyer 300',
            'tier_now' => 'Current price',
            'popular' => 'Best value',
            'notes' => [
                'Prices include all fees. There is no automatic renewal.',
                'See the Refund Policy page for refund rules.',
            ],
        ],
    ],
];
