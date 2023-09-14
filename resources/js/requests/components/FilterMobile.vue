<template>
  <div class="row">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
      <div>
        <b-nav pills>
          <div
            class="dropdown"
            left
          >
            <button
              id="dropdownMenuButton"
              class="btn btn-secondary dropdown-toggle"
              type="button"
              data-toggle="dropdown"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <i class="fas fa-circle text-warning"/>
              {{ selectedOption }}
              <i class="fas fa-caret-down"/>
            </button>
            <div
              class="dropdown-menu"
              aria-labelledby="dropdownMenuButton"
            >
              <a
                class="dropdown-item"
                href="#"
                @click="selectOption('In Progress', 'status')"
                ><i class="fas fa-circle text-warning"/> {{ $t('In Progress') }}
              </a>
              <a
                class="dropdown-item"
                href="#"
                @click="selectOption('Completed', 'status')"
                ><i class="fas fa-circle text-primary"/> {{ $t('Completed') }}
              </a>
            </div>
          </div>

          <div
            v-if="type === 'requests'"
            class="dropdown"
          >
            <button
              class="btn btn-secondary dropdown-toggle"
              type="button"
              id="dropdownMenuButton"
              data-toggle="dropdown"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <i class="fas fa-user"/>
              <i class="fas fa-caret-down"/>
            </button>
            <div
              class="dropdown-menu"
              aria-labelledby="dropdownMenuButton"
            >
              <a
                class="dropdown-item"
                href="#"
                @click="selectOption(`(requester = 'admin')`, 'filter')"
                ><i class="fas fa-user"/> {{ $t('Requested by Me') }}
              </a>
              <a
                class="dropdown-item"
                href="#"
                @click="selectOption(`(participant = 'admin')`, 'filter')"
                ><i class="fas fa-users"/> {{ $t('With me as Participant') }}
              </a>
            </div>
          </div>

          <div
            class="dropdown"
            v-if="type === 'tasks'"
          >
            <button
              class="btn btn-secondary dropdown-toggle"
              type="button"
              id="dropdownMenuButton"
              data-toggle="dropdown"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <i class="fas fa-list"/>
              <i class="fas fa-caret-down"/>
            </button>
            <div
              class="dropdown-menu"
              aria-labelledby="dropdownMenuButton"
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

          <div>
            <div class="input-group">
              <input
                type="text"
                class="form-control narrow-input"
                v-model="searchCriteria"
                placeholder="(fulltext LIKE '%someText%')"
              />
              <div class="input-group-append">
                <button
                  class="btn btn-primary"
                  @click="performSearch"
                >
                  <i class="fas fa-search"/>
                </button>
              </div>
            </div>
          </div>
        </b-nav>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    type: String,
  },
  data() {
    return {
      searchCriteria: "",
      selectedOption: "In Progress",
      apiData: [],
      items: [],
      orderBy: "id",
      orderDirection: "DESC",
      additionalParams: "",
      sortOrder: [
        {
          field: "id",
          sortField: "id",
          direction: "desc",
        },
      ],
      fields: [],
      previousFilter: "",
      previousPmql: "",
    };
  },
  methods: {
    selectOption(option, controlName) {
      /* This metod receives parameters from dropdown controls 
      options selected by user*/
      let apiPath = buildApiPath(option, controlName);
      callApiFilter(apiPath);
    },
    buildapiPath(option, controlName) {
      /*This method builds a specific url api string depending 
      of filter used by user*/
      let basePath = this.type + "?page=1&per_page=10&include=process,participants,data&";
      if (controlName === "status") {
        return basePath + 'pmql=(status = "' + option + '")';
      } 
      if (controlName === "filter") {
        return basePath + 'filter = "' + option + '"';
      } 
      if (controlName === "orderBy") {
        return basePath + 'order_by = "' + option + '"';
      } 
      if (controlName === "search") {
        return basePath + 'pmql=(fulltext LIKE "%' + option + '%")';
      }
    },
    callApiFilter(apiPath) {
      /* This is a generic method to call API with previous builded apiPath 
      related to Filters selected by user
      */ 
      ProcessMaker.apiClient
        .get(apiPath, { baseURL: "" })
        .then((response) => {
          this.apiData = response.data;
        })
        .catch((error) => {
          console.error("Error calling API:", error);
        });
    },
    performSearch() {
      // This method sends users's input criteria to filter specific tasks or requests
      this.callApiFilter(this.buildApiPath(this.searchCriteria, "search"))
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

.narrow-input {
  width: 100px;
}
</style>
