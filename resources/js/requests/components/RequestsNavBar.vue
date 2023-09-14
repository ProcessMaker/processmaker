<template>
<div class="row">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
      <div>
        <b-nav pills>
          <div class="dropdown" left>
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-circle text-warning"></i>
              {{ selectedOption }}
              <i class="fas fa-caret-down"></i>
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              <!-- <a class="dropdown-item" href="#"><i class="fas fa-circle text-warning"></i> In Progress</a>
              <a class="dropdown-item" href="#"><i class="fas fa-circle text-primary"></i> Completed</a> -->
              <a class="dropdown-item" href="#" @click="selectOption('In Progress', 'status')"><i class="fas fa-circle text-warning"></i> In Progress</a>
              <a class="dropdown-item" href="#" @click="selectOption('Completed', 'status')"><i class="fas fa-circle text-primary"></i> Completed</a>
            </div>
          </div>


          <div class="dropdown" v-if="type === 'tab_request'">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-user"></i>
              <i class="fas fa-caret-down"></i>
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              <a class="dropdown-item" href="#" @click="selectOption('Requested by Me', 'filter')"><i class="fas fa-user"></i> Requested by Me</a>
              <a class="dropdown-item" href="#" @click="selectOption('With me as Participant', 'filter')"><i class="fas fa-users"></i> With me as Participant</a>
            </div>
          </div>

          <div class="dropdown" v-else>
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-list"></i>
              <i class="fas fa-caret-down"></i>
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              <a class="dropdown-item" href="#" @click="selectOption('By Due Date', 'orderBy')"> By Due Date</a>
              <a class="dropdown-item" href="#" @click="selectOption('By Creation Date', 'orderBy')"> By Creation Date</a>
            </div>
          </div>

          <!-- <button class="btn btn-primary">
            <i class="fas fa-search"></i>
          </button> -->
          <div>
    <div class="input-group">
      <input type="text" class="form-control narrow-input" v-model="searchCriteria" placeholder="(fulltext LIKE '%someText%')">
      <div class="input-group-append">
        <button class="btn btn-primary" @click="performSearch">
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
    type: {
      type: String,
      default: 'tab_request',
    },
  },
  data() {
    return {
      searchCriteria: '',
      selectedOption: "In Progress",
      apiData: null,
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
      this.selectedOption = option;
      if(controlName === 'status'){
        this.callStatus(option);
      } else if( controlName === 'filter'){
        this.callFilter(option);
      } else {
        this.callOrderBy(option);
      }
      
    },
    callStatus(param) {
      let urlBase = "requests/";
      if (this.type === 'tab_tasks') {
        urlBase = "tasks/";
      }
      console.log('status');
      console.log('URL BASE: ', urlBase);
      console.log('DROPDOWN: ', param);
      // ProcessMaker.apiClient.get(urlBase, param)
      //   .then(response => {
      //     this.apiData = response.data;
      //   })
      //   .catch(error => {
      //     console.error('Error al hacer la solicitud a la API:', error);
      //   });
    },
    callFilter(param) {
      let urlBase = "requests/";
      if (this.type === 'tab_tasks') {
        urlBase = "tasks/";
      }
      console.log('filter');
      console.log('URL BASE: ', urlBase);
      console.log('DROPDOWN: ', param);
      // ProcessMaker.apiClient.get(urlBase, param)
      //   .then(response => {
      //     this.apiData = response.data;
      //   })
      //   .catch(error => {
      //     console.error('Error al hacer la solicitud a la API:', error);
      //   });
    },
    callOrderBy(param) {
      let urlBase = "requests/";
      if (this.type === 'tab_tasks') {
        urlBase = "tasks/";
      }
      console.log('orderBy');
      console.log('URL BASE: ', urlBase);
      console.log('DROPDOWN: ', param);
      // ProcessMaker.apiClient.get(urlBase, param)
      //   .then(response => {
      //     this.apiData = response.data;
      //   })
      //   .catch(error => {
      //     console.error('Error al hacer la solicitud a la API:', error);
      //   });
    },
    performSearch() {
     
      console.log('Search Criteria', this.searchCriteria);
      
      // ProcessMaker.apiClient.get(urlBase, param)
      //   .then(response => {
      //     this.apiData = response.data;
      //   })
      //   .catch(error => {
      //     console.error('Error al hacer la solicitud a la API:', error);
      //   });
    },
    fetchData() {
      // ProcessMaker.apiClient.get(`requests/${this.requestId}`)
      ProcessMaker.apiClient.get(`requests/`).then((response) => {
        this.items = response.data;
      });
      console.log(this.items);
    },
    fetchInitData() {
      let pmql = "";

      let { filter } = this;

if (filter && filter.length) {
  if (filter.isPMQL()) {
    pmql = `(${pmql}) and (${filter})`;
    filter = "";
  }
}

if (this.previousFilter !== filter) {
  this.page = 1;
}

this.previousFilter = filter;

if (this.previousPmql !== pmql) {
  this.page = 1;
}

this.previousPmql = pmql;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          `requests?page=1&per_page=10&include=process,participants,data` +
            `&pmql=${encodeURIComponent(pmql)}&filter=${filter}&order_by=${
              this.orderBy === "__slot:ids" ? "id" : this.orderBy
            }&order_direction=${this.orderDirection}${this.additionalParams}`
        )
        .then((response) => {
          this.data = this.transform(response.data);
          console.log('Consulta GET');
          console.log(this.data);
        })
        .catch((error) => {
          console.log('Error');
          if (_.has(error, "response.data.message")) {
            ProcessMaker.alert(error.response.data.message, "danger");
          } else if (_.has(error, "response.data.error")) {
          } else {
            throw error;
          }
        });
    },
  },
  mounted() {
    // Llamar a fetchData() cuando se cargue el componente por primera vez
    //this.fetchData();
    //this.fetchInitData();
  },
  formatStatus(status) {
    let color = "success";
    let label = "In Progress";
    switch (status) {
      case "DRAFT":
        color = "danger";
        label = "Draft";
        break;
      case "CANCELED":
        color = "danger";
        label = "Canceled";
        break;
      case "COMPLETED":
        color = "primary";
        label = "Completed";
        break;
      case "ERROR":
        color = "danger";
        label = "Error";
        break;
    }
    return `<i class="fas fa-circle text-${color}"></i> <span>${this.$t(
      label
    )}</span>`;
  },
  transform(data) {
    // Clean up fields for meta pagination so vue table pagination can understand
    data.meta.last_page = data.meta.total_pages;
    data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
    data.meta.to = data.meta.from + data.meta.count;
    data.data = this.jsonRows(data.data);
    for (const record of data.data) {
      // format Status
      record.status = this.formatStatus(record.status);
    }
    return data;
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
