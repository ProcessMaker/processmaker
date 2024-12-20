<template>
  <div class="inbox-process-menu">
    <div class="menu-sections">
      <div class="process-section">
        <div class="buttons-list">
          <button
            v-for="process in processesList"
            :key="process.id"
            class="menu-btn"
            @click="openProcessDashboard(process.id, 'process')"
          >
            <img
              class="icon-process small-icon"
              :src="getIconProcess(process)"
              :alt="$t(labelIcon)"
            >
            <span
              :id="`title-${process.id}`"
              class="title-process"
            >
              {{ process.name }}
            </span>
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
      pmql: `user_id=${window.ProcessMaker.user.id}`,
      processesList: [],
      labelIcon: "Default Icon",
      dashboards: [
        { id: 1, name: "Dashboard 1" },
        { id: 2, name: "Dashboard 2" },
        { id: 3, name: "Dashboard 3" },
      ],
    };
  },
  mounted() {
    this.loadProcesses();
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
    loadProcesses(callback, message) {
      if(message === 'bookmark') {
        this.processesList = [];
      }
      const url = this.buildURL();
      console.log("url PDM", url);
      ProcessMaker.apiClient
        .get(url)
        .then((response) => {
          this.processesList = this.processesList.concat(response.data.data);
        });
    },
    /**
     * Build URL for Process Cards
     */
    buildURL() {
      return `process_bookmarks/processes?page=1`
        + `&per_page=100`
        + `&pmql=${encodeURIComponent(this.pmql)}`
        + "&bookmark=true"
        + "&launchpad=true"
        + "&order_by=name&order_direction=asc"
        + `&include=user,categories,category`;
    },
    getIconProcess(process) {
      let icon = "Default Icon";
      const unparseProperties = process?.launchpad?.properties || null;
      if (unparseProperties !== null) {
        icon = JSON.parse(unparseProperties)?.icon || "Default Icon";
      }

      return `/img/launchpad-images/icons/${icon}.svg`;
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
  height: 350px;
  overflow-y: auto;
}

.menu-btn {
  border: none;
  border-radius: 4px;
  background-color: transparent;
  cursor: pointer;
  text-align: left;
  width: 100%;
  display: flex;
  align-items: center;
  height: 45px;
}

.menu-btn:hover,
.menu-btn:active {
  background-color: #E4EDF3;
}

.divider {
  border-top: 1px solid #eee;
  margin: 1rem 0;
}

h4 {
  margin-bottom: 0.5rem;
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

.icon-process {
  transform: scale(0.5);
  flex-shrink: 0;
}

.title-process {
  font-size: 15px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: inline-block;
  max-width: 200px;
}
</style>
