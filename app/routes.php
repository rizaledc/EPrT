<?php
declare(strict_types=1);

/**
 * Daftar rute: [METHOD|METHOD2, pola, 'Controller@method'].
 * Pola memakai {nama} untuk segmen dinamis (tanpa garis miring).
 */
return [
    ['GET',      '/',                                   'HomeController@landing'],
    ['GET',      '/kamus',                              'HomeController@glossary'],
    ['GET',      '/syarat-ketentuan',                  'LegalController@terms'],
    ['GET',      '/privasi',                           'LegalController@privacy'],
    ['GET',      '/pengembalian-dana',                 'LegalController@refund'],
    ['GET',      '/kontak',                            'LegalController@contactPage'],
    ['GET',      '/harga',                             'LegalController@pricing'],
    ['GET',      '/bahasa/{lang}',                      'HomeController@setLang'],
    ['GET',      '/robots.txt',                         'HomeController@robots'],
    ['GET',      '/sitemap.xml',                        'HomeController@sitemap'],
    ['GET',      '/coba',                               'PracticeController@sample'],

    ['GET|POST', '/daftar',                             'AuthController@register'],
    ['GET|POST', '/daftar/kode-pemulihan',              'AuthController@recoveryCode'],
    ['GET|POST', '/masuk',                              'AuthController@login'],
    ['POST',     '/keluar',                             'AuthController@logout'],
    ['GET|POST', '/lupa-password',                      'AuthController@forgot'],
    ['GET|POST', '/akun',                               'AccountController@index'],
    ['GET|POST', '/install',                            'InstallController@index'],

    ['GET',      '/latihan',                            'PracticeController@map'],
    ['GET',      '/latihan/{blok}/{rumah}',             'PracticeController@rumah'],
    ['GET',      '/latihan/{blok}/{rumah}/{seksi}',     'PracticeController@session'],
    ['GET',      '/simulasi/{n}',                       'PracticeController@simulation'],
    ['GET',      '/simulasi/{n}/{seksi}',               'PracticeController@simulationSession'],
    ['GET',      '/sesi/{id}',                          'PracticeController@reviewSession'],
    ['POST',     '/ulangi/{id}',                        'PracticeController@retryWrong'],
    ['POST',     '/latih-ulang',                        'PracticeController@drillTag'],
    ['GET',      '/hasil/{id}',                         'PracticeController@result'],
    ['GET',      '/riwayat',                            'PracticeController@history'],

    ['GET',      '/rapor/akhir',                        'ReportController@final'],
    ['GET',      '/rapor/akhir.pdf',                    'ReportController@finalPdf'],
    ['GET',      '/rapor/{blok}.pdf',                   'ReportController@blokPdf'],

    ['GET',      '/api/attempt/{id}',                   'ApiController@attempt'],
    ['POST',     '/api/answer',                         'ApiController@answer'],
    ['POST',     '/api/flag',                           'ApiController@flag'],
    ['POST',     '/api/attempt/{id}/tick',              'ApiController@tick'],
    ['POST',     '/api/attempt/{id}/finish',            'ApiController@finish'],
    ['GET',      '/api/sample',                         'ApiController@sample'],
    ['POST',     '/api/sample/answer',                  'ApiController@sampleAnswer'],
    ['GET',      '/api/username-check',                 'ApiController@usernameCheck'],

    ['GET',      '/admin',                              'AdminController@dashboard'],
    ['GET',      '/admin/pengguna',                     'AdminController@users'],
    ['GET',      '/admin/pengguna.csv',                 'AdminController@usersCsv'],
    ['GET',      '/admin/pengguna/{id}',                'AdminController@userDetail'],
    ['POST',     '/admin/pengguna/{id}/aksi',           'AdminController@userAction'],
    ['GET',      '/admin/soal',                         'AdminController@questions'],
    ['GET|POST', '/admin/soal/baru',                    'AdminController@questionForm'],
    ['GET|POST', '/admin/soal/{id}',                    'AdminController@questionForm'],
    ['POST',     '/admin/soal/{id}/aksi',               'AdminController@questionAction'],
    ['GET',      '/admin/grup',                         'AdminController@passages'],
    ['GET|POST', '/admin/grup/baru',                    'AdminController@passageForm'],
    ['GET|POST', '/admin/grup/{id}',                    'AdminController@passageForm'],
    ['POST',     '/admin/audio/upload',                 'AdminController@audioUpload'],
    ['GET|POST', '/admin/impor',                        'AdminController@import'],
    ['GET',      '/admin/ekspor/{slug}',                'AdminController@export'],
    ['GET',      '/admin/ekspor-sql',                   'AdminController@exportSql'],
    ['GET|POST', '/admin/kamus',                        'AdminController@glossary'],
    ['GET|POST', '/admin/sponsor',                      'AdminController@sponsors'],
    ['GET|POST', '/admin/pengaturan',                   'AdminController@settings'],
    ['GET',      '/admin/log',                          'AdminController@log'],
];
