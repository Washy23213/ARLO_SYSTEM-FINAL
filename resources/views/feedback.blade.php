@extends('components.layouts.dashboard')

@section('title', 'Submit Feedback')

@section('content')
@php
    $selectedType = old('type', 'General Feedback');
    $selectedCategory = collect($feedbackCategories ?? [])->firstWhere('value', $selectedType)
        ?? collect($feedbackCategories ?? [])->firstWhere('value', 'General Feedback')
        ?? (($feedbackCategories ?? [])[0] ?? null);
@endphp
<div class="space-y-6">
    <div class="surface-card overflow-hidden p-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-700">Feedback Routing</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Choose the right feedback category first</h2>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-3 text-sm text-emerald-900">
                <p class="font-semibold">Current selection</p>
                <p id="feedbackSelectedTypeLabel" class="mt-1">{{ $selectedCategory['title'] ?? 'General Feedback' }}</p>
            </div>
        </div>

        <form
            id="alumniFeedbackForm"
            action="{{ route('feedback.submit') }}"
            method="POST"
            class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[0.95fr_1.05fr]"
            data-confirm-submit="Send this feedback to the admin team?"
            data-confirm-title="Send feedback"
            data-confirm-label="Send Feedback"
        >
            @csrf

            <aside class="space-y-4">
                <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50/70 p-5">
                    <h3 class="text-base font-semibold text-slate-900">Select one feedback path</h3>

                    <div class="mt-4 grid gap-3">
                        @foreach ($feedbackCategories as $category)
                            @php $isSelected = $selectedType === $category['value']; @endphp
                            <label
                                class="feedback-category-card block cursor-pointer rounded-2xl border px-4 py-4 transition-all duration-200 {{ $isSelected ? 'border-emerald-500 bg-emerald-50 shadow-sm ring-4 ring-emerald-100' : 'border-slate-200 bg-white hover:border-emerald-300 hover:bg-emerald-50/40' }}"
                                data-feedback-card
                                data-feedback-value="{{ $category['value'] }}"
                                data-feedback-title="{{ $category['title'] }}"
                                data-feedback-subject-placeholder="{{ $category['subject_placeholder'] }}"
                                data-feedback-message-placeholder="{{ $category['message_placeholder'] }}"
                                data-feedback-tip="{{ $category['tip'] }}"
                            >
                                <input
                                    type="radio"
                                    name="type"
                                    value="{{ $category['value'] }}"
                                    class="sr-only"
                                    {{ $isSelected ? 'checked' : '' }}
                                >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $category['title'] }}</p>
                                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.2em] text-emerald-700">{{ $category['value'] }}</p>
                                    </div>
                                    <span class="rounded-full border border-emerald-200 bg-white px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        Choose
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @error('type')
                        <p class="mt-3 text-sm font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5">
                    <div class="flex items-start gap-3">
                        <span class="mt-1 inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <i class='bx bx-check-shield text-xl'></i>
                        </span>
                        <div>
                            <h3 id="feedbackGuideTitle" class="text-base font-semibold text-slate-900">{{ $selectedCategory['title'] ?? 'General Feedback' }}</h3>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-amber-100 bg-amber-50/70 px-4 py-4">
                        <p class="text-sm font-semibold text-amber-900">What to include</p>
                        <p id="feedbackGuideTip" class="mt-2 text-sm leading-6 text-amber-900/90">{{ $selectedCategory['tip'] ?? 'Choose the option that best matches your concern.' }}</p>
                    </div>
                </div>
            </aside>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Write your feedback</h3>
                </div>

                <div class="mt-5 space-y-5">
                    <div>
                        <label for="feedbackSubject" class="mb-1.5 block text-sm font-semibold text-gray-700">Subject</label>
                        <input
                            id="feedbackSubject"
                            name="subject"
                            type="text"
                            value="{{ old('subject') }}"
                            placeholder="{{ $selectedCategory['subject_placeholder'] ?? 'Brief title for your feedback' }}"
                            class="w-full rounded-xl border border-gray-300 bg-slate-50 px-4 py-3 text-sm focus:border-emerald-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            required
                        >
                        @error('subject')
                            <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="feedbackMessage" class="mb-1.5 block text-sm font-semibold text-gray-700">Message</label>
                        <textarea
                            id="feedbackMessage"
                            name="message"
                            rows="8"
                            placeholder="{{ $selectedCategory['message_placeholder'] ?? 'Describe your concern, issue, or suggestion in detail.' }}"
                            class="w-full rounded-2xl border border-gray-300 bg-slate-50 px-4 py-3 text-sm leading-relaxed focus:border-emerald-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            required
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-4 rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 via-white to-teal-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-md">
                            <i class='bx bx-send mr-2 text-lg'></i> Submit Feedback
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    (() => {
        const cards = Array.from(document.querySelectorAll('[data-feedback-card]'));
        const subjectInput = document.getElementById('feedbackSubject');
        const messageInput = document.getElementById('feedbackMessage');
        const selectedTypeLabel = document.getElementById('feedbackSelectedTypeLabel');
        const guideTitle = document.getElementById('feedbackGuideTitle');
        const guideTip = document.getElementById('feedbackGuideTip');

        if (!cards.length || !subjectInput || !messageInput || !selectedTypeLabel || !guideTitle || !guideTip) {
            return;
        }

        const activeCardClasses = ['border-emerald-500', 'bg-emerald-50', 'shadow-sm', 'ring-4', 'ring-emerald-100'];
        const inactiveCardClasses = ['border-slate-200', 'bg-white'];

        const applySelection = (selectedCard) => {
            cards.forEach((card) => {
                const input = card.querySelector('input[type="radio"]');
                const isSelected = card === selectedCard;

                card.classList.toggle('hover:border-emerald-300', !isSelected);
                card.classList.toggle('hover:bg-emerald-50/40', !isSelected);

                activeCardClasses.forEach((className) => card.classList.toggle(className, isSelected));
                inactiveCardClasses.forEach((className) => card.classList.toggle(className, !isSelected));

                if (input) {
                    input.checked = isSelected;
                }
            });

            selectedTypeLabel.textContent = selectedCard.dataset.feedbackTitle || selectedCard.dataset.feedbackValue || 'General Feedback';
            guideTitle.textContent = selectedCard.dataset.feedbackTitle || selectedCard.dataset.feedbackValue || 'General Feedback';
            guideTip.textContent = selectedCard.dataset.feedbackTip || 'Choose the option that best matches your concern.';
            subjectInput.placeholder = selectedCard.dataset.feedbackSubjectPlaceholder || 'Brief title for your feedback';
            messageInput.placeholder = selectedCard.dataset.feedbackMessagePlaceholder || 'Describe your concern, issue, or suggestion in detail.';
        };

        cards.forEach((card) => {
            card.addEventListener('click', () => applySelection(card));
        });

        const initiallySelected = cards.find((card) => card.querySelector('input[type="radio"]')?.checked) || cards[0];

        if (initiallySelected) {
            applySelection(initiallySelected);
        }
    })();
</script>
@endsection
