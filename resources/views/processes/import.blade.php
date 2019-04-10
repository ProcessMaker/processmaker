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
                        <h5 class="mb-0">{{__('Import Process')}}</h5>
                    </div>
                    <div class="card-body">
                        <div id="pre-import" v-if="! importing && ! imported">
                            <h5 class="card-title">{{__('You are about to import a Process.')}}</h5>
                            <p class="card-text">{{__('You can reassign users, groups, and environment variables after import.')}}</p>
                            <input type="file" ref="file" class="d-none" @change="handleFile" accept=".bpm4">
                            <button @click="$refs.file.click()" class="btn btn-secondary ml-2">
                                <i class="fas fa-upload"></i>
                                {{__('Browse')}}
                            </button>
                        </div>
                        <div id="during-import" v-if="importing" v-cloak>
                            <h4 class="card-title mt-5 mb-5">
                                <i class="fas fa-circle-notch fa-spin"></i> {{ __('Importing') }}...
                            </h4>
                        </div>
                        <div id="post-import" class="text-left" v-if="imported" v-cloak>
                            <h5>{{ __('Status') }}</h5>
                            <ul v-show="options" class="mb-0 fa-ul">
                                <li v-for="item in options">
                                    <span class="fa-li">
                                        <i :class="item.success ? 'fas fa-check text-success' : 'fas fa-times text-danger'"></i>
                                    </span>
                                    @{{  item.message }} <strong>@{{  item.label }}</strong>
                                </li>
                            </ul>
                            <div id="post-import-assignable" v-if="assignable" v-cloak>
                                <hr>
                                <h5>{{ __('Configuration') }}</h5>
                                <span class="card-text">
                                    {{ __('The following items should be configured to ensure your process is functional.') }}
                                </span>
                                <div>
                                    <span class="card-text"><strong></strong></span>
                                    <table id="assignable-table">
                                        <tbody>
                                        <tr v-for="item in assignable">
                                            <td class="assignable-name text-right">
                                                @{{ item.prefix }} <strong>@{{item.name }}</strong> @{{ item.suffix }}
                                                <i  class="assignable-arrow fas fa-long-arrow-alt-right"></i>
                                            </td>
                                            <td class="assignable-entity">
                                                <multiselect v-model="item.value"
                                                             placeholder="{{__('Type to search 1')}}"
                                                             :options="usersAndGroups"
                                                             :multiple="false"
                                                             track-by="id"
                                                             :show-labels="false"
                                                             :searchable="true"
                                                             :internal-search="false"
                                                             label="fullname"
                                                             v-if="item.type == 'task'"
                                                             group-values="items"
                                                             group-label="type"
                                                             @search-change="loadUsers($event, true)"
                                                             class="assignable-input">
                                                </multiselect>
                                                <multiselect v-model="item.value"
                                                             placeholder="{{__('Type to search 2')}}"
                                                             :options="users"
                                                             :multiple="false"
                                                             track-by="id"
                                                             :show-labels="false"
                                                             :searchable="true"
                                                             :internal-search="false"
                                                             label="fullname"
                                                             v-if="item.type == 'script'"
                                                             @search-change="loadUsers($event, false)"
                                                             class="assignable-input">
                                                </multiselect>
                                                {!!Form::text('value', null,['v-if' => "item.type == 'environment_variable'",
                                                'class'=> 'form-control assignable-input', 'v-model'=> 'item.value',
                                                'placeholder' => __('Type environment variable')])!!}
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="card-footer-pre-import" class="card-footer bg-light" align="right" v-if="! importing && ! imported">
                        <button type="button" class="btn btn-outline-secondary" @click="onCancel">
                            {{__('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click="importFile"
                                :disabled="uploaded == false">
                            {{__('Import')}}
                        </button>
                    </div>
                    <div id="card-footer-post-import" class="card-footer bg-light" align="right" v-if="imported" v-cloak>
                        <div v-if="assignable">
                            <button type="button" class="btn btn-outline-secondary" @click="onAssignmentCancel">
                                {{__('Cancel')}}
                            </button>
                            <button type="button" class="btn btn-secondary ml-2" @click="onAssignmentSave">
                                {{__('Save')}}
                            </button>
                        </div>
                        <div v-if="! assignable">
                            <button type="button" class="btn btn-secondary ml-2" @click="onCancel">
                                {{__('List Processes')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style type="text/css">
        [v-cloak] {
            display: none;
        }

        strong {
            font-weight: 700;
        }

        #assignable-table {
            margin-top: 1rem;
        }

        #assignable-table tr {
            border-bottom: 1px solid #eee;
        }

        #assignable-table tr:last-child {
            border-bottom: 0;
        }

        #assignable-table td {
            padding-bottom: 1rem;
            padding-left: 1rem;
            padding-right: 1rem;
            padding-top: 1rem;
            vertical-align: middle;
        }

        #assignable-table td.assignable-name {
            padding-right: 0;
        }

        .assignable-arrow {
            padding-left: 1rem;
        }

        .assignable-input {
            border-color: #b6bfc6;
            border-radius: 5px;
            min-width: 300px;
        }

        .card-title i {
            color: #00bf9c;
        }

        .card-body {
            transition: all 1s;
        }
    </style>
@endsection

@section('js')
    <script>
      new Vue({
        el: '#importProcess',
        data: {
          file: '',
          uploaded: false,
          submitted: false,
          options: [],
          assignable: null,
          importing: false,
          imported: false,
          selectedUser: null,
          usersAndGroups: [],
          users: [],
        },
        filters: {
          titleCase: function (value) {
            value = value.toString();
            return value.charAt(0).toUpperCase() + value.slice(1);
          }
        },
        methods: {
          loadUsers(filter, getGroups) {
            ProcessMaker.apiClient
              .get("users" + (typeof filter === 'string' ? '?filter=' + filter : ''))
              .then(response => {
                let users = response.data.data;
                if (getGroups) {
                  this.loadUsersAndGroups(filter, users)
                } else {
                  this.users = users;
                }
              });
          },
          loadUsersAndGroups(filter, users) {
            ProcessMaker.apiClient
              .get("groups" + (typeof filter === 'string' ? '?filter=' + filter : ''))
              .then(response => {
                let groups = response.data.data.map(item => {
                  return {
                    'id': item.id,
                    'fullname': item.name
                  }
                });
                this.usersAndGroups = [];
                this.usersAndGroups.push({
                  'type': '{{__('Users')}}',
                  'items' : users ? users : []
                });
                this.usersAndGroups.push({
                  'type': '{{__('Groups')}}',
                  'items' : groups ? groups : []
                });
              });
          },
          onAssignmentSave() {
            alert('save');
          },
          onAssignmentCancel() {
            alert('cancel');
          },
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
            this.importing = true;
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
              this.options = response.data.status;
              this.assignable = response.data.assignable;
              let message = '{{__('The process was imported.')}}';
              let variant = 'success';
              for (let item in this.options) {
                if (!this.options[item].success) {
                  message = '{{__('The process was imported, but with errors.')}}';
                  variant = 'warning'
                }
              }
              ProcessMaker.alert(message, variant);
              this.importing = false;
              this.imported = true;
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
