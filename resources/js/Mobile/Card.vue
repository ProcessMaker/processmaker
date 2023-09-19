<template>
  <b-card class="m-3">
    <b-card-text>
      <b-row>
        <b-col cols="9">
          <span class="titleInfo">
            <template v-if="type === 'tasks'">
              {{ item.element_name }}
            </template>
            <template v-if="type === 'requests'">
              {{ item.element_name }}
            </template>
          </span>
        </b-col>
        <b-col
          cols="3"
          class="align-left"
        >
          <span>
            #{{ item.id }}
          </span>
        </b-col>
      </b-row>
      <div class="mt-3 mb-3">
        <template v-if="type === 'tasks'">
          <b-link href="">
            #{{ item.process_request.id }}
            {{ item.process.name }}
          </b-link>
        </template>
        <template v-if="type === 'requests'">
          <span class="dateInfo">
            {{ $t("Started") }}: {{ item.initiated_at }}
          </span>
        </template>
      </div>
      <b-row align-h="between">
        <b-col cols="4">
          <b-badge
            pill
            variant="custom"
          >
            {{ showDate(item) }}
          </b-badge>
        </b-col>
        <b-col
          v-if="type === 'requests'"
          cols="4"
        >
          {{ item.id }}
        </b-col>
      </b-row>
    </b-card-text>
  </b-card>
</template>

<script>
export default {
  props: ["type", "item"],
  data() {
    return {
      colorStatus: "",
      labelStatus: "",
    };
  },
  mounted() {
    this.setStatus();
  },
  methods: {
    setStatus() {
      if (this.type === "requests") {
        this.colorRequest();
      }
      if (this.type === "tasks") {
        this.colorTasks();
      }
    },
    colorRequest() {
      switch (this.item.status) {
        case "DRAFT":
          this.colorStatus = "danger";
          this.labelStatus = "Draft";
          break;
        case "CANCELED":
          this.colorStatus = "danger";
          this.labelStatus = "Canceled";
          break;
        case "COMPLETED":
          this.colorStatus = "primary";
          this.labelStatus = "Completed";
          break;
        case "ERROR":
          this.colorStatus = "danger";
          this.labelStatus = "Error";
          break;
        default:
          this.colorStatus = "warning";
          this.labelStatus = "In Progress";
          break;
      }
    },
    colorTasks() {
      const { status } = this.item;
      const isSelfService = this.item.is_self_service;
      if (status === "ACTIVE" && isSelfService) {
        this.colorStatus = "warning";
        this.labelStatus = "Self Service";
        return;
      }
      if (status === "ACTIVE") {
        this.colorStatus = "warning";
        this.labelStatus = "In Progress";
        return;
      }
      if (status === "CLOSED") {
        this.colorStatus = "primary";
        this.labelStatus = "Completed";
        return;
      }
      this.colorStatus = "secondary";
      this.labelStatus = "status";
    },
    showDate(item) {
      if (this.type === "tasks") {
        if (item.due_notified === 1) {
          return this.formatDate(item.due_at);
        }
        return this.formatDate(item.created_at);
      }
    },
    formatDate(value, format) {
      const formatDate = format || "";
      if (value) {
        return window.moment(value)
          .format(formatDate);
      }
      return "n/a";
    },
  },
};
</script>

<style scoped>
a,
.dateInfo {
  color: #57646F;
}
.titleInfo {
  color: #1572C2;
  font-size: 16px;
  font-style: normal;
  font-weight: 600;
  line-height: normal;
}
.badge-custom {
  color: #44494E;
  background-color: #FFF5DB;
}
.align-left {
  text-align: end;
}
</style>
