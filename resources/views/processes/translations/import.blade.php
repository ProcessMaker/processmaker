@extends('layouts.layout')

@section('title')
    {{__('Import Screen')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
      __('Designer') => route('processes.index'),
        __('Screens') => route('screens.index'),
        __('Import') => null,
    ]])
@endsection
@section('content')
<div class="container mb-3" id="importProcessTranslation" v-cloak>
  <div class="row">
      <div class="col">
          <div class="card text-center">
              <div class="card-header bg-light" align="left">
                  <h5 class="mb-0">Import Process Translation</h5>
                  <small class="text-muted">Import a Process Translation into process <b>{{ $process->name }}</b> for this ProcessMaker environment</small>
              </div>
              <div class="card-body">
                  <div id="pre-import" v-if="! importing && ! imported">
                      <draggable-file-upload v-if="!file || file && !fileIsValid" ref="file" v-model="file" :options="{singleFile: true}" :displayUploaderList="false" :accept="['application/json']"></draggable-file-upload>
                      <div v-else class="text-left">
                         <h5>You are about to import the following translations for the process <strong>{{$process->name}}</strong>:</h5>
                          <div class="border-dotted p-3 col-4 text-center font-weight-bold my-3">
                              @{{file.name}}
                              <b-button 
                                  variant="link" 
                                  @click="removeFile" 
                                  class="p-0"
                                  aria-describedby=""
                              >
                                  <i class="fas fa-times-circle text-danger"></i>
                              </b-button>
                          </div>
                          <div>
                            <div v-for="(language, key) in importData" class="mb-3">
                              <h5 class="mb-1"><b>@{{ key.toUpperCase() + ' - ' + language.languageHuman }}</b></h5>
                              <li v-for="screen in language.screens">@{{ screen }}</li>
                            </div>
                          </div>
                      </div>
                  </div>
              </div>
              <div id="card-footer-pre-import" class="card-footer bg-light" align="right"
                   v-if="! importing && ! imported">
                  <button type="button" class="btn btn-outline-secondary" @click="onCancel">
                      {{__('Cancel')}}
                  </button>
                  <button type="button" class="btn btn-primary ml-2"
                      :class="{'disabled': loading}"
                      :disabled="fileIsValid === false || loading"
                      @click="onImport">
                          <span v-if="!loading">{{__('Import')}}</span>
                          <i v-if="loading" class="fas fa-spinner fa-spin p-0"></i>
                          <span v-if="loading">{{__('Importing')}}</span>
                  </button>
              </div>
          </div>
      </div>
  </div>
</div>
@endsection

@section('js')
    <script src="{{mix('js/processes/translations/import.js')}}"></script>
    <script>
      new Vue({
        el: '#importProcessTranslation',
        data: {
          file: '',
          process: @json($process),
          uploaded: false,
          submitted: false,
          importing: false,
          imported: false,
          fileIsValid: false,
          loading: false,
          options: [],
          importData: [],
        },
        watch: {
          file() {
            this.fileIsValid = false;
            if (!this.file) {
              return;
            }
            this.validateFile();
            this.processName = this.file.name.split('.').slice(0,-1).toString();
          }
        },
        methods: {
          validateFile() {
            if (!this.file) {
              return;
            }

            let formData = new FormData();
            formData.append('file', this.file);

            ProcessMaker.apiClient.post(`/processes/${this.process.id}/import/translation/validation`, formData,
              {
                headers: {
                  'Content-Type': 'multipart/form-data'
                }
              }
            )
            .then(response => {   
              this.importData = response.data.importData
              this.fileIsValid = true;
            }).catch(error => {
              const message = error.response?.data?.error || error.response?.data?.message || error.message;
              ProcessMaker.alert(message, 'danger');
            });
          },
          removeFile() {

          },
          onCancel() {
            window.location = `/processes/${$processId}/edit`;
          },
          onImport() {
            let formData = new FormData();
            formData.append('file', this.file);
            formData.append('processId', this.processId);

            if (this.submitted) {
              return
            }

            this.submitted = true;
            ProcessMaker.apiClient.post(`/processes/${this.process.id}/import/translation`, formData,
              {
                headers: {
                  'Content-Type': 'multipart/form-data'
                }
              }
            ).then(response => {
              let message = this.$t('The process translation was imported correctly.');
              let variant = 'success';
              ProcessMaker.alert(message, variant);
            })
            .catch((error) => {
              this.submitted = false;
              ProcessMaker.alert(this.$t('Unable to import the translations.'), 'danger');
            });
          },
        }
      })
    </script>

<style type="text/css" scoped>
  [v-cloak] {
      display: none;
  }

  strong {
      font-weight: 700;
  }

  .card-body {
      transition: all 1s;
  }

  .border-dotted {
      border: 3px dotted #e0e0e0;
  }

  .fw-medium {
      font-weight:500;
  }
</style>
@endsection
