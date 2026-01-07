<template>
  <div class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0">
    <base-table
      :columns="columns"
      :data="data"
    />
    <pagination
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
          { key: 'status', label: this.$t('Status') },
          { key: 'duration', label: this.$t('Execution Time') },
          { key: 'tools', label: this.$t('Tools') },
          { key: 'tokens_used', label: this.$t('Tokens Used') },
          { key: 'created_at', label: this.$t('Date'), format: dateFormatter },
        ],
        execution: [
          { key: 'process_request_id', label: this.$t('Request ID') },
          { key: 'node_id', label: this.$t('Node ID') },
          { key: 'flow_genie_name', label: this.$t('FlowGenie Name') },
          { key: 'user_name', label: this.$t('User') },
          { key: 'status', label: this.$t('Status') },
          { key: 'duration', label: this.$t('Execution Time') },
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
  },
  watch: {
    category: {
      handler() {
        this.resetAndFetch();
      },
    },
    logType: {
      handler() {
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
      this.fetchData();
    },
    async fetchData(params = {}) {
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
      }
    },
    handlePageChange(newPage) {
      this.page = newPage;
      this.fetchData();
    },
    refresh(params = {}) {
      this.page = 1;
      this.fetchData(params);
    },
  },
};
</script>

