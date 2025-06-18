<template>
  <b-modal
    :visible="show"
    title="Job Details"
    size="lg"
    @close="$emit('close')"
    @hide="$emit('close')"
  >
    <div v-if="job">
      <div class="row">
        <div class="col-md-6">
          <strong>Job ID:</strong> {{ job.id }}<br>
          <strong>Name:</strong> {{ job.name }}<br>
          <strong>Queue:</strong> {{ job.queue }}<br>
          <strong>Status:</strong>
          <b-badge :variant="getStatusVariant(job.status)">
            {{ getStatusText(job.status) }}
          </b-badge>
        </div>
        <div class="col-md-6">
          <strong>Tenant ID:</strong> {{ job.tenant_id }}<br>
          <strong>Attempts:</strong> {{ job.attempts }}<br>
          <strong>Timestamp:</strong> {{ formatTimestamp(job.timestamp) }}
        </div>
      </div>
      <hr>
      <h6>Job Data</h6>
      <pre class="bg-light p-3">{{ JSON.stringify(job, null, 2) }}</pre>
    </div>
  </b-modal>
</template>

<script>
export default {
  name: "JobDetailsModal",
  props: {
    show: {
      type: Boolean,
      default: false,
    },
    job: {
      type: Object,
      default: null,
    },
  },
  methods: {
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
  },
};
</script>
