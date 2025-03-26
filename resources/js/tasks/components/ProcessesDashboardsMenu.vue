<template>
  <div class="inbox-process-menu">
    <div class="menu-sections">
      <div class="divider-custom">
        <span class="divider-text">{{ $t("Processes") }}</span>
        <div class="divider-line"></div>
      </div>
      <div class="buttons-list-process">
        <button
          v-for="process in processesList"
          :key="process.id"
          class="menu-btn"
          :class="{
            selected:
              selectedItem.id === process.id && selectedItem.type === 'process',
          }"
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

      <div class="divider-custom">
        <span class="divider-text">{{ $t("Dashboards") }}</span>
        <div class="divider-line"></div>
      </div>

      <div class="buttons-list-dashboard">
        <button
          v-for="dashboard in dashboards"
          :key="dashboard.id"
          class="menu-btn"
          :class="{
            selected:
              selectedItem.id === dashboard.id &&
              selectedItem.type === 'dashboard',
          }"
          @click="openProcessDashboard(dashboard.id, 'dashboard')"
        >
          <img
            class="icon-size"
            :src="`/img/launchpad-images/icons/Launchpad.svg`"
            :alt="$t('No Image')"
          />
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
      selectedItem: {
        id: JSON.parse(sessionStorage.getItem("selectedMenuItem"))?.id || null,
        type: JSON.parse(sessionStorage.getItem("selectedMenuItem"))?.type || null,
      },
    };
  },
  mounted() {
    // Get the selected item from sessionStorage
    const savedItem = sessionStorage.getItem("selectedMenuItem");
    if (savedItem) {
      const { id, type } = JSON.parse(savedItem);
      this.openProcessDashboard(id, type);
    }

    this.loadProcesses();
    this.loadDashboards();
  },
  methods: {
    openProcessDashboard(id, type) {
      this.selectedItem = { id, type };
      // Save the state for selected button in sessionStorage
      sessionStorage.setItem("selectedMenuItem", JSON.stringify({ id, type }));

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
      return `process_bookmarks/processes/menu?
        &pmql=${encodeURIComponent(this.pmql)}
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
        if (!response.data.dashboard) {
          this.clearSelection();
          this.$emit('get-all-tasks');
          return;
        }
        this.screen = response.data.screen;
        this.formData = response.data.formData;

        // Saved in sessionStorage (data is deleted when the browser is closed)
        sessionStorage.setItem(
          "dashboard_screen",
          JSON.stringify(response.data.screen)
        );
        sessionStorage.setItem(
          "dashboard_formData",
          JSON.stringify(response.data.formData)
        );

        this.callDashboardViewScreen(id, this.screen, this.formData);
      });
    },
    clearSelection() {
      this.selectedItem = { id: null, type: null };
      sessionStorage.removeItem("selectedMenuItem");
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
  flex-direction: column;
  max-height: calc(100vh - 650px);
  min-height: 80px;
  overflow-y: auto;
  padding-left: 15px;
}

.menu-btn {
  border: none;
  border-radius: 8px;
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
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: flex-end;
  margin: 0;
}

.divider-text {
  font-size: 13px;
  color: #959595;
  white-space: nowrap;
  font-weight: 600;
  line-height: 24px;
}

.divider-line {
  flex-grow: 1;
  height: 2px;
  background-color: #dee2e6;
}

.icon-size {
  width: 20px;
  height: 15px;
}

.menu-btn.selected {
  background-color: #e4edf3;
  border-radius: 8px;
}

.menu-btn.selected .title-process,
.menu-btn.selected .title-dashboard {
  color: #1472c2;
  font-weight: 700;
  font-size: 14px;
  line-height: 19px;
}
</style>
