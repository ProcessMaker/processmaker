<div>
<nav aria-label="breadcrumb" class="sticky-top navbar-z-index">
    <ol class="breadcrumb bg-light text-primary">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
        @foreach($routes as $title => $link)
            @php
                if (is_callable($link)) {
                    list($title, $link) = $link();
                }
            @endphp

            @if ($loop->last)
                <li class="breadcrumb-item active font-weight-bold">
            @else
                <li class="breadcrumb-item">
            @endif

            @if ($link != null)
                <a href="{{ $link }}">
                    {{ $title }}
                </a>
            @else
                {{ $title }}
            @endif

            </li>
        @endforeach
    </ol>
</nav>
</div>