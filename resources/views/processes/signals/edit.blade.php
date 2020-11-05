@extends('layouts.layout')

@section('title')
  {{__('Edit Signal')}}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', ['routes' => [
    __('Designer') => route('processes.index'),
    __('Signals') => route('signals.index'),
    __('Edit') . " " . $signal->getName() => null,
  ]])
@endsection

@section('content')
<div class="px-3" id="editSignal">
  <div class="row">
    <div class="col-12">
      <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
          <a class="nav-item nav-link active" id="nav-detail-tab" data-toggle="tab" href="#nav-detail" role="tab" aria-controls="nav-detail" aria-selected="true">
            {{__('Details')}}
          </a>
          <a class="nav-item nav-link" id="nav-catch-tab" data-toggle="tab" href="#nav-catch" role="tab" aria-controls="nav-catch" aria-selected="false">
            {{__('Catch Events')}}
          </a>
        </div>
      </nav>

      <div class="tab-content" id="nav-tabContent">

        <div class="card card-body border-top-0 tab-pane p-3 fade show active" id="nav-detail" role="tabpanel" aria-labelledby="nav-detail-tab">
          <div class="modal-body">
            <div class="form-signal">
                {!! Form::label('id', __('Id')) !!}<small class="ml-1">*</small>
                {!! Form::text('id', null, ['id' => 'id','class'=> 'form-control', 'v-model' =>
                'formData.id', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.id}']) !!}
                <small id="emailHelp" class="form-text text-muted">{{__('signal id must be distinct')}}</small>
                <div class="invalid-feedback" v-for="id in errors.id">@{{id}}</div>
            </div>
            <div class="form-signal">
                {!! Form::label('name', __('Name')) !!}<small class="ml-1">*</small>
                {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' =>
                'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
                <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
            </div>
          </div>
          <div class="d-flex justify-content-end mt-3">
              {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
              {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'onUpdate', 'id'=>'saveSingal']) !!}
          </div>
        </div>
        <div class="card card-body border-top-0 tab-pane p-3 fade" id="nav-catch" role="tabpanel" aria-labelledby="nav-catch-tab">

        </div>

      </div>
    </div>

  </div>
</div>
@endsection

@section('js')
<script>
  new Vue({
    el: '#editSignal',
    data() {
      return {
        showAddUserModal: false,
        formData: {
          id: @json($signal->getId()),
          name: @json($signal->getName()),
        },
        filter: '',
        errors: {
          'name': null,
          'id': null,
        },
      }
    },
    methods: {
      onCloseAddUser() {
        this.selectedUsers = [];
      },
      resetErrors() {
        this.errors = Object.assign({}, {
          id: null,
          name: null,
        });
      },
      onClose() {
        window.location.href = '/designer/signals';
      },
      onUpdate() {
        this.resetErrors();
        ProcessMaker.apiClient.put('signals/' + this.formData.id, this.formData)
          .then(response => {
            ProcessMaker.alert(this.$t('Update Signal Successfully'), 'success');
            this.onClose();
          })
          .catch(error => {
            //define how display errors
            if (error.response.status && error.response.status === 422) {
              // Validation error
              this.errors = error.response.data.errors;
            }
          });
      },
    }
  });
</script>
@endsection
