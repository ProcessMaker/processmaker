<template>
  <div class="px-3">
    <div class="row">
      <!-- Overall Statistics -->
      <div class="col-12 mb-4">
        <b-card header="Overall Statistics">
          <div
            id="overall-stats"
            class="row"
          >
            <div class="col-md-2">
              <div class="text-center">
                <h3 class="text-primary">{{ overallStats.totalTenants }}</h3>
                <small class="text-muted">Total Tenants</small>
              </div>
            </div>
            <div class="col-md-2">
              <div class="text-center">
                <h3 class="text-info">{{ overallStats.totalJobs }}</h3>
                <small class="text-muted">Total Jobs</small>
              </div>
            </div>
            <div class="col-md-2">
              <div class="text-center">
                <h3 class="text-warning">{{ overallStats.totalProcessing }}</h3>
                <small class="text-muted">Processing</small>
              </div>
            </div>
            <div class="col-md-2">
              <div class="text-center">
                <h3 class="text-success">{{ overallStats.totalCompleted }}</h3>
                <small class="text-muted">Completed</small>
              </div>
            </div>
            <div class="col-md-2">
              <div class="text-center">
                <h3 class="text-danger">{{ overallStats.totalFailed }}</h3>
                <small class="text-muted">Failed</small>
              </div>
            </div>
            <div class="col-md-2">
              <div class="text-center">
                <h3 class="text-secondary">{{ overallStats.totalException }}</h3>
                <small class="text-muted">Exceptions</small>
              </div>
            </div>
          </div>
        </b-card>
      </div>

      <!-- Tenants List -->
      <div class="col-12">
        <b-card>
          <template #header>
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                Tenants with Job Activity
              </h5>
              <b-button
                variant="outline-primary"
                size="sm"
                :disabled="loading"
                @click="refreshData"
              >
                <b-icon
                  icon="arrow-clockwise"
                  :animation="loading ? 'spin' : ''"
                />
                Refresh
              </b-button>
            </div>
          </template>

          <b-table
            :items="tenants"
            :fields="tableFields"
            :busy="loading"
            hover
            striped
            responsive
            show-empty
            empty-text="No tenants with job activity found"
          >
            <template #table-busy>
              <div class="text-center">
                <b-spinner class="align-middle" />
                <strong>Loading...</strong>
              </div>
            </template>

            <template #cell(stats)="data">
              <div>
                <b-badge
                  variant="info"
                  class="mr-1"
                >
                  {{ data.item.stats.total }}
                </b-badge>
                <b-badge
                  variant="warning"
                  class="mr-1"
                >
                  {{ data.item.stats.processing }}
                </b-badge>
                <b-badge
                  variant="success"
                  class="mr-1"
                >
                  {{ data.item.stats.completed }}
                </b-badge>
                <b-badge
                  variant="danger"
                  class="mr-1"
                >
                  {{ data.item.stats.failed }}
                </b-badge>
              </div>
            </template>

            <template #cell(actions)="data">
              <b-button
                variant="outline-primary"
                size="sm"
                @click="viewTenantJobs(data.item)"
              >
                View Jobs
              </b-button>
            </template>
          </b-table>
        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "TenantQueuesDashboard",
  data() {
    return {
      loading: false,
      overallStats: {
        totalTenants: "-",
        totalJobs: "-",
        totalProcessing: "-",
        totalCompleted: "-",
        totalFailed: "-",
        totalException: "-",
      },
      tenants: [],
      refreshInterval: null,
      tableFields: [
        { key: "id", label: "Tenant ID" },
        { key: "name", label: "Name" },
        { key: "domain", label: "Domain" },
        { key: "stats", label: "Job Statistics" },
        { key: "actions", label: "Actions" },
      ],
    };
  },
  mounted() {
    this.refreshData();
    // Auto-refresh every 30 seconds
    this.refreshInterval = setInterval(this.refreshData, 30000);
  },
  beforeDestroy() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
  },
  methods: {
    async refreshData() {
      this.loading = true;
      try {
        await Promise.all([
          this.loadOverallStats(),
          this.loadTenants(),
        ]);
      } catch (error) {
        console.error("Error refreshing data:", error);
      } finally {
        this.loading = false;
      }
    },

    async loadOverallStats() {
      try {
        const { data } = await ProcessMaker.apiClient.get("/tenant-queues/overall-stats");
        this.overallStats = {
          totalTenants: data.total_tenants,
          totalJobs: data.total_jobs,
          totalProcessing: data.total_processing,
          totalCompleted: data.total_completed,
          totalFailed: data.total_failed,
          totalException: data.total_exception,
        };
      } catch (error) {
        console.error("Error loading overall stats:", error);
      }
    },

    async loadTenants() {
      try {
        const { data } = await ProcessMaker.apiClient.get("/tenant-queues/tenants");
        this.tenants = data;
      } catch (error) {
        console.error("Error loading tenants:", error);
        this.tenants = [];
      }
    },

    viewTenantJobs(tenant) {
      this.$router.push(`/tenant/${tenant.id}/jobs`);
    },
  },
};
</script>
