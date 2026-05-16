@props(['paginator', 'alwaysShow' => false])

@if ($alwaysShow || $paginator->hasPages())
    <nav class="flex items-center justify-between border-t border-slate-100 px-4 py-3" aria-label="Pagination">
        {{-- Mobile --}}
        <div class="flex flex-1 justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-300 cursor-not-allowed">
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="ml-3 inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-800 transition">
                    Selanjutnya
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            @else
                <span class="ml-3 inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-300 cursor-not-allowed">
                    Selanjutnya
                </span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-slate-400">
                    Menampilkan
                    <span class="font-medium text-slate-600">{{ $paginator->firstItem() }}</span>
                    -
                    <span class="font-medium text-slate-600">{{ $paginator->lastItem() }}</span>
                    dari
                    <span class="font-medium text-slate-600">{{ $paginator->total() }}</span>
                    data
                </p>
            </div>

            <div>
                <ul class="flex items-center gap-1">
                    {{-- Previous --}}
                    @if ($paginator->onFirstPage())
                        <li class="inline-flex items-center justify-center rounded-lg px-2.5 py-1.5 text-xs text-slate-300 cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        </li>
                    @else
                        <li>
                            <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center justify-center rounded-lg px-2.5 py-1.5 text-xs text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                            </a>
                        </li>
                    @endif

                    {{-- Pages --}}
                    @php
                        $totalPage = $paginator->lastPage();
                        $current = $paginator->currentPage();
                        // Show max 5 page numbers around current page
                        $startPage = max(1, $current - 2);
                        $endPage = min($totalPage, $current + 2);
                        if ($endPage - $startPage < 4) {
                            if ($startPage == 1) {
                                $endPage = min($totalPage, $startPage + 4);
                            } elseif ($endPage == $totalPage) {
                                $startPage = max(1, $endPage - 4);
                            }
                        }
                    @endphp

                    @if ($startPage > 1)
                        <li>
                            <a href="{{ $paginator->url(1) }}" class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-xs text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">1</a>
                        </li>
                        @if ($startPage > 2)
                            <li class="inline-flex items-center justify-center px-2 py-1.5 text-xs text-slate-400">...</li>
                        @endif
                    @endif

                    @for ($page = $startPage; $page <= $endPage; $page++)
                        @if ($page == $current)
                            <li>
                                <span class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm">
                                    {{ $page }}
                                </span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $paginator->url($page) }}" class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-xs text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                                    {{ $page }}
                                </a>
                            </li>
                        @endif
                    @endfor

                    @if ($endPage < $totalPage)
                        @if ($endPage < $totalPage - 1)
                            <li class="inline-flex items-center justify-center px-2 py-1.5 text-xs text-slate-400">...</li>
                        @endif
                        <li>
                            <a href="{{ $paginator->url($totalPage) }}" class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-xs text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">{{ $totalPage }}</a>
                        </li>
                    @endif

                    {{-- Next --}}
                    @if ($paginator->hasMorePages())
                        <li>
                            <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center justify-center rounded-lg px-2.5 py-1.5 text-xs text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </a>
                        </li>
                    @else
                        <li class="inline-flex items-center justify-center rounded-lg px-2.5 py-1.5 text-xs text-slate-300 cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
@endif