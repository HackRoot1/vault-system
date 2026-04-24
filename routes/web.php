<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/register', function () {
    return view('register');
})->name('register');

Route::get('/dashboard', function () {
    return view('dashboard', ['section' => 'dashboard']);
})->name('dashboard');

Route::get('/vaults', function () {
    return view('dashboard', ['section' => 'vaults']);
})->name('vaults');

Route::get('/items', function () {
    return view('dashboard', ['section' => 'items']);
})->name('items');

Route::get('/files', function () {
    return view('dashboard', ['section' => 'files']);
})->name('files');

Route::get('/settings', function () {
    return view('dashboard', ['section' => 'settings']);
})->name('settings');
