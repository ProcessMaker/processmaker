@extends('layouts.layout')

@section('title')
    {{__('Edit Category')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        $itemsTitle => route($itemsRoute),
        $titleMenu => route($routeMenu),
        __('Edit') . " " . $category->name => null,
    ]])
@endsection
@section('content')
    <div class="container" id="editProcessCategory">
        <div class="row">
            <div class="col">
                <div class="card card-body">
                    <div class="form-group">
                        {!! Form::label('name', __('Category Name')) !!}
                        {!! Form::text('name', null, ['id' => 'name','class'=> 'form-control', 'v-model' => 'formData.name',
                        'v-bind:class' => '{"form-control":true, "is-invalid":errors.name}']) !!}
                        <small class="form-text text-muted"
                               v-if="! errors.name">{{__('The category name must be distinct.') }}</small>
                        <div class="invalid-feedback" v-if="errors.name">@{{errors.name[0]}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('status', __('Status')) !!}
                        {!! Form::select('status', ['ACTIVE' => __('active'), 'INACTIVE' => __('inactive')], null, ['id' => 'status',
                        'class' => 'form-control', 'v-model' => 'formData.status', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.status}']) !!}
                        <div class="invalid-feedback" v-if="errors.status">@{{errors.status[0]}}</div>
                    </div>
                    <br>
                    <div class="text-right">
                        {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                        {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
      new Vue({
        el: '#editProcessCategory',
        data() {
          return {
            formData: @json($category),
            location: @json($location),
            route: @json($route),
            errors: {
              'name': null,
              'status': null
            }
          }
        },
        methods: {
          resetErrors() {
            this.errors = Object.assign({}, {
              name: null,
              description: null,
              status: null
            });
          },
          onClose() {
            window.location.href = this.location;
          },
          onUpdate() {
            this.resetErrors();
            ProcessMaker.apiClient.put(this.route + '/' + this.formData.id, this.formData)
              .then(response => {
                ProcessMaker.alert('{{__('The category was saved.')}}', 'success');
                this.onClose();
              })
              .catch(error => {
                if (error.response.status && error.response.status === 422) {
                  this.errors = error.response.data.errors;
                }
              });
          }
        }
      });
    </script>
@endsection
