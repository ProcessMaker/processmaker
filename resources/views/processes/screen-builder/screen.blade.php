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
        <component :is="'{{ $screen->builderComponent() }}'" :screen="{{ $screen }}"
                   :permission="{{ \Auth::user()->hasPermissionsFor('screens') }}">
        </component>
    </div>
@endsection

@section('js')
    <script>
      window.ProcessMaker.EventBus.$on("screen-builder-init", (builder) => {
        // Registrar el EP para script, datasource y execute
        if (builder.watchers) {
          if (@json(route::has('api.scripts.index'))) {
            builder.watchers_config.api.scripts.push((data, filter) => {
              ProcessMaker.apiClient
                .get(@json(route('api.scripts.index' )) + (typeof filter === "string" ? "?filter=" + filter : ""))
                .then(response => {
                  let scripts = response.data.data.map(item => {
                    item.id = "script-" + item.id;
                    return item;
                  });
                  if (scripts) {
                    data.push({
                      "type": @json(__('Scripts')),
                      "items": scripts,
                    });
                  }
                });
            });
          }

          if (@json(route::has('api.data-sources.index'))) {
            builder.watchers_config.api.scripts.push((data, filter) => {
              ProcessMaker.apiClient
                .get('data_sources' + (typeof filter === "string" ? "?filter=" + filter : ""))
                .then(response => {
                  let dataSource = response.data.data.map(item => {
                    item.id = "data_source-" + item.id;
                    item.title = item.name;
                    item.key = 'package-data-sources/data-source-task-service';
                    return item;
                  });
                  if (dataSource) {
                    data.push({
                      "type": @json(__('Data Connectors')),
                      "items": dataSource,
                    });
                  }
                });
            });
          }

          builder.watchers_config.api.execute = @json(route('api.scripts.execute', ['script_id' => 'script_id', 'script_key' => 'script_key']));
          builder.watchers_config.api.execution = @json(route('api.scripts.execution', ['key' => 'execution_key']));
        } else {
          console.warn("Screen builder version does not have watchers");
        }
      });
      window.ProcessMaker.EventBus.$on("screen-renderer-init", (screen) => {
        if (screen.watchers) {
          screen.watchers_config.api.execute = @json(route('api.scripts.execute', ['script_id' => 'script_id', 'script_key' => 'script_key']));
          screen.watchers_config.api.execution = @json(route('api.scripts.execution', ['key' => 'execution_key']));
        } else {
          console.warn("Screen builder version does not have watchers");
        }
      });
    </script>
    <script src="{{mix('js/leave-warning.js')}}"></script>
    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach
    <script src="{{mix('js/processes/screen-builder/main.js')}}"></script>
@endsection
