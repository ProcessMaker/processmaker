@extends('layouts.layout')

@section('title')
    {{__('Signals')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
		__('Designer') => route('processes.index'),
		__('Signals') => null,
	]])
@endsection

@section('content')
    <div class="px-3">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-item nav-link active" id="custom-signals-tab" data-toggle="tab" href="#nav-custom-signals" role="tab"
                   onclick="loadCustomSignals()" aria-controls="nav-custom-signals" aria-selected="true">
                    {{ __('Custom Signals') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-item nav-link" id="nav-system-signals-tab" data-toggle="tab" href="#nav-system-signals" role="tab"
                   onclick="loadSystemSignals()" aria-controls="nav-system-signals" aria-selected="true">
                    {{ __('System Signals') }}
                </a>
            </li>
            @if(hasPackage('package-collections'))
            <li class="nav-item">
                <a class="nav-item nav-link" id="nav-collection-signals-tab" data-toggle="tab" href="#nav-collection-signals" role="tab"
                   onclick="loadCollectionSignals()" aria-controls="nav-collection-signals" aria-selected="true">
                    {{ __('Collection Signals') }}
                </a>
            </li>
            @endif
        </ul>

        <div id="signals-listing">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="nav-custom-signals" role="tabpanel" aria-labelledby="custom-signals-tab">
                    <div class="card card-body p-3 border-top-0">
                        @include('processes.signals.listCustom')
                    </div>
                </div>

                <div class="tab-pane fade show" id="nav-system-signals" role="tabpanel" aria-labelledby="nav-system-signals-tab">
                    <div class="card card-body p-3 border-top-0">
                        @include('processes.signals.listSystem')
                    </div>
                </div>
                @if(hasPackage('package-collections'))
                <div class="tab-pane fade show" id="nav-collection-signals" role="tabpanel" aria-labelledby="nav-collection-signals-tab">
                    <div class="card card-body p-3 border-top-0">
                        @include('processes.signals.listCollection')
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection

<script>
    loadCustomSignals = function () {
        ProcessMaker.EventBus.$emit('api-data-custom-signals', true);
    };
    loadSystemSignals = function () {
        ProcessMaker.EventBus.$emit('api-data-system-signals', true);
    };
</script>

@if(hasPackage('package-collections'))
    <script>
        loadCollectionSignals = function () {
            ProcessMaker.EventBus.$emit('api-data-collection-signals', true);
        };
    </script>
@endif

@section('js')
    <script src="{{ mix('js/processes/signals/index.js') }}"></script>
@endsection
