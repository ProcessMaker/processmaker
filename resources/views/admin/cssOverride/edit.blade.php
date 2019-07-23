@extends('layouts.layout')

@section('title')
    {{__('Edit Process Category')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('CSS override') => null,
    ]])
    <div class="container" id="editCss" v-cloak>
        <div class="row" role="document">
            <div class="col">
                <div class="card card-body">
                    <div class="form-group">
                        {!! Form::label('fileLogo', __('Custom Logo (150x40 pixels)')) !!}
                        <div class="input-group">
                            <input type="text" name="fileLogo" class="form-control" v-model="formData.selectedFileLogo" placeholder="{{__('Choose logo image')}}">
                            <button @click="browseLogo" class="btn btn-secondary"><i class="fas fa-upload"></i>
                                {{__('Upload file')}}
                            </button>
                            <input type="file" class="custom-file-input" :class="{'is-invalid': errors.logo}" ref="customFileLogo" @change.prevent="onFileChangeLogo" accept="image/x-png,image/gif,image/jpeg" style="height: 1em;" >
                            <div class="invalid-feedback" v-for="error in errors.logo">@{{error}}</div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('fileIcon', __('Custom Icon (40x40 pixels)')) !!}
                        <div class="input-group">
                            <input type="text" name="fileIcon" class="form-control" v-model="formData.selectedFileIcon" placeholder="{{__('Choose icon image')}}">
                            <button @click="browseIcon" class="btn btn-secondary"><i class="fas fa-upload"></i>
                                {{__('Upload file')}}
                            </button>
                            <input type="file" class="custom-file-input" :class="{'is-invalid': errors.icon}" ref="customFileIcon" @change.prevent="onFileChangeIcon" accept="image/x-png,image/gif,image/jpeg" style="height: 1em;">
                            <div class="invalid-feedback" v-for="error in errors.icon">@{{error}}</div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('colors', __('Custom Colors')) !!}
                        <small class="d-block">{{ __('Click on the color value to use the color picker.') }}</small>
                        <ul class="list-group w-100">
                            <li class="list-group-item" v-for="item in customData">
                                <color-picker class="d-inline" v-model="item.value" picker="square" model="hex">
                                </color-picker>
                                <span class="badge d-inline" style=""> @{{item.value}} </span> @{{ item.title }}
                            </li>
                        </ul>
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
    <script src="{{mix('js/admin/cssOverride/edit.js')}}"></script>
    <script>

      new Vue({
        el: '#editCss',
        data() {
          return {
            formData: {
              selectedFileLogo: '',
              selectedFileIcon: '',
            },
            customData: [
              {
                id:'primary',
                value: '#3397e1',
                title: __('Primary')
              },
              {
                id: 'secondary',
                value: '#788793',
                title: __('Secondary')
              },
              {
                id: 'success',
                value: '#00bf9c',
                title: __('Success')
              },
              {
                id: 'info',
                value: '#17a2b8',
                title: __('Info')
              },
              {
                id: 'warning',
                value: '#fbbe02',
                title: __('Warning')
              },
              {
                id: 'danger',
                value: '#ed4757',
                title: __('Danger')
              },
              {
                id: 'light',
                value: '#ffffff',
                title: __('Light')
              }
            ],
            errors: {
              'logo': null,
              'icon': null
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
            window.location.href = '/processes/categories';
          },
          onUpdate() {
            this.resetErrors();
            ProcessMaker.apiClient.put('process_categories/' + this.formData.id, this.formData)
              .then(response => {
                ProcessMaker.alert('{{__('The category was saved.')}}', 'success');
                this.onClose();
              })
              .catch(error => {
                if (error.response.status && error.response.status === 422) {
                  this.errors = error.response.data.errors;
                }
              });
          },
          browseLogo() {
            this.$refs.customFileLogo.click();
          },
          onFileChangeLogo(e) {
            let files = e.target.files || e.dataTransfer.files;

            if (!files.length) {
              return;
            }

            this.formData.selectedFileLogo = files[0].name;
            this.formData.fileLogo = this.$refs.customFileLogo.files[0];
          },
          browseIcon() {
            this.$refs.customFileIcon.click();
          },
          onFileChangeIcon(e) {
            let files = e.target.files || e.dataTransfer.files;

            if (!files.length) {
              return;
            }

            this.formData.selectedFileIcon = files[0].name;
            this.formData.fileIcon = this.$refs.customFileIcon.files[0];
          },
        }
      });
    </script>
@endsection