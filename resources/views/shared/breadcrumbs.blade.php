<div id="breadcrumbs" class="d-print-none" v-cloak>
    @if (!isset($dynamic))
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb border-top border-bottom">
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

            @if (isset($showModelerSaveButton) && $showModelerSaveButton == true)
            <button
                type="button"
                class="btn btn-secondary btn-sm position-absolute modeler-save-button"
                data-test="save-process"
                onclick="window.ProcessMaker && window.ProcessMaker.EventBus.$emit('modeler-save')"
            >
                <i class="fas fa-save mr-1"></i>
                {{__('Save')}}
            </button>
            @endif
        </nav>
    @else
        <breadcrumbs ref="breadcrumbs" :routes='@json($routes)'></breadcrumbs>
    @endif
</div>
