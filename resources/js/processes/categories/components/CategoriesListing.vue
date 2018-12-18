<template>
  <div class="data-table">
    <div class="card card-body table-card">
      <vuetable
        :dataManager="dataManager"
        :sortOrder="sortOrder"
        :css="css"
        :api-mode="false"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
      >
        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="onAction('edit-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                title="Edit"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAction('remove-item', props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                title="Remove"
                v-if="props.rowData.processes_count < 1"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        single="Category"
        plural="Categories"
        :perPageSelectEnabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>
    </div>
  </div>
</template>

<script>
import datatableMixin from "../../../components/common/mixins/datatable";

export default {
  mixins: [datatableMixin],
  props: ["filter"],
  data() {
    return {
      orderBy: "name",
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
          title: "Status",
          name: "status",
          sortField: "status",
          callback: this.formatStatus
        },
        {
          title: "# Processes",
          name: "processes_count",
          sortField: "processes_count"
        },
        {
          title: "Modified",
          name: "updated_at",
          sortField: "updated_at",
          callback: "formatDate"
        },
        {
          title: "Created",
          name: "created_at",
          sortField: "created_at",
          callback: "formatDate"
        },
        {
          name: "__slot:actions",
          title: ""
        }
      ]
    };
  },
  methods: {
    fetch() {
      this.loading = true;

      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "process_categories?current_page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection +
            "&include=processesCount"
        )
        .then(response => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    },
    transform(data) {
      // format in a way vuetable is expecting
      data = Object.assign({}, data, data.meta, { meta: null });
      return data;
    },
    onPaginationData() {},
    onAction(action, data, index) {
      switch (action) {
        case "edit-item":
          window.location = "/processes/categories/" + data.id + "/edit";
          break;
        case "remove-item":
          ProcessMaker.confirmModal(
            "Caution!",
            "<b>Are you sure to delete the process </b>" + data.name + "?",
            "",
            () => {
              this.$emit("delete", data);
            }
          );
          break;
      }
    },
    formatStatus(value) {
      let response =
        '<i class="fas fa-circle ' + value.toLowerCase() + '"></i> ';
      return response + _.capitalize(value);
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
