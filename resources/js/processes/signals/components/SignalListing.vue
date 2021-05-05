<template>
  <div class="data-table">
    <data-loading
      :for="/signals\?page/"
      v-show="shouldShowLoader"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="card card-body table-card">
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
        :noDataTemplate="$t('No Data Available')"
      >
        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="onEdit(props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="$t('Edit')"
                v-if="permission.includes('edit-signals')"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onDelete(props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="$t('Delete')"
                v-if="isDeletable(props.rowData) && permission.includes('delete-signals')"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        :single="$t('Signal')"
        :plural="$t('Signals')"
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
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";

export default {
  mixins: [datatableMixin, dataLoadingMixin],
  props: ["filter", "permission"],
  data() {
    return {
      orderBy: "id",

      sortOrder: [
        {
          field: "id",
          sortField: "id",
          direction: "asc",
        },
      ],
      fields: [
        {
          title: () => this.$t("ID"),
          name: "id",
          sortField: "id",
        },
        {
          title: () => this.$t("Name"),
          name: "name",
          sortField: "Name",
        },
        {
          title: () => this.$t("Actions"),
          name: "__slot:actions",
          title: "",
        },
      ],
    };
  },
  methods: {
    isDeletable(data) {
      let catches = data.processes.reduce((carry, process) => carry + process.catches.length, 0);
      return catches === 0;
    },
    onEdit(data, index) {
      window.location = "/designer/signals/" + data.id + "/edit";
    },
    onDelete(data, index) {
      let that = this;
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        "<b>" +
          this.$t("Are you sure you want to delete {{item}}?", {
            item: data.name,
          }) +
          "</b>",
        "",
        function () {
          ProcessMaker.apiClient
            .delete("signals/" + data.id)
            .then((response) => {
              ProcessMaker.alert(this.$t("The signal was deleted."), "success");
              that.fetch();
            });
        }
      );
    },
    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "signals?page=" +
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
        .then((response) => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    },
  },
};
</script>
