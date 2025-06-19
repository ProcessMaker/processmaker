<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <b-card>
          <template #header>
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">
                Job Details
              </h5>
              <div>
                <b-button
                  variant="outline-secondary"
                  size="sm"
                  @click="$router.go(-1)"
                >
                  <b-icon icon="arrow-left" />
                  Back
                </b-button>
              </div>
            </div>
          </template>

          <div
            v-if="loading"
            class="text-center py-4"
          >
            <b-spinner class="align-middle" />
            <strong class="ml-2">Loading job details...</strong>
          </div>

          <div
            v-else-if="error"
            class="text-center py-4"
          >
            <b-alert
              variant="danger"
              show
            >
              <h6>Error Loading Job Details</h6>
              <p>{{ error }}</p>
              <b-button
                variant="outline-danger"
                @click="loadJobDetails"
              >
                Try Again
              </b-button>
            </b-alert>
          </div>

          <div
            v-else-if="job"
            class="row"
          >
            <div class="col-md-6">
              <h6>Job Information</h6>
              <dl class="row">
                <dt class="col-sm-4">
                  Job ID:
                </dt>
                <dd class="col-sm-8">
                  <code>{{ job.id }}</code>
                </dd>

                <dt class="col-sm-4">
                  Name:
                </dt>
                <dd class="col-sm-8">
                  {{ job.name }}
                </dd>

                <dt class="col-sm-4">
                  Queue:
                </dt>
                <dd class="col-sm-8">
                  {{ job.queue }}
                </dd>

                <dt class="col-sm-4">
                  Status:
                </dt>
                <dd class="col-sm-8">
                  <b-badge :variant="getStatusVariant(job.status)">
                    {{ getStatusText(job.status) }}
                  </b-badge>
                </dd>
              </dl>
            </div>

            <div class="col-md-6">
              <h6>Additional Information</h6>
              <dl class="row">
                <dt class="col-sm-4">
                  Tenant ID:
                </dt>
                <dd class="col-sm-8">
                  {{ job.tenant_id }}
                </dd>

                <dt class="col-sm-4">
                  Attempts:
                </dt>
                <dd class="col-sm-8">
                  {{ job.attempts }}
                </dd>

                <dt class="col-sm-4">
                  Queued At:
                </dt>
                <dd class="col-sm-8">
                  {{ formatTimestamp(job.queued_at) }}
                </dd>

                <dt class="col-sm-4">
                  Completed At:
                </dt>
                <dd class="col-sm-8">
                  {{ formatTimestamp(job.completed_at) }}
                </dd>
              </dl>
            </div>

            <div class="col-12 mt-4">
              <h6>Job Data</h6>
              <pre class="bg-light p-3 border rounded">{{ getJobData() }}</pre>
            </div>
          </div>
        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "JobDetails",
  props: {
    tenantId: {
      type: String,
      required: true,
    },
    jobId: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      job: null,
      loading: true,
      error: null,
    };
  },
  mounted() {
    this.loadJobDetails();
  },
  methods: {
    async loadJobDetails() {
      this.loading = true;
      this.error = null;

      try {
        const { data } = await ProcessMaker.apiClient.get(`/tenant-queues/${this.tenantId}/jobs/${this.jobId}`);
        this.job = data;
      } catch (error) {
        console.error("Error loading job details:", error);
        this.error = error.response?.data?.message || error.message || "Failed to load job details";
      } finally {
        this.loading = false;
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
    getJobData() {
      console.log(this.job);
      return JSON.stringify(this.job.payload, null, 2);
    },
  },
};
</script>
