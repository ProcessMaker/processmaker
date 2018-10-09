@extends('layouts.layout')

@section('title')
  {{__('Profile')}}
@endsection

@section('content')
<div id="profile">
  <profile></profile>
</div>
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_designer')])
@endsection

@section('js')
<script src="{{mix('js/admin/profile/index.js')}}"></script>
@endsection