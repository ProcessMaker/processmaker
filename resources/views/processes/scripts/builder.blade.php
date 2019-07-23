@extends('layouts.layout')

@section('title')
  {{__('Edit Script')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', ['routes' => [
      __('Designer') => route('processes.index'),
      __('Scripts') => route('scripts.index'),
      __('Edit') . " " . $script->title => null,
  ]])
@endsection

@section('content')
<div id="script-container">
    <script-editor :script="{{$script}}" :script-format="'{{$scriptFormat}}'"></script-editor>
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

ol.breadcrumb {
  margin-bottom: 0;
  border-bottom: 0;
}
</style>
@endsection

@section('js')
    <script src="{{mix('js/processes/scripts/edit.js')}}"></script>
@endsection
