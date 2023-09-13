@extends('layouts.mobile')
@section('title')
{{__($title)}}
@endsection
@section('content_mobile')
<div class="d-flex flex-column" style="min-height: 100vh" id="requests-listing">
<div class="flex-fill">
  <div class="row">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
      <div class="card card-body p-3">
          <span>Welcome Mobile ProcessMaker</span>
      </div>

    </div>


  </div>

  {{-- <div class="row">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
     <requests-nav-bar :type='type'></requests-nav-bar>
    </div>
  </div> --}}

  <div class="row">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
      <div>
        <b-nav pills>
          <div class="dropdown" left>
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-circle text-warning"></i>
              In Progress
              <i class="fas fa-caret-down"></i>
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              <a class="dropdown-item" href="#"><i class="fas fa-circle text-warning"></i> In Progress</a>
              <a class="dropdown-item" href="#"><i class="fas fa-circle text-primary"></i> Completed</a>
            </div>
          </div>


          <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-user"></i>
              <i class="fas fa-caret-down"></i>
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              <a class="dropdown-item" href="#"><i class="fas fa-user"></i> Requested by Me</a>
              <a class="dropdown-item" href="#"><i class="fas fa-users"></i> With me as Participant</a>
            </div>
          </div>
          <button class="btn btn-primary">
            <i class="fas fa-search"></i>
          </button>
        </b-nav>
      </div>
    </div>  
    
  </div>  
  
  


</div>
@endsection

@section('js')
<script src="{{ asset('../../js/requests/index.js') }}"></script>
@endsection

@section('css')

@endsection
