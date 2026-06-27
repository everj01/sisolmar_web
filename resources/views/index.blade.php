@extends('layouts.vertical', ['title' => 'Dashboard'])

@section('css')
@vite(['node_modules/jsvectormap/dist/css/jsvectormap.min.css'])
@endsection

@section('content')
<!-- Start Content-->
@include('layouts.shared/page-title', ['subtitle' => 'Inicio', 'title' => 'Dashboard'])





@endsection

@section('script')
@vite(['resources/js/pages/dashboard.js'])
@endsection