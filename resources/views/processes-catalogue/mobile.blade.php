@extends('layouts.mobile')
@section('title')
{{__($title)}}
@endsection
@section('content_mobile')
  <div class="page-content mb-0" id="processes-catalogue">
    <processes-catalogue
      :process="{{$process ?? 0}}"
      :current-user-id="{{ \Auth::user()->id }}"
      :current-user="{{ \Auth::user() }}"
    >
    </processes-catalogue>
  </div>
@endsection

@section('js')
  <script>
    window.ProcessMaker.isDocumenterInstalled = {{ Js::from(\ProcessMaker\PackageHelper::isPmPackageProcessDocumenterInstalled()) }};
    window.ProcessMaker.permission = {{ Js::from(\Auth::user()->hasPermissionsFor('processes', 'process-templates', 'pm-blocks', 'projects')) }};
  </script>
  @foreach($manager->getScripts() as $script)
    <script src="{{$script}}"></script>
  @endforeach
  <script src="{{mix('js/processes-catalogue/index.js')}}"></script>
  <script>
    window.Processmaker.user = @json($currentUser);
  </script>
@endsection

@section('css')

@endsection
