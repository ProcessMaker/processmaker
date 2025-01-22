<template>
  <div class="inbox-process-menu">
    <div class="menu-sections">
      <div class="divider-custom"></div>
      <div class="buttons-list-process">
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
          />
          <span :id="`title-${process.id}`" class="title-process">
            {{ process.name }}
          </span>
        </button>
      </div>

      <div class="divider-custom"></div>

      <div class="buttons-list-dashboard">
        <button
          v-for="dashboard in dashboards"
          :key="dashboard.id"
          class="menu-btn"
          @click="openProcessDashboard(dashboard.id, 'dashboard')"
        >
          <i class="fp-tachometer-alt-average"></i>
          <span :id="`dashboard-${dashboard.id}`" class="title-dashboard">
            {{ dashboard.title }}
          </span>
        </button>
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
      dashboards: [],
      screen: null,
      formData: null,
    };
  },
  mounted() {
    this.loadProcesses();
    this.loadDashboards();
  },
  methods: {
    openProcessDashboard(id, type) {
      const router = this.$router || this.$root.$router;

      if (type === "process") {
        router
          .push({
            name: "process-browser",
            params: { processId: id.toString() },
          })
          .catch((err) => {
            if (err.name !== "NavigationDuplicated") {
              throw err;
            }
          });
      } else {
        this.getDashboardViewScreen(id.toString());
      }

      this.$emit("processDashboardSelected", { id, type });
      this.$emit("menuItemSelected");
    },
    callDashboardViewScreen(id, screen, formData) {
      const router = this.$router || this.$root.$router;
      router
        .push({
          name: "dashboard",
          params: {
            dashboardId: id.toString(),
            screen: screen,
            formData: formData,
          },
        })
        .catch((err) => {
          if (err.name !== "NavigationDuplicated") {
            throw err;
          }
        });
    },
    loadProcesses() {
      const url = this.buildURL();

      ProcessMaker.apiClient.get(url).then((response) => {
        this.processesList = this.processesList.concat(response.data.data);
      });
    },
    loadDashboards() {
      const url = this.buildURLDashboards();

      ProcessMaker.apiClient.get(url).then((response) => {
        this.dashboards = this.dashboards.concat(response.data);
      });
    },
    /**
     * Build URL for Process Cards
     */
    buildURL() {
      return `process_bookmarks/processes?
        &pmql=${encodeURIComponent(this.pmql)}
        &bookmark=true
        &launchpad=true
        &order_by=name&order_direction=asc
        &include=user,categories,category`;
    },
    buildURLDashboards() {
      return `/dynamic-ui/custom-dashboards`;
    },
    buildURLDashboardsScreen(id) {
      return `/dynamic-ui/custom-dashboards/screen/${id}`;
    },
    getIconProcess(process) {
      let icon = "Default Icon";
      const unparseProperties = process?.launchpad?.properties || null;
      if (unparseProperties !== null) {
        icon = JSON.parse(unparseProperties)?.icon || "Default Icon";
      }

      return `/img/launchpad-images/icons/${icon}.svg`;
    },
    getDashboardViewScreen(id) {
      const url = this.buildURLDashboardsScreen(id);

      ProcessMaker.apiClient.get(url).then((response) => {
        this.screen = response.data.screen;
        this.formData = response.data.formData;
        this.callDashboardViewScreen(id, this.screen, this.formData);
      });
    },
  },
};
</script>

<style scoped>
.inbox-process-menu {
  padding: 1rem;
  border-radius: 4px;
}

.menu-sections {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.buttons-list-process {
  display: flex;
  flex-direction: column;
  height: 350px;
  overflow-y: auto;
}

.buttons-list-dashboard {
  padding-left: 15px;
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
  background-color: #e4edf3;
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

.title-dashboard {
  margin-left: 10px;
  font-size: 15px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: inline-block;
  max-width: 200px;
}

.divider-custom {
  border-top: 2px solid #dee2e6;
  margin: 0;
}
</style>
