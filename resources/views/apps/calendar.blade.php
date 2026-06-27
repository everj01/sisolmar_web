@extends('layouts.vertical', ['title' => 'Calendar'])

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Apps", "title" => "Calendar"])

<div class="card">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

@endsection

@section('script')
@vite(['resources/js/pages/app-calendar.js'])
@endsection