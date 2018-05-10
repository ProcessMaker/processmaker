@extends('layouts.layout')

@section('content')
<div class="container ">
  <h1>Run</h1>

  <div>
    <my-vuetable api-url="/test"></my-vuetable>
  </div>
</div>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection

@section('js')
<script>

</script>
@endsection
