@extends('layouts.layout')

@section('title')
  {{__('Processes Catalogue')}}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_processes_catalogue')])
@endsection

@section('content')
  <div id="open-process" class="px-3 page-content mb-0">
    <process-info
      :process-id={{$id}} 
      :permission="{{ \Auth::user()->hasPermissionsFor('processes', 'process-templates', 'pm-blocks') }}"
      is-documenter-installed="{{\ProcessMaker\PackageHelper::isPmPackageProcessDocumenterInstalled()}}"
    >
    </process-info>
  </div>
@endsection

@section('js')
  <script src="{{mix('js/processes-catalogue/open.js')}}"></script>
@endsection
