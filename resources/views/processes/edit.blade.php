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
                           aria-controls="nav-config" aria-selected="true">{{__('Configuration')}}</a>
                        @can('view-process-translations')
                            <a class="nav-item nav-link" id="nav-groups-tab" data-toggle="tab" href="#nav-translations"
                                role="tab"
                                data-test="translation-tab"
                                aria-controls="nav-translations" aria-selected="true">{{__('Translations')}}</a>
                        @endcan
                        <a class="nav-item nav-link" id="nav-groups-tab" data-toggle="tab" href="#nav-notifications"
                           role="tab"
                           aria-controls="nav-notifications" aria-selected="true">{{__('Notifications')}}</a>
                        @isset($addons)
                            @foreach ($addons as $addon)
                                <a class="nav-item nav-link" id="{{$addon['id'] . '-tab'}}" data-toggle="tab"
                                   href="{{'#' . $addon['id']}}" role="tab"
                                   aria-controls="nav-notifications" aria-selected="true">{{ __($addon['title']) }}</a>
                            @endforeach
                        @endisset
                    </div>
                </nav>
                <div class="card card-body card-body-nav-tabs">
                    <div class="tab-content" id="nav-tabContent">

                        {{-- Configuration --}}
                        <div class="tab-pane fade show" :class="{'active': activeTab === ''}" id="nav-config" role="tabpanel"
                             aria-labelledby="nav-config-tab">
                            <required></required>
                            <div class="form-group">
                                {!!Form::label('name', __('Name') . '<small class="ml-1">*</small>', [], false)!!}
                                {!!Form::text('name', null,
                                    [ 'id'=> 'name',
                                        'class'=> 'form-control',
                                        'v-model'=> 'formData.name',
                                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'
                                    ])
                                !!}
                                <small class="form-text text-muted"
                                       v-if="! errors.name">{{ __('The process name must be unique.') }}</small>
                                <div class="invalid-feedback" role="alert" v-if="errors.name">@{{errors.name[0]}}</div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('description', __('Description')  . '<small class="ml-1">*</small>', [], false) !!}
                                {!! Form::textarea('description', null,
                                    ['id' => 'description',
                                        'rows' => 4,
                                        'class'=> 'form-control',
                                        'v-model' => 'formData.description',
                                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}'
                                    ])
                                !!}
                                <div class="invalid-feedback" role="alert" v-if="errors.description">@{{errors.description[0]}}</div>
                            </div>
                            <category-select :label="$t('Category')" api-get="process_categories"
                                api-list="process_categories" v-model="formData.process_category_id"
                                :errors="errors.category"
                                >
                            </category-select>
                            <div class="form-group">
                                <label class="typo__label">{{__('Process Manager')}}</label>
                                <select-user v-model="manager" :multiple="false" :class="{'is-invalid': errors.manager_id}">
                                </select-user>
                                <div class="invalid-feedback" role="alert" v-if="errors.manager_id">@{{errors.manager_id[0]}}</div>
                            </div>
                            <div class="form-group p-0">
                                {!! Form::label('cancelRequest', __('Cancel Request')) !!}
                                <multiselect id="cancelRequest"
                                             v-model="canCancel"
                                             :options="activeUsersAndGroupsWithManager"
                                             :multiple="true"
                                             :show-labels="false"
                                             placeholder="{{__('Type to search')}}"
                                             track-by="id"
                                             label="fullname"
                                             group-values="items"
                                             group-label="label">
                                    <span slot="noResult">{{__('Oops! No elements found. Consider changing the search query.')}}</span>
                                    <template slot="noOptions">
                                        {{ __('No Data Available') }}
                                    </template>
                                </multiselect>
                            </div>
                            <div class="form-group">
                                {!! Form::label('cancelScreen', __('Cancel Screen')) !!}
                                <multiselect aria-label="{{ __('Cancel Screen') }}"
                                             v-model="screenCancel"
                                             :options="screens"
                                             :multiple="false"
                                             :show-labels="false"
                                             placeholder="{{ __('Type to search') }}"
                                             @search-change="loadScreens($event)"
                                             @open="loadScreens()"
                                             track-by="id"
                                             label="title">
                                    <span slot="noResult">{{ __('Oops! No elements found. Consider changing the search query.') }}</span>
                                    <template slot="noOptions">
                                        {{ __('No Data Available') }}
                                    </template>
                                </multiselect>
                                <div class="invalid-feedback" role="alert" v-if="errors.screens">@{{errors.screens[0]}}</div>
                            </div>
                            <div class="form-group p-0">
                                {!! Form::label('editData', __('Edit Data')) !!}
                                <multiselect id="editData"
                                             v-model="canEditData"
                                             :options="activeUsersAndGroups"
                                             :multiple="true"
                                             :show-labels="false"
                                             placeholder="{{__('Type to search')}}"
                                             track-by="id"
                                             label="fullname"
                                             group-values="items"
                                             group-label="label">
                                    <span slot="noResult">{{__('Oops! No elements found. Consider changing the search query.')}}</span>
                                    <template slot="noOptions">
                                        {{ __('No Data Available') }}
                                    </template>
                                </multiselect>
                            </div>
                            <div class="form-group">
                                {!! Form::label('requestDetailScreen', __('Request Detail Screen')) !!}
                                <multiselect aria-label="{{ __('Request Detail Screen') }}"
                                             v-model="screenRequestDetail"
                                             :options="screens"
                                             :multiple="false"
                                             :show-labels="false"
                                             placeholder="{{ __('Type to search') }}"
                                             @search-change="loadScreens($event)"
                                             @open="loadScreens()"
                                             track-by="id"
                                             label="title">
                                    <span slot="noResult">{{ __('Oops! No elements found. Consider changing the search query.') }}</span>
                                    <template slot="noOptions">
                                        {{ __('No Data Available') }}
                                    </template>
                                </multiselect>
                                <div class="invalid-feedback" v-if="errors.request_detail_screen_id">@{{errors.request_detail_screen_id[0]}}</div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('status', __('Status')) !!}
                                <select-status v-model="formData.status" :multiple="false"></select-status>
                            </div>
                            <div class="d-flex justify-content-end mt-2">
                                {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                                {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
                            </div>
                        </div>

                        {{-- Translations --}}
                        @can('view-process-translations')
                            <div class="tab-pane fade show" :class="{'active': activeTab === 'nav-translations'}" id="nav-translations" ref="nav-translations" role="tabpanel"
                                aria-labelledby="nav-translations-tab">
                                
                                <div class="page-content mb-0" id="processTranslationIndex">
                                    <div id="search-bar" class="search mb-3" vcloak>
                                        <div class="d-flex flex-column flex-md-row">
                                            <div class="flex-grow-1">
                                                <div id="search" class="mb-3 mb-md-0">
                                                    <div class="input-group w-100">
                                                        <input id="search-box" v-model="filterTranslations" class="form-control" placeholder="{{__('Search')}}"  aria-label="{{__('Search')}}">
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}">
                                                                <i class="fas fa-search"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @canany(['import-process-translations', 'create-process-translations'])
                                                <div class="d-flex ml-md-0 flex-column flex-md-row">
                                                    @can('import-process-translations')
                                                        <div class="mb-3 mb-md-0 ml-md-2">
                                                            <a href="#" aria-label="{{ __('Import Translation') }}" id="import_translation" class="btn btn-outline-secondary w-100" @click="importTranslation" data-test="translation-import">
                                                                <i class="fas fa-file-import"></i> {{__('Import')}}
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('create-process-translations')
                                                        <div class="mb-3 mb-md-0 ml-md-2">
                                                            <a href="#" 
                                                                aria-label="{{ __('New Translation') }}" 
                                                                id="new_translation" 
                                                                class="btn btn-primary w-100" 
                                                                @click="newTranslation"
                                                                data-test="translation-create-button">
                                                                {{__('+ Translation')}}
                                                            </a>
                                                        </div>
                                                    @endcan
                                                </div>
                                            @endcan
                                        </div>
                                    </div>
                                
                                    <div class="container-fluid">
                                        <process-translation-listing
                                            ref="translationsListing"
                                            :filter="filterTranslations"
                                            :permission="{{ \Auth::user()->hasPermissionsFor('process-translations') }}"
                                            @translated-languages-changed="onTranslatedLanguagesChanged"
                                            @edit-translation="onEditTranslation"
                                            :process-id="{{ $process->id }}"
                                        ></process-translation-listing>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-2">
                                    {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                                    {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
                                </div>

                                <create-process-translation-modal 
                                    ref="createProcessTranslationModal" 
                                    :process-id="{{ $process->id }}"
                                    :permission="{{ \Auth::user()->hasPermissionsFor('process-translations') }}"
                                    process-name="{{ $process->name }}"
                                    :edit-translation="editTranslation"
                                    @create-process-translation-closed="onCreateProcessTranslationClosed"
                                    @translating-language="onTranslatingLanguage"
                                    @language-saved="onLanguageSaved"/>
                            </div>
                        @endcan

                        {{-- Notifications --}}
                        <div class="tab-pane fade show p-3" id="nav-notifications" role="tabpanel"
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
                                            <td class="action" colspan="2">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-requester-completed" type="checkbox"
                                                           v-model="formData.notifications.requester.completed"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-requester-completed"></label>
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
                                            <td class="action" colspan="2">
                                                <div class="custom-control custom-switch">
                                                    <input id="notify-participants-completed" type="checkbox"
                                                           v-model="formData.notifications.participants.completed"
                                                           class="custom-control-input">
                                                    <label class="custom-control-label"
                                                           for="notify-participants-completed"></label>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end mt-2">
                                {!! Form::button(__('Cancel'), ['class'=>'btn btn-outline-secondary', '@click' => 'onClose']) !!}
                                {!! Form::button(__('Save'), ['class'=>'btn btn-secondary ml-2', '@click' => 'onUpdate']) !!}
                            </div>
                        </div>

                        {{-- Addons --}}
                        @isset($addons)
                            @foreach ($addons as $addon)
                                <div class="tab-pane fade show" id="{{$addon['id']}}" role="tabpanel"
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
      test = new Vue({
        el: '#editProcess',
        mixins: addons,
        data() {
          return {
            formData: @json($process),
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
            filterTranslations: "",
            canCancel: @json($canCancel),
            canEditData: @json($canEditData),
            screenRequestDetail: @json($screenRequestDetail),
            screenCancel: @json($screenCancel),
            activeUsersAndGroups: @json($list),
            pause_timer_start_events: false,
            manager: @json($process->manager),
            translatedLanguages: [],
            editTranslation: null,
            activeTab: "",
          }
        },
        mounted() {
            this.activeTab = "";
            if (_.get(this.formData, 'properties.manager_can_cancel_request')) {
                this.canCancel.push(this.processManagerOption());
            }
            
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
        methods: {
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
              status: null,
              screen: null
            });
          },
          onClose() {
            window.location.href = '/processes';
          },
          onTranslatedLanguagesChanged(translatedLanguages) {
            this.translatedLanguages = translatedLanguages;
            this.$refs.createProcessTranslationModal.getAvailableLanguages();
          },
          onEditTranslation(editTranslation) {
            this.editTranslation = editTranslation;
            this.$bvModal.show("createProcessTranslation");
          },
          onCreateProcessTranslationClosed() {
            this.editTranslation = null;
          },
          onTranslatingLanguage() {
            this.$refs.translationsListing.fetch();
            this.$refs.translationsListing.fetchPending();
          },
          onLanguageSaved() {
            this.$refs.translationsListing.fetch();
            this.$refs.translationsListing.fetchPending();
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
            this.resetErrors();
            let that = this;
            this.formData.cancel_request = this.formatAssigneePermissions(this.canCancel);
            this.formData.edit_data = this.formatAssigneePermissions(this.canEditData);
            this.formData.cancel_screen_id = this.formatValueScreen(this.screenCancel);
            this.formData.request_detail_screen_id = this.formatValueScreen(this.screenRequestDetail);
            this.formData.manager_id = this.formatValueScreen(this.manager);
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
        }
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

        #table-notifications td.notify {
        }

        #table-notifications td.action {
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
            border: 1px solid #b6bfc6 !important;
            border-radius: 0.125em !important;
            height: calc(1.875rem + 2px) !important;
        }

        .multiselect__tag {
            background: #788793 !important;
        }

        .multiselect__tag-icon:after {
            color: white !important;
        }
    </style>
@endsection
