<div class="pagination-default ib">
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation">
            <span>
                {{-- Previous Page Link --}}
                @if (!$paginator->onFirstPage())
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <button class="gray float-left" rel="prev">
                            back
                        </button>
                    </a>
                @else
                    <a href="#" rel="prev">
                        <button class="gray float-left" rel="prev">
                            back
                        </button>
                    </a>
                @endif
            </span>

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span>{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="selected"><a href="{{ $url }}">{{ $page }}</a></span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
 
            <span>
                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next">
                        <button class="gray float-right" rel="next">
                            next
                        </button>
                    </a>
                @else
                    <a href="#" rel="next">
                        <button class="gray float-right" rel="next">
                            next
                        </button>
                    </a>
                @endif
            </span>
        </nav>
    @endif
</div>