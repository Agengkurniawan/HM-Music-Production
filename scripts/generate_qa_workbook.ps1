param([string]$OutputPath = "docs\QA_Test_Cases_HM_Music_Production.xlsx")

$ErrorActionPreference = 'Stop'
$cases = [System.Collections.Generic.List[object]]::new()
$script:id = 0
function Add-Case($module,$submodule,$role,$type,$priority,$title,$precondition,$data,$steps,$expected,$severity='Major') {
    $script:id++
    $cases.Add([pscustomobject]@{
        ID=('TC-{0:D3}' -f $script:id); Module=$module; Submodule=$submodule; Role=$role; Type=$type; Priority=$priority
        Scenario=$title; Precondition=$precondition; TestData=$data; Steps=$steps; Expected=$expected
        Actual=''; Status='Not Run'; Severity=$severity; PIC=''; TestDate=''; Evidence=''; DefectID=''; Notes=''
    })
}

# Public, authentication, authorization
Add-Case 'Public Demo' 'Catalog' 'Guest' 'Positive' 'High' 'Guest membuka katalog demo' 'Terdapat demo Published dengan media valid' 'URL /' '1. Buka halaman utama; 2. Amati daftar demo' 'Halaman tampil dan hanya demo Published dengan YouTube/MP4 valid yang muncul'
Add-Case 'Public Demo' 'Catalog' 'Guest' 'Negative' 'Medium' 'Demo Draft tidak tampil ke publik' 'Terdapat demo berstatus Draft' 'URL /' '1. Buka halaman utama; 2. Cari demo Draft' 'Demo Draft tidak ditampilkan'
Add-Case 'Public Demo' 'Play' 'Guest' 'Positive' 'Guest memutar demo Published' 'Demo Published memiliki media valid' 'Demo YouTube atau MP4' '1. Klik play; 2. Periksa respons dan counter' 'Media diputar dan plays_count bertambah satu'
Add-Case 'Public Demo' 'Play' 'Guest' 'Negative' 'Play demo Draft ditolak' 'Demo berstatus Draft' 'POST /demo/{id}/play' '1. Kirim request play untuk demo Draft' 'Sistem mengembalikan 404 dan counter tidak berubah'
Add-Case 'Public Demo' 'Play' 'Guest' 'Negative' 'Play demo tanpa media ditolak' 'Demo Published tidak memiliki YouTube ID atau MP4' 'POST /demo/{id}/play' '1. Kirim request play' 'Sistem mengembalikan 404 dan counter tidak berubah'
Add-Case 'Authentication' 'Login' 'Customer' 'Positive' 'Login customer aktif dengan kredensial valid' 'Customer Active terdaftar' 'Email dan password valid' '1. Buka login; 2. Isi kredensial; 3. Klik Masuk' 'Login berhasil, session diregenerasi, diarahkan ke dashboard'
Add-Case 'Authentication' 'Login' 'Admin' 'Positive' 'Login verified admin' 'Role admin, email sesuai HM_ADMIN_EMAIL, email verified' 'Kredensial admin valid' '1. Login sebagai admin' 'Diarahkan ke admin dashboard'
Add-Case 'Authentication' 'Login' 'Customer' 'Negative' 'Password salah' 'Customer terdaftar' 'Password salah' '1. Isi email valid dan password salah; 2. Masuk' 'Login ditolak, error kredensial tampil, email tetap terisi'
Add-Case 'Authentication' 'Login' 'Customer' 'Negative' 'Email tidak valid atau kosong' 'Tidak ada' 'Email kosong/format salah' '1. Submit formulir login' 'Validasi email tampil dan login tidak dilakukan'
Add-Case 'Authentication' 'Login' 'Customer' 'Negative' 'Akun suspended mencoba login' 'Customer berstatus Suspended' 'Kredensial valid' '1. Login sebagai customer suspended' 'Session dibatalkan, user tetap guest, pesan suspended tampil' 'Critical'
Add-Case 'Authentication' 'Login' 'Admin' 'Negative' 'Admin tidak terverifikasi mencoba login' 'Role admin tetapi email belum verified atau bukan email admin konfigurasi' 'Kredensial valid' '1. Login sebagai admin tidak terverifikasi' 'Login dibatalkan dan pesan admin tidak terverifikasi tampil' 'Critical'
Add-Case 'Authentication' 'Remember Me' 'Customer' 'Positive' 'Login dengan Ingat Saya' 'Customer Active terdaftar' 'Remember=true' '1. Centang Ingat saya; 2. Login; 3. Tutup dan buka browser sesuai kebijakan cookie' 'Remember token/cookie menjaga autentikasi sesuai konfigurasi'
Add-Case 'Authentication' 'Logout' 'Authenticated' 'Positive' 'Logout mengakhiri session' 'User sudah login' 'POST /logout' '1. Klik Logout; 2. Coba buka halaman terproteksi' 'Session invalid, CSRF token baru, diarahkan login'
Add-Case 'Authorization' 'Customer Route' 'Guest' 'Negative' 'Guest mengakses halaman customer' 'Belum login' '/dashboard, /stylesampling' '1. Akses URL customer langsung' 'Diarahkan ke login tanpa data customer terbuka' 'Critical'
Add-Case 'Authorization' 'Customer Route' 'Admin' 'Negative' 'Admin mengakses halaman customer' 'Verified admin login' '/dashboard atau /stylesampling' '1. Akses URL customer' 'Diarahkan ke admin dashboard' 'Critical'
Add-Case 'Authorization' 'Admin Route' 'Customer' 'Negative' 'Customer mengakses halaman admin' 'Customer login' '/admin/*' '1. Akses URL admin langsung' 'Akses ditolak/dialihkan dan data admin tidak terbuka' 'Critical'
Add-Case 'Authorization' 'Admin Route' 'Guest' 'Negative' 'Guest mengakses halaman admin' 'Belum login' '/admin/*' '1. Akses URL admin langsung' 'Diarahkan ke login' 'Critical'
Add-Case 'Authentication' 'Forgot Password' 'Customer' 'Positive' 'Customer meminta link reset' 'Email customer terdaftar' 'Email valid' '1. Klik Lupa Password; 2. Isi email; 3. Kirim link' 'Pesan generik sukses tampil dan email reset dikirim'
Add-Case 'Authentication' 'Forgot Password' 'Guest' 'Positive' 'Email tidak terdaftar tetap mendapat respons aman' 'Email tidak terdaftar' 'unknown@example.com' '1. Minta link reset' 'Pesan generik sama tampil tanpa membocorkan keberadaan akun' 'Critical'
Add-Case 'Authentication' 'Forgot Password' 'Admin' 'Negative' 'Admin tidak menerima reset customer' 'Email admin terdaftar' 'Email admin' '1. Minta reset melalui form customer' 'Respons generik tampil tetapi notifikasi reset tidak dikirim ke admin' 'Critical'
Add-Case 'Authentication' 'Forgot Password' 'Guest' 'Negative' 'Request reset dengan email kosong/invalid' 'Tidak ada' 'kosong atau abc' '1. Submit request reset' 'Validasi pada modal tampil'
Add-Case 'Authentication' 'Forgot Password' 'Guest' 'Negative' 'Request reset terlalu sering' 'Request sebelumnya baru dilakukan' 'Email customer sama' '1. Kirim request berulang dalam throttle window' 'Sistem menolak sementara dan meminta menunggu'
Add-Case 'Authentication' 'Reset Password' 'Customer' 'Positive' 'Reset dengan token valid' 'Token reset customer valid' 'Password baru minimal 8, ada huruf dan angka' '1. Buka link; 2. Isi password dan konfirmasi; 3. Simpan' 'Password berubah, email dianggap verified, diarahkan login dengan pesan sukses'
Add-Case 'Authentication' 'Reset Password' 'Customer' 'Negative' 'Token invalid atau kedaluwarsa' 'Token tidak valid/expired' 'Token invalid' '1. Submit password baru' 'Reset ditolak dan pesan minta link baru tampil' 'Critical'
Add-Case 'Authentication' 'Reset Password' 'Customer' 'Negative' 'Konfirmasi password tidak sama' 'Token valid' 'Password dan konfirmasi berbeda' '1. Submit formulir' 'Validasi confirmed tampil, password lama tetap berlaku'
Add-Case 'Authentication' 'Reset Password' 'Customer' 'Negative' 'Password kurang dari 8 karakter' 'Token valid' 'Abc123' '1. Submit formulir' 'Validasi panjang minimum tampil'
Add-Case 'Authentication' 'Reset Password' 'Customer' 'Negative' 'Password tidak mengandung huruf atau angka' 'Token valid' '12345678 atau abcdefgh' '1. Submit formulir' 'Validasi komposisi password tampil'

# Social authentication
Add-Case 'Social Auth' 'Google' 'Guest' 'Positive' 'Redirect login Google' 'OAuth Google terkonfigurasi' 'intent=login' '1. Klik Login Google' 'Diarahkan ke account chooser Google dengan state, scope, redirect URI valid'
Add-Case 'Social Auth' 'Facebook' 'Guest' 'Positive' 'Redirect login Facebook' 'OAuth Facebook terkonfigurasi' 'intent=login' '1. Klik Login Facebook' 'Diarahkan ke dialog Facebook dengan state dan scope valid'
Add-Case 'Social Auth' 'Callback' 'Guest' 'Positive' 'Social login membuat customer baru' 'Profil provider valid dan email tersedia' 'Profil verified' '1. Selesaikan consent provider' 'Customer Free dibuat, provider ID tertaut, login berhasil'
Add-Case 'Social Auth' 'Callback' 'Customer' 'Positive' 'Social login menautkan akun email yang sama' 'Customer lokal sudah ada' 'Email provider sama' '1. Login social' 'Provider ID ditautkan ke akun existing, tidak membuat duplikat'
Add-Case 'Social Auth' 'Callback' 'Guest' 'Negative' 'OAuth state tidak cocok' 'Session OAuth tersedia' 'state callback berbeda' '1. Panggil callback dengan state salah' 'Login ditolak dan error social tampil' 'Critical'
Add-Case 'Social Auth' 'Callback' 'Guest' 'Negative' 'Provider tidak mengirim email' 'OAuth valid' 'Profil tanpa email' '1. Selesaikan callback' 'Akun tidak dibuat dan pesan error aman tampil'
Add-Case 'Social Auth' 'Configuration' 'Guest' 'Negative' 'OAuth belum dikonfigurasi' 'Client ID/secret kosong' 'Klik provider' '1. Buka login' 'Tombol disabled atau redirect ditolak dengan pesan konfigurasi, tanpa crash'
Add-Case 'Social Auth' 'Account Safety' 'Guest' 'Negative' 'Profil social mencoba memakai email admin' 'Email provider sama dengan admin' 'Profil social admin email' '1. Selesaikan callback' 'Tidak mengubah/menurunkan role admin dan akses customer ditolak' 'Critical'

# Customer dashboard, catalog, notifications
Add-Case 'Customer Dashboard' 'Summary' 'Customer' 'Positive' 'Dashboard menampilkan statistik akun' 'Customer login dan data tersedia' 'Data demo, style, download, subscription' '1. Buka dashboard' 'Jumlah style, plays, downloads, plan dan expiry sesuai database'
Add-Case 'Customer Dashboard' 'Premium Status' 'Customer' 'Positive' 'Subscription aktif terdeteksi premium' 'Subscription Active dan expiry future/null' 'Subscription aktif' '1. Buka dashboard' 'Premium access aktif dan plan sesuai subscription'
Add-Case 'Customer Dashboard' 'Premium Status' 'Customer' 'Negative' 'Subscription expired tidak dianggap premium' 'Subscription Active tetapi expiry masa lalu' 'Subscription expired' '1. Buka dashboard' 'Premium access false dan download premium tetap terkunci'
Add-Case 'Customer Style' 'Catalog' 'Customer' 'Positive' 'Daftar style Published tampil' 'Customer login, style Published punya file' 'type=style' '1. Buka Style Sampling' 'Hanya style Published, kategori valid, dan file tersedia yang tampil'
Add-Case 'Customer Style' 'Filter' 'Customer' 'Positive' 'Filter kategori style' 'Terdapat style beberapa kategori' 'category=Dangdut' '1. Pilih kategori Dangdut' 'Hanya style kategori Dangdut tampil'
Add-Case 'Customer Style' 'Filter' 'Customer' 'Positive' 'Filter expansion pack style' 'Terdapat mapping pack-category' 'pack valid' '1. Pilih pack' 'Style sesuai kategori mapping pack tampil'
Add-Case 'Customer Style' 'Filter' 'Customer' 'Negative' 'Parameter filter tidak valid' 'Customer login' 'type/category/pack acak' '1. Manipulasi query URL' 'Sistem fallback aman tanpa error dan tidak membocorkan data'
Add-Case 'Customer Style' 'Download' 'Premium Customer' 'Positive' 'Download file STY' 'Subscription aktif, style Published, file tersedia' 'Style valid' '1. Klik Download Style' 'File terunduh dengan nama benar, DownloadSale tercatat, counter bertambah'
Add-Case 'Customer Style' 'Download' 'Free Customer' 'Negative' 'Free user download premium style' 'Customer tanpa subscription aktif' 'Style Published' '1. Klik Download' 'Diarahkan ke subscription dan download tidak tercatat' 'Critical'
Add-Case 'Customer Style' 'Download' 'Premium Customer' 'Negative' 'Download style Draft melalui URL' 'Subscription aktif, style Draft' 'URL download langsung' '1. Akses endpoint download' '404 dan file tidak terkirim' 'Critical'
Add-Case 'Customer Style' 'Download' 'Premium Customer' 'Negative' 'File style hilang di storage' 'Style Published, path tercatat tetapi file hilang' 'Style valid' '1. Klik Download' 'Pesan file tidak tersedia dan counter tidak bertambah'
Add-Case 'Notifications' 'Customer Header' 'Customer' 'Positive' 'Notifikasi customer sesuai aktivitas' 'Ada style baru/sampling/payment/subscription event' 'Customer login' '1. Buka panel notifikasi' 'Notifikasi relevan tampil, terurut, dan link mengarah ke fitur benar'
Add-Case 'Notifications' 'Read State' 'Authenticated' 'Positive' 'Menandai notifikasi sudah dibaca' 'Ada notifikasi unread' 'key valid <=180' '1. Klik notifikasi; 2. Reload' 'Endpoint sukses dan notifikasi tetap read untuk user'
Add-Case 'Notifications' 'Read State' 'Guest' 'Positive' 'Read state guest disimpan dalam session' 'Guest memiliki notifikasi publik bila tersedia' 'key valid' '1. Tandai read; 2. Reload session sama' 'Status read bertahan dalam session'
Add-Case 'Notifications' 'Read State' 'Authenticated' 'Negative' 'Key notifikasi kosong/lebih 180 karakter' 'User login' 'key invalid' '1. POST mark read' 'Validasi 422 dan data read tidak dibuat'
Add-Case 'Notifications' 'Isolation' 'Customer' 'Negative' 'Read state tidak terbawa antar-user' 'Dua customer berbeda' 'Key sama' '1. User A mark read; 2. User B buka panel' 'Notifikasi User B tetap unread' 'Critical'

# Subscription and Midtrans
Add-Case 'Subscription Checkout' 'Registration' 'Guest' 'Positive' 'Customer baru checkout premium' 'Midtrans valid' 'Nama, email baru, password confirmed, paket/nominal server' '1. Isi checkout; 2. Bayar' 'User Free, subscription Pending, payment Pending dibuat; diarahkan ke Midtrans; belum login'
Add-Case 'Subscription Checkout' 'Registration' 'Guest' 'Positive' 'Upload foto profil valid' 'Checkout valid' 'JPG/PNG/WEBP <=2MB' '1. Pilih foto; 2. Checkout' 'Foto tersimpan dan URL profil tersedia'
Add-Case 'Subscription Checkout' 'Registration' 'Guest' 'Negative' 'Field wajib kosong' 'Tidak ada' 'Nama/email/password/package/amount kosong' '1. Submit checkout' 'Validasi tampil dan transaksi tidak dibuat'
Add-Case 'Subscription Checkout' 'Registration' 'Guest' 'Negative' 'Password tidak confirmed atau <8' 'Email baru' 'Password invalid' '1. Submit checkout' 'Validasi password tampil dan user tidak dibuat'
Add-Case 'Subscription Checkout' 'Registration' 'Guest' 'Negative' 'Foto profil tipe/ukuran tidak valid' 'Form valid' 'GIF/PDF atau >2MB' '1. Upload; 2. Submit' 'Validasi file tampil dan transaksi tidak dibuat'
Add-Case 'Subscription Checkout' 'Integrity' 'Guest' 'Negative' 'Nominal dimanipulasi dari browser' 'Harga setting 55000' 'amount=1' '1. Ubah payload; 2. Submit' 'Checkout ditolak; payment/subscription tidak dibuat' 'Critical'
Add-Case 'Subscription Checkout' 'Integrity' 'Guest' 'Negative' 'Nama paket dimanipulasi' 'Durasi setting menentukan package' 'package tidak sesuai' '1. Ubah payload package' 'Checkout ditolak dengan pesan refresh halaman' 'Critical'
Add-Case 'Subscription Checkout' 'Renewal' 'Customer' 'Positive' 'Existing customer renewal dengan password benar' 'Customer aktif existing' 'Email sama dan password benar' '1. Checkout renewal; 2. Bayar settlement' 'Masa aktif diperpanjang dari expiry future dan hanya satu subscription Active'
Add-Case 'Subscription Checkout' 'Renewal' 'Customer' 'Positive' 'Social customer checkout tanpa password' 'Customer login punya google_id/facebook_id' 'Tanpa password' '1. Checkout' 'Checkout diterima dan payment Pending dibuat'
Add-Case 'Subscription Checkout' 'Renewal' 'Customer' 'Negative' 'Existing email dengan password salah' 'Customer existing' 'Password salah' '1. Submit checkout' 'Ditolak, payment tidak dibuat'
Add-Case 'Subscription Checkout' 'Renewal' 'Customer' 'Negative' 'User login memakai email customer lain' 'Customer A login; Customer B ada' 'Email B' '1. Submit checkout' 'Ditolak dan tidak mengubah akun mana pun' 'Critical'
Add-Case 'Subscription Checkout' 'Renewal' 'Guest' 'Negative' 'Email admin digunakan untuk subscription customer' 'Admin email terdaftar' 'Email admin' '1. Submit checkout' 'Ditolak dan role admin tidak berubah' 'Critical'
Add-Case 'Subscription Checkout' 'Renewal' 'Guest' 'Negative' 'Customer suspended renewal' 'Customer Suspended' 'Email/password benar' '1. Submit checkout' 'Ditolak dan transaksi tidak dibuat' 'Critical'
Add-Case 'Midtrans' 'Create Transaction' 'Guest/Customer' 'Negative' 'Server key salah atau gateway gagal' 'Konfigurasi invalid/koneksi gagal' 'Checkout valid' '1. Submit checkout' 'Payment Failed, subscription Cancelled, pesan konfigurasi/koneksi tampil' 'Critical'
Add-Case 'Midtrans' 'Finish' 'Customer' 'Positive' 'Finish payment settlement' 'Payment valid selesai di Midtrans' 'order_id valid' '1. Kembali dari Midtrans' 'Status disinkronkan, user login, subscription aktif, diarahkan ke style'
Add-Case 'Midtrans' 'Finish' 'Guest' 'Negative' 'Finish tanpa reference' 'Session reference kosong' 'Tanpa order_id' '1. Buka finish URL' 'Diarahkan subscription dengan error reference'
Add-Case 'Midtrans' 'Finish' 'Guest' 'Negative' 'Finish dengan payment tidak ditemukan' 'Reference tidak ada di DB' 'order_id acak' '1. Buka finish URL' 'Error payment tidak ditemukan, tanpa crash'
Add-Case 'Midtrans' 'Webhook' 'System' 'Positive' 'Webhook settlement valid' 'Payment Pending' 'Signature, order, amount valid' '1. Kirim notification settlement' 'HTTP 200; payment Completed; subscription aktif tepat satu kali'
Add-Case 'Midtrans' 'Webhook' 'System' 'Positive' 'Webhook settlement dikirim ulang' 'Payment sudah Completed' 'Payload sama' '1. Kirim webhook duplikat' 'Idempotent: expiry dan transaksi tidak bertambah dua kali' 'Critical'
Add-Case 'Midtrans' 'Webhook' 'System' 'Negative' 'Signature webhook invalid' 'Payment Pending' 'signature salah' '1. Kirim notification' 'HTTP 403 dan status tidak berubah' 'Critical'
Add-Case 'Midtrans' 'Webhook' 'System' 'Negative' 'Order webhook tidak ditemukan' 'Signature valid' 'order_id unknown' '1. Kirim notification' 'HTTP 404 dan tidak ada data dibuat'
Add-Case 'Midtrans' 'Webhook' 'System' 'Negative' 'Nominal webhook tidak cocok' 'Payment Pending' 'gross_amount berbeda' '1. Kirim notification' 'HTTP 422 dan payment/subscription tetap Pending' 'Critical'
Add-Case 'Midtrans' 'Webhook' 'System' 'Negative' 'Payment deny/cancel/expire' 'Payment Pending' 'Status gagal valid' '1. Kirim webhook' 'Payment menjadi status gagal dan subscription Pending menjadi Cancelled'

# Sampling request workflow
Add-Case 'Sampling N27' 'Create Request' 'Customer' 'Positive' 'Membuat request sampling pack' 'Customer Active login' 'Pack valid, storage 512, notes' '1. Pilih pack; 2. Isi detail; 3. Buat request' 'Order unik dibuat senilai Rp800.000 dengan Pending Payment'
Add-Case 'Sampling N27' 'Create Request' 'Customer' 'Positive' 'Legacy pack dinormalisasi' 'Customer login' 'HM Dangdut Koplo Expansion Packs' '1. Submit request' 'Pack tersimpan sebagai HM Dangdut Expansion Packs'
Add-Case 'Sampling N27' 'Create Request' 'Customer' 'Negative' 'Pack tidak valid/kosong' 'Customer login' 'pack acak/kosong' '1. Submit request' 'Validasi tampil dan request tidak dibuat'
Add-Case 'Sampling N27' 'Create Request' 'Customer' 'Negative' 'Storage keyboard di luar batas' 'Customer login' '0, 4097, non-integer' '1. Submit request' 'Validasi min 1 max 4096 tampil'
Add-Case 'Sampling N27' 'Create Request' 'Customer' 'Negative' 'Customer notes lebih 1000 karakter' 'Customer login' '1001 karakter' '1. Submit request' 'Validasi tampil dan request tidak dibuat'
Add-Case 'Sampling N27' 'Payment' 'Customer' 'Positive' 'Bayar sampling via Midtrans' 'Order Pending milik customer' 'Order valid' '1. Klik Bayar via Midtrans' 'Payment Pending tertaut dan redirect Snap berhasil'
Add-Case 'Sampling N27' 'Payment' 'Customer' 'Positive' 'Retry checkout membatalkan payment lama' 'Order dengan payment Pending' 'Klik bayar ulang' '1. Ulangi checkout' 'Payment lama Cancelled dan payment baru menjadi referensi aktif'
Add-Case 'Sampling N27' 'Payment' 'Customer' 'Positive' 'Payment sudah Paid tidak dibuat ulang' 'Order Paid' 'Klik bayar' '1. Kirim endpoint pay' 'Diarahkan kembali dengan info sudah berhasil; tidak ada payment baru'
Add-Case 'Sampling N27' 'Ownership' 'Customer' 'Negative' 'Membayar order customer lain' 'Dua customer, order milik B' 'ID order B' '1. Customer A panggil endpoint payment' 'HTTP 403 dan order tidak berubah' 'Critical'
Add-Case 'Sampling N27' 'Sync Payment' 'Customer' 'Positive' 'Sinkron settlement sampling' 'Payment Pending dan Midtrans settlement' 'Amount sesuai' '1. Klik Cek Status' 'Payment Completed, request Paid, upload N27 terbuka'
Add-Case 'Sampling N27' 'Sync Payment' 'Customer' 'Positive' 'Sinkron payment masih pending' 'Midtrans pending' 'Status pending' '1. Klik Cek Status' 'Pesan masih diproses; status tetap Pending'
Add-Case 'Sampling N27' 'Sync Payment' 'Customer' 'Negative' 'Sync tanpa transaksi' 'Request belum dibayar' 'Tidak ada payment' '1. Klik Cek Status' 'Pesan belum ada transaksi; status tidak berubah'
Add-Case 'Sampling N27' 'Sync Payment' 'Customer' 'Negative' 'Sync settlement dengan amount mismatch' 'Payment Pending' 'Gross amount berbeda' '1. Klik Cek Status' 'Ditolak; request/payment tetap Pending' 'Critical'
Add-Case 'Sampling N27' 'Upload N27' 'Customer' 'Positive' 'Upload N27 setelah Paid' 'Order Paid milik customer' 'File .n27 <=100MB' '1. Pilih file; 2. Upload' 'File tersimpan, nama asli tercatat, status N27 Uploaded'
Add-Case 'Sampling N27' 'Upload N27' 'Customer' 'Positive' 'Upload ulang N27 sebelum final delivery' 'Order Paid/Processing belum Ready' 'File .n27 baru' '1. Upload file baru' 'Referensi file diperbarui dan status N27 Uploaded'
Add-Case 'Sampling N27' 'Upload N27' 'Customer' 'Negative' 'Upload sebelum pembayaran' 'Order Pending Payment' 'File .n27 valid' '1. Panggil upload' 'Ditolak dan file tidak tersimpan' 'Critical'
Add-Case 'Sampling N27' 'Upload N27' 'Customer' 'Negative' 'Ekstensi bukan N27' 'Order Paid' 'PDF/ZIP berganti nama' '1. Upload file' 'Validasi ekstensi tampil dan status tidak berubah'
Add-Case 'Sampling N27' 'Upload N27' 'Customer' 'Negative' 'File N27 melebihi 100MB' 'Order Paid' 'File >102400KB' '1. Upload file' 'Validasi ukuran tampil dan file tidak tersimpan'
Add-Case 'Sampling N27' 'Upload N27' 'Customer' 'Negative' 'Upload ke order customer lain' 'Order Paid milik B' 'File valid' '1. Customer A upload ke ID B' 'HTTP 403 dan file/order tidak berubah' 'Critical'
Add-Case 'Sampling N27' 'Delivery' 'Customer' 'Negative' 'Upload setelah status Ready/Completed' 'Order Ready atau Completed' 'File N27 valid' '1. Coba upload lagi' 'Ditolak untuk melindungi hasil final'

# Admin modules
Add-Case 'Admin Dashboard' 'Metrics' 'Admin' 'Positive' 'Dashboard menampilkan KPI akurat' 'Admin login dan data tersedia' 'Users, subscriptions, payments, downloads, demos' '1. Buka dashboard; 2. Cocokkan DB' 'Semua KPI, chart, trending, revenue bulan berjalan akurat'
Add-Case 'Admin Demo' 'Create' 'Admin' 'Positive' 'Tambah demo YouTube' 'Admin login' 'Title, URL YouTube valid, genre/status valid' '1. Isi form; 2. Simpan' 'URL dinormalisasi, video ID terbaca, demo tersimpan'
Add-Case 'Admin Demo' 'Create' 'Admin' 'Positive' 'Tambah demo MP4' 'Admin login' 'MP4 <=200MB tanpa YouTube' '1. Upload MP4; 2. Simpan' 'File tersimpan dan demo dapat dimainkan'
Add-Case 'Admin Demo' 'Create' 'Admin' 'Negative' 'Demo tanpa YouTube dan MP4' 'Admin login' 'Media kosong' '1. Submit form' 'Validasi media tampil dan demo tidak dibuat'
Add-Case 'Admin Demo' 'Validation' 'Admin' 'Negative' 'URL YouTube invalid' 'Admin login' 'URL non-YouTube/ID invalid' '1. Submit' 'Validasi URL tampil'
Add-Case 'Admin Demo' 'Validation' 'Admin' 'Negative' 'BPM di luar 1-300' 'Admin login' '0/301/non-integer' '1. Submit' 'Validasi BPM tampil'
Add-Case 'Admin Demo' 'Validation' 'Admin' 'Negative' 'Duration invalid' 'Admin login' '4:99 atau teks' '1. Submit' 'Format mmm:ss ditolak'
Add-Case 'Admin Demo' 'Validation' 'Admin' 'Negative' 'Upload non-MP4 atau >200MB' 'Admin login' 'AVI/PDF atau file besar' '1. Submit' 'Validasi file tampil'
Add-Case 'Admin Demo' 'Update' 'Admin' 'Positive' 'Ganti media demo dan hapus file lama' 'Demo punya MP4 lama' 'MP4 baru' '1. Edit; 2. Upload baru; 3. Simpan' 'Path baru tersimpan dan file lama dihapus'
Add-Case 'Admin Demo' 'Update' 'Admin' 'Negative' 'Hapus satu-satunya media tanpa pengganti' 'Demo hanya punya MP4' 'remove=true, YouTube kosong' '1. Update' 'Ditolak agar demo tidak kehilangan seluruh media'
Add-Case 'Admin Demo' 'Trending' 'Admin' 'Positive' 'Toggle trending' 'Demo ada' 'is_trending true/false' '1. Ubah status trending' 'Flag dan urutan katalog/dashboard berubah sesuai nilai'
Add-Case 'Admin Demo' 'Delete' 'Admin' 'Positive' 'Hapus demo beserta MP4' 'Demo punya MP4' 'ID demo' '1. Hapus; 2. Konfirmasi' 'Record dan file MP4 terhapus, tidak tampil di publik'
Add-Case 'Admin Style' 'Upload' 'Admin' 'Positive' 'Upload style valid' 'Admin login' 'STY/PRS/SST <=50MB, kategori/pack valid' '1. Isi form; 2. Upload; 3. Simpan' 'Style tersimpan Premium sebagai Draft/Published sesuai pilihan'
Add-Case 'Admin Style' 'Upload' 'Admin' 'Positive' 'Upload cover image valid' 'Form style valid' 'Image <=5MB' '1. Upload cover; 2. Simpan' 'Cover tersimpan dan tampil di katalog'
Add-Case 'Admin Style' 'Upload' 'Admin' 'Negative' 'Ekstensi style tidak valid' 'Admin login' 'ZIP/PDF/MP3' '1. Upload' 'Error bag uploadStyle tampil dan record tidak dibuat'
Add-Case 'Admin Style' 'Upload' 'Admin' 'Negative' 'Style lebih 50MB' 'Admin login' 'File >51200KB' '1. Upload' 'Validasi ukuran tampil'
Add-Case 'Admin Style' 'Upload' 'Admin' 'Negative' 'Kategori atau pack tidak valid' 'Admin login' 'Nilai manipulasi' '1. Submit' 'Validasi Rule::in tampil'
Add-Case 'Admin Style' 'Publish' 'Admin' 'Positive' 'Aktifkan style lengkap' 'Pack dan style file tersedia' 'Status Draft' '1. Klik Activate' 'Status Published dan customer non-suspended mendapat notifikasi'
Add-Case 'Admin Style' 'Publish' 'Admin' 'Negative' 'Aktifkan style tanpa pack/file' 'Data legacy tidak lengkap' 'Status Draft' '1. Klik Activate' 'Publish ditolak dan daftar komponen kurang tampil'
Add-Case 'Admin Style' 'Update' 'Admin' 'Positive' 'Edit nama/kategori/deskripsi' 'Style ada' 'Data valid' '1. Edit; 2. Simpan' 'Data berubah dan notifikasi hanya dikirim bila Published'
Add-Case 'Admin Style' 'Update' 'Admin' 'Negative' 'Deskripsi >1000 atau nama >140' 'Style ada' 'Data terlalu panjang' '1. Simpan' 'Validasi tampil, data lama tetap'
Add-Case 'Admin Style' 'Deactivate' 'Admin' 'Positive' 'Pindahkan Published ke Draft' 'Style Published' 'ID style' '1. Klik Deactivate' 'Status Draft dan hilang dari katalog customer'
Add-Case 'Admin Style' 'Delete' 'Admin' 'Positive' 'Hapus style' 'Style ada' 'ID style' '1. Hapus; 2. Konfirmasi' 'Record hilang dan tidak dapat diakses customer'
Add-Case 'Admin Sampling' 'Payment' 'Admin' 'Positive' 'Konfirmasi manual pembayaran sampling' 'Request Pending' 'Amount tepat 800000' '1. Isi nominal; 2. Confirm' 'Payment Completed dibuat/diupdate, request Paid, upload N27 terbuka'
Add-Case 'Admin Sampling' 'Payment' 'Admin' 'Negative' 'Konfirmasi nominal salah' 'Request Pending' '0 atau bukan 800000' '1. Confirm' 'Ditolak dan payment_status tetap Pending' 'Critical'
Add-Case 'Admin Sampling' 'N27 Download' 'Admin' 'Positive' 'Download N27 customer' 'File N27 ada di storage' 'Request valid' '1. Klik Download N27' 'File terkirim memakai nama asli/fallback'
Add-Case 'Admin Sampling' 'N27 Download' 'Admin' 'Negative' 'Download N27 belum tersedia/hilang' 'Path kosong atau file hilang' 'Request valid' '1. Klik Download' 'Pesan file belum tersedia, tanpa server error'
Add-Case 'Admin Sampling' 'Processing' 'Admin' 'Positive' 'Mulai proses N27' 'Payment Paid dan N27 uploaded' 'Request valid' '1. Klik Processing' 'Status menjadi Processing'
Add-Case 'Admin Sampling' 'Processing' 'Admin' 'Negative' 'Processing sebelum Paid' 'Payment Pending' 'Request valid' '1. Klik Processing' 'Ditolak dan status tidak berubah'
Add-Case 'Admin Sampling' 'Processing' 'Admin' 'Negative' 'Processing tanpa N27' 'Payment Paid, file belum upload' 'Request valid' '1. Klik Processing' 'Ditolak dan status tidak berubah'
Add-Case 'Admin Sampling' 'Delivery' 'Admin' 'Positive' 'Simpan link hasil Ready' 'Paid dan N27 tersedia' 'URL Google Drive valid, status Ready' '1. Isi link/catatan; 2. Simpan' 'Link tampil ke customer, delivered_at terisi, status Ready'
Add-Case 'Admin Sampling' 'Delivery' 'Admin' 'Positive' 'Tandai delivery Completed' 'Paid dan N27 tersedia' 'URL valid, status Completed' '1. Simpan' 'completed_at terisi dan customer melihat hasil final'
Add-Case 'Admin Sampling' 'Delivery' 'Admin' 'Negative' 'Delivery sebelum Paid atau tanpa N27' 'Prerequisite belum lengkap' 'URL valid' '1. Simpan delivery' 'Ditolak dan link tidak dipublikasikan' 'Critical'
Add-Case 'Admin Sampling' 'Delivery' 'Admin' 'Negative' 'Link atau status delivery invalid' 'Paid dan N27 tersedia' 'URL invalid/status selain Ready-Completed' '1. Simpan' 'Validasi tampil dan data tidak berubah'
Add-Case 'Admin Downloads' 'Sales List' 'Admin' 'Positive' 'Daftar download sales akurat' 'Ada beberapa download style' 'Filter/tabel halaman' '1. Buka Download Sales' 'Data customer, style, filename, status, waktu, tipe sesuai record'
Add-Case 'Admin Users' 'List' 'Admin' 'Positive' 'Daftar hanya customer dengan ringkasan' 'Ada admin dan customer' 'Buka User Management' '1. Buka halaman' 'Hanya customer tampil beserta counts, latest payment/subscription/request/download'
Add-Case 'Admin Users' 'Status' 'Admin' 'Positive' 'Ubah status customer' 'Customer ada' 'Active/Review/Suspended' '1. Pilih status; 2. Simpan' 'Status dan last_activity diperbarui'
Add-Case 'Admin Users' 'Status' 'Admin' 'Positive' 'Suspend sekaligus cancel subscription' 'Customer punya subscription Active' 'Suspended + cancel=true' '1. Simpan' 'Subscription Cancelled dan plan menjadi Free'
Add-Case 'Admin Users' 'Status' 'Admin' 'Negative' 'Status di luar opsi' 'Customer ada' 'Deleted/Blocked' '1. Manipulasi payload' 'Validasi menolak'
Add-Case 'Admin Users' 'Scope' 'Admin' 'Negative' 'Operasi user management terhadap admin' 'Target role admin' 'ID admin' '1. Panggil endpoint status/plan/password' 'HTTP 404 dan admin tidak berubah' 'Critical'
Add-Case 'Admin Users' 'Plan' 'Admin' 'Positive' 'Upgrade plan manual' 'Customer ada' 'Plan premium valid + expiry today/future' '1. Pilih plan; 2. Simpan' 'Subscription aktif baru dibuat, active lama dibatalkan, user Active'
Add-Case 'Admin Users' 'Plan' 'Admin' 'Positive' 'Turunkan plan ke Free' 'Customer premium' 'Free' '1. Update plan' 'Semua subscription Active Cancelled dan plan Free'
Add-Case 'Admin Users' 'Plan' 'Admin' 'Negative' 'Plan invalid atau expiry masa lalu' 'Customer ada' 'Plan acak/tanggal kemarin' '1. Submit' 'Validasi menolak dan subscription tidak berubah'
Add-Case 'Admin Users' 'Password' 'Admin' 'Positive' 'Admin reset password customer' 'Customer ada' 'Password >=8 confirmed' '1. Isi password; 2. Simpan; 3. Login customer' 'Hash password berubah dan customer dapat login dengan password baru'
Add-Case 'Admin Users' 'Password' 'Admin' 'Negative' 'Reset password tidak confirmed/<8' 'Customer ada' 'Password invalid' '1. Submit' 'Validasi menolak dan password lama tetap'
Add-Case 'Admin Users' 'Sync Access' 'Admin' 'Positive' 'Sinkron legacy pending access' 'Pending subscription/payment ada' 'Customer valid' '1. Klik Sync Access' 'Subscription Active, payment Completed, plan/status user sinkron'
Add-Case 'Admin Users' 'Sync Access' 'Admin' 'Negative' 'Sync tanpa legacy pending data' 'Tidak ada pending payment/subscription' 'Customer valid' '1. Klik Sync' 'Pesan tidak ada data pending dan tidak membuat akses'
Add-Case 'Admin Subscription' 'List' 'Admin' 'Positive' 'Lifecycle subscription diklasifikasi benar' 'Data Active/Expiring/Expired/Pending/Cancelled' 'Tanggal batas 7 hari' '1. Buka halaman' 'Status lifecycle, urutan, summary, amount sesuai aturan'
Add-Case 'Admin Subscription' 'Renew' 'Admin' 'Positive' 'Renew Expiring Soon/Expired/Cancelled/Pending' 'Subscription bukan Active normal' 'Subscription valid' '1. Klik Renew' 'Status Active, expiry ditambah sesuai package, user Active, payment terkait Completed'
Add-Case 'Admin Subscription' 'Renew' 'Admin' 'Negative' 'Renew subscription masih Active di luar window' 'Lifecycle Active' 'Subscription valid' '1. Klik Renew' 'Ditolak agar tidak memperpanjang manual terlalu awal'
Add-Case 'Admin Subscription' 'Suspend' 'Admin' 'Positive' 'Cancel subscription' 'Subscription ada' 'ID subscription' '1. Klik Suspend/Cancel' 'Subscription Cancelled dan user plan Free'
Add-Case 'Admin Settings' 'Subscription' 'Admin' 'Positive' 'Simpan harga dan durasi plan' 'Admin login' 'Harga berformat rupiah; durasi valid' '1. Ubah setting; 2. Simpan; 3. Buka checkout' 'Nilai tersimpan dan checkout memakai harga/package baru'
Add-Case 'Admin Settings' 'Midtrans' 'Admin' 'Positive' 'Simpan konfigurasi sandbox/production' 'Admin login' 'Keys <=180, environment 0/1' '1. Isi setting; 2. Simpan' 'Nilai tersimpan dan label endpoint mengikuti environment'
Add-Case 'Admin Settings' 'Validation' 'Admin' 'Negative' 'Harga tanpa digit atau field wajib kosong' 'Admin login' 'abc/kosong' '1. Submit' 'Validasi tampil dan setting lama tetap'
Add-Case 'Admin Settings' 'Validation' 'Admin' 'Negative' 'Durasi/environment tidak valid' 'Admin login' 'duration acak; production=2' '1. Manipulasi payload' 'Validasi Rule::in menolak'

# Cross-cutting quality
Add-Case 'Security' 'CSRF' 'Any' 'Negative' 'Mutasi tanpa CSRF token' 'Session browser aktif' 'POST/PATCH/PUT/DELETE tanpa token' '1. Kirim request mutasi tanpa CSRF' 'Request ditolak 419 dan data tidak berubah' 'Critical'
Add-Case 'Security' 'IDOR' 'Customer' 'Negative' 'Manipulasi ID resource customer lain' 'Dua customer memiliki order berbeda' 'ID order/download B' '1. Customer A ubah URL ID' 'Akses ditolak dan tidak ada data/file B terbuka' 'Critical'
Add-Case 'Security' 'XSS' 'Customer/Admin' 'Negative' 'Input script pada text field' 'Form notes/name tersedia' '<script>alert(1)</script>' '1. Simpan input; 2. Buka halaman terkait' 'Script tidak dieksekusi; output di-escape'
Add-Case 'Security' 'File Upload' 'Customer/Admin' 'Negative' 'File executable dengan ekstensi palsu' 'Upload tersedia' 'payload PHP/HTML rename ke ekstensi allowed' '1. Upload; 2. Coba akses publik' 'File berbahaya ditolak/tidak dieksekusi' 'Critical'
Add-Case 'Usability' 'Responsive' 'All' 'Positive' 'Layout utama responsif' 'Halaman tersedia' '360px, 768px, 1366px' '1. Uji login, reset, customer, admin pada viewport' 'Tidak ada overlap, horizontal scroll tak perlu, aksi tetap dapat digunakan'
Add-Case 'Compatibility' 'Browser' 'All' 'Positive' 'Flow kritis lintas browser' 'Browser versi supported' 'Chrome, Edge, Firefox, mobile' '1. Jalankan login, checkout, upload, download' 'Perilaku dan tampilan konsisten'
Add-Case 'Reliability' 'Double Submit' 'Customer/Admin' 'Negative' 'Klik submit dua kali pada transaksi' 'Form payment/upload aktif' 'Double click/network lambat' '1. Klik dua kali cepat' 'Tidak membuat duplikat order/payment yang tidak semestinya' 'Critical'
Add-Case 'Performance' 'Page Load' 'All' 'Positive' 'Halaman dengan volume data besar' 'Minimal 1.000 demo/style/user/request' 'Dataset besar' '1. Buka list/dashboard; 2. Ukur respons' 'Halaman tetap stabil; target respons disepakati QA/Product'

$headers = @('Test Case ID','Module','Submodule','Role','Type','Priority','Scenario / Objective','Precondition','Test Data','Test Steps','Expected Result','Actual Result','Status','Severity if Fail','PIC','Test Date','Evidence Link','Defect ID','Notes')
$rows = [System.Collections.Generic.List[object]]::new()
foreach ($c in $cases) { $rows.Add(@($c.ID,$c.Module,$c.Submodule,$c.Role,$c.Type,$c.Priority,$c.Scenario,$c.Precondition,$c.TestData,$c.Steps,$c.Expected,$c.Actual,$c.Status,$c.Severity,$c.PIC,$c.TestDate,$c.Evidence,$c.DefectID,$c.Notes)) }

function XmlEscape([object]$value) { if ($null -eq $value) { return '' }; return [System.Security.SecurityElement]::Escape([string]$value) }
function ColName([int]$n) { $s=''; while($n -gt 0){$n--; $s=[char](65+($n%26))+$s; $n=[math]::Floor($n/26)}; $s }
function SheetXml($data,[int[]]$widths,$autoFilter=$true,$freeze=$true,$validations='') {
    $sb=[Text.StringBuilder]::new(); [void]$sb.Append('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">')
    if($freeze){[void]$sb.Append('<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>')}
    [void]$sb.Append('<cols>'); for($i=0;$i -lt $widths.Count;$i++){[void]$sb.Append('<col min="'+($i+1)+'" max="'+($i+1)+'" width="'+$widths[$i]+'" customWidth="1"/>')}; [void]$sb.Append('</cols><sheetData>')
    for($r=0;$r -lt $data.Count;$r++){ $rn=$r+1; [void]$sb.Append('<row r="'+$rn+'">'); for($c=0;$c -lt $data[$r].Count;$c++){ $ref=(ColName ($c+1))+$rn; $style=if($r -eq 0){1}elseif($c -ge 6 -and $c -le 10){2}else{3}; $value=[string]$data[$r][$c]; if($value.StartsWith('=')){[void]$sb.Append('<c r="'+$ref+'" s="'+$style+'"><f>'+(XmlEscape $value.Substring(1))+'</f><v>0</v></c>')}else{[void]$sb.Append('<c r="'+$ref+'" t="inlineStr" s="'+$style+'"><is><t xml:space="preserve">'+(XmlEscape $value)+'</t></is></c>')} }; [void]$sb.Append('</row>') }
    [void]$sb.Append('</sheetData>'); if($autoFilter){$last=ColName $data[0].Count; [void]$sb.Append('<autoFilter ref="A1:'+$last+$data.Count+'"/>')}; if($validations){[void]$sb.Append($validations)}; [void]$sb.Append('</worksheet>'); return $sb.ToString()
}

$summary=[System.Collections.Generic.List[object]]::new()
$summary.Add(@('HM Music Production - QA Test Tracking','Value')); $summary.Add(@('Generated', (Get-Date -Format 'yyyy-MM-dd HH:mm')))
$summary.Add(@('Total Test Cases', [string]$cases.Count)); $summary.Add(@('Positive', [string](@($cases|Where-Object Type -eq 'Positive').Count)))
$summary.Add(@('Negative', [string](@($cases|Where-Object Type -eq 'Negative').Count))); $summary.Add(@('Critical Priority', [string](@($cases|Where-Object Priority -eq 'Critical').Count)))
$summary.Add(@('High Priority', [string](@($cases|Where-Object Priority -eq 'High').Count))); $summary.Add(@('How to use','Isi Actual Result, Status, PIC, Test Date, Evidence Link, Defect ID, dan Notes pada sheet Test Cases.'))
$summary.Add(@('Status options','Not Run, In Progress, Passed, Failed, Blocked, Retest, N/A')); $summary.Add(@('Progress formula','Lihat sheet Execution Summary; angka otomatis berubah saat Status pada Test Cases diperbarui.'))
$moduleNames=@($cases.Module|Sort-Object -Unique)
$exec=[System.Collections.Generic.List[object]]::new(); $exec.Add(@('Module','Total','Not Run','In Progress','Passed','Failed','Blocked','Retest','N/A','Pass Rate'))
for($i=0;$i -lt $moduleNames.Count;$i++){ $row=$i+2; $m=$moduleNames[$i]; $exec.Add(@($m,"=COUNTIF('Test Cases'!B:B,A$row)","=COUNTIFS('Test Cases'!B:B,A$row,'Test Cases'!M:M,C`$1)","=COUNTIFS('Test Cases'!B:B,A$row,'Test Cases'!M:M,D`$1)","=COUNTIFS('Test Cases'!B:B,A$row,'Test Cases'!M:M,E`$1)","=COUNTIFS('Test Cases'!B:B,A$row,'Test Cases'!M:M,F`$1)","=COUNTIFS('Test Cases'!B:B,A$row,'Test Cases'!M:M,G`$1)","=COUNTIFS('Test Cases'!B:B,A$row,'Test Cases'!M:M,H`$1)","=COUNTIFS('Test Cases'!B:B,A$row,'Test Cases'!M:M,I`$1)","=IFERROR(E$row/(B$row-I$row),0)")) }
$defects=[System.Collections.Generic.List[object]]::new(); $defects.Add(@('Defect ID','Test Case ID','Title','Module','Severity','Priority','Status','Assignee','Reported Date','Retest Date','Environment','Steps / Description','Expected','Actual','Evidence Link','Notes'))
$reference=[System.Collections.Generic.List[object]]::new()
$reference.Add(@('Field','Allowed Values / Guidance')); $reference.Add(@('Test Status','Not Run | In Progress | Passed | Failed | Blocked | Retest | N/A'))
$reference.Add(@('Severity','Critical | Major | Minor | Trivial')); $reference.Add(@('Priority','Critical | High | Medium | Low'))
$reference.Add(@('Defect Status','Open | Assigned | In Progress | Fixed | Retest | Reopened | Closed | Rejected | Deferred')); $reference.Add(@('Suggested Environment','Local | Development | Staging | Production Smoke'))
$reference.Add(@('Scope Note','Cases diturunkan dari routes, controllers, validation rules, models, middleware, services, dan automated feature tests pada project.'))
$reference.Add(@('Business Values','Subscription default Rp55.000; sampling pack Rp800.000; N27 max 100MB; style max 50MB; cover max 5MB; demo MP4 max 200MB.'))
$reference.Add(@('Execution Rule','Satu baris untuk satu hasil eksekusi. Bila perlu multi-browser/build, duplikasi test case atau catat run terpisah.'))
$reference.Add(@('Evidence','Masukkan link screenshot/video/log yang dapat diakses tim.')); $reference.Add(@('Defect Linkage','Jika Failed, buat Defect ID lalu isi ID yang sama di Test Cases dan Defect Log.'))

$validation='<dataValidations count="4"><dataValidation type="list" allowBlank="1" sqref="M2:M1048576"><formula1>"Not Run,In Progress,Passed,Failed,Blocked,Retest,N/A"</formula1></dataValidation><dataValidation type="list" allowBlank="1" sqref="N2:N1048576"><formula1>"Critical,Major,Minor,Trivial"</formula1></dataValidation><dataValidation type="list" allowBlank="1" sqref="F2:F1048576"><formula1>"Critical,High,Medium,Low"</formula1></dataValidation><dataValidation type="list" allowBlank="1" sqref="E2:E1048576"><formula1>"Positive,Negative"</formula1></dataValidation></dataValidations>'

$temp=Join-Path ([IO.Path]::GetTempPath()) ('hm-qa-'+[guid]::NewGuid()); New-Item -ItemType Directory -Path $temp | Out-Null
@('_rels','docProps','xl','xl\_rels','xl\worksheets') | ForEach-Object { New-Item -ItemType Directory -Path (Join-Path $temp $_) -Force | Out-Null }
function WriteUtf8($path,$text){[IO.File]::WriteAllText($path,$text,[Text.UTF8Encoding]::new($false))}
WriteUtf8 (Join-Path $temp '[Content_Types].xml') '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet4.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet5.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>'
WriteUtf8 (Join-Path $temp '_rels\.rels') '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>'
WriteUtf8 (Join-Path $temp 'xl\workbook.xml') '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Summary" sheetId="1" r:id="rId1"/><sheet name="Test Cases" sheetId="2" r:id="rId2"/><sheet name="Execution Summary" sheetId="3" r:id="rId3"/><sheet name="Defect Log" sheetId="4" r:id="rId4"/><sheet name="Reference" sheetId="5" r:id="rId5"/></sheets></workbook>'
WriteUtf8 (Join-Path $temp 'xl\_rels\workbook.xml.rels') '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/><Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet4.xml"/><Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet5.xml"/><Relationship Id="rId6" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>'
$styles='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF7030A0"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border/><border><left style="thin"><color rgb="FFD9E1F2"/></left><right style="thin"><color rgb="FFD9E1F2"/></right><top style="thin"><color rgb="FFD9E1F2"/></top><bottom style="thin"><color rgb="FFD9E1F2"/></bottom></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top"/></xf></cellXfs></styleSheet>'
WriteUtf8 (Join-Path $temp 'xl\styles.xml') $styles
WriteUtf8 (Join-Path $temp 'xl\worksheets\sheet1.xml') (SheetXml $summary @(38,105) $false $true)
$testCaseData=[System.Collections.Generic.List[object]]::new(); $testCaseData.Add($headers); foreach($row in $rows){$testCaseData.Add($row)}
WriteUtf8 (Join-Path $temp 'xl\worksheets\sheet2.xml') (SheetXml $testCaseData @(14,24,22,18,12,12,42,42,34,58,58,45,14,16,18,16,28,16,36) $true $true $validation)
WriteUtf8 (Join-Path $temp 'xl\worksheets\sheet3.xml') (SheetXml $exec @(30,12,14,14,12,12,12,12,10,14) $true $true)
WriteUtf8 (Join-Path $temp 'xl\worksheets\sheet4.xml') (SheetXml $defects @(15,15,35,25,12,12,16,20,16,16,22,55,45,45,28,35) $true $true)
WriteUtf8 (Join-Path $temp 'xl\worksheets\sheet5.xml') (SheetXml $reference @(26,110) $true $true)

$fullOutput=Join-Path (Get-Location) $OutputPath; $parent=Split-Path $fullOutput -Parent; New-Item -ItemType Directory -Path $parent -Force | Out-Null
$zip=$fullOutput+'.zip'; if(Test-Path $zip){Remove-Item -LiteralPath $zip}; if(Test-Path $fullOutput){Remove-Item -LiteralPath $fullOutput}
Compress-Archive -Path (Join-Path $temp '*') -DestinationPath $zip -CompressionLevel Optimal
Move-Item -LiteralPath $zip -Destination $fullOutput
Remove-Item -LiteralPath $temp -Recurse -Force
Write-Output "Created $fullOutput with $($cases.Count) test cases."
