@extends('layouts.mobile')

@section('title')
  {{ __('Edit Task') }}
@endsection

@section('navbar-mobile')
@endsection

@section('content_mobile')
<div v-cloak id="taskMobile">
  <navbar-task-mobile></navbar-task-mobile>
  
  <div class="d-flex flex-column" style="min-height: 100vh">
    <div class="flex-fill">
      <div class="row">
        <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
          <div class="card card-body p-3">
            <span>Task here</span>
          </div>
        </div>
      </div>
    </div>
  </div>
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