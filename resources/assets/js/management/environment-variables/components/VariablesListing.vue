<template>
  <div>
    <vuetable :dataManager="dataManager" :sortOrder="sortOrder" :css="css" :api-mode="false" @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data" pagination-path="meta">
      <template slot="actions" slot-scope="props">
        <div class="actions">
          <i class="fas fa-ellipsis-h"></i>
          <div class="popout">
            <b-btn variant="action" @click="onAction('edit-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Edit">
              <i class="fas fa-edit"></i>
            </b-btn>
            <b-btn variant="action" @click="onAction('remove-item', props.rowData, props.rowIndex)" v-b-tooltip.hover title="Remove">
              <i class="fas fa-trash-alt"></i>
            </b-btn>
          </div>
        </div>
      </template>
    </vuetable>
    <pagination single="Variable" plural="Variables" :perPageSelectEnabled="true" @changePerPage="changePerPage" @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
    <b-modal ref="editItem" size="md" centered title="Edit Variable">
      <form>
        <form-input :error="errors.name" v-model="name" label="Name" helper="Name must be distinct"></form-input>
        <form-input v-model="description" label="Description"></form-input>
        <form-input type="password" v-model="value" label="Value"></form-input>
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

export default {
  mixins: [datatableMixin],
  props: ["filter"],
  components: { FormInput },
  data() {
    return {
      orderBy: "name",
      // Our listing of variables
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc"
        }
      ],
      fields: [
        {
          title: "Name",
          name: "name",
          sortField: "name"
        },
        {
          title: "Description",
          name: "description",
          sortField: "description"
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
      name: "",
      description: "",
      value: "",
      uid: "",
      errors: {
        name: null,
        description: null,
        value: null
      },
      rules: {}
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
      this.name = this.data.data[index].name;
      this.description = this.data.data[index].description;
      // Do not load value, always reset it to empty string
      this.value = "";
      this.uid = this.data.data[index].uid;
      this.curIndex = index;
      this.$refs.editItem.show();
    },
    submitEdit(rowIndex) {
      window.ProcessMaker.apiClient
        .put("environment-variables/" + this.uid, {
          name: this.name,
          description: this.description,
          value: this.value
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
      (this.name = ""),
        (this.description = ""),
        (this.value = "");
    },
    hideEditModal() {
      this.$refs.editItem.hide();
    },
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "environment-variables?page=" +
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

