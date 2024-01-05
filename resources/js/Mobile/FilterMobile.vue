<template>
  <b-container class="bv-example-row">
    <div class="d-flex justify-content-between">
      <div>
        <div
          v-if="showDropdowns"
          class="dropdown"
        >
          <button
            id="statusDropdown"
            class="btn btn-secondary dropdown-toggle dropdown-style"
            type="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
          >
            <i :class="selectedIconStatus" />
            {{ selectedOptionStatus }}
            <i class="fas fa-caret-down" />
          </button>
          <div
            class="dropdown-menu"
            aria-labelledby="statusDropdown"
          >
            <a
              class="dropdown-item"
              @click="selectOption('In Progress', 'status', 'fas fa-circle text-warning')"
            >
              <i class="fas fa-circle text-warning" />
              {{ $t('In Progress') }}
            </a>
            <a
              class="dropdown-item"
              @click="selectOption('Completed', 'status', 'fas fa-circle text-primary')"
            >
              <i class="fas fa-circle text-primary" />
              {{ $t('Completed') }}
            </a>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-between">
        <div
          v-if="showDropdowns && type === 'requests'"
          class="dropdown"
        >
          <button
            id="requestsDropdown"
            class="btn btn-secondary dropdown-toggle"
            type="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
          >
            <i :class="selectedIconFilter" />
            <i class="fas fa-caret-down" />
          </button>
          <div
            class="dropdown-menu"
            aria-labelledby="requestsDropdown"
          >
            <a
              class="dropdown-item"
              @click="selectOption(`requester`, 'filter', 'fas fa-user')"
            >
              <i class="fas fa-user" />
              {{ $t('As Requester') }}
              <i
                v-if="selectedIconFilter=== 'fas fa-user'"
                class="fas fa-check ml-auto text-success"
              />
            </a>
            <a
              class="dropdown-item"
              @click="selectOption(`participant`, 'filter', 'fas fa-users')"
            >
              <i class="fas fa-users" />
              {{ $t('As Participant') }}
              <i
                v-if="selectedIconFilter === 'fas fa-users'"
                class="fas fa-check ml-auto text-success"
              />
            </a>
          </div>
        </div>
        <div
          v-if="showDropdowns && type === 'tasks'"
          class="dropdown"
        >
          <button
            id="tasksDropdown"
            class="btn btn-secondary dropdown-toggle"
            type="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
          >
            <i class="fas fa-list" />
            <i class="fas fa-caret-down" />
          </button>
          <div
            class="dropdown-menu"
            aria-labelledby="tasksDropdown"
          >
            <a
              class="dropdown-item"
              @click="selectOption('due_at', 'orderBy')"
            >
              {{ $t('By Due Date') }}
            </a>
            <a
              class="dropdown-item"
              @click="selectOption('created_at', 'orderBy')"
            >
              {{ $t('By Creation Date') }}
            </a>
          </div>
        </div>
        <div class="d-flex align-items-end ml-1">
          <button
            class="btn btn-primary"
            @click="toggleInput"
          >
            <i class="fas fa-search" />
          </button>
          <input
            v-if="showInput"
            ref="input"
            v-model="searchCriteria"
            type="text"
            class="form-control narrow-input"
            placeholder="(fulltext LIKE '%someText%')"
            @keyup.enter="performSearch"
          >
          <button
            v-if="showInput"
            class="btn btn-clear"
            @click="clearSearch"
          >
            <i class="fas fa-times" />
          </button>
        </div>
      </div>
    </div>
  </b-container>
</template>
<script>
export default {
  props: {
    type: String,
  },
  data() {
    return {
      searchCriteria: "",
      selectedOptionStatus: "In Progress",
      selectedIconStatus: "fas fa-circle text-warning",
      selectedIconFilter: "fas fa-user",
      apiData: [],
      showInput: false,
      showDropdowns: true,
      pmql: "",
      status: "",
      statusChange: false,
      searchText: "",
      filter: "",
    };
  },
  methods: {
    /**
     * This boolean method shows or hide elements
     */
    toggleInput() {
      this.showInput = !this.showInput;
      this.showDropdowns = !this.showInput;
    },
    /**
     * This method receives parameters from dropdown controls options selected by user
     */
    selectOption(option, controlName, icon) {
      this.callApiFilter(this.buildApiPath(option, controlName, icon));
    },
    buildPmql() {
      this.pmql = "";
      this.pmql += this.status;
      if (this.searchText !== "") {
        this.pmql += this.searchText;
      }
      if (this.filter !== "") {
        this.pmql += this.filter;
      }
      return `pmql=${this.pmql}`;
    },
    /**
     * This method builds a specific url api string depending of filter used by user
     */
    buildApiPath(option, controlName, icon) {
      if (controlName === "status") {
        this.selectedOptionStatus = option;
        this.selectedIconStatus = icon;
        this.status = `AND (status = "${option}")`;
        this.statusChange = true;
        return this.buildPmql();
      }
      if (controlName === "filter") {
        this.selectedIconFilter = icon;
        this.filter = `AND (${option} = "${Processmaker.user.username}")`;
        return this.buildPmql();
      }
      if (controlName === "orderBy") {
        return `order_by=${option}`;
      }
      if (controlName === "search") {
        this.searchText = `AND (fulltext LIKE "%${option}%")`;
        return this.buildPmql();
      }
      return "";
    },
    /**
     * This is a generic method to call API with previous builded apiPath
     * related to Filters selected by user
     */
    callApiFilter(payload) {
      if (this.type === "tasks") {
        if (payload.startsWith("pmql")) {
          this.$parent.$refs.taskMobileList.updatePmql(payload.substr(5));
        }
        if (payload.startsWith("order_by")) {
          this.$parent.$refs.taskMobileList.updateOrder(payload.substr(9));
        }
        this.$parent.$refs.taskMobileList.fetch(true);
      }
      if (this.type === "requests") {
        if (payload.startsWith("pmql")) {
          this.$parent.$refs.requestsMobileList.updatePmql(payload.substr(5), this.statusChange);
        }
        this.$parent.$refs.requestsMobileList.fetch(true);
      }
    },
    /**
     * This method sends users's input criteria to filter specific tasks or requests
     */
    performSearch() {
      this.callApiFilter(this.buildApiPath(this.searchCriteria, "search"));
    },
    clearSearch() {
      this.searchCriteria = "";
      this.performSearch();
      this.toggleInput();
    },
  },
};
</script>
<style>
  .has-search .form-control {
    padding-left: 2.375rem;
  }
  .has-search .form-control-feedback {
    position: absolute;
    z-index: 2;
    display: block;
    width: 2.375rem;
    height: 2.375rem;
    line-height: 2.375rem;
    text-align: center;
    pointer-events: none;
    color: #aaa;
  }
  .expanded .dropdown {
    display: none;
  }
  .hidden-input {
    display: none;
  }
  .dropdown-item {
    display: flex;
    align-items: center;
  }
  .dropdown-item .fas.fa-check {
    margin-left: auto;
    color: black;
  }
  .btn-clear {
    background: transparent;
    border: none;
    outline: none;
    cursor: pointer;
    padding: 8px 4px;
    margin-left: 5px;
    color: #888;
    }

  .dropdown-toggle {
    font-size: 12px;
    padding: 5px 10px;
  }
  .dropdown-item {
    font-size: 12px;
  }
  .narrow-input {
    font-size: 12px;
    width: 100%;
    padding: 5px 60px;
  }
  .dropdown-style {
    background-color: white !important;
    color: black !important;
  }

</style>
