@extends('layouts.vertical', ['title' => 'Gallery'])


@section('css')
@vite(['node_modules/glightbox/dist/css/glightbox.min.css'])
@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Apps", "title" => "Gallery"])

<div class="w-full filters-group-wrap mb-3">
    <ul class="filter-options flex flex-wrap gap-4">
        <li class="active" data-group="all"><a href="javascript:void(0)" class="btn">All
                Items</a></li>
        <li data-group="design"><a href="javascript:void(0)" class="btn">Design</a></li>
        <li data-group="creative"><a href="javascript:void(0)" class="btn">Creative</a>
        </li>
        <li data-group="digital"><a href="javascript:void(0)" class="btn">Digital</a>
        </li>
        <li data-group="photography"><a href="javascript:void(0)" class="btn">Photography</a>
        </li>
    </ul>
</div>

<div id="gallery-wrapper" class="flex justify-center">
    <div class="xl:w-1/4 lg:w-1/3 p-3 picture-item" data-groups='["creative", "photography"]'>
        <a class="image-popup" href="/images/small/img-1.jpg">
            <div class="relative block overflow-hidden rounded group transition-all duration-500">
                <img src="/images/small/img-1.jpg" class="rounded transition-all duration-500 group-hover:scale-105" alt="work-image">
                <div class="absolute inset-3 flex items-end cursor-pointer rounded bg-white p-3 opacity-0 transition-all duration-500 group-hover:opacity-80">
                    <div>
                        <p class="text-sm text-default-400">Media, Icons</p>
                        <h6 class="text-base text-black font-medium">Open Imagination</h6>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="xl:w-1/4 lg:w-1/3 p-3 picture-item" data-groups='["design", "digital"]'>
        <a class="image-popup" href="/images/small/img-2.jpg">
            <div class="relative block overflow-hidden rounded group transition-all duration-500">
                <img src="/images/small/img-2.jpg" class="rounded transition-all duration-500 group-hover:scale-105" alt="work-image">
                <div class="absolute inset-3 flex items-end cursor-pointer rounded bg-white p-3 opacity-0 transition-all duration-500 group-hover:opacity-80">
                    <div>
                        <p class="text-sm text-default-400">Illustrations</p>
                        <h6 class="text-base text-black font-medium">Locked Steel Gate</h6>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="xl:w-1/4 lg:w-1/3 p-3 picture-item" data-groups='["creative", "photography"]'>
        <a class="image-popup" href="/images/small/img-3.jpg">
            <div class="relative block overflow-hidden rounded group transition-all duration-500">
                <img src="/images/small/img-3.jpg" class="rounded transition-all duration-500 group-hover:scale-105" alt="work-image">
                <div class="absolute inset-3 flex items-end cursor-pointer rounded bg-white p-3 opacity-0 transition-all duration-500 group-hover:opacity-80">
                    <div>
                        <p class="text-sm text-default-400">Graphics, UI Elements</p>
                        <h6 class="text-base text-black font-medium">Mac Sunglasses</h6>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="xl:w-1/4 lg:w-1/3 p-3 picture-item" data-groups='["design", "photography"]'>
        <a class="image-popup" href="/images/small/img-4.jpg">
            <div class="relative block overflow-hidden rounded group transition-all duration-500">
                <img src="/images/small/img-4.jpg" class="rounded transition-all duration-500 group-hover:scale-105" alt="work-image">
                <div class="absolute inset-3 flex items-end cursor-pointer rounded bg-white p-3 opacity-0 transition-all duration-500 group-hover:opacity-80">
                    <div>
                        <p class="text-sm text-default-400">Icons, Illustrations</p>
                        <h6 class="text-base text-black font-medium">Morning Dew</h6>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="xl:w-1/4 lg:w-1/3 p-3 picture-item" data-groups='["photography", "design"]'>
        <a class="image-popup" href="/images/small/img-5.jpg">
            <div class="relative block overflow-hidden rounded group transition-all duration-500">
                <img src="/images/small/img-5.jpg" class="rounded transition-all duration-500 group-hover:scale-105" alt="work-image">
                <div class="absolute inset-3 flex items-end cursor-pointer rounded bg-white p-3 opacity-0 transition-all duration-500 group-hover:opacity-80">
                    <div>
                        <p class="text-sm text-default-400">UI Elements, Media</p>
                        <h6 class="text-base text-black font-medium">Console Activity</h6>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="xl:w-1/4 lg:w-1/3 p-3 picture-item" data-groups='["digital", "creative"]'>
        <a class="image-popup" href="/images/small/img-6.jpg">
            <div class="relative block overflow-hidden rounded group transition-all duration-500">
                <img src="/images/small/img-6.jpg" class="rounded transition-all duration-500 group-hover:scale-105" alt="work-image">
                <div class="absolute inset-3 flex items-end cursor-pointer rounded bg-white p-3 opacity-0 transition-all duration-500 group-hover:opacity-80">
                    <div>
                        <p class="text-sm text-default-400">Graphics</p>
                        <h6 class="text-base text-black font-medium">Sunset Bulb Glow</h6>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="xl:w-1/4 lg:w-1/3 p-3 picture-item" data-groups='["creative", "digital"]'>
        <a class="image-popup" href="/images/small/img-7.jpg">
            <div class="relative block overflow-hidden rounded group transition-all duration-500">
                <img src="/images/small/img-7.jpg" class="rounded transition-all duration-500 group-hover:scale-105" alt="work-image">
                <div class="absolute inset-3 flex items-end cursor-pointer rounded bg-white p-3 opacity-0 transition-all duration-500 group-hover:opacity-80">
                    <div>
                        <p class="text-sm text-default-400">Icons, Illustrations</p>
                        <h6 class="text-base text-black font-medium">Morning Dew</h6>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="xl:w-1/4 lg:w-1/3 p-3 picture-item" data-groups='["design", "digital"]'>
        <a class="image-popup" href="/images/small/img-5.jpg">
            <div class="relative block overflow-hidden rounded group transition-all duration-500">
                <img src="/images/small/img-5.jpg" class="rounded transition-all duration-500 group-hover:scale-105" alt="work-image">
                <div class="absolute inset-3 flex items-end cursor-pointer rounded bg-white p-3 opacity-0 transition-all duration-500 group-hover:opacity-80">
                    <div>
                        <p class="text-sm text-default-400">Illustrations</p>
                        <h6 class="text-base text-black font-medium">Locked Steel Gate</h6>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

@endsection

@section('script')
@vite(['resources/js/pages/gallery.js'])
@endsection