<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <b-card>
          <template #header>
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">
                Tenant Jobs - {{ tenant ? tenant.name : `Tenant ${tenantId}` }}
              </h5>
              <div>
                <b-button
                  variant="outline-secondary"
                  size="sm"
                  @click="$router.push('/')"
                  class="mr-2"
                >
                  <b-icon icon="arrow-left" />
                  Back to Dashboard
                </b-button>
                <b-button
                  variant="outline-danger"
                  size="sm"
                  @click="clearTenantJobs"
                >
                  Clear Tenant Data
                </b-button>
              </div>
            </div>
          </template>

          <div class="row mb-3">
            <div class="col-md-6">
              <b-form-group
                label="Filter by Status:"
                label-for="status-filter"
              >
                <b-form-select
                  id="status-filter"
                  v-model="selectedStatus"
                  :options="statusOptions"
                  @change="onStatusChange"
                />
              </b-form-group>
            </div>
            <div class="col-md-6">
              <div class="d-flex justify-content-end align-items-end h-100">
                <b-button
                  variant="outline-primary"
                  size="sm"
                  :disabled="loading"
                  @click="loadTenantJobs"
                >
                  <b-icon
                    icon="arrow-clockwise"
                    animation="loading ? 'spin' : ''"
                  />
                  Refresh
                </b-button>
              </div>
            </div>
          </div>

          <b-table
            :items="jobs"
            :fields="tableFields"
            :busy="loading"
            hover
            striped
            responsive
            show-empty
            empty-text="No jobs found"
          >
            <template #table-busy>
              <div class="text-center">
                <b-spinner class="align-middle" />
                <strong class="ml-2">Loading...</strong>
              </div>
            </template>

            <template #cell(id)="data">
              <code>{{ data.item.id }}</code>
            </template>

            <template #cell(status)="data">
              <b-badge :variant="getStatusVariant(data.item.status)">
                {{ getStatusText(data.item.status) }}
              </b-badge>
            </template>

            <template #cell(queued)="data">
              {{ formatTimestamp(data.item.queued_at) }}
            </template>

            <template #cell(completed)="data">
              {{ formatTimestamp(data.item.completed_at) }}
            </template>

            <template #cell(runtime)="data">
              {{ formatRuntime(data.item.queued_at, data.item.completed_at) }}
            </template>

            <template #cell(actions)="data">
              <b-button
                variant="outline-info"
                size="sm"
                @click="viewJobDetails(data.item)"
              >
                Details
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
  name: "TenantJobs",
  props: {
    tenantId: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      tenant: null,
      jobs: [],
      loading: false,
      selectedStatus: "",
      statusOptions: [
        { value: "", text: "All Statuses" },
        { value: "pending", text: "Pending" },
        { value: "completed", text: "Completed" },
        { value: "failed", text: "Failed" },
        { value: "exception", text: "Exception" },
      ],
      tableFields: [
        { key: "id", label: "Job ID" },
        { key: "name", label: "Name" },
        { key: "queue", label: "Queue" },
        { key: "status", label: "Status" },
        { key: "queued", label: "Queued" },
        { key: "completed", label: "Completed" },
        { key: "runtime", label: "Runtime" },
        { key: "actions", label: "Actions" },
      ],
    };
  },
  mounted() {
    this.loadTenantInfo();
    this.loadTenantJobs();
  },
  methods: {
    async loadTenantInfo() {
      try {
        const { data } = await ProcessMaker.apiClient.get("/tenant-queues/tenants");
        this.tenant = data.find((t) => t.id.toString() === this.tenantId);
      } catch (error) {
        console.error("Error loading tenant info:", error);
      }
    },
    async loadTenantJobs() {
      this.loading = true;
      try {
        const params = this.selectedStatus ? { status: this.selectedStatus } : {};
        const { data } = await ProcessMaker.apiClient.get(`/tenant-queues/${this.tenantId}/jobs`, { params });
        this.jobs = data.jobs || [];
      } catch (error) {
        console.error("Error loading tenant jobs:", error);
        this.jobs = [];
        this.$bvToast.toast("Error loading tenant jobs", {
          title: "Error",
          variant: "danger",
          solid: true,
        });
      } finally {
        this.loading = false;
      }
    },
    onStatusChange() {
      this.loadTenantJobs();
    },
    viewJobDetails(job) {
      this.$router.push(`/tenant/${this.tenantId}/jobs/${job.id}`);
    },
    async clearTenantJobs() {
      const confirmed = await this.$bvModal.msgBoxConfirm(
        "Are you sure you want to clear all job data for this tenant?",
        {
          title: "Confirm Action",
          size: "sm",
          buttonSize: "sm",
          okVariant: "danger",
          okTitle: "Yes",
          cancelTitle: "No",
          footerClass: "p-2",
          hideHeaderClose: false,
          centered: true,
        }
      );

      if (confirmed) {
        try {
          const { data } = await ProcessMaker.apiClient.delete(`/tenant-queues/${this.tenantId}/clear`);

          if (data.message) {
            this.$bvToast.toast(data.message, {
              title: "Success",
              variant: "success",
              solid: true,
            });
            this.loadTenantJobs();
          } else {
            this.$bvToast.toast("Error clearing tenant data", {
              title: "Error",
              variant: "danger",
              solid: true,
            });
          }
        } catch (error) {
          console.error("Error clearing tenant jobs:", error);
          this.$bvToast.toast("Error clearing tenant data", {
            title: "Error",
            variant: "danger",
            solid: true,
          });
        }
      }
    },
    getStatusVariant(status) {
      const variants = {
        pending: "warning",
        completed: "success",
        failed: "danger",
        exception: "secondary",
      };
      return variants[status] || "light";
    },
    getStatusText(status) {
      const texts = {
        pending: "Pending",
        completed: "Completed",
        failed: "Failed",
        exception: "Exception",
      };
      return texts[status] || status;
    },
    formatTimestamp(timestamp) {
      if (!timestamp) return "-";
      return new Date(timestamp * 1000).toLocaleString();
    },
    /**
     * Format the runtime of a job.
     * @param {number} createdAt - The timestamp of the job creation.
     * @param {number} completedAt - The timestamp of the job completion.
     * @returns {string} The formatted runtime.
     */
    formatRuntime(queuedAt, completedAt) {
      console.log(queuedAt, completedAt);
      if (!queuedAt || !completedAt) return "-";
      const runtime = completedAt - queuedAt;
      return `${runtime.toFixed(2)}s`;
    },
  },
};
</script>
