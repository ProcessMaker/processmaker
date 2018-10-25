@extends('layouts.layout')

@section('title')
{{__('Processes')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
<div class="container row-fluid page-content" id="processIndex">
  <div class="row">
    <div class="col-md-1">
      <h1 class="page-title">{{__('Processes')}}</h1>
    </div>
    <div class="col-md-7">
      <input id="processes-listing-search" v-model="filter" class="form-control col-sm-3" placeholder="{{__('Search')}}...">
    </div>
    <div class="col-md-4" align="right">
      <button type="button" class="btn btn-action text-light" data-toggle="modal" data-target="#addProcess"><i class="fas fa-plus"></i> {{__('Process')}}</button>
    </div>
  </div>
  <processes-listing ref="processListing" :filter="filter" v-on:edit="edit" v-on:reload="reload"></processes-listing>
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
			{!!Form::label('name', 'Name');!!}
			{!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.name}'])!!}
			<div class="invalid-feedback" v-for="name in addError.name">@{{name}}</div>
        </div>
        <div class="form-group">
			{!!Form::label('description', 'Description');!!}
			{!!Form::textarea('description', null, ['class'=> 'form-control', 'rows' => '3', 'v-model'=> 'description', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.description}'])!!}
			<div class="invalid-feedback" v-for="description in addError.description">@{{description}}</div>
        </div>
        <div class="form-group">
			{!!Form::label('process_category_id', 'Category');!!}
			{!!Form::select('process_category_id', $processCategories, null, ['class'=> 'form-control', 'v-model'=> 'categoryOptions', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.process_category_id}'])!!}
			<div class="invalid-feedback" v-for="category in addError.process_category_id">@{{category}}</div>
        </div>
        <div class="form-group">
			{!!Form::label('status', 'Status');!!}
			{!!Form::select('status', [''=>'Select','ACTIVE'=> 'Active', 'INACTIVE'=> 'Inactive'], null, ['class'=> 'form-control', 'v-model'=> 'status', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addError.status}'])!!}
			<div class="invalid-feedback" v-for="status in addError.status">@{{status}}</div>
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
<script>
	new Vue({
	el: '#addProcess',
	data: {
		name: '',
		categoryOptions: '',
		description: '',
		addError: {},
		submitted: false,
		status: ''
	},
	methods: {
		onSubmit() {
		this.submitted = true;
		ProcessMaker.apiClient.post("/processes", {
			name: this.name,
			description: this.description,
			process_category_id: this.process_category_id,
			status: this.status
			})
			.then(response => {
			ProcessMaker.alert('Process successfully added', 'success')
			window.location = "/processes/" + response.data.id
			})
			.catch(error => {
			if (error.response.status === 422) {
				this.addError = error.response.data.errors
			}
			})
			.finally(() => {
			this.submitted = false
			})
		}
	}
	})
</script>
<script src="{{mix('js/processes/index.js')}}"></script>
@endsection
