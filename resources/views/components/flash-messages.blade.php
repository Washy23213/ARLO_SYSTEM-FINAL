@php
    $flashMessages = collect();

    if (session('success')) {
        $flashMessages->push([
            'type' => 'success',
            'title' => 'Success',
            'message' => session('success'),
        ]);
    }

    if (session('status')) {
        $flashMessages->push([
            'type' => 'info',
            'title' => 'Update',
            'message' => session('status'),
        ]);
    }

    if (session('info')) {
        $flashMessages->push([
            'type' => 'info',
            'title' => 'Notice',
            'message' => session('info'),
        ]);
    }

    if (session('logout')) {
        $flashMessages->push([
            'type' => 'logout',
            'title' => 'Signed out',
            'message' => session('logout'),
        ]);
    }

    if (session('warning')) {
        $flashMessages->push([
            'type' => 'warning',
            'title' => 'Notice',
            'message' => session('warning'),
        ]);
    }

    if (session('error')) {
        $flashMessages->push([
            'type' => 'error',
            'title' => 'Something went wrong',
            'message' => session('error'),
        ]);
    }

    if ($errors->any()) {
        $flashMessages->push([
            'type' => 'error',
            'title' => 'Please check the form',
            'message' => $errors->first(),
        ]);
    }
@endphp

@if($flashMessages->isNotEmpty())
    <div class="flash-stack" data-flash-stack aria-live="polite">
        @foreach($flashMessages as $flash)
            @php
                $flashIcon = match ($flash['type']) {
                    'success' => 'bx-check-circle',
                    'logout' => 'bx-log-out-circle',
                    'error' => 'bx-error-circle',
                    'warning' => 'bx-error-circle',
                    default => 'bx-info-circle',
                };

                $flashLabel = match ($flash['type']) {
                    'success' => 'Success',
                    'logout' => 'Signed out',
                    'error' => 'Error',
                    'warning' => 'Alert',
                    default => 'Notice',
                };
            @endphp
            <div
                class="flash-message flash-message--{{ $flash['type'] }}"
                data-flash-message
                role="{{ $flash['type'] === 'success' ? 'status' : 'alert' }}"
            >
                <span class="flash-message__accent" aria-hidden="true"></span>
                <div class="flash-message__icon">
                    <i class='bx {{ $flashIcon }}'></i>
                </div>
                <div class="flash-message__content">
                    <div class="flash-message__eyebrow">
                        <span class="flash-message__label">{{ $flashLabel }}</span>
                    </div>
                    <p class="flash-message__title">{{ $flash['title'] }}</p>
                    <p class="flash-message__body">{{ $flash['message'] }}</p>
                </div>
                <button type="button" class="flash-message__close" data-flash-dismiss aria-label="Dismiss message">
                    <i class='bx bx-x'></i>
                </button>
            </div>
        @endforeach
    </div>
@endif
