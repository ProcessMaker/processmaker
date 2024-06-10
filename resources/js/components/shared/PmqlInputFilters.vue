<template>
  <div>
    <div class="position-relative">
      <div v-if="showFilterPopup" class="filter-dropdown-panel-container card" v-click-outside="closeFiltersPopup">
        <div v-if="type == 'requests'" class="card-body">
          <label for="process_name_filter">{{$t('Process')}}</label>
          <multiselect id="process_name_filter" v-model="process"
            class="mb-3"
            @search-change="getProcesses"
            @input="buildPmql"
            :show-labels="true"
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
        <label for="process_status_filter">{{$t('Status')}}</label>
        <multiselect id="process_status_filter" v-model="status"
          class="mb-3"
          :show-labels="true"
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
        <label for="process_requester_filter">{{$t('Requester')}}</label>
        <multiselect id="process_requester_filter"
          v-model="requester"
          @search-change="getRequesters"
          @input="buildPmql"
          class="mb-3"
          :show-labels="true"
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
                <img v-if="props.option.avatar && props.option.avatar.length > 0" class="option__image"
                    :src="props.option.avatar">
                <span v-else class="initials bg-warning text-white p-1"> {{getInitials(props.option.firstname, props.option.lastname)}}</span>
                <span class="ml-1">{{props.option.fullname}}</span>
            </template>
          </multiselect>
        <label for="process_participant_filter">{{$t('Participants')}}</label>
        <multiselect id="process_participant_filter" v-model="participants"
          @search-change="getParticipants"
          @input="buildPmql"
          class="mb-3"
          :show-labels="true"
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
                <img v-if="props.option.avatar && props.option.avatar.length > 0" class="option__image"
                    :src="props.option.avatar">
                <span v-else class="initials bg-warning text-white p-1"> {{getInitials(props.option.firstname, props.option.lastname)}}</span>
                <span class="ml-1">{{props.option.fullname}}</span>
            </template>
          </multiselect>
        </div>
        <div v-if="type == 'tasks'" class="card-body">
          <label for="process_request_filter">{{$t('Request')}}</label>
          <multiselect id="process_request_filter"
            v-model="request"
            @search-change="getRequests"
            @input="buildPmql"
            class="mb-3"
            :show-labels="true"
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
          <label for="process_task_filter">{{$t('Task')}}</label>
          <multiselect id="process_task_filter"
            v-model="name"
            @search-change="getNames"
            @input="buildPmql"
            class="mb-3"
            :show-labels="true"
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
          <label for="process_status_options_filter">{{$t('Status')}}</label>
          <multiselect id="process_status_options_filter"
            v-model="status"
            class="mb-3"
            :show-labels="true"
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
        <div v-if="type == 'projects'" class="card-body">
          <label for="project_title_filter">{{$t('Title')}}</label>
          <multiselect id="project_title_filter"
            v-model="projects"
            @search-change="getProjects"
            @input="buildPmql"
            class="mb-3"
            :show-labels="true"
            :loading="isLoading.projects"
            open-direction="bottom"
            label="title"
            :options="projectOptions"
            :track-by="'id'"
            :multiple="true"
            :aria-label="$t('Project')"
            :placeholder="$t('Project')">
              <template slot="noResult">
                  {{ $t('No Results') }}
              </template>
              <template slot="noOptions">
                  {{ $t('No Data Available') }}
              </template>
              <template slot="selection" slot-scope="{ values, search, isOpen }">
                  <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('projects') }}</span>
              </template>
            </multiselect>
          
          <label for="project_member_filter">{{$t('Members')}}</label>
            <multiselect id="project_member_filter"
                v-model="members"
                @input="buildPmql"
                class="mb-3"
                :loading="isLoading.projects"
                open-direction="bottom"
                group-values="items"
                group-label="type"
                label="name"
                :options="memberOptions"
                track-by="id"
                :multiple="true"
                :show-labels="false"
                :internal-search="true"
                >
                :aria-label="$t('Member')"
                :placeholder="$t('Member')">
                  <template slot="noResult">
                      {{ $t('No Results') }}
                  </template>
                  <template slot="noOptions">
                      {{ $t('No Data Available') }}
                  </template>
                  <template slot="selection" slot-scope="{ values, search, isOpen }">
                      <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('members') }}</span>
                  </template>
              </multiselect>

            <label for="project_category_filter">{{$t('Category')}}</label>
            <multiselect id="project_category_filter"
                v-model="categories"
                @input="buildPmql"
                class="mb-3"
                :show-labels="true"
                :loading="isLoading.projects"
                open-direction="bottom"
                label="name"
                :options="categoriesOptions"
                :track-by="'id'"
                :multiple="true"
                :aria-label="$t('Category')"
                :placeholder="$t('Category')">
                  <template slot="noResult">
                      {{ $t('No Results') }}
                  </template>
                  <template slot="noOptions">
                      {{ $t('No Data Available') }}
                  </template>
                  <template slot="selection" slot-scope="{ values, search, isOpen }">
                      <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('categories') }}</span>
                  </template>
            </multiselect>
        </div>
        <div class="card-footer bg-white text-right">
          <button class="btn btn-secondary-outline btn-sm" @click="resetFilters">Reset</button>
          <button class="btn btn-primary btn-sm" @click="applyFilters">Apply</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import advancedFilterStatusMixin from "../../common/advancedFilterStatusMixin";

let myEvent;
export default {
  directives: {
    clickOutside: {
      bind(el, binding, vnode) {
        myEvent = function (event) {
          if (!(el === event.target || el.contains(event.target) || vnode.context.$refs.filterButton.contains(event.target))) {
            vnode.context[binding.expression](event);
          }
        };
        document.body.addEventListener("click", myEvent);
      },
      unbind() {
        document.body.removeEventListener("click", myEvent);
      },
    },
  },
  mixins: [
    advancedFilterStatusMixin,
  ],
  props: [
    "type",
    "paramProcess",
    "paramStatus",
    "paramRequester",
    "paramParticipants",
    "paramRequest",
    "paramName",
    "paramProjects",
    'paramProjectMembers',
    'paramProjectCategories',
    "permission",
  ],
  data() {
    return {
      pmql: "",
      showFilterPopup: false,
      process: [],
      status: [],
      requester: [],
      request: [],
      name: [],
      participants: [],
      members: [],
      categories: [],
      categoriesOptions: [],
      memberOptions: [],
      processOptions: [],
      statusOptions: [],
      requesterOptions: [],
      participantsOptions: [],
      requestOptions: [],
      nameOptions: [],
      projectOptions: [],
      selectedFilters: [],
      projects: [],
      isLoading: {
        process: false,
        requester: false,
        status: false,
        participants: false,
        request: false,
        name: false,
        projects: false,
      },
    };
  },
  watch: {
    pmql(query) {
      this.$emit("filterspmqlchange", [query, this.getSelectedFilters()]);
    },
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

    if (this.paramProjects && Array.isArray(this.paramProjects)) {
      this.projects = this.paramProjects;
    }

    if (this.paramProjectCategories && Array.isArray(this.paramProjectCategories)) {
      this.projectCategories = this.paramProjectCategories;
    }

    if (this.paramProjectMembers && Array.isArray(this.paramProjectMembers)) {
      this.projectMembers = this.paramProjectMembers;
    }
    
    this.buildPmql();
    this.getAll();
  },

  mounted() {
    ProcessMaker.EventBus.$on("removefilter", (filter) => {
      this.removeFilter(filter);
    });
  },

  methods: {
    closeFiltersPopup() {
      this.showFilterPopup = false;
    },
    toggleFiltersPopup() {
      this.showFilterPopup = !this.showFilterPopup;
    },

    applyFilters() {
      this.buildPmql();
      this.showFilterPopup = false;
    },

    removeFilter(filter) {
      if (filter[0] === "process") {
        this.process = [];
      }

      if (filter[0] === "status") {
        this.status = [];
      }

      if (filter[0] === "requester") {
        this.requester = [];
      }

      if (filter[0] === "participants") {
        this.participants = [];
      }

      if (filter[0] === "request") {
        this.request = [];
      }

      if (filter[0] === "name") {
        this.name = [];
      }

      this.buildPmql();
    },

    resetFilters() {
      this.process = [];
      this.status = [];
      this.requester = [];
      this.participants = [];
      this.request = [];
      this.name = [];
      this.projects = [];
      this.members = [];
      this.categories = [];
      this.pmql = "";

      if (this.type === "tasks") {
        const isSelfService = this.status.find((status) => status.value === "Self Service");
        // Add default filter by user id
        if (!isSelfService) {
          const userId = parseInt(window.ProcessMaker.user.id);
          this.pmql = `(user_id = ${userId})`;
        }
      }
      this.$emit('filterspmqlchange', [this.pmql, this.getSelectedFilters()]);
      this.showFilterPopup = false;
    },

    getSelectedFilters() {
      this.selectedFilters = [];

      if (this.process.length) {
        this.selectedFilters.push(["process", this.process]);
      }

      if (this.status.length) {
        this.selectedFilters.push(["status", this.status]);
      }

      if (this.requester.length) {
        this.selectedFilters.push(["requester", this.requester]);
      }

      if (this.participants.length) {
        this.selectedFilters.push(["participants", this.participants]);
      }

      if (this.request.length) {
        this.selectedFilters.push(["request", this.request]);
      }

      if (this.name.length) {
        this.selectedFilters.push(["name", this.name]);
      }

      return this.selectedFilters;
    },

    buildPmql() {
      switch (this.type) {
        case 'requests':
          this.buildRequestPmql();
          break;
        case 'tasks':
          this.buildTaskPmql(this.advancedFilter);
          break;
        case 'projects':
          this.buildProjectPmql();
          break;
      }
    },
    buildProjectPmql() {
      let clauses = [];
      //Parse projects
      if (this.projects.length) {
        let string = '';
        this.projects.forEach((project, key) => {
          string += 'title = "' + project.title + '"';
          if (key < this.projects.length - 1) string += ' OR ';
        });
        clauses.push(string);
      }

      if (this.members.length) {
        let string = '';
        this.members.forEach((member, key) => {
          string += 'participant = "' + member.name + '"';
          if (key < this.members.length - 1) string += ' OR ';
        });
        clauses.push(string);
      }

      if (this.categories.length) {
        let string = '';
        this.categories.forEach((category, key) => {
          string += 'category = "' + category.name + '"';
          if (key < this.categories.length - 1) string += ' OR ';
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
    buildTaskPmql(advancedFilter = null) {
        let clauses = [];
        const isSelfService = this.status.find(status => status.value === 'Self Service');

        let selfServiceFilterFound = advancedFilter.some(filter => filter.subject?.type === 'Status' && filter.value === 'Self Service');

        if (!selfServiceFilterFound && !isSelfService) {
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
      this.isLoading.projects = value;
    },
    getAll() {
      switch (this.type) {
          case 'requests':
              this.getAllRequests();
              break;
          case 'tasks':
              this.getAllTasks();
              break;
          case 'projects':
              this.getAllProjects();
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
    async getAllProjects() {
      try {
        this.allLoading(true);

        const { data } = await ProcessMaker.apiClient.get("/projects/search?type=project_all");
        
        this.projectOptions = data.projects ? data.projects : [];
        
        if (data.members?.users) {
          const usersWithMappedNames = data.members.users
            .filter(user => !!user)
            .map(({fullname, ...user}) => ({...user, name: fullname }));
          
          this.memberOptions.push({
            type: this.$t('Users'),
            items: usersWithMappedNames
          });
        }

        if (data.members?.groups) {
          const groups = data.members.groups.map(({name, ...group}) => ({...group, name: name }));
          this.memberOptions.push({
            type: this.$t('Groups'),
            items: groups,
          });
        }

        // Extract categories
        this.categoriesOptions = data.categories;

        this.allLoading(false);
      } catch (error) {
        console.error("Error:", error);
        this.allLoading(false);
      }
    },
    addUsernameToFullName(user) {
      if (!user.fullname || ! user.username)
      {
        return user;
      }
      return {...user, fullname: `${user.fullname}`};
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
    getProjects(query) {
      this.isLoading.projects = true;     
      ProcessMaker.apiClient
        .get("/projects/search?type=projects&filter=" + query)
        .then(response => {
            this.projectOptions = response.data;
            this.isLoading.projects = false
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
}

.filter-dropdown-panel-container {
  min-width: 30rem;
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.125);
  box-shadow: 0 6px 12px 2px rgba(0, 0, 0, 0.168627451);
  position: absolute;
  left: 0;
  top: 2.5rem;
  border-radius: 3px;
  z-index: 1;
  max-width: 40rem;
}
</style>
