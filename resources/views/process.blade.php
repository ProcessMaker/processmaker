@extends('layouts.layout')

@section('content')

  <h1>Process</h1>

  <div class="container">
    <my-vuetable api-url="/test"></my-vuetable>
  </div>
@endsection

@section('sidebar')
  @include('sidebars.default', ['sidebar'=> $sidebar_admin])
@endsection

@section('js')
<script>

</script>
@endsection
