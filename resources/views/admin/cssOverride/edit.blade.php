@extends('layouts.layout')

@section('title')
    {{ __('Customize UI') }}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Customize UI') => null,
    ]])
@endsection
@section('content')
    <div class="container" id="editCss" v-cloak>
        <div class="row" role="document">
            <div class="col">
                <div class="card card-body p-3">
                    <div class="form-group">
                        {!! Form::label('fileLogin', __('Custom Login Logo')) !!}
                        <small class="d-block">{{ __('We recommended a transparent PNG at :size pixels.', ['size' => '292x52']) }}</small>
                        <div class="input-group">
                            <input type="text" name="fileLogin" class="form-control" v-model="fileLogin.selectedFile"
                                   placeholder="{{__('Choose a login logo image')}}">
                            <button type="button" @click="browseLogin" class="btn btn-secondary"><i class="fas fa-upload"></i>
                                {{__('Upload file')}}
                            </button>
                            <input type="file" class="custom-file-input" :class="{'is-invalid': errors.logo}"
                                   ref="customFileLogin" @change.prevent="onFileChangeLogin"
                                   accept="image/x-png,image/gif,image/jpeg" style="height: 1em;">
                            <div class="invalid-feedback d-block" v-for="error in errors.fileLogin">@{{error}}</div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('fileLogo', __('Custom Logo')) !!}
                        <small class="d-block">{{ __('Use a transparent PNG at :size pixels for best results.', ['size' => '150x40']) }}</small>
                        <div class="input-group">
                            <input type="text" name="fileLogo" class="form-control" v-model="fileLogo.selectedFile"
                                   placeholder="{{__('Choose logo image')}}">
                            <button type="button" @click="browseLogo" class="btn btn-secondary"><i class="fas fa-upload"></i>
                                {{__('Upload file')}}
                            </button>
                            <input type="file" class="custom-file-input" :class="{'is-invalid': errors.logo}"
                                   ref="customFileLogo" @change.prevent="onFileChangeLogo"
                                   accept="image/x-png,image/gif,image/jpeg" style="height: 1em;">
                            <div class="invalid-feedback" v-for="error in errors.fileLogo">@{{error}}</div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('fileIcon', __('Custom Icon')) !!}
                        <small class="d-block">{{ __('Use a transparent PNG at :size pixels for best results.', ['size' => '40x40']) }}</small>
                        <div class="input-group">
                            <input type="text" name="fileIcon" class="form-control" v-model="fileIcon.selectedFile"
                                   placeholder="{{__('Choose icon image')}}">
                            <button type="button" @click="browseIcon" class="btn btn-secondary"><i class="fas fa-upload"></i>
                                {{__('Upload file')}}
                            </button>
                            <input type="file" class="custom-file-input" :class="{'is-invalid': errors.icon}"
                                   ref="customFileIcon" @change.prevent="onFileChangeIcon"
                                   accept="image/x-png,image/gif,image/jpeg" style="height: 1em;">
                            <div class="invalid-feedback d-block" v-for="error in errors.fileIcon">@{{error}}</div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('colors', __('Custom Colors')) !!}
                        <small class="d-block">{{ __('Click on the color value to use the color picker.') }}</small>
                        <ul class="list-group w-100">
                            <li class="list-group-item" v-for="item in customColors">
                                <div class="input-group">
                                    <div v-bind:class = "(item.value == '#000000') ? 'text-light input-group-prepend' : 'input-group-prepend'">
                                        <color-picker :color="item.value" v-model="item.value"></color-picker>
                                    </div>
                                    <div class="input-group-append">
                                        <span class="pl-2">@{{ item.title }}</span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="form-group">
                        {!! Form::label('fileIcon', __('Custom Font')) !!}
                        <multiselect v-model="selectedSansSerifFont"
                                     placeholder="{{__('Type to search')}}"
                                     :options="fontsDefault"
                                     :multiple="false"
                                     :show-labels="false"
                                     :searchable="true"
                                     track-by="id"
                                     label="title"
                        >
                            <template slot="noResult">
                                {{ __('No elements found. Consider changing the search query.') }}
                            </template>
                            <template slot="noOptions">
                                {{ __('No Data Available') }}
                            </template>
                            <template slot="singleLabel" slot-scope="props">
                                <span :style="font(props.option.id)">@{{ props.option.title }}</span>
                            </template>
                            <template slot="option" slot-scope="props">
                                <span :style="font(props.option.id)">@{{ props.option.title }}</span>
                            </template>
                        </multiselect>
                    </div>
                    <br>
                    <div class="d-flex">
                        {!! Form::button('<i class="fas fa-undo"></i> ' . __('Reset'), ['class'=>'btn btn-outline-danger', '@click' => 'onReset', ':disabled' => '!config' ]) !!}
                        {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary ml-auto', '@click' => 'onClose']) !!}
                        {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-3', '@click' => 'onSubmit']) !!}
                    </div>
                </div>
            </div>
        </div>

        <b-modal id="modalLoading"
                 ref="modalLoading"
                 v-bind:hide-header="true"
                 v-bind:hide-footer="true"
                 v-bind:no-close-on-backdrop="true"
                 v-bind:no-close-on-esc="true"
                 v-bind:hide-header-close="true">
            <div class="container text-center">
                <div class="icon-container m-4">
                    <svg class="lds-gear" width="100%" height="50%" xmlns="http://www.w3.org/2000/svg"
                         xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 100 100"
                         preserveAspectRatio="xMidYMid">
                        <g transform="translate(50 50)">
                            <g transform="rotate(248.825)">
                                <animateTransform attributeName="transform" type="rotate" values="0;360"
                                                  keyTimes="0;1" dur="4.7s"
                                                  repeatCount="indefinite">
                                </animateTransform>
                                <path d="M37.43995192304605 -6.5 L47.43995192304605 -6.5 L47.43995192304605 6.5 L37.43995192304605 6.5 A38 38 0 0 1 35.67394948182593 13.090810836924174 L35.67394948182593 13.090810836924174 L44.33420351967032 18.090810836924174 L37.83420351967032 29.34914108612188 L29.17394948182593 24.34914108612188 A38 38 0 0 1 24.34914108612188 29.17394948182593 L24.34914108612188 29.17394948182593 L29.34914108612188 37.83420351967032 L18.090810836924184 44.33420351967032 L13.090810836924183 35.67394948182593 A38 38 0 0 1 6.5 37.43995192304605 L6.5 37.43995192304605 L6.500000000000001 47.43995192304605 L-6.499999999999995 47.43995192304606 L-6.499999999999996 37.43995192304606 A38 38 0 0 1 -13.09081083692417 35.67394948182593 L-13.09081083692417 35.67394948182593 L-18.09081083692417 44.33420351967032 L-29.34914108612187 37.834203519670325 L-24.349141086121872 29.173949481825936 A38 38 0 0 1 -29.17394948182592 24.34914108612189 L-29.17394948182592 24.34914108612189 L-37.83420351967031 29.349141086121893 L-44.33420351967031 18.0908108369242 L-35.67394948182592 13.090810836924193 A38 38 0 0 1 -37.43995192304605 6.5000000000000036 L-37.43995192304605 6.5000000000000036 L-47.43995192304605 6.500000000000004 L-47.43995192304606 -6.499999999999993 L-37.43995192304606 -6.499999999999994 A38 38 0 0 1 -35.67394948182593 -13.090810836924167 L-35.67394948182593 -13.090810836924167 L-44.33420351967032 -18.090810836924163 L-37.834203519670325 -29.34914108612187 L-29.173949481825936 -24.34914108612187 A38 38 0 0 1 -24.349141086121893 -29.17394948182592 L-24.349141086121893 -29.17394948182592 L-29.349141086121897 -37.834203519670304 L-18.0908108369242 -44.334203519670304 L-13.090810836924195 -35.67394948182592 A38 38 0 0 1 -6.500000000000005 -37.43995192304605 L-6.500000000000005 -37.43995192304605 L-6.500000000000007 -47.43995192304605 L6.49999999999999 -47.43995192304606 L6.499999999999992 -37.43995192304606 A38 38 0 0 1 13.090810836924149 -35.67394948182594 L13.090810836924149 -35.67394948182594 L18.090810836924142 -44.33420351967033 L29.349141086121847 -37.83420351967034 L24.349141086121854 -29.17394948182595 A38 38 0 0 1 29.17394948182592 -24.349141086121893 L29.17394948182592 -24.349141086121893 L37.834203519670304 -29.349141086121897 L44.334203519670304 -18.0908108369242 L35.67394948182592 -13.090810836924197 A38 38 0 0 1 37.43995192304605 -6.500000000000007 M0 -20A20 20 0 1 0 0 20 A20 20 0 1 0 0 -20"></path>
                            </g>
                        </g>
                    </svg>
                </div>
                <h3 class="display-6">{{ __('Regenerating CSS Files') }}</h3>
                <p class="lead">{{ __('Please wait while the files are generated. The screen will be updated when finished.') }}</p>
            </div>
        </b-modal>
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
            fileLogin: {
              file: null,
              selectedFile: null,
            },
            fileLogo: {
              file: null,
              selectedFile: null,
            },
            fileIcon: {
              file: null,
              selectedFile: null,
            },
            colors: null,
            selectedSansSerifFont: {
              'id': "'Open Sans'",
              'title': 'Default Font'
            },
            colorDefault: [
              {
                id: '$primary',
                value: '#3397E1',
                title: __('Primary')
              },
              {
                id: '$secondary',
                value: '#788793',
                title: __('Secondary')
              },
              {
                id: '$success',
                value: '#00BF9C',
                title: __('Success')
              },
              {
                id: '$info',
                value: '#17A2B8',
                title: __('Info')
              },
              {
                id: '$warning',
                value: '#FBBE02',
                title: __('Warning')
              },
              {
                id: '$danger',
                value: '#ED4757',
                title: __('Danger')
              },
              {
                id: '$dark',
                value: '#000000',
                title: __('Dark')
              },
              {
                id: '$light',
                value: '#FFFFFF',
                title: __('Light')
              },
            ],
            fontsDefault: [
              {
                'id': "'Open Sans'",
                'title': 'Default Font'
              },
              {
                "id": "Menlo, Monaco, Consolas, 'Courier New', monospace",
                "title": "Mono Type"
              },
              {
                "id": "Arial",
                "title": "Arial"
              },
              {
                "id": "'Arial Black'",
                "title": "Arial Black"
              },
              {
                "id": "Bookman",
                "title": "Bookman"
              },
              {
                "id": "'Comic Sans MS'",
                "title": "Comic Sans MS"
              },
              {
                "id": "'Courier New'",
                "title": "Courier New"
              },
              {
                "id": "Garamond",
                "title": "Garamond"
              },
              {
                "id": "Georgia",
                "title": "Georgia"
              },
              {
                "id": "Helvetica",
                "title": "Helvetica"
              },
              {
                "id": "Impact",
                "title": "Impact"
              },
              {
                "id": "'Times New Roman'",
                "title": "Times New Roman"
              },
              {
                "id": "Verdana",
                "title": "Verdana"
              },
              {
                "id": "Palatino",
                "title": "Palatino"
              },
              {
                "id": "'Trebuchet MS'",
                "title": "Trebuchet MS"
              },
            ],
            errors: {
              'login': null,
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
              if (this.config.config.login != "null") {
                this.fileLogin.selectedFile = this.config.config.login;
              }
              if (this.config.config.logo != "null") {
                this.fileLogo.selectedFile = this.config.config.logo;
              }
              if (this.config.config.icon != "null") {
                this.fileIcon.selectedFile = this.config.config.icon;
              }
              if (this.config.config.sansSerifFont != "null") {
                this.selectedSansSerifFont = JSON.parse(this.config.config.sansSerifFont);
              }
            }
          },
        },
        computed: {
          customColors() {
            let data = this.colorDefault;
            if (this.config && this.config.config.variables) {
              data = JSON.parse(this.config.config.variables);
            }
            return data;
          }
        },
        mounted() {
          let userID = document.head.querySelector('meta[name="user-id"]');
          window.Echo.private(
            `ProcessMaker.Models.User.${userID.content}`
          ).notification(response => {
            if (response.type == "ProcessMaker\\Notifications\\SassCompiledNotification") {
              ProcessMaker.alert('{{ __('The styles were recompiled.') }}', 'success');
              this.onClose();
            }
          });
        },
        methods: {
          resetErrors() {
            this.errors = Object.assign({}, {
              login: null,
              logo: null,
              icon: null,
              colors: null
            });
          },
          onClose() {
            window.location.href = '/admin/customize-ui';
          },
          onSubmit() {
            this.resetErrors();

            let formData = new FormData();
            formData.append('key', this.key);
            formData.append('fileLoginName', this.fileLogin.selectedFile);
            formData.append('fileLogoName', this.fileLogo.selectedFile);
            formData.append('fileIconName', this.fileIcon.selectedFile);
            formData.append('fileLogin', this.fileLogin.file);
            formData.append('fileLogo', this.fileLogo.file);
            formData.append('fileIcon', this.fileIcon.file);
            formData.append('variables', JSON.stringify(this.customColors));
            formData.append('sansSerifFont', JSON.stringify(this.selectedSansSerifFont));

            this.onCreate(formData);
          },
          onReset() {
            let that = this;
            ProcessMaker.confirmModal(
              this.$t('Caution!'),
              "<b>" + this.$t('Are you sure you want to reset the UI styles?') + "</b>",
              "",
              () => {
                let formData = new FormData();
                formData.append('key', this.key);
                formData.append('reset', 'true');
                formData.append('fileLogoName', '');
                formData.append('fileIconName', '');
                formData.append('fileLogo', '');
                formData.append('fileIcon', '');
                formData.append('variables', JSON.stringify(this.colorDefault));
                formData.append('sansSerifFont', JSON.stringify({id:"'Open Sans'", value:'Open Sans'}));

                this.onCreate(formData);
              }
            );
          },
          onCreate(data) {
            ProcessMaker.apiClient.post('customize-ui', data)
              .then(response => {
                this.$refs.modalLoading.show();
              })
              .catch(error => {
                if (error.response.status && error.response.status === 422) {
                  this.errors = error.response.data.errors;
                }
              });
          },
          onUpdate(data) {
            ProcessMaker.apiClient.put('customize-ui', data)
              .then(response => {
                this.$refs.modalLoading.show();
              })
              .catch(error => {
                if (error.response.status && error.response.status === 422) {
                  this.errors = error.response.data.errors;
                }
              });
          },
          font(value) {
            return 'font-family:' + value;
          },
          browseLogin() {
            this.$refs.customFileLogin.click();
          },
          onFileChangeLogin(e) {
            console.log('onFileChangeLogin');
            let files = e.target.files || e.dataTransfer.files;

            if (!files.length) {
              return;
            }

            this.fileLogin.selectedFile = files[0].name;
            this.fileLogin.file = this.$refs.customFileLogin.files[0];
          },
          browseLogo() {
            this.$refs.customFileLogo.click();
          },
          onFileChangeLogo(e) {
            console.log('onFileChangeLogo');
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
            console.log('onFileChangeIcon');
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

    <style lang="scss" scoped>
        .icon-container {
            display: inline-block;
            width: 8em;
            margin-bottom: 1em;

        .i {
            color: #96b0aa;
            font-size: 5em;
        }

        .svg {
            fill: #aeb5bb;
        }
        
        }
        
        .vc-sketch {
            position: absolute;
            z-index: 100;
        }
    </style>
@endsection

