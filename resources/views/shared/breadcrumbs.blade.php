<div class="sticky-top navbar-z-index">
<nav aria-label="breadcrumb" class="border-top border-bottom d-flex bg-light mt-auto mb-auto">
    <ol class="breadcrumb m-0">
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

    @if (isset($saveButtonEvent))
    <button
        type="button"
        class="btn btn-secondary btn-sm ml-auto mt-auto mb-auto mr-4"
        data-test="save-process"
        onclick="window.ProcessMaker.EventBus.$emit('{{ $saveButtonEvent }}')"
    >
        <i class="fas fa-save mr-1"></i>
        {{__('Save')}}
    </button>
    @endif
</nav>
</div>
