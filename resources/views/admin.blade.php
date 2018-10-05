@extends('layouts.layout')

@section('content')
<div class="container ml-2">
  <h1>Admin</h1>
</div>
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('js')

@endsection
