@extends('layouts.layout')

@section('content')
<div class="container ml-2">
  <h1>Admin</h1>
  <!-- <p>UI Customization</p>
  <div>
  <p>Add your company logo</p>
</div> -->
</div>
@endsection

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_admin])
@endsection

@section('js')

@endsection
