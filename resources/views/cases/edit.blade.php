@extends('layouts.layout')

@section('title')
  {{ __('Case Detail') }}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', ['routes' => [
      __('Cases') => route('cases.index'),
  ]])
@endsection

@section('content')
<div id="case-detail" class="containe-fluid mr-3 ml-3 px-3 bg-light">
  <case-detail></case-detail>
</div>
@endsection

@section('js')
  <script src="{{mix('js/cases/edit.js')}}"></script>
@endsection
