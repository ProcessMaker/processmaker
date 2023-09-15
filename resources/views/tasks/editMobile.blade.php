@extends('layouts.mobile')

@section('title')
  {{__('Edit Task')}}
@endsection

@section('content_mobile')
<div v-cloak id="taskMobile">
  <div id="navbarTaskMobile" class="d-flex bg-primary p-2 justify-content-between">
    <button
      type="buttom"
      class="dropleft btn btn-primary"
      @click="returnTasks()"
    >
      <i class="fas fa-arrow-left"></i>
    </button>
    <div>
      <button
        type="buttom"
        class="dropleft btn btn-primary text-capitalize"
      >
        <i class="fas fa-chevron-left mr-1"></i>
        {{__('Prev')}}
      </button>
      <button
        type="buttom"
        class="dropleft btn btn-primary text-capitalize"
      >
        {{__('Next')}}
        <i class="fas fa-chevron-right ml-1"></i>
    </button>
    </div>
    <button
      type="buttom"
      class="dropleft btn btn-primary"
    >
      <i class="fas fa-info-circle"></i>
    </button>
  </div>
  
  <div class="d-flex flex-column" style="min-height: 100vh">
  <div class="flex-fill">
    <div class="row">
      <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
        <div class="card card-body p-3">
            <span>Welcome Mobile ProcessMaker</span>
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
      methods: {
        returnTasks() {
            window.location = `/tasks`
        },
      },
    });
  </script>
    
@endsection

@section('css')

@endsection