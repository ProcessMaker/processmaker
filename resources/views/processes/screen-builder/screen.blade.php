@extends('layouts.layout', ['content_margin'=>''])

@section('title')
    {{__('Edit Screen')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Screens') => route('screens.index'),
        $screen->title => null,
    ]])
@endsection

@section('content')
    <div id="screen-container" style="display: contents !important">
        <screen-builder :screen="{{$screen}}"
                        :permission="{{ \Auth::user()->hasPermissionsFor('screens') }}">
        </screen-builder>
    </div>
@endsection

@section('js')
    <script>
        window.ProcessMaker.EventBus.$on("screen-builder-init", (builder) => {
            // Registrar el EP para script, datasource y execute
            if (builder.watchers) {
                builder.watchers.api.scriptsIndex = @json(route('api.scripts.index'));
                builder.watchers.api.execute = @json(route('api.scripts.execute', ['script' => 'script_id']));
            } else {
                console.warn('Screen builder version does not have watchers');
            }
        });
    </script>
    <script src="{{mix('js/leave-warning.js')}}"></script>
    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach
    <script src="{{mix('js/processes/screen-builder/main.js')}}"></script>
@endsection
