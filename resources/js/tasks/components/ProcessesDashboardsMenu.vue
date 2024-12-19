<template>
  <div class="inbox-process-menu">
    <div class="menu-sections">
      <div class="process-section">
        <h4>Processes</h4>
        <div class="buttons-list">
          <button
            v-for="process in processes"
            :key="process.id"
            class="menu-btn"
            @click="openProcessDashboard(process.id, 'process')"
          >
            <i class="fas fa-check"></i> {{ process.name }}
          </button>
        </div>
      </div>

      <div class="divider"></div>

      <div class="dashboard-section">
        <h4>Dashboards</h4>
        <div class="buttons-list">
          <button
            v-for="dashboard in dashboards"
            :key="dashboard.id"
            class="menu-btn"
            @click="openProcessDashboard(dashboard.id, 'dashboard')"
          >
            <i class="fas fa-check"></i> {{ dashboard.name }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ProcessesDashboardsMenu",
  data() {
    return {
      selectedProcess: null,
      // This section will be modified with dynamic data in future tickets
      processes: [
        { id: 1, name: "Process 1" },
        { id: 2, name: "Process 2" },
        { id: 3, name: "Process 3" },
        { id: 4, name: "Process 4" },
        { id: 5, name: "Process 5" },
        { id: 6, name: "Process 6" },
        { id: 7, name: "Process 7" },
        { id: 8, name: "Process 8" },
        { id: 9, name: "Process 9" },
      ],
      dashboards: [
        { id: 1, name: "Dashboard 1" },
        { id: 2, name: "Dashboard 2" },
        { id: 3, name: "Dashboard 3" },
      ],
    };
  },
  methods: {
    openProcessDashboard(id, type) {
      const router = this.$router || this.$root.$router;
      
      if (type === 'process') {
        router
          .push({
            name: "process-browser",
            params: { processId: id.toString() }
          })
          .catch((err) => {
            if (err.name !== "NavigationDuplicated") {
              throw err;
            }
          });
      } else {
        router
          .push({
            name: "dashboard",
            params: { dashboardId: id.toString() }
          })
          .catch((err) => {
            if (err.name !== "NavigationDuplicated") {
              throw err;
            }
          });
      }

      this.$emit("processDashboardSelected", { id, type });
    },
  },
};
</script>

<style scoped>
.inbox-process-menu {
  padding: 1rem;
  border: 1px solid #eee;
  border-radius: 4px;
}

.menu-sections {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.buttons-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  height: 250px;
  overflow-y: auto;
}

.menu-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  background-color: #f0f0f0;
  cursor: pointer;
  text-align: left;
  width: 100%;
}

.divider {
  border-top: 1px solid #eee;
  margin: 1rem 0;
}

h4 {
  margin-bottom: 0.5rem;
}

.menu-btn:hover {
  background-color: #e0e0e0;
}

.menu-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.selected-task {
  margin-top: 1rem;
  padding: 0.5rem;
  background-color: #f8f8f8;
  border-radius: 4px;
}
</style>
