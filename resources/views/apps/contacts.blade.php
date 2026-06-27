@extends('layouts.vertical', ['title' => 'Contacts'])

@section('css')

@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Apps", "title" => "Contacts"])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

    <!-- Card 1 -->
    <div class="card overflow-hidden">
        <!-- Cover Image Section -->
        <div class="relative">
            <img class="w-full h-44 object-cover" src="/images/small/img-1.jpg"  alt="Cover Image">

            <!-- Profile Image with Status -->
            <div class="absolute -bottom-14 start-1/2 transform -translate-x-1/2">
                <div class="relative">
                    <img class="w-28 h-28 rounded-full border-4 border-white shadow-md" src="/images/users/avatar-1.jpg" alt="Profile Picture">
                    <span class="absolute bottom-1 end-1 w-5 h-5 bg-green-500 border-2 border-white rounded-full"></span>
                </div>
            </div>
        </div>

        <!-- Name, Title, and Location -->
        <div class="pt-16 pb-4 text-center">
            <h2 class="text-xl font-semibold text-default-700 mb-2">Morgan Alexander</h2>
            <p class="text-default-600 text-sm">Los Angeles, CA</p>
            <span class="text-xs text-default-400">Professional Photographer</span>
        </div>

        <!-- Social Icons Section -->
        <div class="py-4 px-6">
            <div class="flex justify-center space-x-6">
                <a href="#" class="text-primary hover:text-blue-700 transition-all">
                    <i class="i-ph-facebook-logo text-3xl"></i>
                </a>
                <a href="#" class="text-pink-500 hover:text-pink-700 transition-all">
                    <i class="i-ph-instagram-logo text-3xl"></i>
                </a>
                <a href="#" class="text-blue-700 hover:text-blue-900 transition-all">
                    <i class="i-ph-linkedin-logo text-3xl"></i>
                </a>
                <a href="#" class="text-default-700 hover:text-default-900 transition-all">
                    <i class="i-ph-x-logo text-3xl"></i>
                </a>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pb-4 px-6 flex justify-center space-x-4">
            <button class="btn bg-primary text-white">Follow</button>
            <button class="btn border-primary text-primary hover:bg-primary hover:text-white">Message</button>
        </div>

        <!-- Stats Section -->
        <div class="flex justify-around bg-default-50 py-4 border-t border-dashed border-default-200">
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">20.3k</p>
                <p class="text-sm text-default-500">Followers</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">1.5k</p>
                <p class="text-sm text-default-500">Posts</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">1.2k</p>
                <p class="text-sm text-default-500">Following</p>
            </div>
        </div>
    </div>

    <!-- Card 2 -->
    <div class="card overflow-hidden">
        <div class="relative">
            <img class="w-full h-44 object-cover" src="/images/small/img-2.jpg" alt="Cover Image">
            <div class="absolute -bottom-14 start-1/2 transform -translate-x-1/2">
                <div class="relative">
                    <img class="w-28 h-28 rounded-full border-4 border-white shadow-md" src="/images/users/avatar-2.jpg" alt="Profile Picture">
                    <span class="absolute bottom-1 end-1 w-5 h-5 bg-red-500 border-2 border-white rounded-full"></span>
                </div>
            </div>
        </div>
        <div class="pt-16 pb-4 text-center">
            <h2 class="text-xl font-semibold text-default-700 mb-2">Isabella Grace</h2>
            <p class="text-default-600 text-sm">New York, NY</p>
            <span class="text-xs text-default-400">Content Creator</span>
        </div>
        <div class="py-4 px-6">
            <div class="flex justify-center space-x-6">
                <a href="#" class="text-primary hover:text-blue-700 transition-all"><i class="i-ph-facebook-logo text-3xl"></i></a>
                <a href="#" class="text-pink-500 hover:text-pink-700 transition-all"><i class="i-ph-instagram-logo text-3xl"></i></a>
                <a href="#" class="text-blue-700 hover:text-blue-900 transition-all"><i class="i-ph-linkedin-logo text-3xl"></i></a>
                <a href="#" class="text-default-700 hover:text-default-900 transition-all"><i class="i-ph-x-logo text-3xl"></i></a>
            </div>
        </div>
        <div class="pb-4 px-6 flex justify-center space-x-4">
            <button class="btn bg-primary text-white">Follow</button>
            <button class="btn border-primary text-primary hover:bg-primary hover:text-white">Message</button>
        </div>
        <div class="flex justify-around bg-default-50 py-4 border-t border-dashed border-default-200">
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">15.4k</p>
                <p class="text-sm text-default-500">Followers</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">1.2k</p>
                <p class="text-sm text-default-500">Posts</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">980</p>
                <p class="text-sm text-default-500">Following</p>
            </div>
        </div>
    </div>

    <!-- Card 3 -->
    <div class="card overflow-hidden">
        <div class="relative">
            <img class="w-full h-44 object-cover" src="/images/small/img-3.jpg" alt="Cover Image">
            <div class="absolute -bottom-14 start-1/2 transform -translate-x-1/2">
                <div class="relative">
                    <img class="w-28 h-28 rounded-full border-4 border-white shadow-md" src="/images/users/avatar-3.jpg" alt="Profile Picture">
                    <span class="absolute bottom-1 end-1 w-5 h-5 bg-yellow-500 border-2 border-white rounded-full"></span>
                </div>
            </div>
        </div>
        <div class="pt-16 pb-4 text-center">
            <h2 class="text-xl font-semibold text-default-700 mb-2">Liam Johnson</h2>
            <p class="text-default-600 text-sm">San Francisco, CA</p>
            <span class="text-xs text-default-400">UI/UX Designer</span>
        </div>
        <div class="py-4 px-6">
            <div class="flex justify-center space-x-6">
                <a href="#" class="text-primary hover:text-blue-700 transition-all"><i class="i-ph-facebook-logo text-3xl"></i></a>
                <a href="#" class="text-pink-500 hover:text-pink-700 transition-all"><i class="i-ph-instagram-logo text-3xl"></i></a>
                <a href="#" class="text-blue-700 hover:text-blue-900 transition-all"><i class="i-ph-linkedin-logo text-3xl"></i></a>
                <a href="#" class="text-default-700 hover:text-default-900 transition-all"><i class="i-ph-x-logo text-3xl"></i></a>
            </div>
        </div>
        <div class="pb-4 px-6 flex justify-center space-x-4">
            <button class="btn bg-primary text-white">Follow</button>
            <button class="btn border-primary text-primary hover:bg-primary hover:text-white">Message</button>
        </div>
        <div class="flex justify-around bg-default-50 py-4 border-t border-dashed border-default-200">
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">18.9k</p>
                <p class="text-sm text-default-500">Followers</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">2.3k</p>
                <p class="text-sm text-default-500">Posts</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">2.1k</p>
                <p class="text-sm text-default-500">Following</p>
            </div>
        </div>
    </div>

    <!-- Card 4 -->
    <div class="card overflow-hidden">
        <div class="relative">
            <img class="w-full h-44 object-cover" src="/images/small/img-4.jpg" alt="Cover Image">
            <div class="absolute -bottom-14 start-1/2 transform -translate-x-1/2">
                <div class="relative">
                    <img class="w-28 h-28 rounded-full border-4 border-white shadow-md" src="/images/users/avatar-4.jpg" alt="Profile Picture">
                    <span class="absolute bottom-1 end-1 w-5 h-5 bg-blue-500 border-2 border-white rounded-full"></span>
                </div>
            </div>
        </div>
        <div class="pt-16 pb-4 text-center">
            <h2 class="text-xl font-semibold text-default-700 mb-2">Sophia Miller</h2>
            <p class="text-default-600 text-sm">Miami, FL</p>
            <span class="text-xs text-default-400">Graphic Designer</span>
        </div>
        <div class="py-4 px-6">
            <div class="flex justify-center space-x-6">
                <a href="#" class="text-primary hover:text-blue-700 transition-all"><i class="i-ph-facebook-logo text-3xl"></i></a>
                <a href="#" class="text-pink-500 hover:text-pink-700 transition-all"><i class="i-ph-instagram-logo text-3xl"></i></a>
                <a href="#" class="text-blue-700 hover:text-blue-900 transition-all"><i class="i-ph-linkedin-logo text-3xl"></i></a>
                <a href="#" class="text-default-700 hover:text-default-900 transition-all"><i class="i-ph-x-logo text-3xl"></i></a>
            </div>
        </div>
        <div class="pb-4 px-6 flex justify-center space-x-4">
            <button class="btn bg-primary text-white">Follow</button>
            <button class="btn border-primary text-primary hover:bg-primary hover:text-white">Message</button>
        </div>
        <div class="flex justify-around bg-default-50 py-4 border-t border-dashed border-default-200">
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">21.8k</p>
                <p class="text-sm text-default-500">Followers</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">3.1k</p>
                <p class="text-sm text-default-500">Posts</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">1.9k</p>
                <p class="text-sm text-default-500">Following</p>
            </div>
        </div>
    </div>

    <!-- Card 5 -->
    <div class="card overflow-hidden">
        <div class="relative">
            <img class="w-full h-44 object-cover" src="/images/small/img-5.jpg" alt="Cover Image">
            <div class="absolute -bottom-14 start-1/2 transform -translate-x-1/2">
                <div class="relative">
                    <img class="w-28 h-28 rounded-full border-4 border-white shadow-md" src="/images/users/avatar-5.jpg" alt="Profile Picture">
                    <span class="absolute bottom-1 end-1 w-5 h-5 bg-green-500 border-2 border-white rounded-full"></span>
                </div>
            </div>
        </div>
        <div class="pt-16 pb-4 text-center">
            <h2 class="text-xl font-semibold text-default-700 mb-2">Ethan Brown</h2>
            <p class="text-default-600 text-sm">Chicago, IL</p>
            <span class="text-xs text-default-400">Software Engineer</span>
        </div>
        <div class="py-4 px-6">
            <div class="flex justify-center space-x-6">
                <a href="#" class="text-primary hover:text-blue-700 transition-all"><i class="i-ph-facebook-logo text-3xl"></i></a>
                <a href="#" class="text-pink-500 hover:text-pink-700 transition-all"><i class="i-ph-instagram-logo text-3xl"></i></a>
                <a href="#" class="text-blue-700 hover:text-blue-900 transition-all"><i class="i-ph-linkedin-logo text-3xl"></i></a>
                <a href="#" class="text-default-700 hover:text-default-900 transition-all"><i class="i-ph-x-logo text-3xl"></i></a>
            </div>
        </div>
        <div class="pb-4 px-6 flex justify-center space-x-4">
            <button class="btn bg-primary text-white">Follow</button>
            <button class="btn border-primary text-primary hover:bg-primary hover:text-white">Message</button>
        </div>
        <div class="flex justify-around bg-default-50 py-4 border-t border-dashed border-default-200">
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">25.6k</p>
                <p class="text-sm text-default-500">Followers</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">4.2k</p>
                <p class="text-sm text-default-500">Posts</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">3.0k</p>
                <p class="text-sm text-default-500">Following</p>
            </div>
        </div>
    </div>

    <!-- Card 6 -->
    <div class="card overflow-hidden">
        <div class="relative">
            <img class="w-full h-44 object-cover" src="/images/small/img-6.jpg" alt="Cover Image">
            <div class="absolute -bottom-14 start-1/2 transform -translate-x-1/2">
                <div class="relative">
                    <img class="w-28 h-28 rounded-full border-4 border-white shadow-md" src="/images/users/avatar-6.jpg" alt="Profile Picture">
                    <span class="absolute bottom-1 end-1 w-5 h-5 bg-red-500 border-2 border-white rounded-full"></span>
                </div>
            </div>
        </div>
        <div class="pt-16 pb-4 text-center">
            <h2 class="text-xl font-semibold text-default-700 mb-2">Olivia Martin</h2>
            <p class="text-default-600 text-sm">Austin, TX</p>
            <span class="text-xs text-default-400">Marketing Specialist</span>
        </div>
        <div class="py-4 px-6">
            <div class="flex justify-center space-x-6">
                <a href="#" class="text-primary hover:text-blue-700 transition-all"><i class="i-ph-facebook-logo text-3xl"></i></a>
                <a href="#" class="text-pink-500 hover:text-pink-700 transition-all"><i class="i-ph-instagram-logo text-3xl"></i></a>
                <a href="#" class="text-blue-700 hover:text-blue-900 transition-all"><i class="i-ph-linkedin-logo text-3xl"></i></a>
                <a href="#" class="text-default-700 hover:text-default-900 transition-all"><i class="i-ph-x-logo text-3xl"></i></a>
            </div>
        </div>
        <div class="pb-4 px-6 flex justify-center space-x-4">
            <button class="btn bg-primary text-white">Follow</button>
            <button class="btn border-primary text-primary hover:bg-primary hover:text-white">Message</button>
        </div>
        <div class="flex justify-around bg-default-50 py-4 border-t border-dashed border-default-200">
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">30.4k</p>
                <p class="text-sm text-default-500">Followers</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">6.1k</p>
                <p class="text-sm text-default-500">Posts</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-medium text-default-800">5.0k</p>
                <p class="text-sm text-default-500">Following</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')

@endsection