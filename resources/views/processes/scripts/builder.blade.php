@extends('layouts.layout', ['content_margin' => ''])

@section('title')
  {{ __('Edit Script') }}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', [
      'routes' => [
          __('Designer') => route('processes.index'),
          __('Scripts') => route('scripts.index'),
          __('Edit') . ' ' . $script->title => null,
      ],
  ])
@endsection

@section('content')
  <div id="script-container">
    <script-editor 
      :script="{{ $script }}" 
      :script-executor='{!! json_encode($script->scriptExecutor) !!}'
      test-data="{{ json_encode($testData, JSON_PRETTY_PRINT) }}" 
      :auto-save-delay="{{ $autoSaveDelay }}"
      :is-versions-installed="@json($isVersionsInstalled)" 
      :is-draft="@json($isDraft)"
      :package-ai="{{ hasPackage('package-ai') ? true : false }}"
      >
    </script-editor>
  </div>
@endsection

@section('css')
  <style>
    div.main {
      position: relative;
    }

    #script-container {
      position: absolute;
      width: 100%;
      max-width: 100%;
      height: 100%;
      max-height: 100%;
      overflow: hidden;
    }
  </style>
@endsection

@section('js')
  <script src="{{mix('js/leave-warning.js')}}"></script>
  @foreach ($manager->getScripts() as $script)
    <script src="{{ $script }}"></script>
  @endforeach
  <script src="{{ mix('js/processes/scripts/edit.js') }}"></script>
@endsection
