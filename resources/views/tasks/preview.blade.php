<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Security-Policy" content="script-src * 'unsafe-inline' 'unsafe-eval';
        object-src 'self';
        worker-src 'self' blob:;">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="is-prod" content="{{ config('app.env') == 'production' ? 'true' : 'false' }}">
    <meta name="app-url" content="{{ config('app.url') }}">
    <meta name="open-ai-nlq-to-pmql" content="{{ config('app.open_ai_nlq_to_pmql') }}">
    <meta name="i18n-mdate" content='{!! json_encode(ProcessMaker\i18nHelper::mdates()) !!}'>
    <meta name="screen-cache-enabled" content="{{ config('app.screen.cache_enabled') ? 'true' : 'false' }}">
    <meta name="screen-cache-timeout" content="{{ config('app.screen.cache_timeout') }}">
    @if(Auth::user())
    <meta name="user-id" content="{{ Auth::user()->id }}">
    <meta name="datetime-format" content="{{ Auth::user()->datetime_format ?: config('app.dateformat') }}">
    <meta name="timezone" content="{{ Auth::user()->timezone ?: config('app.timezone') }}">
    <meta name="request-id" content="{{ $task->processRequest->id }}">
    @endif
    <meta name="timeout-worker" content="{{ mix('js/timeout.js') }}">
    <meta name="timeout-length" content="{{ Session::has('rememberme') && Session::get('rememberme') ? "Number.MAX_SAFE_INTEGER" : config('session.lifetime') }}">
    <meta name="timeout-warn-seconds" content="{{ config('session.expire_warning') }}">
    @if(Session::has('_alert'))
      <meta name="alert" content="show">
      @php
      list($type,$message) = json_decode(Session::get('_alert'));
      Session::forget('_alert');
      @endphp
      <meta name="alertVariant" content="{{$type}}">
      <meta name="alertMessage" content="{{$message}}">
    @endif

    <title>{{__('Edit Task')}}</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ \ProcessMaker\Models\Setting::getFavicon() }}">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="/css/bpmn-symbols/css/bpmn.css" rel="stylesheet">
    @yield('css')
    <script type="text/javascript">
    @if(Auth::user())
      window.Processmaker = {
        csrfToken: "{{csrf_token()}}",
        userId: "{{\Auth::user()->id}}",
        messages: [],
        apiTimeout: {{config('app.api_timeout')}}
      };
      @if(config('broadcasting.default') == 'redis')
        window.Processmaker.broadcasting = {
          broadcaster: "socket.io",
          host: "{{config('broadcasting.connections.redis.host')}}",
          key: "{{config('broadcasting.connections.redis.key')}}"
        };
      @endif
      @if(config('broadcasting.default') == 'pusher')
        window.Processmaker.broadcasting = {
          broadcaster: "pusher",
          key: "{{config('broadcasting.connections.pusher.key')}}",
          cluster: "{{config('broadcasting.connections.pusher.options.cluster')}}",
          forceTLS: {{config('broadcasting.connections.pusher.options.use_tls') ? 'true' : 'false'}},
          debug: {{config('broadcasting.connections.pusher.options.debug') ? 'true' : 'false'}}
        };
      @endif
    @endif
  </script>
    @isset($addons)
        <script>
            var addons = [];
        </script>
        @foreach ($addons as $addon)
            @if (!empty($addon['script']))
                {!! $addon['script'] !!}
            @endif
        @endforeach
    @endisset

    @if (config('global_header'))
        <!-- Start Global Header -->
        {!! config('global_header') !!}
        <!-- End Global Header -->
    @endif
</head>
<body>
  <div id="sidebar" style="display: 'none'"></div>
  <div id="navbar" style="display: 'none'"></div>
    <div v-cloak id="task" class="container-fluid px-3">
        <div class="d-flex flex-column flex-md-row" id="interactionListener">
            <div class="flex-grow-1">
                <div v-if="isSelfService" class="alert alert-primary" role="alert">
                    <button type="button" class="btn btn-primary" @click="claimTask">{{__('Claim Task')}}</button>
                    {{__('This task is unassigned, click Claim Task to assign yourself.')}}
                </div>
                <div class="container-fluid h-100 d-flex flex-column">
                    <div id="tabContent" class="tab-content flex-grow-1">
                        <task
                          ref="task"
                          class="card border-0"
                          v-model="formData"
                          :initial-task-id="{{ $task->id }}"
                          :initial-request-id="{{ $task->process_request_id }}"
                          :user-id="{{ Auth::user()->id }}"
                          csrf-token="{{ csrf_token() }}"
                          initial-loop-context="{{ $task->getLoopContext() }}"
                          @task-updated="taskUpdated"
                          @after-submit="afterSubmit"
                          @submit="submit"
                          @completed="completed"
                          @@error="error"
                          @closed="closed"
                          @redirect="redirectToTask"
                          :task-preview="true"
                          :always-allow-editing="alwaysAllowEditing"
                          :disable-interstitial="disableInterstitial"
                        ></task>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<!-- Scripts -->
@if(config('broadcasting.default') == 'redis')
<script src="{{config('broadcasting.connections.redis.host')}}/socket.io/socket.io.js"></script>
@endif
<script src="{{ mix('js/manifest.js') }}"></script>
<script src="{{ mix('js/vue-vendor.js') }}"></script>
<script src="{{ mix('js/fortawesome-vendor.js') }}"></script>
<script src="{{ mix('js/bootstrap-vendor.js') }}"></script>
<script>
  window.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
  const screenBuilderScripts = @json($manager->getScripts());
  const task = @json($task);
  const userHasAccessToTask = {{ Auth::user()->can('update', $task) ? "true": "false" }};
  const userIsAdmin = {{ Auth::user()->is_administrator ? "true": "false" }};
  const userIsProcessManager = {{ Auth::user()->id === $task->process?->manager_id ? "true": "false" }};
  const screenFields = @json($screenFields);
</script>
<script src="{{ mix('js/tasks/loaderPreview.js')}}"></script>
<script>
  window.ProcessMaker.EventBus.$on("screen-renderer-init", (screen) => {
    if (screen.watchers_config) {
      screen.watchers_config.api.execute = @json(route('api.scripts.execute', ['script_id' => 'script_id', 'script_key' => 'script_key']));
      screen.watchers_config.api.execution = @json(route('api.scripts.execution', ['key' => 'execution_key']));
    } else {
      console.warn('Screen builder version does not have watchers');
    }
  });

  window.PM4ConfigOverrides = {
    requestFiles: @json($files),
    getScreenEndpoint: 'tasks/{{ $task->id }}/screens',
    postScriptEndpoint: '/scripts/execute/{id}?task_id={{ $task->id }}',
  };
</script>

<script src="{{mix('js/tasks/preview.js')}}"></script>

<style>
  .inline-input {
      margin-right: 6px;
  }

  .inline-button {
      background-color: rgb(109, 124, 136);
      font-weight: 100;
  }

  .input-and-select {
      width: 212px;
  }

  .multiselect__element span img {
      border-radius: 50%;
      height: 20px;
  }

  .multiselect__tags-wrap img {
      height: 15px;
      border-radius: 50%;
  }

  .multiselect__tag-icon:after {
      color: white !important;
  }

  .multiselect__option--highlight {
      background: #00bf9c !important;
  }

  .multiselect__option--selected.multiselect__option--highlight {
      background: #00bf9c !important;
  }

  .multiselect__tag {
      background: #788793 !important;
  }

  .multiselect__tag-icon:after {
      color: white !important;
  }
</style>
