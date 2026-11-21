<?php

use App\Models\post;
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
    'umur' => '17 tahun'
    ]);
});



Route::get('/blog', function () {
     $web_post = [];
    return view('blog', [
        'title' => 'blog',
        'post' => post::all(),
    ]);
});

Route::get('/blog-post/{slug}', function (string $slug) {
    $web_post = post::all(); 
    $single_post = collect($web_post)->firstWhere('slug', $slug);

    if (! $single_post) {
        abort(404);
    }

    return view('blog-post', [
        'title' => 'Single Post',
        'post' => $single_post,
    ]);
});
