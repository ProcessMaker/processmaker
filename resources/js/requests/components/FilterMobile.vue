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
              class="btn btn-secondary dropdown-toggle"
              type="button"
              id="dropdownMenuButton"
              data-toggle="dropdown"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <i class="fas fa-circle text-warning"></i>
              {{ selectedOption }}
              <i class="fas fa-caret-down"></i>
            </button>
            <div
              class="dropdown-menu"
              aria-labelledby="dropdownMenuButton"
            >
              <a
                class="dropdown-item"
                href="#"
                @click="selectOption('In Progress', 'status')"
                ><i class="fas fa-circle text-warning"></i> In Progress</a
              >
              <a
                class="dropdown-item"
                href="#"
                @click="selectOption('Completed', 'status')"
                ><i class="fas fa-circle text-primary"></i> Completed</a
              >
            </div>
          </div>

          <div
            class="dropdown"
            v-if="type === 'requests'"
          >
            <button
              class="btn btn-secondary dropdown-toggle"
              type="button"
              id="dropdownMenuButton"
              data-toggle="dropdown"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <i class="fas fa-user"></i>
              <i class="fas fa-caret-down"></i>
            </button>
            <div
              class="dropdown-menu"
              aria-labelledby="dropdownMenuButton"
            >
              <a
                class="dropdown-item"
                href="#"
                @click="selectOption('Requested by Me', 'filter')"
                ><i class="fas fa-user"></i> Requested by Me</a
              >
              <a
                class="dropdown-item"
                href="#"
                @click="selectOption('With me as Participant', 'filter')"
                ><i class="fas fa-users"></i> With me as Participant</a
              >
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
              <i class="fas fa-list"></i>
              <i class="fas fa-caret-down"></i>
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
                By Due Date</a
              >
              <a
                class="dropdown-item"
                href="#"
                @click="selectOption('By Creation Date', 'orderBy')"
              >
                By Creation Date</a
              >
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
                  <i class="fas fa-search"></i>
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
      let apiPath = buildApiPath(option, controlName);
      callApiFilter(apiPath);
    },
    buildapiPath(option, controlName) {
      let basePath =
        this.type + "?page=1&per_page=10&include=process,participants,data&";
      if (controlName === "status") {
        return basePath + 'pmql=(status = "' + option + '")';
      } else if (controlName === "filter") {
        return basePath + 'filter = "' + option + '"';
      } else if (controlName === "orderBy") {
        return basePath + 'order_by = "' + option + '"';
      } else if (controlName === "search") {
        return basePath + 'pmql=(fulltext LIKE "%' + option + '%")';
      }
    },
    callApiFilter(apiPath) {
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
      let apiPath = buildApiPath(this.searchCriteria, "search");
      callApiFilter(apiPath);
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
