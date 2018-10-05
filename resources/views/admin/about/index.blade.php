@extends('layouts.layout'])

@section('title')
  {{__('About')}}
@endsection

@Section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@Section('content')
@endsection

@Section('js')
@endsection