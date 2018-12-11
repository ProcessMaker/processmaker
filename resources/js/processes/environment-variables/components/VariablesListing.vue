<template>
  <div class="data-table">
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
              variant="action"
              @click="onAction('edit-item', props.rowData, props.rowIndex)"
              v-b-tooltip.hover
              title="Edit"
            >
              <i class="fas fa-edit"></i>
            </b-btn>
            <b-btn
              variant="action"
              @click="onAction('remove-item', props.rowData, props.rowIndex)"
              v-b-tooltip.hover
              title="Remove"
            >
              <i class="fas fa-trash-alt"></i>
            </b-btn>
          </div>
        </div>
      </template>
    </vuetable>
    <pagination
      single="Variable"
      plural="Variables"
      :perPageSelectEnabled="true"
      @changePerPage="changePerPage"
      @vuetable-pagination:change-page="onPageChange"
      ref="pagination"
    ></pagination>
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
    onAction(action, data, index) {
      switch (action) {
        case "edit-item":
          window.location =
            "/processes/environment-variables/" + data.id + "/edit";
          break;
        case "remove-item":
          ProcessMaker.confirmModal(
            "Caution!",
            "<b>Are you sure to delete the Environment Variable </b>" +
              data.name +
              "?",
            "",
            () => {
              this.$emit("delete", data);
            }
          );
          break;
      }
    },
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "environment_variables?page=" +
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
