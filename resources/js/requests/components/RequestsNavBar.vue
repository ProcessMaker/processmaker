<template>
  <!-- <ul class="nav nav-pills">
      <div>
      <div class="form-group">
        <label for="dropdown">Select Option:</label>
        <select id="dropdown" v-model="selectedOption" @change="fetchData">
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
        </select>
      </div>
      <ul>
        <li v-for="item in items" :key="item.id">Request:{{ item.name }}</li>
      </ul>
    </div>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">Participant</a>
    <div class="dropdown-menu">
      <a class="dropdown-item" href="#">My Requests</a>
      <a class="dropdown-item" href="#">Participant</a>
    </div>
  </li>
  <div class="input-group">
    <input type="text" class="form-control" placeholder="Search">
    <div class="input-group-append">
      <button class="btn btn-secondary" type="button">
        <i class="fa fa-search"></i>
      </button>
    </div>
  </div>
</ul> -->
  <div>
    <b-navbar
      type="light"
      variant="light"
    >
      <b-nav-form>
        <b-form-input
          class="mr-sm-2"
          placeholder="Search"
        ></b-form-input>
        <b-button
          variant="outline-success"
          class="my-2 my-sm-0"
          type="submit"
          >Search</b-button
        >
      </b-nav-form>

      <b-nav-item-dropdown
        text="Lang"
        right
      >
        <b-dropdown-item href="#">EN</b-dropdown-item>
        <b-dropdown-item href="#">ES</b-dropdown-item>
        <b-dropdown-item href="#">RU</b-dropdown-item>
        <b-dropdown-item href="#">FA</b-dropdown-item>
      </b-nav-item-dropdown>

      <b-nav-item-dropdown
        text="User"
        right
      >
        <b-dropdown-item href="#">Account</b-dropdown-item>
        <b-dropdown-item href="#">Settings</b-dropdown-item>
      </b-nav-item-dropdown>
    </b-navbar>
  </div>
</template>

<script>
export default {
  data() {
    return {
      selectedOption: "In Progress",
      items: [],
    };
  },
  methods: {
    fetchData() {
      // ProcessMaker.apiClient.get(`requests/${this.requestId}`)
      ProcessMaker.apiClient.get(`requests/`).then((response) => {
        this.items = response.data;
      });
      console.log(this.items);
    },
    // fetchInitData() {
    //   let pmql = "";
    //   // Load from our api client
    //   ProcessMaker.apiClient
    //     .get(
    //       `requests?page=${this.page}&per_page=${this.perPage}&include=process,participants,data` +
    //         `&pmql=${encodeURIComponent(pmql)}&filter=${filter}&order_by=${
    //           this.orderBy === "__slot:ids" ? "id" : this.orderBy
    //         }&order_direction=${this.orderDirection}${this.additionalParams}`
    //     )
    //     .then((response) => {
    //       this.data = this.transform(response.data);
    //       console.log('Consulta GET');
    //       console.log(this.data);
    //     })
    //     .catch((error) => {
    //       console.log('Error');
    //       if (_.has(error, "response.data.message")) {
    //         ProcessMaker.alert(error.response.data.message, "danger");
    //       } else if (_.has(error, "response.data.error")) {
    //       } else {
    //         throw error;
    //       }
    //     });
    // },
  },
  mounted() {
    // Llamar a fetchData() cuando se cargue el componente por primera vez
    this.fetchData();
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
</style>
