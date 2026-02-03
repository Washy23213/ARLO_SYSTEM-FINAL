@extends('components.layouts.dashboard')

@section('title', 'Settings')

@section('content')
<div class="pl-8 pr-6 pt-6 pb-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
        <p class="text-gray-600 mt-1">Manage your account and preferences</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Settings -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Information</h2>
                <form class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" value="{{ auth()->user()->firstname ?? '' }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" value="{{ auth()->user()->lastname ?? '' }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" value="{{ auth()->user()->email ?? '' }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" value="{{ auth()->user()->phone ?? '' }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <input type="text" value="{{ auth()->user()->department ?? '' }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Graduation Year</label>
                        <input type="number" value="{{ auth()->user()->yearGraduated ?? '' }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                        Update Profile
                    </button>
                </form>
            </div>
        </div>

        <!-- Account Settings -->
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Settings</h2>
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Change Password</h3>
                        <button class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors">
                            Change Password
                        </button>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Privacy Settings</h3>
                        <button class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors">
                            Manage Privacy
                        </button>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Notification Preferences</h3>
                        <button class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors">
                            Manage Notifications
                        </button>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-white rounded-lg shadow-sm border border-red-200 p-6">
                <h2 class="text-lg font-semibold text-red-900 mb-4">Danger Zone</h2>
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Delete Account</h3>
                        <p class="text-sm text-gray-600 mb-3">Once you delete your account, there is no going back. Please be certain.</p>
                        <button class="w-full bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors">
                            Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection