<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('landing'))->name('home');

require __DIR__.'/auth.php';
require __DIR__.'/super-admin.php';
require __DIR__.'/agency.php';
