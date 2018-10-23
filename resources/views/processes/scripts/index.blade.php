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
            <button type="button" class="btn btn-action text-light" data-toggle="modal" data-target="#addScript"><i class="fas fa-plus"></i> {{__('Script')}}</button>
          </div>
        </div>
        <script-listing :filter="filter"></script-listing>
      </div>
    </div>
  </div>
  <div class="modal" tabindex="-1" role="dialog" id="addScript">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{__('Add A Script')}}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          {!!Form::label('title', __('Title'))!!}
          {!!Form::text('title', null, ['class'=> 'form-control', 'v-model'=> 'title', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.title}'])!!}
          <div class="invalid-feedback" v-for="title in addError.title">@{{title}}</div>
        </div>
        <div class="form-group">
          {!!Form::label('description', __('Description'))!!}
          {!!Form::textarea('description', null, ['rows'=>'2','class'=> 'form-control', 'v-model'=> 'description', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.description}'])!!}
          <div class="invalid-feedback" v-for="description in addError.description">@{{description}}</div>
        </div>
        <div class="form-group">
          {!!Form::label('language', __('Language'))!!}
          {!!Form::select('language', [''=>__('Select'),'php' => 'PHP', 'lua' => 'Lua'], null, ['class'=> 'form-control', 'v-model'=> 'language', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.language}']);!!}
        <div class="invalid-feedback" v-for="language in addError.language">@{{language}}</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{__('Close')}}</button>
        <button type="button" class="btn btn-secondary" id="disabledForNow" @click="onSubmit" :disabled="submitted">{{__('Save')}}</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
  new Vue({
    el: '#addScript',
    data: {
      title: '',
      language: '',
      description: '',
      code: '[]',
      addError: {},
      submitted: false,
    },
    methods: {
      onSubmit() {
        this.submitted = true;
        ProcessMaker.apiClient.post("/scripts", {
          title: this.title,
          language: this.language,
          description: this.description,
          code: this.code
        })
        .then(response => {
          ProcessMaker.alert('{{__('Script successfully added')}}', 'success')
          window.location = "/processes/scripts/" + response.data.id
        })
        .catch(error => {
          if (error.response.status === 422) {
            this.addError = error.response.data.errors
          }
        })
        .finally(()=> {
          this.submitted = false
        })
      }
    }
  })
</script>
<script src="{{mix('js/processes/scripts/index.js')}}"></script>
@endsection
