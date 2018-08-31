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
      <form-input :error="errors.username" v-model="username" label="Username" helper="Username must be distinct"></form-input>
      <form-input v-model="firstname" label="First Name"></form-input>
      <form-input v-model="lastname" label="Last Name"></form-input>
      <form-select v-model="status" label="Status" name="status" :options="statusOptions"></form-select>
      <form-input :error="errors.password" v-model="password" type="password" label="Password"></form-input>
      <form-input :error="errors.confpassword" v-model="confpassword" type="password" 
                  label="Confirm Password" :validationData="data" validation="same:password"></form-input>
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
import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";
import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";

export default {
  mixins: [datatableMixin],
  props: ["filter"],
  components: { FormInput, FormSelect },
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
      status: "",
      statusOptions: [
        { value: "ACTIVE", content: "active" },
        { value: "INACTIVE", content: "inactive" }
      ],
      curIndex: "",
      password: "",
      confpassword: "",
      uid: "",
      errors: {
        password: null,
        confpassword: null,
        username: null
      },
      rules: {}
    };
  },
  methods: {
    formatStatus(status) {
      status = status.toLowerCase();
      let bubbleColor = {
        active: "text-success",
        inactive: "text-danger",
        draft: "text-warning",
        archived: "text-info"
      };
      let response =
        '<i class="fas fa-circle ' + bubbleColor[status] + ' small"></i> ';
      status = status.charAt(0).toUpperCase() + status.slice(1);
      return response + status;
    },
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
          username: this.username,
          firstname: this.firstname,
          lastname: this.lastname,
          status: this.status,
          password: this.password
        })
        .then(response => {
          ProcessMaker.alert("Saved", "success");
          this.clearForm();
          this.hideEditModal();
          this.fetch();
        })
        .catch(error => {
          if (error.response.status === 422) {
            // Validation error
            let fields = Object.keys(error.response.data.errors);
            for (let field of fields) {
              this.errors[field] = error.response.data.errors[field][0];
            }
          }
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
</style>

