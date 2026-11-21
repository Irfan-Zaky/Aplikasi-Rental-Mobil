<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', [
        'title' => 'home',
    ]);
});

Route::get('/about', function () {
    return view('about', [
        'title' => 'about',
        'nama' => 'fadhil',
        'prodi' => 'trpl',
        'umur' => '17 tahun',
    ]);
});

Route::get('/blog', [PostController::class, 'index']);

Route::get('/blog-post/{id}', [PostController::class, 'blog']);
