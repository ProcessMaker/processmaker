@extends('layouts.mobile')

@section('title')
  {{ __('Edit Task') }}
@endsection

@section('content_mobile')
<div v-cloak id="taskMobile" class="container-fluid">
  <navbar-task-mobile><navbar-task-mobile/>
</div>
@endsection

@section('js')
  <script src="{{mix('js/tasks/show.js')}}"></script>
  <script>
    const store = new Vuex.Store();
    const main = new Vue({
      mixins:addons,
      store: store,
      el: "#taskMobile",
      data: {},
      methods: {},
    });
  </script>
    
@endsection

@section('css')

@endsection