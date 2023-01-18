<template>
   <div class="container mb-3" id="importProcess" v-cloak>
        <div class="row">
            <div class="col">
                <div class="card text-center">
                    <div class="card-header bg-light" align="left">
                        <h5 class="mb-0">{{$t('Import Process')}}</h5>
                        <small class="text-muted">{{ $t('Import a Process and its associated assets into this ProcessMaker environment') }}</small>
                    </div>
                    <div class="card-body">
                        <div id="pre-import" v-if="! importing && ! imported">
                            <draggable-file-upload v-if="!file || file && !fileIsValid" ref="file" v-model="file" :options="{singleFile: true}" :displayUploaderList="false" :accept="['.spark', 'application/json']"></draggable-file-upload>
                            <div v-else class="text-left">
                               <h5> {{ $t("You are about to import") }} <strong>{{processName}}</strong></h5>
                                <div class="border-dotted p-3 col-4 text-center font-weight-bold my-3">
                                    {{file.name}} 
                                    <b-button 
                                        variant="link" 
                                        @click="removeFile" 
                                        class="p-0"
                                        aria-describedby=""
                                    >
                                        <i class="fas fa-times-circle text-danger"></i>
                                    </b-button>
                                </div>
                                <b-form-group>
                                    <h6>{{ $t('Select Import Type') }}</h6>
                                    <b-form-radio 
                                        v-for="(item, index) in importTypeOptions" 
                                        v-model="selectedImportOption" 
                                        v-uni-aria-describedby="index.toString()"
                                        :key="item.value" 
                                        :value="item.value"
                                    >
                                        <span class="fw-medium">{{ item.content }}</span>
                                        <div>
                                            <small v-uni-id="index.toString()" class="text-muted">{{item.helper}}</small>
                                        </div>
                                    </b-form-radio>
                                </b-form-group>
                            </div>
                            <enter-password-modal ref="enter-password-modal" @verified-password="importFile($event)"></enter-password-modal>
                            <import-process-modal ref="import-process-modal" :existingAssets="existingAssets" :processName="processName" :userHasEditPermissions="true" @import-new="onImportAsNew" @update-process="importFile($event)"></import-process-modal>
                        </div>
                        <!-- <div id="during-import" v-if="importing" v-cloak>
                            <h4 class="card-title mt-5 mb-5">
                                <i class="fas fa-circle-notch fa-spin"></i> {{ $t('Importing') }}...
                            </h4>
                        </div> -->
                        
                        <!-- <div id="post-import" class="text-left" v-if="imported" v-cloak>
                            <h5>{{ $t('Status') }}</h5>
                            <ul v-show="options" class="mb-0 fa-ul">
                                <li v-for="item in options">
                                    <span class="fa-li">
                                        <i :class="item.success ? 'fas fa-check text-success' : 'fas fa-times text-danger'"></i>
                                    </span>
                                    {{ $t(item.message) }} <strong>{{ item.label }}</strong>
                                </li>
                            </ul>
                            <div id="post-import-assignable" v-if="assignable" v-cloak>
                                <hr>
                                <h5>{{ $t('Configuration') }}</h5>
                                <span class="card-text">
                                    {{ $t('The following items should be configured to ensure your process is functional.') }}
                                </span>
                                <div>
                                    <span class="card-text"><strong></strong></span>
                                    <table id="assignable-table">
                                        <tbody>
                                        <tr v-for="item in assignable">
                                            <td class="assignable-name text-right">
                                                {{ $t(item.prefix) }} <strong>{{item.name }}</strong> {{ $t(item.suffix) }}
                                                <i class="assignable-arrow fas fa-long-arrow-alt-right"></i>
                                            </td>
                                            <td v-if="item.type === 'webentryCustomRoute'" class="assinable-entity">
                                              <b-input v-model="item.value" @change="checkForExistingRoute(item)" :class="{'is-invalid': item.error }"></b-input>
                                              <div class="invalid-feedback" v-if="item.error" role="alert"><div v-html="item.error"></div></div>
                                            </td>
                                            <td v-else class="assignable-entity">
                                                <label for="search-task-text" class="d-none">{{$t('Type to search task')}}</label>
                                                <multiselect id="search-task-text"
                                                             v-model="item.value"
                                                             :placeholder="$t('Type to search task')"
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
                                                        {{ $t('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ $t('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                                <label for="search-user-text" class="d-none">{{$t('Type to search a user')}}</label>
                                                <multiselect id="search-user-text"
                                                             v-model="item.value"
                                                             :placeholder="$t('Type to search a user')"
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
                                                        {{ $t('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ $t('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                                <label for="search-script-text" class="d-none">{{$t('Type to search a script')}}</label>
                                                <multiselect id="search-script-text"
                                                             v-model="item.value"
                                                             :placeholder="$t('Type to search a script')"
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
                                                        {{ $t('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ $t('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                                <label for="search-user-text" class="d-none">{{$t('Type to search a process')}}</label>
                                                <multiselect id="search-user-text"
                                                             v-model="item.value"
                                                             :placeholder="$t('Type to search a process')"
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
                                                        {{ $t('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ $t('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                                <multiselect v-model="item.value"
                                                             :placeholder="$t('Type to search')"
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
                                                        {{ $t('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        <template v-if="!dataSourcesInstalled">
                                                          {{ $t('Data Sources Package not installed.') }}
                                                        </template>
                                                        <template v-else>
                                                          {{ $t('No Data Available') }}
                                                        </template>
                                                    </template>
                                                </multiselect>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="assignable-name text-right">
                                                {{ $t('Assign') }}
                                                <strong>{{ $t('Process Manager') }}</strong> {{ $t('to') }}
                                                <i class="assignable-arrow fas fa-long-arrow-alt-right"></i>
                                            </td>
                                            <td class="assignable-entity">
                                              <select-user v-model="manager" :multiple="false"></select-user>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="assignable-name text-right">
                                                {{ $t('Assign') }}
                                                <strong>{{ $t('Cancel Request') }}</strong> {{ $t('to') }}
                                                <i class="assignable-arrow fas fa-long-arrow-alt-right"></i>
                                            </td>
                                            <td class="assignable-entity">
                                                <label for="search-user-groups-text" class="d-none">{{$t('Type to search')}}</label>
                                                <multiselect id="search-user-groups-text"
                                                            v-model="cancelRequest"
                                                             :placeholder="$t('Type to search')"
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
                                                        {{ $t('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ $t('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="assignable-name text-right">
                                                {{ $t('Assign') }} <strong>{{ $t('Edit Data') }}</strong> {{ $t('to') }}
                                                <i class="assignable-arrow fas fa-long-arrow-alt-right"></i>
                                            </td>
                                            <td class="assignable-entity">
                                                <label for="search-user-groups-text-assing" class="d-none">{{$t('Type to search')}}</label>
                                                <multiselect id="search-user-groups-text-assing"
                                                             v-model="processEditData"
                                                             :placeholder="$t('Type to search')"
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
                                                        {{ $t('No elements found. Consider changing the search query.') }}
                                                    </template>
                                                    <template slot="noOptions" >
                                                        {{ $t('No Data Available') }}
                                                    </template>
                                                </multiselect>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="assignable-name text-right">
                                                {{ $t('Assign') }} <strong>{{ $t('Status') }}</strong> {{ $t('to') }}
                                                <i class="assignable-arrow fas fa-long-arrow-alt-right"></i>
                                            </td>
                                            <td class="assignable-entity">
                                                <label for="search-status-text" class="d-none">{{$t('Type to search status')}}</label>
                                                <select-status v-model="status" :multiple="false"></select-status>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div> -->
                    </div>
                    <div id="card-footer-pre-import" class="card-footer bg-light" align="right"
                         v-if="! importing && ! imported">
                        <button type="button" class="btn btn-outline-secondary" @click="onCancel">
                            {{$t('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-primary ml-2" @click="checkForPassword" :disabled="fileIsValid === false">
                            {{$t('Import')}}
                        </button>
                    </div>
                    <div id="card-footer-post-import" class="card-footer bg-light" align="right" v-if="imported"
                         v-cloak>
                        <div v-if="assignable">
                            <button type="button" class="btn btn-secondary ml-2" @click="onAssignmentSave">
                                {{$t('Save')}}
                            </button>
                        </div>
                        <div v-if="! assignable">
                            <button type="button" class="btn btn-secondary ml-2" @click="onCancel">
                                {{$t('List Processes')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
const importingCode = window.location.hash.match(/#code=(.+)/);
import DraggableFileUpload from '../../../components/shared/DraggableFileUpload';
import EnterPasswordModal from '../components/EnterPasswordModal';
import ImportProcessModal from '../components/ImportProcessModal';
import { createUniqIdsMixin } from "vue-uniq-ids";
const uniqIdsMixin = createUniqIdsMixin();

export default {
    props: [''],
    components: {DraggableFileUpload, EnterPasswordModal, ImportProcessModal},
    mixins: [uniqIdsMixin],
    data() {
        return {
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
            status: 'ACTIVE',
            importTypeOptions: [
                {"value": "basic", "content": "Basic", "helper": "Import all assets from the uploaded package."},
                {"value": "custom", "content": "Custom", "helper": "Select which  types of assets from the uploaded package should be imported to this environment."},
            ],
            fileIsValid: false,
            selectedImportOption: "basic",
            processName: null,
            passwordEnabled: false,
            assetsExist: false,

            manifest: {},
            rootUuid: '',
        }
    },
    filters: {
        titleCase: function (value) {
            value = value.toString();
            return value.charAt(0).toUpperCase() + value.slice(1);
        }
    },
    watch: {
        file() {
            this.fileIsValid = false;
            if (!this.file) {
                return
            }
            this.validateFile();
            this.processName = this.file.name.split('.').slice(0,-1).toString();
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
        existingAssets() {
            return Object.values(this.manifest).filter(asset => {
                return asset.existing_id !== null;
            }).map(asset => {
                return {
                    type: asset.type,
                    existingName: asset.existing_name, 
                    importingName: asset.name,
                    existingId: asset.existing_id,
                };
            });
        }
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
                    'type': this.$t('Special Assignments'),
                    'items': [
                        {
                        'id': 'requester',
                        'fullname': this.$t('Requester')
                        },
                        {
                        'id': 'previous_task_assignee',
                        'fullname': this.$t('Previous Task Assignee')
                        },
                    ]
                    });
                }

                this.usersAndGroups.push({
                    'type': this.$t('Users'),
                    'items': users ? users : []
                });
                this.usersAndGroups.push({
                    'type': this.$t('Groups'),
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
        reload() {
            window.location.reload();
        },
        onCancel() {
            window.location = '/processes';
        },
        importFile() {
            if (this.existingAssets.length > 0)
            {
                this.assetsExist = true;
            }
            switch (this.selectedImportOption) {
                case 'basic':
                    this.handleBasicImport();
                    break;
            
                default:
                    // TODO:: IMPORT/EXPORT HANDLE CUSTOM IMPORT
                    break;
            }
        },
        handleBasicImport() {
            // TODO: IMPORT/EXPORT check if process already exists. and users have edit permissions
            if (this.assetsExist) {
                this.$nextTick(() => {    
                    this.$refs['enter-password-modal'].hide();  
                    this.$refs['import-process-modal'].show();
                });
            } else {
                // this.importing = true;
                let formData = new FormData();
                formData.append('file', this.file);
        
                if (this.submitted) {
                    return;
                }
                this.submitted = true;
                ProcessMaker.apiClient.post('/import/do-import', formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }).then(response => {
                    console.log('RESPONSE', response);
                }).catch(error => {
                    ProcessMaker.alert(this.$t('Unable to import the process.')  + (error.response.data.message ? ': ' + error.response.data.message : ''), 'danger');
                    this.submitted = false;
                });

                // ProcessMaker.apiClient.post('/processes/import?queue=1', formData,
                //     {
                //         headers: {
                //             'Content-Type': 'multipart/form-data'
                //         }
                //     }
                // )
                // .then(response => {
                //     window.location.hash = `#code=${response.data.code}`;
                //     this.importingCode = response.data.code;
                // })
                // .catch(error => {
                //     this.submitted = false;
                //     ProcessMaker.alert(this.$t('Unable to import the process.')  + (error.response.data.message ? ': ' + error.response.data.message : ''), 'danger');
                // });
            }
            
        },
        checkForPassword() {
            if (!this.passwordEnabled) {
               this.importFile(false);
            } else {
                this.showEnterPasswordModal();
            }
        },
        showEnterPasswordModal() {
            this.$refs['enter-password-modal'].show();
        },
        importReady(response) {
            let message = this.$t("Unable to import the process.");
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
            if (!item.value) {
                item.error = 'Segment is required';
                return
            }
            item.value = item.value.replace(/\s+/g, '-').toLowerCase();

            ProcessMaker.apiClient.get(`/webentry/custom_route/check/${item.value}`)
                .then(response => {
                item.error = null;
                })
                .catch(error => {
                item.error = error.response.data.error;
                });
        },
        validateFile() {
            if (!this.file) {
                return;
            }
            let formData = new FormData();
            formData.append('file', this.file);

            ProcessMaker.apiClient.post('/processes/import/validation', formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }
            )
            .then(response => {
                this.manifest = response.data.manifest;
                this.rootUuid = response.data.rootUuid;
                this.fileIsValid = true;
            });
        },
         removeFile() {
            this.file = '';
        },
        onImportAsNew() {
            console.log('ROUTER', this.$router);
            this.$router.push({name: 'import-new-process', params: {file: this.file}})
            // console.log('file', this.file);
            // console.log('route to new vue');
        }
    },
    mounted() {
        let received = false;
        window.Echo.private(`ProcessMaker.Models.User.${window.ProcessMaker.user.id}`).notification((response) => {
            if (!received && response.type === 'ProcessMaker.Notifications.ImportReady' && this.importingCode === response.code) {
                received = true;
                this.importReady(response);
            }
        });
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
}
</script>

<style type="text/css" scoped>
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

    .border-dotted {
        border: 3px dotted #e0e0e0;
    }

    .fw-medium {
        font-weight:500;
    }

    .fw-semibold {
        font-weight: 600;
    }
</style>