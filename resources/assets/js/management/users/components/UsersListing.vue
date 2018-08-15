<template>
  <div>
    <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false"  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data" pagination-path="meta">
        <template slot="actions" slot-scope="props"> 
          <div class="actions">
            <i class="fas fa-ellipsis-h"></i>
            <div class="popout">
              <b-btn variant="action" @click="onAction('edit-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Edit"><i class="fas fa-edit"></i></b-btn>
              <b-btn variant="action" @click="onAction('remove-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Remove"><i class="fas fa-trash-alt"></i></b-btn>
            </div>
          </div>
      </template>  
    </vuetable>
    <pagination single="User" plural="Users" :perPageSelectEnabled="true" @changePerPage="changePerPage" @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
    <b-modal ref="editItem" size="md" centered title="Edit User">
    <form>
      <div class="form-group">
        <label for="edit-username">Username</label>
        <input id="edit-username" class="form-control" v-model="username">
      </div>
      <div class="form-group">
        <label for="edit-first-name">First Name</label>
        <input id="edit-first-name" class="form-control" v-model="firstname">
      </div>
      <div class="form-group">
        <label for="edit-last-name">Last Name</label>
        <input id="edit-last-name" class="form-control" v-model="lastname">
      </div>
      <div class="form-group">
        <label for="edit-password">Password</label>
        <input id="edit-password" type="password" class="form-control" v-model="password">
      </div>
      <div class="form-group">
        <label for="confirm-password">Confirm Password</label>
        <input id="confirm-password" type="password" class="form-control" v-model="confpassword">
      </div>
    </form>
    <div slot="modal-footer">
      <b-button @click="hideEditModal" class="btn btn-outline-success btn-sm text-uppercase">
        Cancel
      </b-button>
      <b-button @click="submitEdit" class="btn btn-success btn-sm text-uppercase">
        Save
      </b-button>
      </div>
    </b-modal>
   </div>
</template>

<script>
import Vuetable from "vuetable-2/src/components/Vuetable";
import datatableMixin from "../../../components/common/mixins/datatable";
import Pagination from "../../../components/common/Pagination";

export default {
  mixins: [datatableMixin],
  props: ["filter"],
  data() {
    return {
      orderBy: "username",
      // Our listing of users
      sortOrder: [
        {
          field: "username",
          sortField: "username",
          direction: "asc"
        }
      ],
      fields: [
        {
          title: "Username",
          name: "username",
          sortField: "username"
        },
        {
          title: "Full Name",
          name: "full_name",
          sortField: "full_name"
        },
        {
          title: "Status",
          name: "status",
          sortField: "status",
          callback: this.formatStatus
        },
        {
          title: "Role",
          name: "role",
          sortField: "role"
        },
        {
          title: "Login",
          name: "last_login",
          sortField: "last_login",
          callback: this.formatDate
        },
        {
          title: "Expires On",
          name: "expires_at",
          sortField: "expires_at",
          callback: this.formatDate
        },
        {
          title: "Created At",
          name: "created_at",
          sortField: "created_at",
          callback: this.formatDate
        },
        {
          title: "Updated At",
          name: "updated_at",
          sortField: "updated_at",
          callback: this.formatDate
        },
        {
          name: "__slot:actions",
          title: ""
        }
      ],
      username: "",
      firstname: "",
      lastname: "",
      uid: "",
      status: "",
      curIndex: "",
      password: "",
      confpassword: ""
    };
  },
  methods: {
    onAction(action, data, index) {
      switch (action) {
        case "edit-item":
          this.showEditModal(data, index);
      }
    },
    showEditModal(data, index) {
      this.username = this.data.data[index].username;
      this.firstname = this.data.data[index].firstname;
      this.lastname = this.data.data[index].lastname;
      this.status = this.data.data[index].status;
      this.password = this.data.data[index].password;
      this.confpassword = this.data.data[index].confpassword;
      this.role = this.data.data[index].role;
      this.uid = this.data.data[index].uid;
      this.curIndex = index;
      this.$refs.editItem.show();
    },
    submitEdit(rowIndex) {
      window.ProcessMaker.apiClient
        .put("users/" + this.uid, {
          uid: this.uid,
          username: this.username,
          firstname: this.firstname,
          lastname: this.lastname,
          status: this.status,
          password: this.password,
          confpassword: this.confpassword
        })
        .then(response => {
          ProcessMaker.alert("Saved", "success");
          this.clearForm();
          this.hideEditModal();
          this.fetch();
        })
        .catch(err => {
          ProcessMaker.alert("There was an error with your edit", "danger");
        });
    },
    clearForm(curIndex) {
      (this.username = ""),
        (this.firstname = ""),
        (this.lastname = ""),
        (this.status = ""),
        (this.password = ""),
        (this.confpassword = "");
    },
    hideEditModal() {
      this.$refs.editItem.hide();
    },
    formatStatus(value) {
      value = value.toLowerCase();
      let response = '<i class="fas fa-circle ' + value + '"></i> ';
      value = value.charAt(0).toUpperCase() + value.slice(1);
      return response + value;
    },
    transform(data) {
      // Bring in our mixin version, but we have to do additional transformation to create a full_name field
      // Clean up fields for meta pagination so vue table pagination can understand
      data.meta.last_page = data.meta.total_pages;
      data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
      data.meta.to = data.meta.from + data.meta.count;

      // Create full name field
      // Iterate through each data.data row and create one
      for (let record of data.data) {
        record["full_name"] = [record["firstname"], record["lastname"]].join(
          " "
        );
        // put in placeholder for case count
        record["task_count"] = "#";
      }
      return data;
    },
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "users?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    }
  }
};
</script>

<style lang="scss" scoped>
/deep/ i.fa-circle {
  &.active {
    color: green;
  }
  &.inactive {
    color: red;
  }
}
</style>

