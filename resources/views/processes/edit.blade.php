@extends('layouts.layout', ['title' => __('Processes Management')])

@section('title')
    {{__('Configure Process')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index'),
        $process->name => null,
    ]])
@endsection
@section('content')
    <div class="container" id="editProcess" v-cloak>
        <div class="row">
            <div class="col-12">

                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-config"
                           role="tab"
                           aria-controls="nav-config" aria-selected="true" @click="activateTab">{{__('Configuration')}}</a>
                        <a class="nav-item nav-link" id="nav-groups-tab" data-toggle="tab" href="#nav-notifications"
                           role="tab"
                           aria-controls="nav-notifications" aria-selected="true" @click="activateTab">{{__('Notifications')}}</a>
                        @isset($addons)
                            @foreach ($addons as $addon)
                                <a class="nav-item nav-link" id="{{$addon['id'] . '-tab'}}" data-toggle="tab"
                                   href="{{'#' . $addon['id']}}" role="tab"
                                   aria-controls="nav-notifications" aria-selected="true"
                                   @click="activateTab"
                                >
                                    {{ __($addon['title']) }}
                                </a>
                            @endforeach
                        @endisset
                    </div>
                </nav>
                <div class="card card-body card-body-nav-tabs">
                    <div class="tab-content" id="nav-tabContent">

                        {{-- Configuration --}}
                        <div
                            class="tab-pane show"
                            :class="{'active': activeTab === '' || activeTab === 'nav-config'}"
                            id="nav-config"
                            role="tabpanel"
                            aria-labelledby="nav-config-tab"
                        >
                            <div class="card card-custom-info">
                                <div
                                    class="card-header"
                                    id="headingInfo"
                                >
                                    <button
                                        class="btn btn-custom-info"
                                        type="button"
                                        data-toggle="collapse"
                                        data-target="#collapseInfo"
                                        aria-expanded="true"
                                        aria-controls="collapseInfo"
                                    >
                                        <span>
                                            {{__('Process Information')}}
                                        </span>
                                    </button>
                                </div>
                                <div
                                    id="collapseInfo"
                                    class="collapse show"
                                    aria-labelledby="headingInfo"
                                >
                                    <div class="card-body">
                                        <b-row>
                                            <b-col>
                                                <div class="form-group">
                                                    {{ html()->label(__('Name') . '
                                                        <small class="ml-1 required-text-color">*</small>', 'name') }}
                                                    {{ html()->text('name')->id('name')->class('form-control')->attribute('v-model', 'formData.name')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.name}') }}
                                                    <small class="form-text text-muted"
                                                        v-if="! errors.name">{{ __('The process name must be unique.') }}</small>
                                                    <div
                                                        class="invalid-feedback"
                                                        role="alert" v-if="errors.name"
                                                    >
                                                        @{{errors.name[0]}}
                                                    </div>
                                                </div>
                                                <category-select
                                                    :label="$t('Category')"
                                                    api-get="process_categories"
                                                    api-list="process_categories"
                                                    v-model="formData.process_category_id"
                                                    :errors="errors.category"
                                                >
                                                </category-select>
                                            </b-col>
                                            <b-col>
                                                <div class="form-group">
                                                    {{ html()->label(__('Description') . '<small class="ml-1 required-text-color">*</small>', 'description') }}
                                                    {{ html()->textarea('description')->id('description')->rows(4)->class('form-control')->attribute('v-model', 'formData.description')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.description}') }}
                                                    <div
                                                        class="invalid-feedback"
                                                        role="alert"
                                                        v-if="errors.description"
                                                    >
                                                        @{{errors.description[0]}}
                                                    </div>
                                                </div>
                                                <project-select
                                                    :label="$t('Project')"
                                                    api-get="projects"
                                                    api-list="projects"
                                                    v-model="selectedProjects"
                                                    :errors="errors.projects"
                                                />
                                            </b-col>
                                        </b-row>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-custom-info">
                              <div class="card-header"
                                   id="headingProcessPermissions">
                                <button
                                  class="btn btn-custom-info"
                                  type="button"
                                  data-toggle="collapse"
                                  data-target="#collapseProcessPermissions"
                                  aria-expanded="true"
                                  aria-controls="collapseProcessPermissions"
                                  >
                                  <span>
                                    {{__('Process Permissions')}}
                                  </span>
                                </button>
                              </div>
                              <div id="collapseProcessPermissions"
                                   class="collapse show"
                                   aria-labelledby="headingProcessPermissions">
                                <div class="card-body">
                                  <b-row>
                                    <b-col>
                                      <div class="form-group">
                                        <label class="typo__label">{{__('Process Manager')}}</label>
                                        <select-user
                                          v-model="manager"
                                          :multiple="false"
                                          :class="{'is-invalid': errors.manager_id}"
                                          />
                                        <div
                                          v-if="errors.manager_id"
                                          class="invalid-feedback"
                                          role="alert"
                                          >
                                          @{{errors.manager_id[0]}}
                                        </div>
                                      </div>
                                    </b-col>
                                    <b-col>
                                    </b-col>
                                  </b-row>
                                </div>
                              </div>
                            </div>
                          
                            <div class="card card-custom-info">
                                <div
                                    class="card-header"
                                    id="headingMore"
                                >
                                    <button
                                        class="btn btn-custom-info"
                                        type="button"
                                        data-toggle="collapse"
                                        data-target="#collapseMore"
                                        aria-expanded="true"
                                        aria-controls="collapseMore"
                                    >
                                        <span>
                                            {{__('More Information')}}
                                        </span>
                                    </button>
                                </div>
                                <div
                                    id="collapseMore"
                                    class="collapse show"
                                    aria-labelledby="headingMore"
                                >
                                    <div class="card-body">
                                        <b-row>
                                            <b-col>
                                                <div class="form-group">
                                                    {{ html()->label(__('Case Title'), 'case_title') }}
                                                    {{ html()->text('case_title')->id('case_title')->class('form-control')->attribute('v-model', 'formData.case_title')->attribute('v-bind:class', '{\'form-control\':true, \'is-invalid\':errors.case_title}') }}
                                                    <div
                                                        v-if="errors.case_title"
                                                        class="invalid-feedback"
                                                        role="alert"
                                                    >
                                                        @{{errors.case_title[0]}}
                                                    </div>
                                                    <small class="form-text text-muted" v-if="! errors.name">
                                                        {{ __('This field has a limit of 200 characters when calculated') }}
                                                    </small>
                                                </div>
                                                <div class="form-group">
                                                    {{ html()->label(__('Cancel Screen'), 'cancelScreen') }}
                                                    <multiselect
                                                        aria-label="{{ __('Cancel Screen') }}"
                                                        v-model="screenCancel"
                                                        :options="screens"
                                                        :multiple="false"
                                                        :show-labels="false"
                                                        placeholder="{{ __('Type to search') }}"
                                                        @search-change="loadScreens($event)"
                                                        @open="loadScreens()"
                                                        track-by="id"
                                                        label="title"
                                                    >
                                                        <span slot="noResult">
                                                            @{{ __(noElementsFoundMsg) }}
                                                        </span>
                                                        <template slot="noOptions">
                                                            {{ __('No Data Available') }}
                                                        </template>
                                                    </multiselect>
                                                    <div
                                                        v-if="errors.screens"
                                                        class="invalid-feedback"
                                                        role="alert"
                                                    >
                                                        @{{errors.screens[0]}}
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    {{ html()->label(__('Request Detail Screen'), 'requestDetailScreen') }}
                                                    <multiselect
                                                        aria-label="{{ __('Request Detail Screen') }}"
                                                        v-model="screenRequestDetail"
                                                        :options="screens"
                                                        :multiple="false"
                                                        :show-labels="false"
                                                        placeholder="{{ __('Type to search') }}"
                                                        @search-change="loadScreens($event)"
                                                        @open="loadScreens()"
                                                        track-by="id"
                                                        label="title"
                                                    >
                                                        <span slot="noResult">
                                                            @{{
                                                                __(noElementsFoundMsg)
                                                            }}
                                                        </span>
                                                        <template slot="noOptions">
                                                            {{ __('No Data Available') }}
                                                        </template>
                                                    </multiselect>
                                                    <div
                                                        v-if="errors.request_detail_screen_id"
                                                        class="invalid-feedback"
                                                    >
                                                        @{{errors.request_detail_screen_id[0]}}
                                                    </div>
                                                </div>
                                            </b-col>
                                            <b-col>
                                                <div class="form-group p-0">
                                                    {{ html()->label(__('Cancel Case'), 'cancelRequest') }}
                                                    <multiselect
                                                        id="cancelRequest"
                                                        v-model="canCancel"
                                                        :options="activeUsersAndGroupsWithManager"
                                                        :multiple="true"
                                                        :show-labels="false"
                                                        placeholder="{{__('Type to search')}}"
                                                        track-by="id"
                                                        label="fullname"
                                                        group-values="items"
                                                        group-label="label"
                                                    >
                                                        <span slot="noResult">
                                                            @{{
                                                                noElementsFoundMsg
                                                            }}
                                                        </span>
                                                        <template slot="noOptions">
                                                            {{ __('No Data Available') }}
                                                        </template>
                                                    </multiselect>
                                                </div>
                                                <div class="form-group p-0">
                                                    {{ html()->label(__('Edit Data'), 'editData') }}
                                                    <multiselect
                                                        id="editData"
                                                        v-model="canEditData"
                                                        :options="activeUsersAndGroups"
                                                        :multiple="true"
                                                        :show-labels="false"
                                                        placeholder="{{__('Type to search')}}"
                                                        track-by="id"
                                                        label="fullname"
                                                        group-values="items"
                                                        group-label="label"
                                                    >
                                                        <span slot="noResult">
                                                            @{{
                                                                __(noElementsFoundMsg)
                                                            }}
                                                        </span>
                                                        <template slot="noOptions">
                                                            {{ __('No Data Available') }}
                                                        </template>
                                                    </multiselect>
                                                </div>
                                                <div class="form-group">
                                                    {{ html()->label(__('Status'), 'status') }}
                                                    <select-status
                                                        v-model="formData.status"
                                                        :multiple="false"
                                                    />
                                                </div>
                                            </b-col>
                                        </b-row>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-2">
                                {{ html()->button(__('Cancel'), 'button')->class('btn btn-outline-secondary button-custom')->attribute('@click', 'onClose') }}
                                {{ html()->button(__('Save'), 'button')->class('btn btn-secondary ml-3 button-custom')->attribute('@click', 'onUpdate') }}
                            </div>
                        </div>

                        {{-- Notifications --}}
                        <div class="tab-pane show p-3" id="nav-notifications" role="tabpanel"
                             aria-labelledby="nav-notifications-tab">
                            <div class="form-group p-0">

                                <table id="table-notifications" class="table">
                                    <thead>
                                    <tr>
                                        <th class="notify"></th>
                                        <th class="action">{{__('Request Started')}}</th>
                                        <th class="action">{{__('Request Canceled')}}</th>
                                        <th class="action">{{__('Request Completed')}}</th>
                                        <th class="action">{{__('Request Error')}}</th>
                                        <th class="action">{{__('Request Commented')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="notify">{{__('Notify Process Manager')}}</td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-manager-started" type="checkbox"
                                                           v-model="formData.notifications.manager.started"
                                                            class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-manager-started"></label>
                                                </div>
                                            </td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-manager-canceled" type="checkbox"
                                                           v-model="formData.notifications.manager.canceled"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-manager-canceled"></label>
                                                </div>
                                            </td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-manager-completed" type="checkbox"
                                                           v-model="formData.notifications.manager.completed"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-manager-completed"></label>
                                                </div>
                                            </td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-manager-error" type="checkbox"
                                                           v-model="formData.notifications.manager.error"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-manager-error"></label>
                                                </div>
                                            </td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-manager-comment" type="checkbox"
                                                           v-model="formData.notifications.manager.comment"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-manager-comment"></label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="notify">{{__('Notify Requester')}}</td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-requester-started" type="checkbox"
                                                           v-model="formData.notifications.requester.started"
                                                            class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-requester-started"></label>
                                                </div>
                                            </td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-requester-canceled" type="checkbox"
                                                           v-model="formData.notifications.requester.canceled"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-requester-canceled"></label>
                                                </div>
                                            </td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-requester-completed" type="checkbox"
                                                           v-model="formData.notifications.requester.completed"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-requester-completed"></label>
                                                </div>
                                            </td>
                                            <td class="action"></td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-requester-comment" type="checkbox"
                                                           v-model="formData.notifications.requester.comment"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-requester-comment"></label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="notify">{{__('Notify Participants')}}</td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">

                                                </div>
                                            </td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-participants-canceled" type="checkbox"
                                                           v-model="formData.notifications.participants.canceled"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-participants-canceled"></label>
                                                </div>
                                            </td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-participants-completed" type="checkbox"
                                                           v-model="formData.notifications.participants.completed"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-participants-completed"></label>
                                                </div>
                                            </td>
                                            <td class="action"></td>
                                            <td class="action">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-participants-comment" type="checkbox"
                                                           v-model="formData.notifications.participants.comment"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-participants-comment"></label>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end mt-2">
                                {{ html()->button(__('Cancel'), 'button')->class('btn btn-outline-secondary')->attribute('@click', 'onClose') }}
                                {{ html()->button(__('Save'), 'button')->class('btn btn-secondary ml-2')->attribute('@click', 'onUpdate') }}
                            </div>
                        </div>

                        {{-- Addons --}}
                        @isset($addons)
                            @foreach ($addons as $addon)
                                <div class="tab-pane show" id="{{$addon['id']}}" role="tabpanel"
                                     aria-labelledby="nav-notifications-tab">
                                    {!! $addon['content'] !!}
                                </div>
                            @endforeach
                        @endisset
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="{{mix('js/processes/edit.js')}}"></script>
    <script>
        window.addEventListener("load", function() {
            test = new Vue({
                el: '#editProcess',
                mixins: addons,
                data() {
                return {
                    formData: @json($process),
                    assignedProjects: @json($assignedProjects),
                    isDraft: @json($isDraft),
                    selectedProjects: '',
                    dataGroups: [],
                    value: [],
                    errors: {
                    name: null,
                    description: null,
                    category: null,
                    status: null,
                    screen: null
                    },
                    screens: [],
                    canCancel: @json($canCancel),
                    canEditData: @json($canEditData),
                    screenRequestDetail: @json($screenRequestDetail),
                    screenCancel: @json($screenCancel),
                    activeUsersAndGroups: @json($list),
                    pause_timer_start_events: false,
                    manager: @json($process->manager),
                    activeTab: "",
                    noElementsFoundMsg: 'Oops! No elements found. Consider changing the search query.',
                    reassignmentPermissions: {
                        users: [],
                        groups: []
                    },
                }
                },
                mounted() {
                    this.activeTab = "";
                    if (_.get(this.formData, 'properties.manager_can_cancel_request')) {
                        this.canCancel.push(this.processManagerOption());
                    }
                    
                    const reassignmentPermissions = _.get(this.formData, 'properties.reassignment_permissions');
                    if (reassignmentPermissions) {
                        this.reassignmentPermissions = reassignmentPermissions;
                    }

                    this.selectedProjects = this.assignedProjects.length > 0 ? this.assignedProjects.map(project => project.id) : null;

                    let path = new URL(location.href).href;
                    let target = path.split('#');

                    if (target[1] !== undefined) {
                        this.activeTab = target[1];
                    }

                },
                computed: {
                    activeUsersAndGroupsWithManager() {
                        const usersAndGroups = _.cloneDeep(this.activeUsersAndGroups);
                        usersAndGroups[0]['items'].unshift(this.processManagerOption());
                        return usersAndGroups;
                    }
                },
                watch: {
                    selectedProjects: {
                        handler() {
                            this.formData.projects = this.selectedProjects;
                        }
                    }
                },
                methods: {
                activateTab(event) {
                    window.location.href = event.target.href;
                },
                loadScreens(filter) {
                    ProcessMaker.apiClient
                    .get("screens?order_direction=asc&status=active&type=DISPLAY" + (typeof filter === 'string' ? '&filter=' + filter : ''))
                    .then(response => {
                        this.screens = response.data.data;
                    });
                },
                resetErrors() {
                    this.errors = Object.assign({}, {
                    name: null,
                    description: null,
                    category: null,
                    projects: null,
                    status: null,
                    screen: null
                    });
                },
                onClose() {
                    window.location.href = '/processes';
                },
                formatAssigneePermissions(data) {
                    let response = {};

                    response['users'] = [];
                    response['groups'] = [];
                    response['pseudousers'] = [];

                    data.forEach(item => {
                    if (item.type == 'user') {
                        response['users'].push(parseInt(item.id));
                    }

                    if (item.type == 'group') {
                        response['groups'].push(parseInt(item.id));
                    }

                    if (item.type === 'pseudouser') {
                        response['pseudousers'].push(item.id);
                    }
                    });
                    return response;
                },
                formatValueScreen(item) {
                    return (item && item.id) ? item.id : null
                },
                onUpdate() {
                    let shouldDelete = false;
                    if (this.isDraft) {
                        ProcessMaker.confirmModal(
                            this.$t("Caution!"),
                            this.$t("You are about to publish a draft version. Are you sure you want to proceed?"),
                            "",
                            () => {
                                this.handleUpdate();
                            }
                        );
                    } else {
                        this.handleUpdate();
                    }
                },
                handleUpdate() {
                    this.resetErrors();
                    let that = this;
                    this.formData.cancel_request = this.formatAssigneePermissions(this.canCancel);
                    this.formData.edit_data = this.formatAssigneePermissions(this.canEditData);
                    this.formData.cancel_screen_id = this.formatValueScreen(this.screenCancel);
                    this.formData.request_detail_screen_id = this.formatValueScreen(this.screenRequestDetail);
                    this.formData.manager_id = this.formatValueScreen(this.manager);
                    this.formData.reassignment_permissions = this.reassignmentPermissions;
                    
                    ProcessMaker.apiClient.put('processes/' + that.formData.id, that.formData)
                    .then(response => {
                        ProcessMaker.alert(this.$t('The process was saved.'), 'success', 5, true);
                        that.onClose();
                    })
                    .catch(error => {
                        //define how display errors
                        if (error.response.status && error.response.status === 422) {
                        // Validation error
                        that.errors = error.response.data.errors;
                        }
                    });
                },
                processManagerOption() {
                    return {
                        type: 'pseudouser',
                        id: 'manager',
                        fullname: this.$t('Process Manager')
                    };
                },
                newTranslation() {
                    this.$bvModal.show("createProcessTranslation");
                },
                importTranslation() {
                    window.location = `/processes/${this.formData.id}/import/translation`
                },
                reassignmentClicked() {
                    this.$refs["listReassignment"].add();
                }
                },
            });
        });

    </script>
@endsection

@section('css')
    <style>
        .card-body-nav-tabs {
            border-top: 0;
        }

        .nav-tabs .nav-link.active {
            background: white;
            border-bottom: 0;
        }

        #table-notifications {
            margin-bottom: 20px;
        }

        #table-notifications th {
            border-top: 0;
        }

        #table-notifications thead {
            text-align: center;
        }

        #table-notifications td.action {
            text-align: center;
        }

        #table-notifications td.notify {
            width: 215px;
        }

        .inline-input {
            margin-right: 6px;
        }

        .inline-button {
            background-color: rgb(109, 124, 136);
            font-weight: 100;
        }

        .input-and-select {
            width: 212px;
        }

        .multiselect__tags-wrap {
            display: flex !important;
        }

        .multiselect__tag-icon:after {
            color: white !important;
        }

        .multiselect__option--highlight {
            background: #00bf9c !important;
        }

        .multiselect__option--selected.multiselect__option--highlight {
            background: #00bf9c !important;
        }

        .multiselect__tags {
            border: 1px solid var(--borders, #cdddee) !important;
            border-radius: 4px !important;
            height: 40px !important;
        }

        .multiselect__tag {
            background: #788793 !important;
        }

        .multiselect__tag-icon:after {
            color: white !important;
        }

        .card-custom-info {
            border: 0px;
            margin-bottom: 5px;
        }

        .card-custom-info .card-header {
            border: 0px;
            height: 40px;
            padding: 8px 19.59px;
            border-radius: 4px;
            opacity: 0px;
            background: #f2f8fe;
        }

        .card-custom-info .card-body {
            padding: 24px 13px;
        }
       
        .btn-custom-info {
            display: flex;
            justify-content: space-between;
            width: 100%;
            height: 24px;
            padding: 0px;
            left: 42.59px;
            color: #556271;
            font-family: 'Open Sans', sans-serif;
            font-size: 16px;
            font-weight: 400;
            line-height: 21px;
            letter-spacing: -0.02em;
            text-align: left;
            text-transform: capitalize;
        }
        .btn-custom-info:after {
            font-family: "Font Awesome 5 Free";
            font-weight: 600;
            content: "\f0d7";";
        }

        .btn-custom-info:not(.collapsed):after {
            content: "\f0d8";
        }

        .btn-custom-info:focus {
            box-shadow: none;
        }

        .custom-size-icon {
            width: 10px;
        }
        
        .required-text-color,
        .form-group[required=required] label::after {
            color: #1572c2;
            opacity: 1;
        }
        
        .form-group label{
            color: #556271;
            font-family: 'Open Sans', sans-serif;
            font-size: 16px;
            font-weight: 600;
            line-height: 24px;
            letter-spacing: -0.02em;
        }

        .form-control {
            border-radius: 4px;
            border: 1px solid var(--borders, #cdddee)
        }

        .button-custom {
            width: 118px;
            height: 40px;
            padding: 0px 15px;
            border-radius: 4px;
            font-family: 'Open Sans', sans-serif;
            font-size: 16px;
            font-weight: 600;
            line-height: 24px;
            letter-spacing: -0.02em;
            text-align: center;
        }
    </style>
@endsection
