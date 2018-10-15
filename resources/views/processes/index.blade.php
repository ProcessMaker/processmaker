@extends('layouts.layout')

@section('title')
  {{__('Processes')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="container page-content" id="processIndex">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Processes')}}</h1>
                        <input id="processes-listing-search" v-model="filter" class="form-control col-sm-3"
                               placeholder="{{__('Search')}}...">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <button type="button" class="btn btn-action text-light" data-toggle="modal" data-target="#addProcess"><i class="fas fa-plus"></i> {{__('Process')}}</button>
                    </div>
                </div>
                <processes-listing ref="processListing" :filter="filter" v-on:edit="edit"
                                   v-on:reload="reload"></processes-listing>
            </div>
        </div>
    </div>
      <div class="modal" tabindex="-1" role="dialog" id="addProcess">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{__('Add A Process')}}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          {!!Form::label('title', 'Title');!!}
          {!!Form::text('title', null, ['class'=> 'form-control'])!!}
          <div class="invalid-feedback"></div>
        </div>
        <div class="form-group">
          {!!Form::label('description', 'Description');!!}
          {!!Form::textarea('description', null, ['class'=> 'form-control', 'rows' => '3'])!!}
          <div class="invalid-feedback"></div>
        </div>
        <div class="form-group">
          {!!Form::label('category', 'Category');!!}
          {!!Form::select('category', [], null, ['class'=> 'form-control'])!!}
          <div class="invalid-feedback"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-secondary" id="disabledForNow" @click="onSubmit" :disabled="submitted">Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
    <script src="{{mix('js/processes/index.js')}}"></script>
@endsection
