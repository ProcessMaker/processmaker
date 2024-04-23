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
    <div class="container" id="importScreen">
        <div class="row">
            <div class="col">
                <div class="card text-center">
                    <div class="card-header bg-light text-left">
                        <h5>{{__('Import Screen')}}</h5>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" v-if="!fileName">
                          {{__('You are about to import a Screen.')}}
                        </h5>
                        <h5 class="card-title" v-else v-cloak>
                          {{__('You are about to import ')}} @{{ fileName }}
                        </h5>
                        <input
                            id="import-file"
                            type="file"
                            ref="file"
                            class="d-none"
                            @change="handleFile"
                            accept=".json"
                            aria-label="{{__('select file')}}">
                        <button
                            type="button"
                            @click="$refs.file.click()"
                            class="btn btn-secondary ml-2"
                            data-cy="button-browse"
                            >
                            <i class="fas fa-upload"></i>
                            {{__('Browse')}}
                        </button>
                    </div>
                    <div class="card-footer bg-light text-right">
                        <button
                          type="button"
                          class="btn btn-outline-secondary"
                          @click="onCancel"
                          data-cy="button-cancel"
                          >
                            {{__('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click="importFile"
                                :disabled="uploaded == false" data-cy="button-import">
                            {{__('Import')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <b-modal ref="responseImport" :title="__('Import Screen')" @hide="reload" ok-only centered v-cloak>
            <ul v-show="options" class="list-unstyled">
                <li v-for="item in options">
                    <i :class="item.success ? 'fas fa-check text-success' : 'fas fa-times text-danger'"></i>
                    @{{item.label}} - @{{item.message}}
                    <span v-if="item.info" :class="'text-danger d-block'"> @{{item.info}}.</span>
                </li>
            </ul>
            <div slot="modal-footer" class="w-100 text-right">
                <button
                    type="button"
                    class="btn btn-secondary ml-2"
                    @click="onCancel"
                    data-cy="button-list-screen"
                    >
                    {{__('List Screens')}}
                </button>
            </div>
        </b-modal>

    </div>
@endsection

@section('js')
    <script>
      new Vue({
        el: '#importScreen',
        data: {
          file: '',
          uploaded: false,
          submitted: false,
          options: [],
          fileName: ''
        },
        methods: {
          handleFile(e) {
            this.file = this.$refs.file.files[0];
            this.uploaded = true;
            this.submitted = false;
            this.fileName = this.file.name;
            console.log(this.file);
          },
          reload() {
            window.location.reload();
          },
          onCancel() {
            window.location = '{{ route("screens.index") }}';
          },
          importFile() {
            let formData = new FormData();
            formData.append('file', this.file);
            if (this.submitted) {
              return
            }
            this.submitted = true;
            ProcessMaker.apiClient.post('/screens/import',
              formData,
              {
                headers: {
                  'Content-Type': 'multipart/form-data'
                }
              }
            ).then(response => {
              if (!response.data.status) {
                ProcessMaker.alert(this.$t('Unable to import the screen.'), 'danger');
                return;
              }
              this.options = response.data.status;
              let message = this.$t('The screen was imported.');
              let variant = 'success';
              for (let item in this.options) {
                if (!this.options[item].success) {
                  message = this.$t('The screen was imported, but with errors.');
                  variant = 'warning'
                }
              }
              ProcessMaker.alert(message, variant);
              this.$refs.responseImport.show();
            })
              .catch(error => {
                this.submitted = false;
                ProcessMaker.alert(
                  this.$t('Unable to import the screen.') +
                  (error.response.data.message ? ': ' + error.response.data.message : ''),
                  'danger'
                  );
              });
          },
        }
      })
    </script>
@endsection
