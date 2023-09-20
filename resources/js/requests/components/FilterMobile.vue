<template>
  <b-container class="bv-example-row">
    <b-row align-h="between">
      <b-col cols="8">
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
            <i :class="selectedIconStatus"/>
            {{ selectedOptionStatus }}
            <i class="fas fa-caret-down"/>
          </button>
          <div
            class="dropdown-menu"
            aria-labelledby="statusDropdown"
          >
            <a
              class="dropdown-item"
              href="#"
              @click="selectOption('In Progress', 'status', 'fas fa-circle text-warning')"
            >
              <i class="fas fa-circle text-warning"/>
              {{ $t('In Progress') }}
            </a>
            <a
              class="dropdown-item"
              href="#"
              @click="selectOption('Completed', 'status', 'fas fa-circle text-primary')"
            >
              <i class="fas fa-circle text-primary"/>
              {{ $t('Completed') }}
            </a>
          </div>
        </div>
      </b-col>
      <b-col cols="1" class="d-flex">
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
            <i :class="selectedIconFilter"/>
            <i class="fas fa-caret-down"/>
          </button>
          <div
            class="dropdown-menu"
            aria-labelledby="requestsDropdown"
          >
            <a
              class="dropdown-item"
              href="#"
              @click="selectOption(`(requester = 'admin')`, 'filter', 'fas fa-user')"
            >
              <i class="fas fa-user"/>
              {{ $t('Requested by Me') }}
              <i v-if="selectedIconFilter=== 'fas fa-user'" class="fas fa-check ml-auto text-success"/>
            </a>
            <a
              class="dropdown-item"
              href="#"
              @click="selectOption(`(participant = 'admin')`, 'filter', 'fas fa-users')"
            >
              <i class="fas fa-users"/>
              {{ $t('With me as Participant') }}
              <i v-if="selectedIconFilter === 'fas fa-users'" class="fas fa-check ml-auto text-success"/>
            </a>
          </div>
        </div>
        <div
          class="dropdown"
          v-if="showDropdowns && type === 'tasks'"
        >
          <button
            id="tasksDropdown"
            class="btn btn-secondary dropdown-toggle"
            type="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
          >
            <i class="fas fa-list"/>
            <i class="fas fa-caret-down"/>
          </button>
          <div
            class="dropdown-menu"
            aria-labelledby="tasksDropdown"
          >
            <a
              class="dropdown-item"
              href="#"
              @click="selectOption('By Due Date', 'orderBy')"
            >
              {{ $t('By Due Date') }}
            </a>
            <a
              class="dropdown-item"
              href="#"
              @click="selectOption('By Creation Date', 'orderBy')"
            >
              {{ $t('By Creation Date') }}
            </a>
          </div>
        </div>
      </b-col>
      <div class="d-flex align-items-end">
        <button
          class="btn btn-primary ml-2"
          @click="toggleInput"
        >
          <i class="fas fa-search"/>
        </button>
        <input
          v-if="showInput"
          ref="input"
          type="text"
          class="form-control narrow-input"
          v-model="searchCriteria"
          placeholder="(fulltext LIKE '%someText%')"
          @keyup.enter="performSearch"
        />
        <button
          v-if="showInput"
          class="btn btn-clear"
          @click="clearSearch"
        >
        <i class="fas fa-times"/>
    </button>
      </div>
    </b-row>
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
    /** 
     * This method builds a specific url api string depending of filter used by user
     */
    buildApiPath(option, controlName, icon) {
      let basePath = this.type + "?page=1&per_page=10&include=process,participants,data&";
      if (controlName === "status") {
        this.selectedOptionStatus = option;
        this.selectedIconStatus = icon;
        return basePath + 'pmql=(status = "' + option + '")';
      }
      if (controlName === "filter") {
        this.selectedIconFilter = icon;
        return basePath + 'filter = "' + option + '"';
      }
      if (controlName === "orderBy") {
        return basePath + 'order_by = "' + option + '"';
      }
      if (controlName === "search") {
        return basePath + 'pmql=(fulltext LIKE "%' + option + '%")';
      }
    },
    /** 
     * This is a generic method to call API with previous builded apiPath 
     * related to Filters selected by user
     */
    callApiFilter(apiPath) {
      ProcessMaker.apiClient
        .get(apiPath)
        .then((response) => {
          this.apiData = response.data;
        })
        .catch((error) => {
          console.error("Error calling API:", error);
        });
    },
    /** 
     * This method sends users's input criteria to filter specific tasks or requests 
     */ 
    performSearch() {
      this.callApiFilter(this.buildApiPath(this.searchCriteria, "search"))
    },
    clearSearch() {
      this.searchCriteria = '';
      this.toggleInput();
    }
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
@media (max-width: 767px) {
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
}
</style>
