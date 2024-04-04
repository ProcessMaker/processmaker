@extends('layouts.layout')

@section('title')
    {{__('Processes Catalogue')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_processes_catalogue')])
@endsection

@section('content')
  <div class="px-3 page-content mb-0" id="processes-catalogue">
    <processes-catalogue
      :process="{{$process ?? 0}}"
      :launchpad="{{$launchpad ?? 0}}"
      :current-user-id="{{ \Auth::user()->id }}"
      :permission="{{ \Auth::user()->hasPermissionsFor('processes', 'process-templates', 'pm-blocks', 'projects') }}"
      :current-user="{{ \Auth::user() }}"
      is-documenter-installed="{{\ProcessMaker\PackageHelper::isPmPackageProcessDocumenterInstalled()}}"
    >
  </processes-catalogue>
  </div>
@endsection

@section('js')
  <script src="{{mix('js/processes-catalogue/index.js')}}"></script>
@endsection
