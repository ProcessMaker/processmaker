@extends('layouts.layout')

@section('title')
    {{ __('Change Styles') }}
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
                            <input type="text" name="fileLogo" class="form-control" v-model="fileLogo.selectedFile"
                                   placeholder="{{__('Choose logo image')}}">
                            <button @click="browseLogo" class="btn btn-secondary"><i class="fas fa-upload"></i>
                                {{__('Upload file')}}
                            </button>
                            <input type="file" class="custom-file-input" :class="{'is-invalid': errors.logo}"
                                   ref="customFileLogo" @change.prevent="onFileChangeLogo"
                                   accept="image/x-png,image/gif,image/jpeg" style="height: 1em;">
                            <div class="invalid-feedback" v-for="error in errors.logo">@{{error}}</div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('fileIcon', __('Custom Icon (40x40 pixels)')) !!}
                        <div class="input-group">
                            <input type="text" name="fileIcon" class="form-control" v-model="fileIcon.selectedFile"
                                   placeholder="{{__('Choose icon image')}}">
                            <button @click="browseIcon" class="btn btn-secondary"><i class="fas fa-upload"></i>
                                {{__('Upload file')}}
                            </button>
                            <input type="file" class="custom-file-input" :class="{'is-invalid': errors.icon}"
                                   ref="customFileIcon" @change.prevent="onFileChangeIcon"
                                   accept="image/x-png,image/gif,image/jpeg" style="height: 1em;">
                            <div class="invalid-feedback" v-for="error in errors.icon">@{{error}}</div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('colors', __('Custom Colors')) !!}
                        <small class="d-block">{{ __('Click on the color value to use the color picker.') }}</small>
                        <ul class="list-group w-100">
                            <li class="list-group-item" v-for="item in customData">
                                <div class="input-group">
                                    <color-picker :color="item.value" v-model="item.value"></color-picker>
                                    <div class="input-group-append">
                                        <span class="pl-2">@{{ item.title }}</span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <br>
                    <div class="text-right">
                        {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                        {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onSubmit']) !!}
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
            config: @json($config),
            key: 'css-override',
            fileLogo: {
              file: null,
              selectedFile: null,
            },
            fileIcon: {
              file: null,
              selectedFile: null,
            },
            colors: null,
            optionsData: [
              {
                id: '$primary',
                value: '#3397e1',
                title: __('Primary')
              },
              {
                id: '$secondary',
                value: '#788793',
                title: __('Secondary')
              },
              {
                id: '$success',
                value: '#00bf9c',
                title: __('Success')
              },
              {
                id: '$info',
                value: '#17a2b8',
                title: __('Info')
              },
              {
                id: '$warning',
                value: '#fbbe02',
                title: __('Warning')
              },
              {
                id: '$danger',
                value: '#ed4757',
                title: __('Danger')
              },
              {
                id: '$light',
                value: '#ffffff',
                title: __('Light')
              }
            ],
            errors: {
              'logo': null,
              'icon': null,
              'colors': null,
            }
          }
        },
        watch: {
          config: {
            immediate: true,
            handler() {
              if (!this.config || !this.config.config) {
                return;
              }
              if (this.config.config.logo != "null") {
                this.fileLogo.selectedFile = this.config.config.logo;
              }
              if (this.config.config.icon != "null") {
                this.fileIcon.selectedFile = this.config.config.icon;
              }
            }
          },
        },
        computed: {
          customData() {
            let data = this.optionsData;
            if (this.config && this.config.config.variables) {
              data = JSON.parse(this.config.config.variables);
            }
            return data;
          }
        },
        methods: {
          resetErrors() {
            this.errors = Object.assign({}, {
              logo: null,
              icon: null,
              colors: null
            });
          },
          onClose() {
            window.location.href = '/admin/css';
          },
          onSubmit() {
            this.resetErrors();

            let formData = new FormData();
            formData.append('key', this.key);
            formData.append('fileLogoName', this.fileLogo.selectedFile);
            formData.append('fileIconName', this.fileIcon.selectedFile);
            formData.append('fileLogo', this.fileLogo.file);
            formData.append('fileIcon', this.fileIcon.file);
            formData.append('variables', JSON.stringify(this.customData));

            this.onCreate(formData);
          },
          onCreate(data) {
            ProcessMaker.apiClient.post('css_settings', data)
              .then(response => {
                ProcessMaker.alert('{{__('The Settings css was saved.')}}', 'success', 5, true);
                this.onClose();
              })
              .catch(error => {
                if (error.response.status && error.response.status === 422) {
                  this.errors = error.response.data.errors;
                }
              });
          },
          onUpdate(data) {
            ProcessMaker.apiClient.put('css_settings', data)
              .then(response => {
                ProcessMaker.alert('{{__('The Settings css was update.')}}', 'success', 5, true);
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

            this.fileLogo.selectedFile = files[0].name;
            this.fileLogo.file = this.$refs.customFileLogo.files[0];
          },
          browseIcon() {
            this.$refs.customFileIcon.click();
          },
          onFileChangeIcon(e) {
            let files = e.target.files || e.dataTransfer.files;

            if (!files.length) {
              return;
            }

            this.fileIcon.selectedFile = files[0].name;
            this.fileIcon.file = this.$refs.customFileIcon.files[0];
          },
        }
      });
    </script>
@endsection
