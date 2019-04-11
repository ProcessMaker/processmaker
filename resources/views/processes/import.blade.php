@extends('layouts.layout')

@section('title')
    {{__('Import Process')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Processes') => route('processes.index'),
        __('Import') => null,
    ]])
    <div class="container" id="importProcess">
        <div class="row">
            <div class="col">
                <div class="card text-center">
                    <div class="card-header bg-light" align="left">
                        <h5>{{__('Import Process')}}</h5>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">{{__('You are about to import a Process.')}}</h5>
                        <p class="card-text">{{__('User assignments and sensitive environment variables will not be imported.')}}</p>
                        <input type="file" ref="file" class="d-none" @change="handleFile" accept=".spark">
                        <button @click="$refs.file.click()" class="btn btn-secondary ml-2">
                            <i class="fas fa-upload"></i>
                            {{__('Browse')}}
                        </button>
                    </div>
                    <div class="card-footer bg-light" align="right">
                        <button type="button" class="btn btn-outline-secondary" @click="onCancel">
                            {{__('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click="importFile"
                                :disabled="uploaded == false">
                            {{__('Import')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <b-modal ref="responseImport" :title="__('Import Process')" @hide="reload" ok-only centered v-cloak>
            <ul v-show="options" class="list-unstyled">
                <li v-for="item in options">
                    <i :class="item.success ? 'fas fa-check text-success' : 'fas fa-times text-danger'"></i>
                    @{{item.label}} - @{{item.message}}
                </li>
            </ul>
            <div slot="modal-footer" class="w-100" align="right">
                <button type="button" class="btn btn-secondary ml-2" @click="onCancel">{{__('List Processes')}}
                </button>
            </div>
        </b-modal>

    </div>
@endsection

@section('js')
    <script>
      new Vue({
        el: '#importProcess',
        data: {
          file: '',
          uploaded: false,
          submitted: false,
          options: []
        },
        methods: {
          handleFile(e) {
            this.file = this.$refs.file.files[0];
            this.uploaded = true;
            this.submitted = false;
          },
          reload() {
            window.location.reload();
          },
          onCancel() {
            window.location = '{{ route("processes.index") }}';
          },
          importFile() {
            let formData = new FormData();
            formData.append('file', this.file);
            if (this.submitted) {
              return
            }
            this.submitted = true;
            ProcessMaker.apiClient.post('/processes/import',
              formData,
              {
                headers: {
                  'Content-Type': 'multipart/form-data'
                }
              }
            ).then(response => {
              if (!response.data.status) {
                ProcessMaker.alert('{{__('Unable to import the process.')}}', 'danger');
                return;
              }
              this.options = response.data.status;
              let message = '{{__('The process was imported.')}}';
              let variant = 'success';
              for (let item in this.options) {
                if (!this.options[item].success) {
                  message = '{{__('The process was imported, but with errors.')}}';
                  variant = 'warning'
                }
              }
              ProcessMaker.alert(message, variant);
              this.$refs.responseImport.show();
            })
              .catch(error => {
                this.submitted = false;
                ProcessMaker.alert('{{__('Unable to import the process.')}}', 'danger')
              });
          }
        }
      })
    </script>
@endsection
