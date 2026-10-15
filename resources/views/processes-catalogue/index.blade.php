@extends('layouts.layoutnextvite', ['content_margin' => '', 'overflow-auto' => ''])

@section('title')
    {{__('Processes Catalogue')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_processes_catalogue')])
@endsection

@section('meta')
  <meta name="request-id" content="">
@endsection

@section('content')
  <div id="processes-catalogue" class="px-3 tw-h-[99%] tw-overflow-hidden">
    <processes-catalogue
      :process="{{$process ?? 0}}"
      :current-user-id="{{ \Auth::user()->id }}"
      :current-user="{{ \Auth::user() }}"
      :user-config="{{$userConfiguration ?? []}}"
    >
    </processes-catalogue>
  </div>
@endsection

@section('js')
  {{-- temporal must exist before loader (Vite modules run after deferred classic scripts) --}}
  <script>
    window.temporal = {};
    window.temporal.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
    window.temporal.isDocumenterInstalled = {{
      Js::from(\ProcessMaker\PackageHelper::isPmPackageProcessDocumenterInstalled())
    }};
    window.temporal.permission = {{
      Js::from(\Auth::user()->hasPermissionsFor('processes', 'process-templates', 'pm-blocks', 'projects', 'documentation'))
    }};
    window.temporal.defaultSavedSearch = {{{$defaultSavedSearch ?? 'null'}}};
    window.temporal.isTceCustomization = {{{config('app.tce_customization_enable') ? 'true' : 'false'}}};
    window.temporal.metricsApiEndpoint = `{{{$metricsApiEndpoint}}}`;
    window.temporal.userConfiguration = @json($userConfiguration ?? []);
    window.temporal.user = @json($currentUser);
    window.packages = window.temporal.packages;
  </script>
  @vite(['resources/js/processes-catalogue/loaderProcessesCatalogue.js'])
  @foreach($manager->getScripts() as $script)
    <script defer src="{{$script}}"></script>
  @endforeach
  @vite(['resources/js/processes-catalogue/processesCatalogue.js'])
@endsection
