<div class="sticky-top navbar-z-index">
<nav aria-label="breadcrumb" >
    <ol class="breadcrumb bg-light">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
        @foreach($routes as $title => $link)
            @php
                if (is_callable($link)) {
                    list($title, $link) = $link();
                }
            @endphp

            @if ($loop->last)
                <li class="breadcrumb-item active">
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
