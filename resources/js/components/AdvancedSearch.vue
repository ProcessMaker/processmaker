<template>
    <div id="search-bar" class="search advanced-search mb-3">
        <div class="d-flex">
            <div class="flex-grow-1">
                <div v-if="! advanced" class="search-bar-advanced d-flex flex-column flex-md-row w-100">
                    <div v-if="type == 'requests'" class="search-bar-inputs flex-grow d-flex flex-column flex-md-row w-100">
                        <label for="process_name_filter" class="d-none">{{$t('Process')}}</label>
                        <multiselect id="process_name_filter" v-model="process"
                                     @search-change="getProcesses"
                                     @input="buildPmql"
                                     :show-labels="false"
                                     :loading="isLoading.process"
                                     open-direction="bottom"
                                     label="name"
                                     :options="processOptions"
                                     :track-by="'id'"
                                     :multiple="true"
                                     :aria-label="$t('Process')"
                                     :placeholder="$t('Process')">
                            <template slot="noResult">
                                {{ $t('No Results') }}
                            </template>
                            <template slot="noOptions">
                                {{ $t('No Data Available') }}
                            </template>
                            <template slot="selection" slot-scope="{ values, search, isOpen }">
                                <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('processes') }}</span>
                            </template>
                        </multiselect>
                      <label for="process_status_filter" class="d-none">{{$t('status')}}</label>
                      <multiselect id="process_status_filter" v-model="status"
                                     :show-labels="false"
                                     @input="buildPmql"
                                     :loading="isLoading.status"
                                     open-direction="bottom"
                                     label="name"
                                     :options="statusOptions"
                                     track-by="value"
                                     :multiple="true"
                                     :aria-label="$t('Status')"
                                     :placeholder="$t('Status')">
                            <template slot="noResult">
                                {{ $t('No Results') }}
                            </template>
                            <template slot="noOptions">
                                {{ $t('No Data Available') }}
                            </template>
                            <template slot="selection" slot-scope="{ values, search, isOpen }">
                                <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('statuses') }}</span>
                            </template>
                        </multiselect>
                      <label for="process_requester_filter" class="d-none">{{$t('Requester')}}</label>
                      <multiselect id="process_requester_filter"
                                   v-model="requester"
                                     @search-change="getRequesters"
                                     @input="buildPmql"
                                     :show-labels="false"
                                     :loading="isLoading.requester"
                                     open-direction="bottom"
                                     label="fullname"
                                     :options="requesterOptions"
                                     :track-by="'id'"
                                     :multiple="true"
                                     :aria-label="$t('Requester')"
                                     :placeholder="$t('Requester')">
                            <template slot="noResult">
                                {{ $t('No Results') }}
                            </template>
                            <template slot="noOptions">
                                {{ $t('No Data Available') }}
                            </template>
                            <template slot="selection" slot-scope="{ values, search, isOpen }">
                                <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('requesters') }}</span>
                            </template>
                            <template slot="option" slot-scope="props">
                                <img v-if="props.option.avatar.length > 0" class="option__image"
                                     :src="props.option.avatar">
                                <span v-else class="initials bg-warning text-white p-1"> {{getInitials(props.option.firstname, props.option.lastname)}}</span>
                                <span class="ml-1">{{props.option.fullname}}</span>
                            </template>
                        </multiselect>
                      <label for="process_participant_filter" class="d-none">{{$t('Participants')}}</label>
                      <multiselect id="process_participant_filter" v-model="participants"
                                     @search-change="getParticipants"
                                     @input="buildPmql"
                                     :show-labels="false"
                                     :loading="isLoading.participants"
                                     open-direction="bottom"
                                     label="fullname"
                                     :options="participantsOptions"
                                     :track-by="'id'"
                                     :multiple="true"
                                     :aria-label="$t('Participants')"
                                     :placeholder="$t('Participants')">
                            <template slot="noResult">
                                {{ $t('No Results') }}
                            </template>
                            <template slot="noOptions">
                                {{ $t('No Data Available') }}
                            </template>
                            <template slot="selection" slot-scope="{ values, search, isOpen }">
                                <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('requesters') }}</span>
                            </template>
                            <template slot="option" slot-scope="props">
                                <img v-if="props.option.avatar.length > 0" class="option__image"
                                     :src="props.option.avatar">
                                <span v-else class="initials bg-warning text-white p-1"> {{getInitials(props.option.firstname, props.option.lastname)}}</span>
                                <span class="ml-1">{{props.option.fullname}}</span>
                            </template>
                        </multiselect>
                    </div>
                    <div v-if="type == 'tasks'" class="search-bar-inputs flex-grow d-flex flex-column flex-md-row w-100">
                      <label for="process_request_filter" class="d-none">{{$t('Request')}}</label>
                      <multiselect id="process_request_filter"
                                  v-model="request"
                                     @search-change="getRequests"
                                     @input="buildPmql"
                                     :show-labels="false"
                                     :loading="isLoading.request"
                                     open-direction="bottom"
                                     label="name"
                                     :options="requestOptions"
                                     :track-by="'id'"
                                     :multiple="true"
                                     :aria-label="$t('Request')"
                                     :placeholder="$t('Request')">
                            <template slot="noResult">
                                {{ $t('No Results') }}
                            </template>
                            <template slot="noOptions">
                                {{ $t('No Data Available') }}
                            </template>
                            <template slot="selection" slot-scope="{ values, search, isOpen }">
                                <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('requests') }}</span>
                            </template>
                        </multiselect>
                      <label for="process_task_filter" class="d-none">{{$t('Task')}}</label>
                      <multiselect id="process_task_filter"
                                     v-model="name"
                                     @search-change="getNames"
                                     @input="buildPmql"
                                     :show-labels="false"
                                     :loading="isLoading.name"
                                     open-direction="bottom"
                                     label="name"
                                     :options="nameOptions"
                                     :track-by="'name'"
                                     :multiple="true"
                                     :aria-label="$t('Task')"
                                     :placeholder="$t('Task')">
                            <template slot="noResult">
                                {{ $t('No Results') }}
                            </template>
                            <template slot="noOptions">
                                {{ $t('No Data Available') }}
                            </template>
                            <template slot="selection" slot-scope="{ values, search, isOpen }">
                                <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('names') }}</span>
                            </template>
                        </multiselect>
                      <label for="process_status_options_filter" class="d-none">{{$t('Status')}}</label>
                      <multiselect id="process_status_options_filter"
                                      v-model="status"
                                     :show-labels="false"
                                     @input="buildPmql"
                                     :loading="isLoading.status"
                                     open-direction="bottom"
                                     label="name"
                                     :options="statusOptions"
                                     track-by="value"
                                     :multiple="true"
                                     :aria-label="$t('Status')"
                                     :placeholder="$t('Status')">
                            <template slot="noResult">
                                {{ $t('No Results') }}
                            </template>
                            <template slot="noOptions">
                                {{ $t('No Data Available') }}
                            </template>
                            <template slot="selection" slot-scope="{ values, search, isOpen }">
                                <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('statuses') }}</span>
                            </template>
                        </multiselect>
                    </div>
                    <div class="search-bar-actions d-flex flex-shrink mt-3 mt-md-0">
                        <b-btn class="btn-search-toggle mr-2 mr-md-0" variant="secondary" @click="toggleAdvanced" v-b-tooltip.hover :title="$t('Advanced Mode')"><i class="fas fa-ellipsis-h"></i></b-btn>
                        <b-btn class="btn-search-run flex-grow-1" variant="primary" @click="runSearch()" v-b-tooltip.hover :title="$t('Search')"><i class="fas fa-search"></i><span class="d-md-none"> Search</span></b-btn>
                        <div class="search-bar-additions">
                          <div v-for="addition in additions">
                            <component :is="addition" :permission="permission"></component>
                          </div>
                        </div>
                    </div>
                </div>

                <pmql-input v-if="advanced"
                      ref="pmql_input"
                      :search-type="type"
                      :value="pmql"
                      :ai-enabled="true"
                      :aria-label="$t('Advanced Search (PMQL)')"
                      :search-label="$t('Search using natural language or PMQL')"
                      @submit="onNLQConversion">
                      
                      <template v-slot:left-buttons>
                        <b-btn class="btn-search-toggle" variant="success" @click="toggleAdvanced" v-b-tooltip.hover :title="$t('Basic Mode')"><i class="fas fa-ellipsis-h"></i></b-btn>
                        <div class="d-flex">
                          <div class="d-flex mr-1" v-for="addition in additions">
                            <component class="d-flex" :is="addition" :permission="permission"></component>
                          </div>
                        </div>
                      </template>
                </pmql-input>
            </div>
        </div>
    </div>
</template>

<script>

import isPMQL from "../modules/isPMQL";
import PmqlInput from "../components/shared/PmqlInput";

export default {
  components: { PmqlInput },
  props: ["type", "paramProcess", "paramStatus", "paramRequester", "paramParticipants", "paramRequest", "paramName", "permission"],
  data() {
    return {
        process: [],
        status: [],
        requester: [],
        request: [],
        name: [],
        participants: [],
        processOptions: [],
        statusOptions: [],
        requesterOptions: [],
        participantsOptions: [],
        requestOptions: [],
        nameOptions: [],
        additions: [],
        advanced: false,
        assistantEnabled: false,
        assistantLoading: false,
        searchInputLabel: 'Search using natural language or PMQL',
        pmql: '',
        isLoading: {
          process: false,
          requester: false,
          status: false,
          participants: false,
          request: false,
          name: false,
      },
    };
  },
  watch: {
    pmql(query) {
        this.$emit('change', query);
    },
  },
  mounted() {
    ProcessMaker.EventBus.$on('advanced-search-addition', (component) => {
      this.additions.push(component);
    });
  },
  methods: {
      onNLQConversion(pmql) {
        this.pmql = pmql;
        this.runSearch(true);
      },
      toggleAdvanced() {
        if (this.advanced) {
          this.advanced = false;
        } else {
          this.advanced = true;
          Vue.nextTick().then(() => {
            // this.$refs.search_input.focus();
          });
        }
      },
      toggleAssistant() {
        if (this.advanced) {
            this.assistantEnabled = !this.assistantEnabled;
            this.pmql = '';
        }
      },
      runSearch(advanced) {
        if (! advanced) {
          this.buildPmql();
        }

        this.$emit('submit');
      },

      buildPmql() {
          switch (this.type) {
              case 'requests':
                  this.buildRequestPmql();
                  break;
              case 'tasks':
                  this.buildTaskPmql();
                  break;
          } 
      },
      buildRequestPmql() {
        let clauses = [];

        //Parse process
        if (this.process.length) {
          let string = '';
          this.process.forEach((process, key) => {
            string += 'request = "' + process.name + '"';
            if (key < this.process.length - 1) string += ' OR ';
          });
          clauses.push(string);
        }
        
        //Parse status
        if (this.status.length) {
          let string = '';
          this.status.forEach((status, key) => {
            string += 'status = "' + status.value + '"';
            if (key < this.status.length - 1) string += ' OR ';
          });
          clauses.push(string);
        }
        
        //Parse requester
        if (this.requester.length) {
          let string = '';
          this.requester.forEach((requester, key) => {
            string += 'requester = "' + requester.username + '"';
            if (key < this.requester.length - 1) string += ' OR ';
          });
          clauses.push(string);
        }
        
        //Parse participants
        if (this.participants.length) {
          let string = '';
          this.participants.forEach((participants, key) => {
            string += 'participant = "' + participants.username + '"';
            if (key < this.participants.length - 1) string += ' OR ';
          });
          clauses.push(string);
        }
        
        this.pmql = '';
        clauses.forEach((string, key) => {
          this.pmql += '(';
          this.pmql += string;
          this.pmql += ')';
          if (key < clauses.length - 1) this.pmql += ' AND ';
        });          
      },
      buildTaskPmql() {
          let clauses = [];
          const isSelfService = this.status.find(status => status.value === 'Self Service');

          // Add default filter by user id
          if (!isSelfService) {
            const userId = parseInt(window.ProcessMaker.user.id);
            clauses.push('user_id = ' + userId);
          }

          //Parse request
          if (this.request.length) {
            let string = '';
            this.request.forEach((request, key) => {
              string += 'request = "' + request.name + '"';
              if (key < this.request.length - 1) string += ' OR ';
            });
            clauses.push(string);
          }

          //Parse names
          if (this.name.length) {
            let string = '';
            this.name.forEach((name, key) => {
              string += 'task = "' + name.name + '"';
              if (key < this.name.length - 1) string += ' OR ';
            });
            clauses.push(string);
          }

          //Parse status
          if (this.status.length) {
            let string = '';
            this.status.forEach((status, key) => {
              string += 'status = "' + status.value + '"';
              if (key < this.status.length - 1) string += ' OR ';
            });
            clauses.push(string);
          }

          this.pmql = '';
          clauses.forEach((string, key) => {
            this.pmql += '(';
            this.pmql += string;
            this.pmql += ')';
            if (key < clauses.length - 1) this.pmql += ' AND ';
          });
      },
      getInitials(firstname, lastname) {
          let initials = "";
          if (firstname) {
              initials += firstname.match(/./u)[0];
          }
          if (lastname) {
              initials += lastname.match(/./u)[0];
          }
          return initials || null;
      },
      allLoading(value) {
        this.isLoading.process = value;
        this.isLoading.status = value;
        this.isLoading.requester = value;
        this.isLoading.participants = value;
      },
      getAll() {
        switch (this.type) {
            case 'requests':
                this.getAllRequests();
                break;
            case 'tasks':
                this.getAllTasks();
                break;
        }
      },
      getAllRequests(){
        this.allLoading(true);
        ProcessMaker.apiClient
            .get("/requests/search?type=all", { baseURL: '' })
            .then(response => {
                this.processOptions = response.data.process;
                this.statusOptions = response.data.status;
                this.requesterOptions = response.data.requester;
                this.participantsOptions = response.data.participants;
                this.allLoading(false);
            });
      },
      getAllTasks() {
        this.allLoading(true);
        ProcessMaker.apiClient
          .get("/tasks/search?type=task_all", { baseURL: '' })
          .then(response => {
            this.requestOptions = response.data.request;
            this.statusOptions = response.data.status;
            this.nameOptions = response.data.name;
            this.allLoading(false);
            setTimeout(3000)
          });
      },
      getStatus() {
        this.isLoading.status = true;
        ProcessMaker.apiClient
            .get("/requests/search?type=status", { baseURL: '' })
            .then(response => {
                this.statusOptions = response.data;
                this.isLoading.status = false
                setTimeout(3000)
            });
      },
      getProcesses(query) {
          this.isLoading.process = true
          ProcessMaker.apiClient
              .get("/requests/search?type=process&filter=" + query, { baseURL: '' })
              .then(response => {
                  this.processOptions = response.data;
                  this.isLoading.process = false
                  setTimeout(3000)
              });
      },
      getRequesters(query) {
          this.isLoading.requester = true
          ProcessMaker.apiClient
              .get("/requests/search?type=requester&filter=" + query, { baseURL: '' })
              .then(response => {
                  this.requesterOptions = response.data;
                  this.isLoading.requester = false
                  setTimeout(3000)
              });
      },
      getParticipants(query) {
          this.isLoading.participants = true
          ProcessMaker.apiClient
              .get("/requests/search?type=participants&filter=" + query, { baseURL: '' })
              .then(response => {
                  this.participantsOptions = response.data;
                  this.isLoading.participants = false
                  setTimeout(3000)
              });
      },
      getTaskStatus() {
        this.isLoading.status = true;
        ProcessMaker.apiClient
          .get("/tasks/search?type=task_status", { baseURL: '' })
          .then(response => {
            this.statusOptions = response.data;
            this.isLoading.status = false
            setTimeout(3000)
          });
      },
      getRequests(query) {
        this.isLoading.request = true
        ProcessMaker.apiClient
          .get("/tasks/search?type=request&filter=" + query, { baseURL: '' })
          .then(response => {
            this.requestOptions = response.data;
            this.isLoading.request = false
            setTimeout(3000)
          });
      },
      getNames(query) {
        this.isLoading.name = true
        ProcessMaker.apiClient
          .get("/tasks/search?type=name&filter=" + query, { baseURL: '' })
          .then(response => {
            this.nameOptions = response.data;
            this.isLoading.name = false
            setTimeout(3000)
          });
      }
  },
  created() {
    if (this.paramProcess && Array.isArray(this.paramProcess)) {
        this.process = this.paramProcess;
    }
    
    if (this.paramStatus && Array.isArray(this.paramStatus)) {
        this.status = this.paramStatus;
    }
    
    if (this.paramRequester && Array.isArray(this.paramRequester)) {
        this.requester = this.paramRequester;
    }
    
    if (this.paramParticipants && Array.isArray(this.paramParticipants)) {
        this.participants = this.paramParticipants;
    }
    
    this.buildPmql();
    this.getAll();
  }
};
</script>

<style lang="scss">
    .advanced-search {
        .multiselect__placeholder {
            padding-top: 1px;
        }
        
        .multiselect,
        .multiselect__input,
        .multiselect__single {
            font-size: 14px;
        }
        
        .multiselect__input {
            left: 1px;
            padding: 2px 0 2px 10px;
            position: absolute;
            top: 8px;
        }
        
        .multiselect--active {
            .multiselect__input {
                width: 99% !important;
            }
        }

        .multiselect__single {
            padding-bottom: 2px;
            padding-top: 2px;
        }

        .group {
            position: relative;
            background-color: #ffffff;
            color: #b6bfc6;
            border-radius: 2px;
        }

        input.search-input {
            background: none;
            color: gray;
            padding: 0.375rem 0.75rem;
            padding-top: 1.2rem;
            display: block;
            width: 100%;
            border: none;
            border-radius: 3px 0 0 3px;
            border: 1px solid rgba(0, 0, 0, 0.125);
            border-right: none;
            height: 3.5rem;
        }

        input.search-input:focus {
            outline: none;
        }

        input.search-input:focus ~ label, input.search-input:valid ~ label {
            top: 0px;
            font-size: 12px;
            color: #0872C2;
        }

        .float-label {
            color: #c6c6c6;
            font-size: 16px;
            font-weight: normal;
            position: absolute;
            pointer-events: none;
            padding: 0.3rem 0.75rem;
            top: 10px;
            transition: 300ms ease all;
        }
    }
</style>
