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
    __('Edit') . " " . $signal['name'] => null,
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
            <div class="form-group">
              {!! Form::label('name', __('Signal Name')) !!}
              {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' =>
              'formData.name', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}']) !!}
              <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
            </div>
            <div class="form-group">
              {!! Form::label('id', __('Signal ID')) !!}
              {!! Form::text('id', null, ['id' => 'id','class'=> 'form-control', 'v-model' =>
              'formData.id', 'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.id}']) !!}
              <small id="emailHelp" class="form-text text-muted"></small>
              <div class="invalid-feedback" v-for="id in errors.id">@{{id}}</div>
            </div>
            <div class="form-group">
              {!! Form::textarea('detail', null, ['id' => 'detail', 'rows' => 4, 'class'=> 'form-control', 'v-bind:placeholder' => '$t("Additional Details (optional)")',
              'v-model' => 'formData.detail', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.detail}']) !!}
              <div class="invalid-feedback" v-if="errors.detail">@{{errors.detail[0]}}</div>
            </div>
          </div>
          @isset($addons)
            @foreach ($addons as $addon)
              {!! __($addon['content']) !!}
            @endforeach
          @endisset
          @if(!hasPackage('package-data-sources'))
          <div class="card-footer text-right mt-3">
            {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
            {!! Form::button(__('Confirm and Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'onUpdate', 'id'=>'saveSingal']) !!}
          </div>
          @endif
        </div>
        <div class="card card-body border-top-0 tab-pane p-3 fade" id="nav-catch" role="tabpanel" aria-labelledby="nav-catch-tab">
          <catch-listing ref="catchList" :filter="filter" items="{{json_encode($signal['processes'])}}" />
        </div>

      </div>
    </div>

  </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/processes/signals/edit.js')}}"></script>

<script>
  new Vue({
    el: '#editSignal',
    mixins: addons,
    data() {
      return {
        showAddUserModal: false,
        formData: @json($signal),
        originalId: (@json($signal)).id,
        filter: '',
        errors: {
          'name': null,
          'id': null,
        },
      }
    },
    methods: {
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
        ProcessMaker.apiClient.put('signals/' + this.originalId, this.formData)
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
