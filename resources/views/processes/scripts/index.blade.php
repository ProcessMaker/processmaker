@extends('layouts.layout')
@section('title')
  {{__('Scripts')}}
@endsection  

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
  <div class="container page-content" id="scriptIndex">
    <div class="row">
      <div class="col-sm-12">
        <div class="row">
          <div class="col-md-8 d-flex align-items-center col-sm-12">
            <h1 class="page-title">{{__('Scripts')}}</h1>
            <input id="script-listing-search" v-model="filter" class="form-control col-sm-3"
                   placeholder="{{__('Search')}}...">
          </div>
          <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
            <a href="#" class="btn btn-action">
              <i class="fas fa-plus"></i> {{__('Script')}}
            </a>
          </div>
        </div>
        <script-listing :filter="filter"></script-listing>
      </div>
    </div>
  </div>
@endsection

@section('js')
  <script src="{{mix('js/processes/scripts/index.js')}}"></script>
@endsection
