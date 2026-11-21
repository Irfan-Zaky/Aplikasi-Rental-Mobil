@extends('layout.main')

@section('container')
    <h1>{{ $post['judul-post'] }}</h1>
    <h2>pembuat : {{ $post['author'] }}</h2>
    <p>{{ $post['blog'] }}</p>
    <a href="/blog">Kembali ke Blog</a>
@endsection
