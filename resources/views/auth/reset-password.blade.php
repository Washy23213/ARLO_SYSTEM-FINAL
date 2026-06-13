<x-layouts.app title="CMU OARL | Reset Password">
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-b from-[#e7ece5] to-[#f4f4f4] px-4 py-10">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
            <h1 class="text-2xl font-bold text-[#003300]">Reset your password</h1>
            <p class="mt-2 text-sm text-gray-600">Create a new password for your CMU OARL account.</p>

            <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email ?? '') }}" required autofocus class="w-full rounded-md border border-gray-300 px-4 py-3 text-black focus:outline-none focus:ring-2 focus:ring-[#1c4000]">
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-gray-700">New password</label>
                    <input id="password" type="password" name="password" data-password-strength-target="resetPasswordStrength" required class="w-full rounded-md border border-gray-300 px-4 py-3 text-black focus:outline-none focus:ring-2 focus:ring-[#1c4000]">
                    <div id="resetPasswordStrength" class="strength-meter mt-3" aria-live="polite">
                        <div class="strength-meter__track">
                            <div class="strength-meter__fill" data-password-strength-fill></div>
                        </div>
                        <div class="strength-meter__meta">
                            <span class="strength-meter__label" data-password-strength-label>Empty</span>
                            <span class="strength-meter__hint" data-password-strength-hint>Use at least 8 characters with a mix of letters, numbers, and symbols.</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-700">Confirm new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required class="w-full rounded-md border border-gray-300 px-4 py-3 text-black focus:outline-none focus:ring-2 focus:ring-[#1c4000]">
                </div>

                <button type="submit" class="w-full rounded-md bg-[#1c4000] px-4 py-3 font-semibold text-white transition hover:bg-[#003300]">
                    Reset password
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
