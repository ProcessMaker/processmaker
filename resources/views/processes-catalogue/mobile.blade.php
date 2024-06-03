@extends('layouts.mobile')
@section('title')
{{__($title)}}
@endsection
@section('content_mobile')
  <div class="px-3 page-content mb-0" id="processes-catalogue">
    <processes-catalogue></processes-catalogue>
  </div>
@endsection

@section('js')
  @foreach($manager->getScripts() as $script)
    <script src="{{$script}}"></script>
  @endforeach
  <script src="{{mix('js/processes-catalogue/index.js')}}"></script>
  <script>
    window.Processmaker.user = @json($currentUser);
    window.Processmaker.is_documenter_installed = {{ \ProcessMaker\PackageHelper::isPmPackageProcessDocumenterInstalled() ? 'true' : 'false' }};
    window.Processmaker.permission = {{ \Auth::user()->hasPermissionsFor('processes', 'process-templates', 'pm-blocks', 'projects') ? 'true' : 'false' }};
    window.Processmaker.can_create_processes = {{ \Auth::user()->can('create-processes') ? 'true' : 'false' }};
  </script>
@endsection

@section('css')

@endsection
