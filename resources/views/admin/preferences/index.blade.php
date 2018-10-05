@extends('layouts.layout')

@section('content')
  <div id="preferences">
    <preferences></preferences>
  </div>
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_admin')])
@endsection

@section('js')
  <script src="{{mix('js/admin/preferences/index.js')}}"></script>
@endsection