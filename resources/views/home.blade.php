@extends('layouts.layout')

@section('content')
<div class="container ml-2">
  <h1>Home</h1>

  <div id="home">
    <div id="inbox">
        <inbox></inbox>
    </div>
  </div>
</div>
@endsection

@section('sidebar')
  @include('sidebars.admin')
@endsection

@section('js')
<script>
new Vue({
  el: '#inbox'
});
</script>
@endsection
