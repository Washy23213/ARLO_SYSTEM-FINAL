<x-layouts.app title="CMU ARLO | Alumni Login">

    <!-- Gradient background wrapper -->
    <div class="min-h-screen flex items-center justify-center 
                bg-gradient-to-b from-[#e7ece5] to-[#f4f4f4] px-4">

        <!-- Login Wrapper -->
        <div class="flex w-full max-w-6xl min-h-[500px] rounded-xl overflow-hidden shadow-xl">

            <!-- Login Form -->
            <form action="{{ route('login.submit') }}" method="POST"
                  class="flex-1 bg-white p-10 flex flex-col justify-center gap-6">
                @csrf

                <h2 class="text-3xl font-bold text-center text-[#003300] mb-6">Alumni Portal Login</h2>

                @if(session('success'))
                    <div class="text-green-100 bg-green-900 px-4 py-2 rounded">
                        {{ session('success') }}
                    </div>
                @endif
                @if(request()->query('registered'))
                    <div class="text-green-100 bg-green-900 px-4 py-2 rounded">
                        Registration successful — you can login now.
                    </div>
                @endif

                {{-- Email / Username --}}
                <input type="email" name="email" placeholder="Username or Email" required
                class="w-full h-[50px] px-5 rounded-md border border-gray-300 text-black focus:outline-none focus:ring-2 focus:ring-[#1c4000] input-field">


                {{-- Password --}}
                <input type="password" name="password" placeholder="Password" required
                       class="w-full h-[50px] px-5 rounded-md border border-gray-300 text-black focus:outline-none focus:ring-2 focus:ring-[#1c4000]">

                {{-- Forgot & Remember --}}
                <div class="flex justify-between items-center text-sm mb-6">
                    <label class="flex items-center gap-2 text-gray-800">
                        <input type="checkbox" name="remember" class="accent-[#1c4000]">
                        Remember me
                    </label>
                    <a href="#" class="text-[#1c4000] hover:underline">Forgot Password?</a>
                </div>

                <button type="submit"
                class="w-full h-[50px] bg-[#1c4000] text-white font-semibold rounded-md hover:bg-[#003300] transition submit-btn">
                Sign in
                </button>

                

                {{-- Sign up --}}
                <p class="text-center text-sm mt-4">
                    Don't have an account? 
                    <a href="{{ route('signup') }}" class="font-semibold text-[#1c4000] hover:underline">Sign up here</a>
                </p>
            </form>

            <!-- Image Side -->
            <div class="hidden md:block md:flex-1">
                <img src="{{ asset('images/login_img.png') }}" alt="Career progress tracking tools"
                     class="w-full h-full object-cover rounded-tr-xl rounded-br-xl">
            </div>

        </div>
    </div>

</x-layouts.app>

