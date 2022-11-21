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
                                     @select="processSelected"
                                     :show-labels="false"
                                     :loading="isLoading.process"
                                     open-direction="bottom"
                                     label="name"
                                     :options="processOptions"
                                     :track-by="'id'"
                                     :multiple="true"
                                     :placeholder="$t('Process')"
                                     :aria-label="this.process ? this.process.ariaLabel : $t('Process')">
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
                                     @select="statusSelected"
                                     :loading="isLoading.status"
                                     open-direction="bottom"
                                     label="name"
                                     :options="statusOptions"
                                     track-by="value"
                                     :multiple="true"
                                     :aria-label="this.status ? this.status.ariaLabel : $t('Status')"
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
                                     @select="requesterSelected"
                                     :show-labels="false"
                                     :loading="isLoading.requester"
                                     open-direction="bottom"
                                     label="fullname"
                                     :options="requesterOptions"
                                     :track-by="'id'"
                                     :multiple="true"
                                     :aria-label="$t('Requester') + ' ' + this.requester[0].fullname"
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
                                     @select="participantSelected"
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
                                     @select="requestSelected"
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
                                     @select="taskSelected"
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
                                     @select="statusOptionsSelected"
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
                <div class="search-bar-advanced d-flex w-100" v-if="advanced">
                    <div class="search-bar-inputs flex-grow w-100">
                        <input ref="search_input" type="text" class="search-bar-manual-input form-control" :placeholder="$t('Advanced Search (PMQL)')" :aria-label="$t('Advanced Search (PMQL)')" v-model="pmql" @keyup.enter="runSearch(true)">
                    </div>
                    <div class="search-bar-actions d-flex flex-shrink btn-search-advanced">
                        <b-btn class="btn-search-toggle" variant="success" @click="toggleAdvanced" v-b-tooltip.hover :title="$t('Basic Mode')"><i class="fas fa-ellipsis-h"></i></b-btn>
                        <b-btn class="btn-search-run" variant="primary" @click="runSearch(true)" v-b-tooltip.hover :title="$t('Search')"><i class="fas fa-search"></i></b-btn>
                        <div class="search-bar-additions">
                          <div v-for="addition in additions">
                            <component :is="addition" :permission="permission"></component>
                          </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</template>

<script>
export default {
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
      toggleAdvanced() {
        if (this.advanced) {
          this.advanced = false;
        } else {
          this.advanced = true;
          Vue.nextTick().then(() => {
            this.$refs.search_input.focus();
          });
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
          if (firstname) {
            return firstname.match(/./u)[0] + lastname.match(/./u)[0]
          } else {
            return null;
          }
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
      },
      processSelected(option) {
        if (this.process) {
          let name = document.getElementById('process_name_filter');
          name.ariaLabel = 'Selected' + ' ' + option.name;
        }
      },
      statusSelected(option) {      
        if (this.status) {
          let status = document.getElementById('process_status_filter');
          status.ariaLabel = 'Selected' + ' ' + option.name;
        }
      },
      requesterSelected(option) {
        if (this.requester) {
          let requester = document.getElementById('process_requester_filter');
          requester.ariaLabel = 'Selected' + ' ' + option.fullname;
        }
      },
      participantSelected(option) {
        if (this.participants) {
          let participants = document.getElementById('process_participant_filter');
          participants.ariaLabel = 'Selected' + ' ' + option.fullname;
        }
      },
      requestSelected(option) {
        if (this.request) {
          let request = document.getElementById('process_request_filter');
          request.ariaLabel = 'Selected' + ' ' + option.name;
        }
      },
      taskSelected(option) {
        if (this.name) {
          let name = document.getElementById('process_task_filter');
          name.ariaLabel = 'Selected' + ' ' + option.name;
        }
      },
      statusOptionsSelected(option) {
        if (this.status) {
          let status = document.getElementById('process_status_options_filter');
          status.ariaLabel = 'Selected' + ' ' + option.name;
        }
      },
  },
  created() {
    if (this.paramProcess && Array.isArray(this.paramProcess)) {
        this.process = this.paramProcess;
        this.process.ariaLabel = 'Selected' + ' ' + this.process[0].name;
    }
    
    if (this.paramStatus && Array.isArray(this.paramStatus)) {
        this.status = this.paramStatus;
        this.status.ariaLabel = 'Selected' + ' ' + this.status[0].name;
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
    }
</style>
