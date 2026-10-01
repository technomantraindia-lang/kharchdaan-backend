@if ($paginator->hasPages())
    <nav class="flex items-center justify-between w-full flex-wrap gap-3 py-2 text-xs font-medium text-slate-600">
        {{-- Left: Showing info --}}
        <div class="text-xs text-slate-500">
            @if ($paginator->firstItem())
                Showing <span class="font-semibold text-slate-800">{{ $paginator->firstItem() }}</span> to <span class="font-semibold text-slate-800">{{ $paginator->lastItem() }}</span> of <span class="font-semibold text-slate-800">{{ $paginator->total() }}</span> results
            @else
                Showing <span class="font-semibold text-slate-800">{{ $paginator->count() }}</span> results
            @endif
        </div>

        {{-- Right: Pagination Links --}}
        <div class="flex items-center gap-1">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center min-w-[32px] h-8 px-2.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-400 cursor-not-allowed text-xs">
                    <i class="fas fa-chevron-left text-[10px]"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center min-w-[32px] h-8 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition text-xs shadow-xs">
                    <i class="fas fa-chevron-left text-[10px]"></i>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="inline-flex items-center justify-center min-w-[32px] h-8 px-2 text-slate-400 text-xs">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="inline-flex items-center justify-center min-w-[32px] h-8 px-2.5 rounded-lg bg-blue-600 text-white font-bold text-xs shadow-xs">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="inline-flex items-center justify-center min-w-[32px] h-8 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition text-xs shadow-xs">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center min-w-[32px] h-8 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition text-xs shadow-xs">
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </a>
            @else
                <span class="inline-flex items-center justify-center min-w-[32px] h-8 px-2.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-400 cursor-not-allowed text-xs">
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
