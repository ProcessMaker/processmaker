@extends('layouts.mobile')
@section('title')
  {{ __('Request Detail') }}
@endsection
@section('content_mobile')
<div v-cloak id="requestMobile">
  <navbar-request-mobile :title="request.name"></navbar-request-mobile>
  <div class="d-flex flex-column" style="min-height: 100vh">
    <div class="flex-fill">
      <div class="row">
        <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
          <div class="card card-body p-3">
            <div class="mt-1">
              <img class="img-under" src="https://h2o-digital.com/wp-content/uploads/2015/09/websites-why-you-should-never-use-under-construction-pages.jpg" alt="to do" />
            </div>
          </div>
        </div>
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
    const main = new Vue({
      el: "#requestMobile",
      mixins: addons,
      data() {
        return {
          request: @json($request),
        }
      },
      methods: {},
    });
  </script>
@endsection

@section('css')

@endsection
