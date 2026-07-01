@extends('layouts.vertical', ['title' => 'Gestión de Cargos'])

@section('css')

@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Recursos Humanos", "title" => "Dashboard"])


<h5>Dashboard del FILE CONTROL</h5>

@endsection

@vite(['resources/js/functions/cargo.js'])
@section('script')

@endsection