@extends('layouts.layout')

@section('content')
<div class="container ml-2">
  <h1>Run</h1>

  <div id="home">
    <inbox></inbox>
  </div>
</div>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection

@section('js')
<script>
new Vue({
    el: '#home'
});
</script>
@endsection
