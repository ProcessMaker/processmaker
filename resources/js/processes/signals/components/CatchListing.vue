<template>
  <div class="data-table">
    <div class="card card-body table-card">
      <vuetable
        :data-manager="dataManager"
        :sort-order="sortOrder"
        :css="css"
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
        :no-data-template="$t('No Data Available')"
        @vuetable:pagination-data="onPaginationData"
      >
        <template
          slot="actions"
          slot-scope="props"
        >
          <div class="actions">
            <div class="popout">
              <b-btn
                v-if="permission.includes('edit-processes')"
                v-b-tooltip.hover
                variant="link"
                :title="$t('Edit')"
                @click="onEdit(props.rowData, props.rowIndex)"
              >
                <i class="fas fa-pen-square fa-lg fa-fw" />
              </b-btn>
              <b-btn
                v-if="permission.includes('edit-processes')"
                v-b-tooltip.hover
                variant="link"
                :title="$t('Delete')"
                @click="onDelete(props.rowData, props.rowIndex)"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw" />
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        ref="pagination"
        :single="$t('Signal')"
        :plural="$t('Signals')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
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
      const fields = JSON.parse(this.items);
      fields.forEach((field) => {
        field.catches.forEach((item) => {
          this.data.push({
            subscriber: item.name,
            type: `#${field.id} ${field.name}`,
          });
        });
      });
      this.loading = false;
    },
  },
};
</script>
