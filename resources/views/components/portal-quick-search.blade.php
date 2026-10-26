<div class="portal-quick-search" data-portal-quick-search>
    <label class="portal-quick-search__field">
        <i class="bx bx-search" aria-hidden="true"></i>
        <span class="sr-only">Search portal pages and settings</span>
        <input
            type="search"
            autocomplete="off"
            placeholder="Search pages or settings..."
            aria-label="Search portal pages and settings"
            aria-expanded="false"
            aria-controls="portalQuickSearchResults"
            data-portal-quick-search-input
        >
        <kbd aria-hidden="true">Ctrl K</kbd>
    </label>
    <div id="portalQuickSearchResults" class="portal-quick-search__results" role="listbox" aria-label="Matching portal destinations" hidden data-portal-quick-search-results>
        @foreach ($items as $item)
            <a
                href="{{ $item['route'] }}"
                class="portal-quick-search__result"
                role="option"
                aria-selected="false"
                data-portal-quick-search-option
                data-search-terms="{{ \Illuminate\Support\Str::lower(($item['label'] ?? '').' '.($item['description'] ?? '').' '.($item['terms'] ?? '')) }}"
            >
                <span class="portal-quick-search__result-icon"><i class="bx {{ $item['icon'] ?? 'bx-right-arrow-alt' }}" aria-hidden="true"></i></span>
                <span class="portal-quick-search__result-copy">
                    <strong>{{ $item['label'] }}</strong>
                    @if (!empty($item['description']))<small>{{ $item['description'] }}</small>@endif
                </span>
                <i class="bx bx-arrow-back portal-quick-search__result-arrow" aria-hidden="true"></i>
            </a>
        @endforeach
        <p class="portal-quick-search__empty" hidden data-portal-quick-search-empty>No matching destination. Try a page, profile field, or setting.</p>
    </div>
</div>
