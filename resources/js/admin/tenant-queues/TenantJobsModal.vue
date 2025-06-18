<template>
  <b-modal
    :visible="show"
    :title="`Tenant Jobs - ${tenant ? tenant.name : ''}`"
    size="xl"
    @close="$emit('close')"
    @hide="$emit('close')"
  >
    <div class="row mb-3">
      <div class="col-md-6">
        <b-form-select
          v-model="selectedStatus"
          :options="statusOptions"
          @change="onStatusChange"
        />
      </div>
      <div class="col-md-6">
        <b-button
          variant="outline-danger"
          size="sm"
          @click="$emit('clear-tenant')"
        >
          Clear Tenant Data
        </b-button>
      </div>
    </div>

    <div class="table-responsive">
      <b-table
        :items="jobs"
        :fields="tableFields"
        :busy="loading"
        hover
        striped
        responsive
      >
        <template #table-busy>
          <div class="text-center">
            <b-spinner class="align-middle" />
            <strong>Loading...</strong>
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

        <template #cell(timestamp)="data">
          {{ formatTimestamp(data.item.timestamp) }}
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
    </div>
  </b-modal>
</template>

<script>
export default {
  name: "TenantJobsModal",
  props: {
    show: {
      type: Boolean,
      default: false,
    },
    tenant: {
      type: Object,
      default: null,
    },
    jobs: {
      type: Array,
      default: () => [],
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
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
        { key: "attempts", label: "Attempts" },
        { key: "timestamp", label: "Timestamp" },
        { key: "actions", label: "Actions" },
      ],
    };
  },
  methods: {
    onStatusChange() {
      this.$emit("refresh", this.tenant.id, this.selectedStatus);
    },
    viewJobDetails(job) {
      this.$emit("view-job-details", this.tenant.id, job.id);
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
  },
};
</script>
