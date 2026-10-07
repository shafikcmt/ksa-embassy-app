{{--
    App-wide pagination (set as default in AppServiceProvider).
    Desktop: "Showing X–Y of Z" + Prev · numbered pages (…) · Next.
    Mobile:  Prev · "Page X of Y" · Next.
--}}
@if ($paginator->hasPages())
    @php
        $base   = 'inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg px-2.5 text-sm font-semibold transition duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500';
        $idle   = $base . ' border border-slate-200 bg-white text-slate-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700';
        $active = $base . ' border border-brand-600 bg-brand-600 text-white shadow-sm';
        $off    = $base . ' cursor-not-allowed border border-slate-100 bg-slate-50 text-slate-300';
    @endphp
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex w-full flex-wrap items-center justify-between gap-3">
        {{-- Summary --}}
        <p class="hidden text-sm text-slate-500 sm:block">
            Showing <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
            of <span class="font-semibold text-slate-700">{{ number_format($paginator->total()) }}</span>
        </p>

        {{-- Mobile --}}
        <div class="flex w-full items-center justify-between gap-2 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="{{ $off }}" aria-disabled="true"><i class="bi bi-chevron-left"></i><span class="ml-1">Prev</span></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $idle }}"><i class="bi bi-chevron-left"></i><span class="ml-1">Prev</span></a>
            @endif
            <span class="text-sm text-slate-500">Page <span class="font-semibold text-slate-700">{{ $paginator->currentPage() }}</span> of {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $idle }}"><span class="mr-1">Next</span><i class="bi bi-chevron-right"></i></a>
            @else
                <span class="{{ $off }}" aria-disabled="true"><span class="mr-1">Next</span><i class="bi bi-chevron-right"></i></span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden items-center gap-1.5 sm:flex">
            @if ($paginator->onFirstPage())
                <span class="{{ $off }}" aria-disabled="true" aria-label="{{ __('pagination.previous') }}"><i class="bi bi-chevron-left"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $idle }}" aria-label="{{ __('pagination.previous') }}"><i class="bi bi-chevron-left"></i></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex h-9 min-w-[2rem] items-center justify-center text-sm text-slate-400" aria-disabled="true">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="{{ $active }}" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $idle }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $idle }}" aria-label="{{ __('pagination.next') }}"><i class="bi bi-chevron-right"></i></a>
            @else
                <span class="{{ $off }}" aria-disabled="true" aria-label="{{ __('pagination.next') }}"><i class="bi bi-chevron-right"></i></span>
            @endif
        </div>
    </nav>
@endif
