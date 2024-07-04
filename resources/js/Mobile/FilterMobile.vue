<template>
  <b-container class="bv-example-row">
    <div class="d-flex justify-content-between">
      <div
        v-if="showDropdowns"
        class="dropdown"
      >
        <button
          id="statusDropdown"
          class="btn btn-secondary dropdown-toggle dropdown-status-style"
          type="button"
          data-toggle="dropdown"
          aria-haspopup="true"
          aria-expanded="false"
        >
          <i :class="selectedIconStatus" />
          {{ selectedOptionStatus }}
          <i class="fas fa-caret-down status-dropdown" />
        </button>
        <div
          class="dropdown-menu mobile-dropdown-menu"
          aria-labelledby="statusDropdown"
        >
          <a
            class="dropdown-item"
            :class="{ 'dropdown-item-selected': selectedOptionStatus === 'In Progress' }"
            @click="selectOption('In Progress', 'status', '')"
          >
            {{ $t('In Progress') }}
          </a>
          <a
            class="dropdown-item"
            :class="{ 'dropdown-item-selected': selectedOptionStatus === 'Completed' }"
            @click="selectOption('Completed', 'status', '')"
          >
            {{ $t('Completed') }}
          </a>
        </div>
      </div>
      <div
        class="d-flex justify-content-between"
        :class="{ 'w-100': showInput }"
      >
        <div
          v-if="showDropdowns && type === 'requests'"
          class="dropdown"
        >
          <button
            id="requestsDropdown"
            class="btn dropdown-toggle dropdown-requests-style"
            type="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
          >
            <img
              src="/img/sort-down-icon.svg"
              alt="sort-down"
            >
          </button>
          <div
            class="dropdown-menu mobile-dropdown-menu"
            aria-labelledby="requestsDropdown"
          >
            <a
              class="dropdown-item"
              :class="{ 'dropdown-item-selected': selectedIconFilter === 'fas fa-user' }"
              @click="selectOption(`requester`, 'filter', 'fas fa-user')"
            >
              {{ $t('As Requester') }}
            </a>
            <a
              class="dropdown-item"
              :class="{ 'dropdown-item-selected': selectedIconFilter === 'fas fa-users' }"
              @click="selectOption(`participant`, 'filter', 'fas fa-users')"
            >
              {{ $t('As Participant') }}
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
        <div
          class="d-flex align-items-end ml-1"
          :class="{ 'w-100': showInput }"
        >
          <button
            class="btn"
            @click="toggleInput"
          >
            <img
              :src="getIconSrc"
              alt="search"
            >
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
      selectedIconStatus: "",
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
  computed: {
    getIconSrc() {
      return this.showInput ? "/img/arrow-left.svg" : "/img/search-icon.svg";
    },
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
    font-size: 16px;
    font-weight: 400;
    color: #556271;
    height: 48px;
  }
  .narrow-input {
    font-size: 12px;
    width: 100%;
    padding: 5px 5px;
    border: none;
  }
  .dropdown-status-style {
    background-color: white !important;
    color: #4C545C !important;
    border: none;
    text-transform: none;
    font-size: 15px;
    font-weight: 400;
  }
  .dropdown-status-style:focus {
    color: #0C8CE9 !important;
    box-shadow: none !important;
  }
  .status-dropdown {
    margin-left: 5px;
  }
  .dropdown-requests-style {
    align-items: center;
    padding: 8px 8px;
  }
  .dropdown-requests-style:focus {
    background-color: #E1EAF0;
  }
  .dropdown-item-selected {
    background-color: #EBEEF2;
  }
  .mobile-dropdown-menu {
    padding-top: 0;
    padding-bottom: 0;
  }
</style>
