@extends('layout.main')

@section('container')
  @foreach ($post as $data)
    <h1><a href="/blog-post/{{ $data['slug'] }}">{{ $data['judul-post'] }}</a></h1>
    <h2>pembuat : {{ $data['author'] }}</h2>
    <p>{{ $data['blog'] }}</p>
  @endforeach
@endsection
