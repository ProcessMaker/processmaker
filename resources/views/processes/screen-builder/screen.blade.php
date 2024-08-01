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
    <div class="flex-grow-1">
      <top-menu
        v-if="screenBuilder"
        class="border-0 bg-white p-0"
        :options="screenBuilder.optionsMenu"
        :environment="screenBuilder"
        :initial-new-items="screenBuilder.$refs.menuScreen.newItems"
        render-top
      />
    </div>
@endsection

@section('content')
    <div class="sr-only">{{ __('A mouse and keyboard are required to use screen builder.') }}</div>
    <div id="screen-container" style="display: contents !important">
        <component :is="'{{ $screen->builderComponent() }}'" :screen="{{ $screen }}"
                   ref="screenBuilder"
                   :permission="{{ \Auth::user()->hasPermissionsFor('screens', 'screen-templates') }}"
                   :auto-save-delay="{{ $autoSaveDelay }}"
                   :is-versions-installed="@json($isVersionsInstalled)"
                   :is-draft="@json($isDraft)"
                   :process-id="{{ (!$processId ? 0 : $processId) }}">
        </component>
    </div>
@endsection

@section('js')
    <script>
      console.log('entro');
      window.ProcessMaker.EventBus.$on("screen-builder-init", (builder) => {
        // Add AI suggestion
        const addSuggestionTo = (component) => {
          const inputControl = builder.$refs.builder.controls.find(c=>c['editor-component'] === component);
          const inputInspector = inputControl.inspector;
          inputInspector.push({
            type: 'FormTextArea',
            field: 'aiSuggestion',
            config: {
              label: 'AI Suggestion',
              helper: 'AI Suggestion prompt',
            }
          });
        };
        builder.$nextTick(() => {
          addSuggestionTo('FormInput');
          addSuggestionTo('FormTextArea');
        });

        // Registrar el EP para script, datasource y execute
        if (builder.watchers) {
          if (@json(route::has('api.scripts.index'))) {
            builder.watchers_config.api.scripts.push((data) => {
              ProcessMaker.apiClient
                .get(@json(route('api.scripts.index' )) + '?per_page=10000')
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
            builder.watchers_config.api.scripts.push((data) => {
              ProcessMaker.apiClient
                .get('data_sources' + '?per_page=10000')
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
      window.Processmaker.user = @json($currentUser);
    </script>
    <script src="{{mix('js/leave-warning.js')}}"></script>
    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach
    <script src="{{mix('js/processes/screen-builder/main.js')}}"></script>
@endsection
