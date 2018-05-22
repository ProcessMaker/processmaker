@extends('layouts.layout')

@section('content')

  <h1>Admin</h1>

  <div class="container">
    <my-vuetable api-url="/test"></my-vuetable>
  </div>
@endsection

@section('sidebar')
  @include('sidebars.manage')
@endsection

@section('js')
<script>

</script>
@endsection
