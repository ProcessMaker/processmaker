@extends('layouts.layoutnextvite', ['content_margin'=>''])

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
<script src="{{ mix('js/manifest.js') }}"></script>
    {{-- Load Monaco AMD loader before any Vue component that uses vue-monaco --}}
    @include('shared.monaco')
    {{-- Register FORM controls before loaderScreen dispatches app-bootstrapped --}}
    @vite(['resources/js/processes/screen-builder/loaderScreen.js'])
    <script>
      // Register EventBus listeners after setupMain() fires 'app-bootstrapped'
      window.addEventListener('app-bootstrapped', function () {
        window.ProcessMaker.EventBus.$on("screen-builder-init", (builder) => {
          if (builder.watchers) {
            if (@json(route::has('api.scripts.index'))) {
              builder.watchers_config.api.scripts.push((data) => {
                ProcessMaker.apiClient
                  .get(@json(route('api.scripts.index')) + '?per_page=10000')
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

        window.ProcessMaker.EventBus.$on("screen-renderer-init", (screen) => {
          screen.$store.dispatch("clipboardModule/setupSaveToServerFn", (items) => {
            return ProcessMaker.apiClient.post('/api/1.1/clipboard/create_or_update', {
              config: items,
            });
          });

          screen.$store.dispatch("clipboardModule/setupLoadFromServerFn", () => {
            return ProcessMaker.apiClient.get('/api/1.1/clipboard/get_by_user')
            .then(handleClipboardResponse)
            .catch(handleClipboardError);

            function handleClipboardResponse(response) {
              if (response && response.data && response.data.config && Array.isArray(response.data.config)) {
                return response.data.config;
              } else {
                throw new Error("No valid clipboard config data in response.");
              }
            }

            function handleClipboardError(error) {
              console.error("Error fetching clipboard data: ", error);
            }
          });
        });
      });
    </script>
    @foreach($manager->getScripts() as $script)
    kiko
        <script defer src="{{$script}}"></script>
    @endforeach

    @if ($type === 'FORM')
      @vite(['resources/js/processes/screen-builder/typeForm.js'])
    @elseif ($type === 'DISPLAY')
      @vite(['resources/js/processes/screen-builder/typeDisplay.js'])
    @endif

    @vite(['resources/js/leave-warning.js'])
    @vite(['resources/js/processes/screen-builder/main.js'])
@endsection
