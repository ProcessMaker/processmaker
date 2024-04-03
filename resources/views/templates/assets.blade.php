@extends('layouts.layout')

@section('title')
  {{ __('Template Assets') }}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', [
      'routes' => [
          __('Designer') => route('processes.index'),
          __('Processes') => route('processes.index'),
      ],
  ])
@endsection

@section('content')
  <div id="template-asset-manager">
    <template-assets-view
      :assets="assets"
      :name="name"
      :response-id="responseId"
      :request="request"
      :redirect-to="redirectTo"
      :wizard-template-uuid="wizardTemplateUuid"
    />
  </div>
@endsection

@section('js')
  <script>
    window.ProcessMaker.importIsRunning = {{ $importIsRunning ? 'true' : 'false' }};
    window.ProcessMaker.queueImports = {{ config('app.queue_imports') ? 'true' : 'false' }};
  </script>
  <script src="{{ mix('js/templates/assets.js') }}"></script>
@endsection
