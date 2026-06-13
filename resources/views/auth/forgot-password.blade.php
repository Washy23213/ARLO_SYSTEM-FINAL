<x-layouts.app title="CMU OARL | Forgot Password">
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-b from-[#e7ece5] to-[#f4f4f4] px-4 py-10">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
            <h1 class="text-2xl font-bold text-[#003300]">Forgot your password?</h1>
            <p class="mt-2 text-sm text-gray-600">Enter your account email and we'll send a secure password reset link.</p>

            @if(session('status') || session('warning') || session('error') || $errors->any())
                @php
                    $messageType = session('error') || $errors->any()
                        ? 'error'
                        : (session('warning') ? 'warning' : 'status');
                    $messageText = session('error') ?? session('warning') ?? session('status') ?? $errors->first();
                    $messageClasses = [
                        'status' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
                        'error' => 'border-rose-200 bg-rose-50 text-rose-800',
                    ][$messageType];
                    $messageIcon = [
                        'status' => 'bx-check-circle',
                        'warning' => 'bx-info-circle',
                        'error' => 'bx-error-circle',
                    ][$messageType];
                @endphp
                <div class="mt-5 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm leading-6 {{ $messageClasses }}" role="{{ $messageType === 'status' ? 'status' : 'alert' }}">
                    <i class="bx {{ $messageIcon }} mt-0.5 text-lg" aria-hidden="true"></i>
                    <p>{{ $messageText }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4" novalidate data-password-reset-form>
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Email address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autofocus
                        class="w-full rounded-md border border-gray-300 px-4 py-3 text-black focus:outline-none focus:ring-2 focus:ring-[#1c4000]"
                        aria-describedby="emailResetHelp emailResetError"
                        data-password-reset-email
                    >
                    <p id="emailResetHelp" class="mt-2 text-xs text-gray-500">Use your registered email address.</p>
                    <p id="emailResetError" class="mt-2 hidden text-sm font-medium text-rose-600" data-password-reset-error></p>
                </div>

                <button type="submit" class="w-full rounded-md bg-[#1c4000] px-4 py-3 font-semibold text-white transition hover:bg-[#003300]" data-password-reset-submit>
                    Send reset link
                </button>
            </form>

            <div class="mt-5 text-center text-sm">
                <a href="{{ route('login') }}" class="font-semibold text-[#1c4000] hover:underline">Back to login</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-password-reset-form]');
            const email = document.querySelector('[data-password-reset-email]');
            const error = document.querySelector('[data-password-reset-error]');
            const submit = document.querySelector('[data-password-reset-submit]');

            if (!form || !email || !error) return;

            const setError = (message = '') => {
                error.textContent = message;
                error.classList.toggle('hidden', !message);
                email.classList.toggle('border-rose-300', !!message);
                email.classList.toggle('focus:ring-rose-200', !!message);
            };

            form.addEventListener('submit', function (event) {
                const value = email.value.trim();

                if (!value) {
                    event.preventDefault();
                    setError('Please enter the email address associated with your CMU OARL account.');
                    email.focus();
                    return;
                }

                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    event.preventDefault();
                    setError('Please enter a valid email address.');
                    email.focus();
                    return;
                }

                setError('');
                if (submit) {
                    submit.disabled = true;
                    submit.textContent = 'Sending reset link...';
                }
            });

            email.addEventListener('input', function () {
                if (error.textContent) setError('');
            });
        });
    </script>
</x-layouts.app>
