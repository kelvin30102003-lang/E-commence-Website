<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home');
})->name('home');

Route::view('/shop', 'pages.shop')->name('shop');
Route::view('/contact', 'pages.contact')->name('contact');
