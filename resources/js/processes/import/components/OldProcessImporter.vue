<template>
    <div id="post-import" class="text-left" v-cloak>
        <h5>{{ $t('Status') }}</h5>
        <ul v-show="options" class="mb-0 fa-ul">
            <li v-for="(item, index) in options" :key="index">
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
                    <tr v-for="(item, index) in assignable" :key="index">
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

        <div id="card-footer-post-import" class="card-footer bg-light" align="right" v-cloak>
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
</template>

<script>
const importingCode = window.location.hash.match(/#code=(.+)/);
import { createUniqIdsMixin } from "vue-uniq-ids";
const uniqIdsMixin = createUniqIdsMixin();

export default {
    props: ['options', 'assignable', 'processId'],
    components: {},
    mixins: [uniqIdsMixin],
    data() {
        return {
            importing: importingCode ? true : false,
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
                window.location.href = `/modeler/${this.processId}`;
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
    },
    mounted() {
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
</style>