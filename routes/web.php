<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('landing'))->name('home');

Route::get('/sitemap.xml', function () {
    $urls = [url('/'), route('login'), route('register')];
    $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $u) {
        $xml .= '<url><loc>'.e($u).'</loc><lastmod>'.now()->toDateString().'</lastmod></url>';
    }

    return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

require __DIR__.'/auth.php';
require __DIR__.'/super-admin.php';
require __DIR__.'/agency.php';
require __DIR__.'/erp.php';
