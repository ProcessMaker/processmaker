@extends('layouts.layout', ['title' => __('About')])

@section('content')
  <div id="about">
    <about></about>
  </div>
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_admin')])
@endsection

@section('js')
  <script src="{{mix('js/management/about/index.js')}}"></script>
@endsection