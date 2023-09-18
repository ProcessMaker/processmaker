@extends('layouts.mobile')
@section('title')
  {{ __('Request Detail') }}
@endsection
@section('content_mobile')
<div id="requestMobile" class="d-flex flex-column" style="min-height: 100vh">
  <navbar-request-mobile></navbar-request-mobile>
  rrrrrrrrrr
  <div class="flex-fill">
    <div class="row">
      <div class="mt-1">
        <img class="img-under" src="https://h2o-digital.com/wp-content/uploads/2015/09/websites-why-you-should-never-use-under-construction-pages.jpg" alt="to do" />
      </div>
    </div>
  </div>
</div>
<style>
  .img-under {
    width: 100%;
  }
</style>
@endsection

@section('js')
  <script src="{{ mix('js/requests/show.js') }}"></script>

  <script>
    const store = new Vuex.Store();
    const main = new Vue({
      mixins:addons,
      store: store,
      el: "#requestMobile",
      data: {},
      methods: {},
    });
  </script>
@endsection

@section('css')

@endsection
