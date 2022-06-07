@extends('layouts.layout')

@section('title')
    {{__('Import Process')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index'),
        __('Import') => null,
    ]])
@endsection
@section('content')
    <div class="container mb-3" id="importProcess" v-cloak>
        <div class="row">
            <div class="col">
                <div class="card text-center">
                    <div class="card-header bg-light" align="left">
                        <h5 class="mb-0">{{__('Import Process')}}</h5>
                    </div>
                    <div class="card-body">
                        <div id="pre-import" v-if="! importing && ! imported">
                            <h5 class="card-title">{{__('You are about to import a Process.')}}</h5>
                            <p class="card-text d-flex justify-content-center">{{__('After importing, you can reassign users and groups to your Process.')}}</p>
                            <div class="d-flex justify-content-center">
                                <b-card header-class="d-flex bg-light align-items-center card-size-header border-0"
                                        class="d-flex flex-row card-border border-0 mb-1" style="width: 40em;">
                                    <i slot="header" class='fas fa-exclamation-circle fa-4x'></i>
                                    {{__('ProcessMaker does not import Environment Variables or Enterprise Packages. You must manually configure these features.')}}
                                </b-card>
                            </div>

                            <input id="import-file" type="file" ref="file" class="d-none" @change="handleFile" accept=".spark,.json" aria-label="{{__('Select a file')}}">

                            <button @click="$refs.file.click()" class="btn btn-secondary">
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
                                    @{{ $t(item.message) }} <strong>@{{ item.label }}</strong>
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
                                                @{{ $t(item.prefix) }} <strong>@{{item.name }}</strong> @{{ $t(item.suffix) }}
                                                <i class="assignable-arrow fas fa-long-arrow-alt-right"></i>
                                            </td>
                                            <td v-if="item.type === 'webentryCustomRoute'" class="assinable-entity">
                                              <b-input v-model="item.value" @change="checkForExistingRoute(item)" :class="{'is-invalid': item.error }"></b-input>
                                              <div class="invalid-feedback" v-if="item.error" role="alert"><div v-html="item.error"></div></div>
                                            </td>
                                            <td v-else class="assignable-entity">
                                                <label for="search-task-text" class="d-none">{{__('Type to search task')}}</label>
                                                <multiselect id="search-task-text"
                                                             v-model="item.value"
                                                             placeholder="{{__('Type to search task')}}"
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
                                                             @search-change="loadUsers($event, true, 'task')"
                                                             @open="loadUsers(null, true, 'task')"
                                                             class="assignable-input">
                                                    <template slot="noResult" >
                                                        {{ __('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ __('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                                <label for="search-user-text" class="d-none">{{__('Type to search a user')}}</label>
                                                <multiselect id="search-user-text"
                                                             v-model="item.value"
                                                             placeholder="{{__('Type to search a user')}}"
                                                             :options="usersAndGroups"
                                                             :multiple="false"
                                                             track-by="id"
                                                             :show-labels="false"
                                                             :searchable="true"
                                                             :internal-search="false"
                                                             label="fullname"
                                                             v-if="item.type == 'startEvent'"
                                                             group-values="items"
                                                             group-label="type"
                                                             @search-change="loadUsers($event, true)"
                                                             @open="loadUsers(null, true)"
                                                             class="assignable-input">
                                                    <template slot="noResult" >
                                                        {{ __('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ __('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                                <label for="search-script-text" class="d-none">{{__('Type to search a script')}}</label>
                                                <multiselect id="search-script-text"
                                                             v-model="item.value"
                                                             placeholder="{{__('Type to search a script')}}"
                                                             :options="users"
                                                             :multiple="false"
                                                             track-by="id"
                                                             :show-labels="false"
                                                             :searchable="true"
                                                             :internal-search="false"
                                                             label="fullname"
                                                             v-if="item.type == 'script'"
                                                             @search-change="loadUsers($event, false)"
                                                             @open="loadUsers(null)"
                                                             class="assignable-input">
                                                    <template slot="noResult" >
                                                        {{ __('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ __('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                                <label for="search-user-text" class="d-none">{{__('Type to search a process')}}</label>
                                                <multiselect id="search-user-text"
                                                             v-model="item.value"
                                                             placeholder="{{__('Type to search a process')}}"
                                                             :options="processes"
                                                             :multiple="false"
                                                             track-by="id"
                                                             :show-labels="false"
                                                             :searchable="true"
                                                             :internal-search="false"
                                                             label="name"
                                                             v-if="item.type == 'callActivity'"
                                                             @search-change="loadProcess($event)"
                                                             @open="loadProcess(null)"
                                                             class="assignable-input">
                                                    <template slot="noResult" >
                                                        {{ __('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ __('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                                <multiselect v-model="item.value"
                                                             placeholder="{{__('Type to search')}}"
                                                             :options="dataSources"
                                                             :multiple="false"
                                                             track-by="id"
                                                             :show-labels="false"
                                                             :searchable="true"
                                                             :internal-search="false"
                                                             label="name"
                                                             v-if="item.type == 'watcherDataSource'"
                                                             @search-change="loadDataSources($event)"
                                                             @open="loadDataSources(null)"
                                                             class="assignable-input">
                                                    <template slot="noResult" >
                                                        {{ __('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        <template v-if="!dataSourcesInstalled">
                                                          {{ __('Data Sources Package not installed.') }}
                                                        </template>
                                                        <template v-else>
                                                          {{ __('No Data Available') }}
                                                        </template>
                                                    </template>
                                                </multiselect>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="assignable-name text-right">
                                                {{ __('Assign') }}
                                                <strong>{{ __('Process Manager') }}</strong> {{ __('to') }}
                                                <i class="assignable-arrow fas fa-long-arrow-alt-right"></i>
                                            </td>
                                            <td class="assignable-entity">
                                              <select-user v-model="manager" :multiple="false"></select-user>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="assignable-name text-right">
                                                {{ __('Assign') }}
                                                <strong>{{ __('Cancel Request') }}</strong> {{ __('to') }}
                                                <i class="assignable-arrow fas fa-long-arrow-alt-right"></i>
                                            </td>
                                            <td class="assignable-entity">
                                                <label for="search-user-groups-text" class="d-none">{{__('Type to search')}}</label>
                                                <multiselect id="search-user-groups-text"
                                                            v-model="cancelRequest"
                                                             placeholder="{{__('Type to search')}}"
                                                             :options="usersAndGroupsWithManger"
                                                             :multiple="true"
                                                             track-by="id"
                                                             :show-labels="false"
                                                             :searchable="true"
                                                             :internal-search="false"
                                                             label="fullname"
                                                             group-values="items"
                                                             group-label="type"
                                                             @search-change="loadUsers($event, true)"
                                                             @open="loadUsers(null, true)"
                                                             class="assignable-input">
                                                    <template slot="noResult" >
                                                        {{ __('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ __('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="assignable-name text-right">
                                                {{ __('Assign') }} <strong>{{ __('Edit Data') }}</strong> {{ __('to') }}
                                                <i class="assignable-arrow fas fa-long-arrow-alt-right"></i>
                                            </td>
                                            <td class="assignable-entity">
                                                <label for="search-user-groups-text-assing" class="d-none">{{__('Type to search')}}</label>
                                                <multiselect id="search-user-groups-text-assing"
                                                             v-model="processEditData"
                                                             placeholder="{{__('Type to search')}}"
                                                             :options="usersAndGroups"
                                                             :multiple="true"
                                                             track-by="id"
                                                             :show-labels="false"
                                                             :searchable="true"
                                                             :internal-search="false"
                                                             label="fullname"
                                                             group-values="items"
                                                             group-label="type"
                                                             @search-change="loadUsers($event, true)"
                                                             @open="loadUsers(null, true)"
                                                             class="assignable-input">
                                                    <template slot="noResult" >
                                                        {{ __('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ __('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="assignable-name text-right">
                                                {{ __('Assign') }} <strong>{{ __('Status') }}</strong> {{ __('to') }}
                                                <i class="assignable-arrow fas fa-long-arrow-alt-right"></i>
                                            </td>
                                            <td class="assignable-entity">
                                                <label for="search-status-text" class="d-none">{{__('Type to search status')}}</label>
                                                <select-status v-model="status" :multiple="false"></select-status>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="card-footer-pre-import" class="card-footer bg-light" align="right"
                         v-if="! importing && ! imported">
                        <button type="button" class="btn btn-outline-secondary" @click="onCancel">
                            {{__('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click="importFile"
                                :disabled="uploaded == false">
                            {{__('Import')}}
                        </button>
                    </div>
                    <div id="card-footer-post-import" class="card-footer bg-light" align="right" v-if="imported"
                         v-cloak>
                        <div v-if="assignable">
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
            height: 40px;
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
      const importingCode = window.location.hash.match(/#code=(.+)/);
      new Vue({
        el: '#importProcess',
        data: {
          file: '',
          uploaded: false,
          submitted: importingCode ? true : false,
          options: [],
          assignable: null,
          importing: importingCode ? true : false,
          imported: false,
          selectedUser: null,
          usersAndGroups: [],
          users: [],
          processes: [],
          manager: null,
          cancelRequest: [],
          processEditData: [],
          importingCode: importingCode ? importingCode[1] : null,
          dataSources: [],
          dataSourcesInstalled: true,
          status: 'ACTIVE'
        },
        filters: {
          titleCase: function (value) {
            value = value.toString();
            return value.charAt(0).toUpperCase() + value.slice(1);
          }
        },
        computed: {
          usersAndGroupsWithManger() {
            const usersAndGroups = _.cloneDeep(this.usersAndGroups);
            const users = _.get(usersAndGroups, '0.items');
            if (!users) {
              return [];
            }
            users.unshift(this.managerOption);
            _.set(usersAndGroups, '0.items', users);
            return usersAndGroups;
          },
          managerOption() {
            return {
                id: 'manager',
                fullname: this.$t('Process Manager')
            };
          },
        },
        methods: {
          loadUsers(filter, getGroups, type) {
            ProcessMaker.apiClient
              .get("users" + (typeof filter === 'string' ? '?filter=' + filter : ''))
              .then(response => {
                let users = response.data.data;
                if (getGroups) {
                  this.loadUsersAndGroups(filter, users, type)
                } else {
                  this.users = users;
                }
              });
          },
          loadUsersAndGroups(filter, users, type) {
            ProcessMaker.apiClient
              .get("groups" + (typeof filter === 'string' ? '?filter=' + filter : ''))
              .then(response => {
                let groups = response.data.data.map(item => {
                  return {
                    'id': 'group-' + item.id,
                    'fullname': item.name
                  }
                });
                this.usersAndGroups = [];
                if (type === 'task') {
                  this.usersAndGroups.push({
                    'type': '{{__('Special Assignments')}}',
                    'items': [
                      {
                        'id': 'requester',
                        'fullname': '{{__('Requester')}}'
                      },
                      {
                        'id': 'previous_task_assignee',
                        'fullname': '{{__('Previous Task Assignee')}}'
                      },
                    ]
                  });
                }

                this.usersAndGroups.push({
                  'type': '{{__('Users')}}',
                  'items': users ? users : []
                });
                this.usersAndGroups.push({
                  'type': '{{__('Groups')}}',
                  'items': groups ? groups : []
                });
              });
          },
          loadProcess(filter) {
            filter =
              ProcessMaker.apiClient
                .get("processes?order_direction=asc&status=active&include=events" + (typeof filter === 'string' ? '&filter=' + filter : ''))
                .then(response => {
                  this.processes = [];
                  response.data.data.forEach(item => {
                    item.events.forEach(start => {
                      this.processes.push({
                        'id': `${start.ownerProcessId}-${item.id}`,
                        'name': item.events.length > 1 ? `${item.name} (${start.ownerProcessId})` : item.name,
                      });
                    });
                  });
                });
          },
          loadDataSources(filter) {
            filter =
              ProcessMaker.apiClient
                .get("data_sources?order_by=name&order_direction=asc" + (typeof filter === 'string' ? '&filter=' + filter : ''))
                .then(response => {
                  this.dataSources = response.data.data;
                }).catch(error => {
                  this.dataSources = [];
                  this.dataSourcesInstalled = false;
                });
          },
          formatAssignee(data) {
            let id,
              response = {};

            response['users'] = [];
            response['groups'] = [];

            data.forEach(item => {
              if (item.id === 'manager') {
                response['pseudousers'] = ['manager'];
              } else if (typeof item.id === "number") {
                response['users'].push(parseInt(item.id));
              } else {
                id = item.id.split('-');
                response['groups'].push(parseInt(id[1]));
              }
            });
            return response;
          },
          formatValueScreen(item) {
            return (item && item.id) ? item.id : null
          },
          onAssignmentSave() {
            ProcessMaker.apiClient.post('/processes/' + this.processId + '/import/assignments',
              {
                "assignable": this.assignable,
                'cancel_request': this.formatAssignee(this.cancelRequest),
                'edit_data': this.formatAssignee(this.processEditData),
                'manager_id': this.formatValueScreen(this.manager),
                'status': this.status,
              })
              .then(response => {
                ProcessMaker.alert(this.$t('All assignments were saved.'), 'success');
                this.onCancel();
              })
              .catch(error => {
                console.log("error", error);
                ProcessMaker.alert(this.$t('Unable cannot save the assignments.'), 'danger');
              });
          },
          onAssignmentCancel() {
            this.onCancel();
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
            ProcessMaker.apiClient.post('/processes/import?queue=1',
              formData,
              {
                headers: {
                  'Content-Type': 'multipart/form-data'
                }
              }
            )
              .then(response => {
                window.location.hash = `#code=${response.data.code}`;
                this.importingCode = response.data.code;
              })
              .catch(error => {
                this.submitted = false;
                ProcessMaker.alert(this.$t('Unable to import the process.')  + (error.response.data.message ? ': ' + error.response.data.message : ''), 'danger');
              });
          },
          importReady(response) {
            let message = '{{__("Unable to import the process.")}}';
            if (!response.data.status) {
              ProcessMaker.alert(message, 'danger');
              return;
            }

            this.options = response.data.status;
            this.importing = false;
            this.imported = true;

            if (!response.data.process.id) {
              ProcessMaker.alert(message, 'danger');
              return;
            }
            this.assignable = response.data.assignable;
            this.processId = response.data.process.id;

            if (_.get(response, 'data.process.properties.manager_can_cancel_request', false)) {
              this.cancelRequest.push(this.managerOption);
            }

            message = this.$t('The process was imported.');
            let variant = 'success';
            for (let item in this.options) {
              if (!this.options[item].success) {
                message = this.$t('The process was imported, but with errors.');
                variant = 'warning'
              }
            }
            ProcessMaker.alert(message, variant);
          },
          checkForExistingRoute(item) {
            ProcessMaker.apiClient.get(`/webentry/custom_route/check/${item.value}`)
              .then(response => {
                item.error = null;
              })
              .catch(error => {
                item.error = error.response.data.error;
              });
          }
        },
        mounted() {
          let received = false;
          window.Echo.private(`ProcessMaker.Models.User.${window.ProcessMaker.user.id}`).notification(
            (response) => {
              if (
                !received &&
                response.type === 'ProcessMaker.Notifications.ImportReady' &&
                this.importingCode === response.code
              ) {
                received = true;
                this.importReady(response);
              }
            },
          );
          if (this.importingCode) {
            ProcessMaker.apiClient.get(`/processes/import/${this.importingCode}/is_ready`)
              .then(response => {
                if (response.data.ready) {
                  received = true;
                  this.importReady(response);
                }
              });
          }
        },
      })
    </script>
@endsection
