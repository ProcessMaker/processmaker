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
                        <input type="file" ref="file" class="d-none" @change="handleFile" accept=".bpm4">
                        <button @click="$refs.file.click()" class="btn btn-secondary ml-2">
                            <i class="fas fa-upload"></i>
                            {{__('Browse')}}
                        </button>
                    </div>
                    <div class="card-footer bg-light" align="right">
                        <button type="button" class="btn btn-outline-secondary" @click="onCancel">
                            {{__('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click="importFile" :disabled="uploaded == false">
                            {{__('Import')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
      new Vue({
        el: '#importProcess',
        data: {
          file: '',
          uploaded: false,
          submitted: false
        },
        methods: {
          handleFile(e) {
            this.file = this.$refs.file.files[0];
            this.uploaded = true
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
              ProcessMaker.alert('{{__('The process was imported.')}}', 'success')
              window.location = '{{ route("processes.index") }}';
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
