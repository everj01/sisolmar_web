@extends('layouts.vertical', ['title' => 'Gestión de documentos'])
@section('css')
@endsection
@section('content')
@include("layouts.shared/page-title", ["subtitle" => "Enterprise", "title" => "Gestión de documentos"])




@endsection
@section('script')
@endsection
@vite(['resources/js/functions/enterprise/gestion_documentos.js'])