@extends('layouts.layout', ['content_margin'=>''])

@section('title')
  {{__('Edit Process')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="sr-only">{{ __('A mouse and keyboard are required to use the modeler.') }}</div>
    <div id="modeler-app">
    </div>
@endsection

@section('meta')
<meta name="anonymous-user-id" content="{{ app(\ProcessMaker\Models\AnonymousUser::class)->id }}">
@endsection

@section('css')
<style>
div.main {
  position: relative;
}

#modeler-app {
  position: relative;
  width: 100%;
  max-width: 100%;
  height: 100%;
  max-height: 100%;
}
</style>
@yield('extra_css')
@endsection

@section('js')
  <script src="{{mix('js/leave-warning.js')}}"></script>
  <script>
  const breadcrumbData = [
    {
      'text':'{{__('Designer')}}',
      'url':'{{route('processes.index')}}'
    },
    {
      'text':'{{__('Processes')}}',
      'url':'{{route('processes.index')}}'
    },
    {
      'text':'{{__('Modeler')}}',
      'url':''
    },
    {
      'text':'{{$process->name}}',
      'url':''
    },
  ]

  window.ProcessMaker.multiplayer = {
    broadcaster: "{{config('multiplayer.default')}}",
    host: "{{config('multiplayer.url')}}",
    enabled: "{{ config('multiplayer.enabled') }}",
  };
  window.ProcessMaker.PMBlockList = @json($pmBlockList);
  window.ProcessMaker.ExternalIntegrationsList = @json($externalIntegrationsList);
  window.ProcessMaker.modeler = {
    process: @json($process),
    autoSaveDelay: @json($autoSaveDelay),
    xml: @json($process->bpmn),
    isVersionsInstalled: @json($isVersionsInstalled),
    isDraft: @json($isDraft),
    processName: @json($process->name),
    signalPermissions: @json($signalPermissions),
    // list of toggles in assignment rules
    configurables: ['SECTION_TITLE:ASSIGNMENT_OPTIONS', 'SELF_SERVICE', 'LOCK_TASK_ASSIGNMENT', 'ALLOW_REASSIGNMENT'],
    // list of items for assignment Types dropdown list
    assignmentTypes: [
      { value: "user_group", label: "Users / Groups" },
      { value: "previous_task_assignee", label: "Previous Task Assignee" },
      { value: "requester", label: "Request Starter" },
      { value: "process_variable", label: "Process Variable" },
      { value: "rule_expression", label: "Rule Expression" },
      { value: "process_manager", label: "Process Manager" },
    ],
    countProcessCategories: @json($countProcessCategories),
    countScreenCategories: @json($countScreenCategories),
    countScriptCategories: @json($countScriptCategories),
    screenTypes: @json($screenTypes),
    scriptExecutors: @json($scriptExecutors),
    isProjectsInstalled: @json($isProjectsInstalled),
    isPackageAiInstalled: @json($isPackageAiInstalled),
    isAiGenerated: @json($isAiGenerated),
    runAsUserDefault: @json($runAsUserDefault),
    abPublish: @json($abPublish),
    alternative: @json($alternative),
    startingPoints: @json($startingPoints),
    resumePoints: @json($resumePoints),
  }
  const warnings = @json($process->warnings);

  window.ProcessMaker.EventBus.$on('modeler-start', ({ loadXML, addWarnings, addBreadcrumbs }) => {
    loadXML(window.ProcessMaker.modeler.xml);
    addWarnings(warnings || []);
    addBreadcrumbs(breadcrumbData || []);
  });
  </script>
    @foreach($manager->getScriptWithParams() as $params)
      <script
      @foreach ($params as $key => $value)
        @if (is_bool($value))
          {{ $key }}
        @else
          {{ $key }}="{{ $value }}"
        @endif
      @endforeach
      ></script>
    @endforeach
  <script src="{{ mix('js/processes/modeler/index.js') }}"></script>
  @yield('extra_js')
@endsection
