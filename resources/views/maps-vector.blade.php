@extends('layouts.vertical', ['title' => 'Dashboard'])

@section('css')
@vite(['node_modules/jsvectormap/dist/css/jsvectormap.min.css'])
@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Maps", "title" => "Vector"])

<div class="grid lg:grid-cols-2 grid-cols-1 gap-6">

    <div class="lg:col-span-2">
        <div class="card">
            <div class="p-6">
                <h4 class="card-title mb-4">World Vector Map</h4>

                <div class="mb-3">
                    <div id="world-map-markers" style="height: 360px"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="p-6">
            <h4 class="card-title mb-4">Canada Vector Map</h4>

            <div class="mb-3">
                <div id="canada-vector-map" style="height: 360px"></div>
            </div>
        </div>
    </div>


    <div class="card">
        <div class="p-6">
            <h4 class="card-title mb-4">Russia Vector Map</h4>

            <div class="mb-3">
                <div id="russia-vector-map" style="height: 360px"></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="p-6">
            <h4 class="card-title mb-4">Italy Vector Map</h4>

            <div class="mb-3">
                <div id="italy-vector-map" style="height: 360px"></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="p-6">
            <h4 class="card-title mb-4">Iraq Vector Map</h4>

            <div class="mb-3">
                <div id="iraq-vector-map" style="height: 360px"></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="p-6">
            <h4 class="card-title mb-4">Spain Vector Map</h4>

            <div class="mb-3">
                <div id="spain-vector-map" style="height: 360px"></div>
            </div>
        </div>
    </div> <!-- end card body -->

</div><!-- end grid-->

@endsection

@section('script')
@vite(['resources/js/pages/maps-vector.js'])
@endsection