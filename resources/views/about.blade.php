@extends('layout.main')

@section('container')
    <h1>saya adalah :</h1>
    <ul>
        <li>nama : {{ $nama }}</li>
        <li>prodi : {{ $prodi }}</li>
        <li>umur : {{ $umur }}</li>
    </ul>
@endsection