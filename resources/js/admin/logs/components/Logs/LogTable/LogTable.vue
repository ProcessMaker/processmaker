<template>
  <div class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0">
    <!-- Loading spinner -->
    <div
      v-if="loading"
      class="tw-flex tw-flex-1 tw-items-center tw-justify-center tw-min-h-[200px]"
    >
      <div class="tw-flex tw-flex-col tw-items-center tw-gap-3">
        <svg
          class="tw-animate-spin tw-h-8 tw-w-8 tw-text-blue-500"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="tw-opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path
            class="tw-opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          />
        </svg>
        <span class="tw-text-sm tw-text-gray-500">{{ $t('Loading...') }}</span>
      </div>
    </div>

    <!-- Table content -->
    <base-table
      v-else
      :columns="columns"
      :data="data"
      :clickable="isAgentCategory"
      :selected-item="selectedSession"
      item-key="session_id"
      @row-click="handleRowClick"
    >
      <!-- Custom case_number cell with link to case -->
      <template #cell-case_number="{ value }">
        <a
          v-if="value"
          :href="`/cases/${value}`"
          class="tw-text-blue-600 hover:tw-text-blue-800 hover:tw-underline tw-font-medium"
        >
          #{{ value }}
        </a>
        <span v-else class="tw-text-gray-400">-</span>
      </template>

      <!-- Custom status cell with colored badge -->
      <template #cell-status="{ value }">
        <span
          class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-xs tw-font-medium"
          :class="getStatusClasses(value)"
        >
          {{ formatStatus(value) }}
        </span>
      </template>

      <!-- Custom tokens_used cell with hover popover -->
      <template #cell-tokens_used="{ value, item }">
        <div class="tw-relative tw-group tw-inline-block">
          <span class="tw-cursor-help tw-border-b tw-border-dotted tw-border-gray-400">
            {{ value }}
          </span>
          <!-- Popover tooltip -->
          <div
            class="
              tw-absolute tw-z-50 tw-bottom-full tw-left-1/2 tw--translate-x-1/2 tw-mb-2
              tw-hidden group-hover:tw-block
              tw-bg-gray-900 tw-text-white tw-text-xs tw-rounded-lg tw-py-2 tw-px-3
              tw-whitespace-nowrap tw-shadow-lg
            "
          >
            <div class="tw-flex tw-flex-col tw-gap-1">
              <div class="tw-flex tw-justify-between tw-gap-4">
                <span class="tw-text-gray-400">{{ $t('Input') }}:</span>
                <span class="tw-font-medium">{{ item.input_tokens }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-gap-4">
                <span class="tw-text-gray-400">{{ $t('Output') }}:</span>
                <span class="tw-font-medium">{{ item.output_tokens }}</span>
              </div>
            </div>
            <!-- Arrow -->
            <div
              class="
                tw-absolute tw-top-full tw-left-1/2 tw--translate-x-1/2
                tw-border-4 tw-border-transparent tw-border-t-gray-900
              "
            />
          </div>
        </div>
      </template>
    </base-table>
    <pagination
      v-if="!loading"
      class="tw-mt-3 tw-shrink-0"
      :page="page"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    />
  </div>
</template>

<script>
import { BaseTable } from '../BaseTable';
import { Pagination } from '../Pagination';
import { dateFormatter } from '../../../utils/date';

export default {
  components: {
    BaseTable,
    Pagination,
  },
  props: {
    category: {
      type: String,
      default: 'email',
    },
    logType: {
      type: String,
      default: 'errors',
    },
  },
  data() {
    return {
      data: [],
      page: 1,
      totalPages: 1,
      perPage: 15,
      loading: false,
      selectedSession: null,
      currentSearch: '',
    };
  },
  computed: {
    columns() {
      // Email log columns
      const emailColumns = {
        errors: [
          { key: 'id', label: this.$t('ID') },
          { key: 'imap_server', label: this.$t('IMAP Server') },
          { key: 'email', label: this.$t('User Email') },
          { key: 'from.email', label: this.$t('Email From') },
          { key: 'error_code', label: this.$t('Error Code') },
          { key: 'error_description', label: this.$t('Description') },
          { key: 'date', label: this.$t('Date'), format: dateFormatter },
        ],
        matched: [
          { key: 'id', label: this.$t('ID') },
          { key: 'imap_server', label: this.$t('IMAP Server') },
          { key: 'email', label: this.$t('User Email') },
          { key: 'from.email', label: this.$t('Email From') },
          { key: 'subject', label: this.$t('Subject') },
          { key: 'body', label: this.$t('Body') },
          { key: 'date_email', label: this.$t('Date Email'), format: dateFormatter },
          { key: 'process_name', label: this.$t('Process') },
          { key: 'process_request_id', label: this.$t('Request ID') },
          { key: 'user_fullname', label: this.$t('User') },
          { key: 'files_attached', label: this.$t('Files Attached') },
          { key: 'created_at', label: this.$t('Created At'), format: dateFormatter },
        ],
        total: [
          { key: 'execution_id', label: this.$t('ID') },
          { key: 'imap_server', label: this.$t('IMAP Server') },
          { key: 'email', label: this.$t('User Email') },
          { key: 'total_emails', label: this.$t('Total Emails') },
          { key: 'total_matched', label: this.$t('Total Matches') },
          { key: 'created_at', label: this.$t('Date'), format: dateFormatter },
        ],
      };

      // FlowGenie Agents log columns
      const agentColumns = {
        design: [
          { key: 'flow_genie_name', label: this.$t('FlowGenie Name') },
          { key: 'user_name', label: this.$t('User') },
          { key: 'model', label: this.$t('Model') },
          { key: 'status', label: this.$t('Status') },
          { key: 'duration', label: this.$t('Execution Time') },
          { key: 'llm_calls', label: this.$t('LLM Calls') },
          { key: 'tools', label: this.$t('Tools') },
          { key: 'tokens_used', label: this.$t('Tokens Used') },
          { key: 'created_at', label: this.$t('Date'), format: dateFormatter },
        ],
        execution: [
          { key: 'case_number', label: this.$t('Case #') },
          { key: 'process_name', label: this.$t('Process') },
          { key: 'node_name', label: this.$t('Node') },
          { key: 'flow_genie_name', label: this.$t('FlowGenie Name') },
          { key: 'user_name', label: this.$t('User') },
          { key: 'status', label: this.$t('Status') },
          { key: 'duration', label: this.$t('Execution Time') },
          { key: 'llm_calls', label: this.$t('LLM Calls') },
          { key: 'tools', label: this.$t('Tools') },
          { key: 'tokens_used', label: this.$t('Tokens Used') },
          { key: 'created_at', label: this.$t('Date'), format: dateFormatter },
        ],
      };

      if (this.category === 'agents') {
        return agentColumns[this.logType] ?? agentColumns.design;
      }

      return emailColumns[this.logType] ?? [];
    },
    apiEndpoint() {
      if (this.category === 'agents') {
        // Map logType to API endpoint for agents
        const agentEndpoints = {
          design: '/api/1.0/flow-genie/logs/design',
          execution: '/api/1.0/flow-genie/logs/execution',
        };
        return agentEndpoints[this.logType] ?? agentEndpoints.design;
      }
      return `/api/1.1/email-start-event/logs/${this.logType}`;
    },
    isAgentCategory() {
      return this.category === 'agents';
    },
  },
  watch: {
    category: {
      handler() {
        this.clearSelection();
        this.resetAndFetch();
      },
    },
    logType: {
      handler() {
        this.clearSelection();
        this.resetAndFetch();
      },
      immediate: true,
    },
  },
  methods: {
    resetAndFetch() {
      this.page = 1;
      this.perPage = 15;
      this.totalPages = 1;
      this.currentSearch = '';
      this.fetchData();
    },
    async fetchData(params = {}) {
      this.loading = true;
      try {
        const response = await ProcessMaker.apiClient.get(this.apiEndpoint, {
          params: {
            page: this.page,
            per_page: this.perPage,
            ...params,
          },
        });
        const { data, ...pagination } = response.data;

        this.data = data;
        // Pagination data
        this.totalPages = pagination.last_page;
        this.page = pagination.current_page;
        this.perPage = pagination.per_page;
      } catch (error) {
        // eslint-disable-next-line no-console
        console.log(error);
        this.data = [];
      } finally {
        this.loading = false;
      }
    },
    handlePageChange(newPage) {
      this.page = newPage;
      this.fetchData({ search: this.currentSearch });
    },
    refresh(params = {}) {
      this.page = 1;
      // Store search for pagination
      if (params.search !== undefined) {
        this.currentSearch = params.search;
      }
      this.fetchData(params);
    },
    getStatusClasses(status) {
      const statusClasses = {
        completed: 'tw-bg-green-100 tw-text-green-800',
        error: 'tw-bg-red-100 tw-text-red-800',
        processing: 'tw-bg-yellow-100 tw-text-yellow-800',
      };
      return statusClasses[status] || 'tw-bg-gray-100 tw-text-gray-800';
    },
    formatStatus(status) {
      const statusLabels = {
        completed: this.$t('Completed'),
        error: this.$t('Error'),
        processing: this.$t('Processing'),
      };
      return statusLabels[status] || status;
    },
    handleRowClick(item) {
      if (this.isAgentCategory) {
        this.selectedSession = item;
        this.$emit('session-selected', item);
      }
    },
    clearSelection() {
      this.selectedSession = null;
      this.$emit('session-selected', null);
    },
  },
};
</script>

