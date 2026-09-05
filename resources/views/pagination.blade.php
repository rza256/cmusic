<div class="pagination-default ib">
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation">
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
                            <span class="selected"><a class="passthrough" data-attr="page" href="{{ $url }}">{{ $page }}</a></span>
                        @else
                            <a class="passthrough" data-attr="page" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </nav>
    @endif
</div>