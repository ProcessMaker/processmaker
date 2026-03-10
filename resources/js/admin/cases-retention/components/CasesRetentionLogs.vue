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
            slot="case_id"
            slot-scope="props"
          >
            <span v-uni-id="`case-id-${props.rowData.id}`">{{ props.rowData.case_id }}</span>
          </template>
          <template
            slot="deleted_at"
            slot-scope="props"
          >
            {{ formatDate(props.rowData.deleted_at) }}
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

const uniqIdsMixin = createUniqIdsMixin();

/**
 * Fake data matching retention_policy_logs schema until the table/API exists.
 * - id, process_id, case_id (single or serialized list), deleted_at, created_at
 */
const FAKE_RETENTION_LOGS = [
  {
    id: 1,
    process_id: 101,
    case_id: 5001,
    deleted_at: "2025-03-01T14:30:00.000000Z",
    created_at: "2025-03-01T14:30:05.000000Z",
  },
  {
    id: 2,
    process_id: 101,
    case_id: 5002,
    deleted_at: "2025-03-02T09:15:00.000000Z",
    created_at: "2025-03-02T09:15:02.000000Z",
  },
  {
    id: 3,
    process_id: 204,
    case_id: 7003,
    deleted_at: "2025-03-03T16:45:00.000000Z",
    created_at: "2025-03-03T16:45:10.000000Z",
  },
  {
    id: 4,
    process_id: 305,
    case_id: 8010,
    deleted_at: "2025-03-04T11:00:00.000000Z",
    created_at: "2025-03-04T11:00:01.000000Z",
  },
  {
    id: 5,
    process_id: 204,
    case_id: 7005,
    deleted_at: "2025-03-05T08:22:00.000000Z",
    created_at: "2025-03-05T08:22:03.000000Z",
  },
];

export default {
  name: "CasesRetentionLogs",
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
      data: [],
      sortOrder: [
        { field: "created_at", sortField: "created_at", direction: "desc" },
      ],
      fields: [
        {
          title: () => this.$t("Process ID"),
          name: "__slot:process_id",
          sortField: "process_id",
          width: "33%",
        },
        {
          title: () => this.$t("Case ID"),
          name: "__slot:case_id",
          sortField: "case_id",
          width: "33%",
        },
        {
          title: () => this.$t("Deleted At"),
          name: "__slot:deleted_at",
          sortField: "deleted_at",
          callback: "formatDate",
          width: "33%",
        },
      ],
    };
  },
  watch: {
    filter() {
      this.page = 1;
      this.fetch();
    },
  },
  methods: {
    fetch() {
      // TODO: replace with API call when retention_policy_logs table and endpoint exist
      const total = FAKE_RETENTION_LOGS.length;
      this.data = {
        data: FAKE_RETENTION_LOGS,
        meta: {
          total,
          per_page: 15,
          current_page: 1,
          last_page: 1,
          from: 1,
          to: total,
          total_pages: 1,
          count: total,
        },
      };
      this.apiDataLoading = false;
    },
    reload() {
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
