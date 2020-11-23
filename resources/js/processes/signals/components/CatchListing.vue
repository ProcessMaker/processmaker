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
                v-if="permission.includes('edit-processes')"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onDelete(props.rowData, props.rowIndex)"
                v-b-tooltip.hover
                :title="$t('Delete')"
                v-if="permission.includes('edit-processes')"
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
  props: ["filter", "items"],
  data() {
    return {
      orderBy: "type",

      sortOrder: [
        {
          field: "type",
          sortField: "type",
          direction: "asc",
        },
      ],
      fields: [
        {
          title: () => this.$t("Subscriber"),
          name: "subscriber",
        },
        {
          title: () => this.$t("Type"),
          name: "type",
        },
      ],
    };
  },
  methods: {
    fetch() {
      this.data = [];
      let fields = JSON.parse(this.items);
      fields.forEach((field) => {
        field.catches.forEach((item) => {
          this.data.push({
            subscriber: item.name,
            type: `#${field.id} ${field.name}`
          });
        });
      });
      this.loading = false;
    },
  },
};
</script>
