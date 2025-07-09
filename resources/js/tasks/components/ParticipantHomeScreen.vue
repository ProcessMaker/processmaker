<template>
  <div
    class="process-catalog-main-participant"
    id="tasks"
    :class="{ 'menu-open': showMenu }"
  >
    <div class="menu">
      <button 
        class="pl-3 menu-title button-class d-flex align-items-center" 
        :class="{ 'button-transparent': selectedProcess !== 'inbox', 'menu-title-inbox': selectedProcess !== 'inbox' }"
        @click="getAllTasks"
      >
        <i class="fp-inbox me-2" style="color: #2773f3; font-size: 20px;"></i>
        <span>{{ $t("Inbox") }}</span>
      </button>
      <ProcessesDashboardsMenu
        ref="processesDashboardsMenu"
        @processDashboardSelected="processDashboardSelected"
        @get-all-tasks="getAllTasks"
      />
    </div>

    <div class="slide-control">
      <a href="#" @click.prevent="hideMenu">
        <i
          class="fa"
          :class="{ 'fa-caret-right': !showMenu, 'fa-caret-left': showMenu }"
        ></i>
      </a>
    </div>

    <div ref="processInfo" class="home-screen-inbox">
      <div v-if="selectedProcess === 'inbox'">
        <div class="px-3 page-content mb-0">
          <div class="row">
            <div class="col" align="right">
              <b-alert
                v-if="inOverdueMessage.length > 0"
                class="align-middle"
                show
                variant="danger"
                v-cloak
                style="text-align: center"
                data-cy="tasks-alert"
              >
                {{ inOverdueMessage }}
              </b-alert>
            </div>
          </div>

          <div class="row">
            <div class="col-sm-12">
              <ul class="nav nav-tabs task-nav" id="requestTab" role="tablist">
                <li class="nav-item">
                  <a
                    class="nav-link task-nav-link"
                    id="inbox-tab"
                    :data-toggle="isDataLoading ? '' : 'tab'"
                    href="#"
                    role="tab"
                    aria-controls="inbox"
                    @click.prevent="!isDataLoading ? switchTab('inbox') : null"
                    aria-selected="true"
                    :class="{ active: tab === 'inbox' }"
                  >
                    {{ $t("Inbox") }}
                  </a>
                </li>
                <li class="nav-item">
                  <a
                    class="nav-link task-nav-link"
                    id="priority-tab"
                    :data-toggle="isDataLoading ? '' : 'tab'"
                    href="#"
                    role="tab"
                    aria-controls="inbox"
                    @click.prevent="
                      !isDataLoading ? switchTab('priority') : null
                    "
                    aria-selected="true"
                    :class="{ active: tab === 'priority' }"
                  >
                    {{ $t("Priority") }}
                  </a>
                </li>
                <li class="nav-item">
                  <a
                    class="nav-link task-nav-link"
                    id="drafts-tab"
                    :data-toggle="isDataLoading ? '' : 'tab'"
                    href="#"
                    role="tab"
                    aria-controls="inbox"
                    @click.prevent="!isDataLoading ? switchTab('draft') : null"
                    aria-selected="true"
                    :class="{ active: tab === 'draft' }"
                    v-if="taskDraftsEnabled"
                  >
                    {{ $t("Drafts") }}
                  </a>
                </li>
              </ul>

              <div class="tab-content" id="task-tabContent">
                <div
                  class="tab-pane fade show active"
                  id="inbox"
                  role="tabpanel"
                  aria-labelledby="inbox-tab"
                >
                  <div class="card card-body task-list-body">
                    <div id="search-bar" class="search advanced-search mb-2">
                      <div class="d-flex">
                        <div class="flex-grow-1">
                          <pmql-input
                            ref="pmql_input"
                            search-type="tasks"
                            :value="pmql"
                            :url-pmql="urlPmql"
                            :filters-value="pmql"
                            :ai-enabled="false"
                            :show-filters="true"
                            :aria-label="$t('Advanced Search (PMQL)')"
                            :param-status="status"
                            :permission="
                              userPermissions.hasPermissionsForUsersGroups
                            "
                            @submit="onNLQConversion"
                            @filterspmqlchange="onFiltersPmqlChange"
                          >
                            <template v-slot:left-buttons>
                              <div class="d-flex">
                                <div
                                  class="d-flex mr-1"
                                  v-for="addition in additions"
                                >
                                  <component
                                    class="d-flex"
                                    :is="addition"
                                    :permission="
                                      userPermissions.hasPermissionsForUsersGroups
                                    "
                                  ></component>
                                </div>
                              </div>
                            </template>

                            <template v-slot:right-buttons>
                              <b-button
                                id="idPopoverInboxRules"
                                class="ml-md-1 task-inbox-rules"
                                variant="primary"
                                @click="onInboxRules"
                              >
                                {{ $t("Inbox Rules") }}
                              </b-button>
                              <b-popover
                                target="idPopoverInboxRules"
                                triggers="hover focus"
                                placement="bottomleft"
                              >
                                <div class="task-inbox-rules-content">
                                  <div>
                                    <img
                                      src="/img/inbox-rule-suggest.svg"
                                      :alt="$t('Inbox Rules')"
                                    />
                                  </div>
                                  <span
                                    class="task-inbox-rules-content-text"
                                    v-html="
                                      $t(
                                        'Inbox Rules act as your personal task manager. You tell them what to look for, and they <strong>take care of things automatically.</strong>'
                                      )
                                    "
                                  >
                                  </span>
                                </div>
                              </b-popover>
                              <b-button
                                v-if="
                                  userPermissions.isAdministrator ||
                                  userPermissions.canEditScreens
                                "
                                class="ml-md-2"
                                :href="savedsearchDefaultsEditRoute"
                              >
                                <i class="fas fw fa-cog"></i>
                              </b-button>
                            </template>
                          </pmql-input>
                        </div>
                      </div>
                    </div>
                    <tasks-list
                      ref="taskList"
                      :filter="filter"
                      :pmql="fullPmql"
                      :columns="columns"
                      :disable-tooltip="false"
                      :disable-quick-fill-tooltip="false"
                      :fetch-on-created="false"
                      @in-overdue="setInOverdueMessage"
                      @data-loading="dataLoading"
                      @tab-count="handleTabCount"
                      @on-fetch-task="onFetchTask"
                      :show-recommendations="true"
                    ></tasks-list>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <router-view v-else :key="$route.fullPath"></router-view>
    </div>
  </div>
</template>

<script>
import ListMixin from "./ListMixin";
import TasksMixin from "../mixins/TasksMixin";
import TasksList from "./TasksList.vue";
import ProcessesDashboardsMenu from "./ProcessesDashboardsMenu.vue";
import router from "../router";

export default {
  name: "ParticipantHomeScreen",
  mixins: [TasksMixin, ListMixin],
  components: {
    TasksList,
    ProcessesDashboardsMenu,
  },
  router,
  props: {
    taskDraftsEnabled: {
      type: Boolean,
      required: true,
    },
    userFilter: {
      type: Object,
      required: true,
    },
    defaultColumns: {
      type: Array,
      required: true,
    },
    userConfiguration: {
      type: Object,
      default: () => ({}),
    },
    userPermissions: {
      type: Object,
      required: true,
    },
    savedsearchDefaultsEditRoute: {
      type: String,
      required: true,
    }
  },
  data() {
    return {
      showMenu: false,
      urlConfiguration: "users/configuration",
      localUserConfiguration: {},
      selectedProcess: window.Processmaker.selectedProcess,
    };
  },
  mounted() {
    this.defineUserConfiguration();
    this.callingTaskList();
  },
  methods: {
    callingTaskList() {
      this.$nextTick(() => {
        if (this.$refs.taskList) {
          this.$refs.taskList.fetch();
        }
      });
    },
    getAllTasks() {
      this.selectedProcess = "inbox";
      if (this.$route.name !== 'inbox') {
        this.$router.push({
          name: "inbox",
        });
      }
      this.$refs.processesDashboardsMenu?.clearSelection();
      this.callingTaskList();
    },
    processDashboardSelected(id, type) {
      this.selectedProcess = type;
      if (type === "process") {
        this.$router.push({
          name: "process-browser",
          query: { process: id },
        });
      } else if (type === "dashboard") {
        this.$router.push({
          name: "dashboard",
          query: { dashboard: id },
        });
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.has-search .form-control {
  padding-left: 2.375rem;
}

.has-search .form-control-feedback {
  position: absolute;
  z-index: 2;
  display: block;
  width: 2.375rem;
  height: 2.375rem;
  line-height: 2.375rem;
  text-align: center;
  pointer-events: none;
  color: #aaa;
}

.card-border {
  border-radius: 4px !important;
}

.card-size-header {
  width: 90px;
}

.option__image {
  width: 27px;
  height: 27px;
  border-radius: 50%;
}

.initials {
  display: inline-block;
  text-align: center;
  font-size: 12px;
  max-width: 25px;
  max-height: 25px;
  min-width: 25px;
  min-height: 25px;
  border-radius: 50%;
}
.task-nav {
  border-bottom: 0 !important;
}
.task-nav-link.active {
  color: #1572c2 !important;
  font-weight: 700;
  font-size: 15px;
}
.task-nav-link {
  color: #556271;
  font-weight: 400;
  font-size: 15px;
  border-top-left-radius: 5px !important;
  border-top-right-radius: 5px !important;
}
.task-list-body {
  border-radius: 5px;
}
.task-inbox-rules {
  width: max-content;
}
.task-inbox-rules-content {
  display: flex;
  justify-content: space-between;
  padding: 15px;
}
.task-inbox-rules-content-text {
  width: 310px;
  padding-left: 10px;
}
.popover {
  max-width: 450px;
}


@media (max-width: 639px) {
  .breadcrum-main {
    display: none;
  }
}

.process-catalog-main-participant {
  display: flex;

  @media (max-width: 639px) {
    display: block;
  }
}

.menu {
  left: -100%;
  height: calc(100vh - 145px);
  overflow: hidden;
  margin-top: 15px;
  transition: all 0.3s;
  flex: 0 0 0px;
  background-color: #f7f9fb;

  .menu-catalog {
    background-color: #f7f9fb;
    flex: 1;
    width: 315px;
    height: 95%;
    overflow-y: scroll;
  }

  @media (max-width: 639px) {
    position: absolute;
    z-index: 4;
    display: flex;
    margin-top: 0;
    width: 85%;
    transition: left 0.3s;
  }
}

.menu-mask {
  display: none;
  position: absolute;
  left: -100%;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0);
  z-index: 3;
  transition: background-color 0.3s;

  @media (max-width: 639px) {
    display: block;
  }
}

.menu-mask.menu-open {
  @media (max-width: 639px) {
    left: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: block;
  }
}

.menu-open .menu {
  left: 0;
  flex: 0 0 250px;
  @media (max-width: 639px) {
    left: 0%;
  }
}

.mobile-slide-close {
  display: none;
  padding-left: 10px;
  padding-top: 10px;
  @media (max-width: 639px) {
    display: block;
  }
}

.slide-control {
  border-left: 1px solid #dee0e1;
  margin-left: 10px;
  width: 29px;

  @media (max-width: 639px) {
    display: none;
  }

  a {
    position: relative;
    left: -11px;
    top: 40px;
    z-index: 5;

    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 60px;
    background-color: #ffffff;
    border-radius: 10px;
    border: 1px solid #dee0e1;
    color: #6a7888;
  }
}

.menu-open .slide-control {
  border-left: 1px solid #dee0e1;

  a {
    left: -11px;
    display: none;
  }
}

.slide-control:hover {
  border-left: 1px solid rgba(72, 145, 255, 0.4);
  box-shadow: -1px 0 0 rgba(72, 145, 255, 0.5);
}
.menu-open .slide-control:hover {
  border-left: 1px solid rgba(72, 145, 255, 0.4);
  box-shadow: -1px 0 0 rgba(72, 145, 255, 0.5);
  a {
    display: flex;
  }
}
.slide-control a:hover {
  background-color: #eaeef2;
}

.mobile-menu-control {
  display: none;
  color: #6a7887;
  font-size: 1.3em;
  margin-top: 10px;
  margin-left: 1em;
  margin-right: 1em;
  align-items: center;

  .menu-button {
    flex-grow: 1;
    i {
      margin-right: 3px;
    }
  }

  .bookmark-button {
    display: flex;
    padding: 10px;
    margin-right: 10px;
    font-size: 1.1em;
  }

  .search-button {
    display: flex;
    padding: 10px;
    font-size: 1.1em;
  }

  @media (max-width: 639px) {
    display: flex;
  }
}

.menu-title {
  color: #1472c2;
  font-size: 14px;
  font-style: normal;
  font-weight: 700;
  line-height: 19.07px;
  letter-spacing: -0.44px;
  display: block;
  width: 92%;
  margin-left: 15px;
  text-align: left;

  @media (max-width: 639px) {
    display: none;
  }
}
.menu-title-inbox {
  color: #4f606d;
  font-size: 14px;
  font-style: normal;
  font-weight: 500;
  line-height: 46.08px;
  letter-spacing: -0.44px;
  display: block;
  width: 92%;
  margin-left: 15px;
  text-align: left;

  @media (max-width: 639px) {
    display: none;
  }
}
.home-screen-inbox {
  width: 100%;
  margin-right: 0px;
  overflow-x: hidden;
  @media (max-width: 639px) {
    padding-left: 0;
  }
}

.button-class {
  background-color: #e4edf3;
  padding: 5px 15px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  height: 44px;
  gap: 10px;
}

.button-transparent {
  background-color: transparent;
}
</style>