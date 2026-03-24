<template>
  <div>
    <div class="data-table">
      <data-loading
        v-show="shouldShowLoader"
        :for="/cases-retention-logs/"
        :empty="$t('No Data Available')"
        :empty-desc="$t('')"
        empty-icon="noData"
      />
      <div
        v-show="!shouldShowLoader"
        class="card card-body table-card"
      >
        <vuetable
          :data-manager="dataManager"
          :sort-order="sortOrder"
          :css="css"
          :api-mode="false"
          :fields="fields"
          :data="data"
          data-path="data"
          :no-data-template="$t('No Data Available')"
          pagination-path="meta"
          @vuetable:pagination-data="onPaginationData"
        >
          <template
            slot="process_id"
            slot-scope="props"
          >
            <span v-uni-id="props.rowData.id.toString()">{{ props.rowData.process_id }}</span>
          </template>
          <template
            slot="case_ids"
            slot-scope="props"
          >
            <case-ids-table-cell
              :case-ids="props.rowData.case_ids"
              :row-id="props.rowData.id"
              :preview-limit="caseIdsPreviewLimit"
            />
          </template>
          <template
            slot="deleted_count"
            slot-scope="props"
          >
            {{ props.rowData.deleted_count }}
          </template>
          <template
            slot="total_time_taken"
            slot-scope="props"
          >
            {{ props.rowData.total_time_taken }}
          </template>
          <template
            slot="deleted_at"
            slot-scope="props"
          >
            {{ formatDate(props.rowData.deleted_at) }}
          </template>
          <template
            slot="created_at"
            slot-scope="props"
          >
            {{ formatDate(props.rowData.created_at) }}
          </template>
        </vuetable>
        <pagination
          ref="pagination"
          :single="$t('Case')"
          :plural="$t('Cases')"
          :per-page-select-enabled="true"
          @changePerPage="changePerPage"
          @vuetable-pagination:change-page="onPageChange"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";
import CaseIdsTableCell from "./CaseIdsTableCell.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  name: "CasesRetentionLogs",
  components: {
    CaseIdsTableCell,
  },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: {
    filter: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      orderBy: "created_at",
      orderDirection: "desc",
      // Object { data, meta } after fetch — not a bare array — so vuetable calls dataManager on sort
      // (see vuetable-2 callDataManager: Array.isArray short-circuits and skips fetch).
      data: [],
      sortOrder: [
        { field: "__slot:created_at", sortField: "created_at", direction: "desc" },
      ],
      fields: [
        {
          title: () => this.$t("Process ID"),
          name: "__slot:process_id",
          sortField: "process_id",
          width: "16.66%",
        },
        {
          title: () => this.$t("Case IDs"),
          name: "__slot:case_ids",
          width: "16.66%",
        },
        {
          title: () => this.$t("Deleted Count"),
          name: "__slot:deleted_count",
          sortField: "deleted_count",
          width: "16.66%",
        },
        {
          title: () => this.$t("Total Time Taken (ms)"),
          name: "__slot:total_time_taken",
          sortField: "total_time_taken",
          width: "16.66%",
        },
        {
          title: () => this.$t("Deleted At"),
          name: "__slot:deleted_at",
          sortField: "deleted_at",
          callback: "formatDate",
          width: "16.66%",
        },
        {
          title: () => this.$t("Created At"),
          name: "__slot:created_at",
          sortField: "created_at",
          callback: "formatDate",
          width: "16.66%",
        },
      ],
      caseIdsPreviewLimit: 5,
    };
  },
  watch: {
    filter() {
      // this.page = 1;
      this.fetch();
    },

  },
  methods: {
    fetch() {
      ProcessMaker.apiClient.get('cases-retention/logs', {
        params: {
          filter: this.filter,
          order_by: this.orderBy,
          order_direction: this.orderDirection,
          page: this.page,
          per_page: this.perPage,
        },
      }).then(response => {
        this.data = this.transform(response.data);
        this.apiDataLoading = false;
      }).catch(error => {
        this.apiDataLoading = false;
      });
    },
    reload() {
      this.fetch();
    },
    changePerPage(value) {
        this.perPage = value;
        if (this.page * value > this.$refs.pagination.tablePagination.total) {
            this.page = Math.floor(this.$refs.pagination.tablePagination.total / value) + 1;
        }
        this.fetch();
    },
    onPageChange(page) {
      if (page == "next") {
        this.page = this.page + 1;
      } else if (page == "prev") {
        this.page = this.page - 1;
      } else {
        this.page = page;
      }
      if (this.page <= 0) {
        this.page = 1;
      }
      let meta = this.$refs.pagination.tablePagination;
      if (this.page > meta.last_page) {
        this.page = meta.last_page;
      }
      this.fetch();
    },
  },
};
</script>

<style lang="scss" scoped>
.data-table {
  .table-card {
    border-radius: 8px;
    border: 1px solid #D7DDE5;
  }

  .vuetable {
    border-radius: 8px;
    overflow: hidden;

    thead {
      th {
        background-color: #FBFBFC !important;
        border-bottom: 1px solid #D7DDE5 !important;
        border-right: 1px solid #D7DDE5 !important;
        font-weight: 600 !important;
        color: #596372 !important;
        padding: 12px 16px !important;
        font-family: 'Inter', sans-serif !important;
        font-weight: 600 !important;
        line-height: 20px !important;
        letter-spacing: -0.01em !important;
        font-size: 14px !important;

        &:last-child {
          border-right: none !important;
        }
      }
    }
  }
}

// Global override for Vuetable styles
:deep(.vuetable) {
  thead th {
    background-color: #FBFBFC !important;
    border-bottom: 1px solid #D7DDE5 !important;
    border-right: 1px solid #D7DDE5 !important;
    font-weight: 600 !important;
    color: #596372 !important;
    padding: 12px 16px !important;
    font-family: 'Inter', sans-serif !important;
    line-height: 20px !important;
    font-size: 14px !important;
  }

  thead th:last-child {
    border-right: none !important;
  }

  tbody {
    tr {
      color: #4E5663 !important;
      border-bottom: 1px solid #E9ECEF !important;
      font-family: 'Inter', sans-serif !important;
      font-weight: 400 !important;
      font-size: 14px !important;
      line-height: 20px !important;

      td {
        padding: 12px 16px !important;
        vertical-align: middle !important;
        border-bottom: 1px solid #E9ECEF !important;
      }
    }
  }
}
</style>
