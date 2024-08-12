<template>
  <div :class="{'mt-3': !mobileApp}">
    <PMTabs v-show="!bTabsHide"
            ref="bTabs"
            v-model="activeTab"
            @input="onTabsInput"
            @changed="onTabsChanged">
      <b-tab v-if="mobileApp && item?.seeTabOnMobile===true"
             v-for="(item, index) in tabsList"
             :key="index"
             :title="formatItemName(item.name)"
             @hook:mounted="checkTabsMounted">
        <template v-if="item.type==='myCases'">
          <filter-mobile type="requests"
                         :outRef="$refs"
                         :outName="'listMobile'+index">
          </filter-mobile>
          <mobile-requests :ref="'listMobile'+index"
                           :filter="item.filter"
                           :process="process">
          </mobile-requests>
        </template>
        <template v-else-if="item.type==='myTasks'">
          <filter-mobile type="tasks"
                         :outRef="$refs"
                         :outName="'listMobile'+index">
          </filter-mobile>
          <mobile-tasks :ref="'listMobile'+index"
                        :filter="item.filter"
                        :process="process">
          </mobile-tasks>
        </template>
        <template v-else>
          <filter-mobile type="tasks"
                         :outRef="$refs"
                         :outName="'listMobile'+index">
          </filter-mobile>
          <mobile-tasks :ref="'listMobile'+index"
                        :filter="item.filter"
                        :process="process">
          </mobile-tasks>
        </template>
      </b-tab>
      <b-tab v-if="!mobileApp"
             v-for="(item, index) in tabsList"
             :key="index"
             :title="formatItemName(item.name)"
             @hook:mounted="checkTabsMounted">
        <PMSearchBar v-model="item.filter">
          <template v-slot:right-content>
            <TabOptions @onTabSettings="onTabSettings"
                         @onDelete="onDelete">
            </TabOptions>
          </template>
        </PMSearchBar>
        <template v-if="item.type==='myCases'">
          <requests-listing :ref="'list'+index"
                            :columns="item.columns"
                            :filter="item.filter"
                            :pmql="item.pmql"
                            :autosaveFilter="false"
                            :fetch-on-created="false"
                            no-results-message="launchpad"
                            :advancedFilterProp="advancedFilterProp">
          </requests-listing>
        </template>
        <template v-else-if="item.type==='myTasks'">
          <tasks-list :ref="'list'+index"
                      :columns="item.columns"
                      :filter="item.filter"
                      :pmql="item.pmql"
                      :autosaveFilter="false"
                      :fetch-on-created="false"
                      :disable-tooltip="false"
                      :disable-quick-fill-tooltip="false"
                      no-results-message="launchpad"
                      :advancedFilterProp="advancedFilterProp">
          </tasks-list>
        </template>
        <template v-else>
          <tasks-list :ref="'list'+index"
                      :columns="item.columns"
                      :filter="item.filter"
                      :pmql="item.pmql"
                      :autosaveFilter="false"
                      :fetch-on-created="false"
                      :saved-search="item.idSavedSearch"
                      no-results-message="launchpad"
                      :advancedFilterProp="item.advanced_filter"
                      @in-overdue="setInOverdueMessage">
          </tasks-list>
        </template>
      </b-tab>
      <template #tabs-end
                v-if="!mobileApp">
        <b-nav-item id="pt-b-nav-item-id"
                    role="presentation"
                    href="#">
          <b>+</b>
        </b-nav-item>
      </template>
    </PMTabs>
    <b-popover :ref="'tabCreate'"
               :target="'pt-b-nav-item-id'"
               :triggers="'click'"
               :container="''"
               :boundary="'viewport'"
               :placement="'bottom'"
               :custom-class="'pt-popover-body'"
               @shown="onShown">
      <CreateSavedSearchTab :ref="'createSavedSearchTab'"
                            :tabsList="tabsList"
                            @onCancel="onCancelCreateSavedSerchTab"
                            @onOk="onOkCreateSavedSerchTab">
      </CreateSavedSearchTab>
    </b-popover>
    <b-modal :ref="'tabSetting'"
             :title="$t('Tab Settings')"
             :button-size="'sm'"
             :centered="true"
             @ok="onOkModal">
      <CreateSavedSearchTab :ref="'tabSettingForm'"
                            :hideFormsButton="true"
                            :showOptionSeeTabOnMobile="true"
                            :tabsList="tabsList"
                            @onOk="onOkTabSetting">
      </CreateSavedSearchTab>
    </b-modal>
    <b-modal :ref="'tabDeletion'"
             :button-size="'sm'"
             :centered="true"
             :title="$t('Confirmation')"
             :ok-title="$t('Delete')"
             :ok-variant="'danger'"
             :cancel-title="$t('Cancel')"
             @ok="onOkDelete">
      {{ removalMessage }}
    </b-modal>
  </div>
</template>

<script>
  import RequestsListing from "../../requests/components/RequestsListing.vue";
  import TasksList from "../../tasks/components/TasksList.vue";
  import CreateSavedSearchTab from "./CreateSavedSearchTab.vue";
  import PMTabs from "../../components/PMTabs.vue";
  import PMSearchBar from "../../components/PMSearchBar.vue";
  import TabOptions from "./TabOptions.vue";
  import FilterMobile from "../../Mobile/FilterMobile.vue";
  import MobileRequests from "../../requests/components/MobileRequests.vue";
  import MobileTasks from "../../tasks/components/MobileTasks.vue";
  export default {
    components: {
      RequestsListing,
      TasksList,
      CreateSavedSearchTab,
      PMTabs,
      PMSearchBar,
      TabOptions,
      FilterMobile,
      MobileRequests,
      MobileTasks
    },
    props: {
      currentUser: {
        type: Object
      },
      process: {
        type: Object
      }
    },
    data() {
      return {
        tabsList: [{
            type: "myCases",
            name: this.$t("My Cases"),
            filter: "",
            pmql: `(user_id = ${ProcessMaker.user.id}) AND (process_id = ${this.process.id})`,
            advanced_filter: null,
            columns: [
              {
                label: "Case #",
                field: "case_number",
                sortable: true,
                default: true,
                width: 80
              },
              {
                label: "Case title",
                field: "case_title",
                sortable: true,
                default: true,
                truncate: true,
                width: 220
              },
              {
                label: "Status",
                field: "status",
                sortable: true,
                default: true,
                width: 100,
                filter_subject: {type: 'Status'}
              },
              {
                label: "Started",
                field: "initiated_at",
                format: "datetime",
                sortable: true,
                default: true,
                width: 160
              },
              {
                label: "Completed",
                field: "completed_at",
                format: "datetime",
                sortable: true,
                default: true,
                width: 160
              }
            ],
            seeTabOnMobile: true
          }, {
            type: "myTasks",
            name: this.$t("My Tasks"),
            filter: "",
            pmql: `(user_id = ${ProcessMaker.user.id}) AND (process_id = ${this.process.id})`,
            advanced_filter: null,
            columns: window.Processmaker.defaultColumns || [],
            seeTabOnMobile: true
          }
        ],
        bTabsHide: false,
        advancedFilterProp: {
          filters: [
            {
              subject: {type: "Status"},
              operator: "=",
              value: "In Progress",
              _column_field: "status",
              _column_label: "Status"
            }
          ]
        },
        activeTab: 0,
        selectTab: "",
        removalMessage: "",
        mobileApp: window.ProcessMaker.mobileApp
      };
    },
    mounted() {
      this.requestTabConfiguration();
      this.verifyTabsLength();
    },
    methods: {
      onTabsInput(activeTabIndex) {
        this.$nextTick(() => {
          if (this.$refs["list" + activeTabIndex] &&
                  this.$refs["list" + activeTabIndex][0]) {
            this.$refs["list" + activeTabIndex][0].fetch();
          }
        });
      },
      onTabsChanged() {
        if (this.selectTab === "last") {
          let index = this.$refs.bTabs.getTabs().length;
          this.activeTab = index - 1;
        }
      },
      onCancelCreateSavedSerchTab() {
        this.$refs.tabCreate.$emit("close");
      },
      onOkCreateSavedSerchTab(tab) {
        this.selectTab = "last";
        this.$refs.tabCreate.$emit("close");
        this.tabsList.push(tab);
        this.saveTabConfiguration();
      },
      onOkModal(evt) {
        evt.preventDefault();
        this.$refs.tabSettingForm.onOk();
      },
      onOkTabSetting(tab) {
        this.$set(this.tabsList, this.activeTab, tab);
        this.saveTabConfiguration();
        this.$nextTick(() => {
          this.$refs.tabSetting.hide();
        });
      },
      onTabSettings() {
        this.$refs.tabSetting.show();
        this.$refs.tabSetting.$nextTick(() => {
          this.$refs.tabSettingForm.set(this.tabsList[this.activeTab], this.activeTab);
        });
      },
      onDelete() {
        let tabName = {name: this.tabsList[this.activeTab].name};
        this.removalMessage = this.$t("Do you want to delete the tab {{name}}?", tabName);
        this.$refs.tabDeletion.show();
      },
      onOkDelete() {
        if (this.tabsList.length > 1) {
          this.tabsList.splice(this.activeTab, 1);
          this.saveTabConfiguration();
        }
      },
      setInOverdueMessage() {
        return "";
      },
      onShown() {
        this.closeOnBlur();
      },
      closeOnBlur() {
        let area = this.$refs.createSavedSearchTab.$el.parentNode;
        area.addEventListener('mouseenter', () => {
          window.removeEventListener('click', this.onCancelCreateSavedSerchTab);
        });
        area.addEventListener('mouseleave', () => {
          window.addEventListener('click', this.onCancelCreateSavedSerchTab);
        });
      },
      setDragDrop() {
        this.$refs.bTabs.getButtons().forEach((button, index) => {
          let el = button.$el;
          el.setAttribute('draggable', true);
          el.ondragstart = (event) => {
            event.dataTransfer.setData("text/plain", index);
          };
          el.ondragover = (event) => {
            event.preventDefault();
          };
          el.ondrop = (event) => {
            event.preventDefault();
            let fromIndex = event.dataTransfer.getData("text/plain");
            if (!/^\d+$/.test(fromIndex)) {
              return;
            }
            fromIndex = parseInt(fromIndex);
            if (fromIndex === index) {
              return;
            }
            let movedTab = this.tabsList.splice(fromIndex, 1)[0];
            this.tabsList.splice(index, 0, movedTab);
            this.saveTabConfiguration();
            this.$nextTick(() => {
              this.activeTab = index;
              this.onTabsInput(index);
            });
          };
        });
      },
      checkTabsMounted() {
        this.$nextTick(() => {
          this.setDragDrop();
        });
      },
      requestTabConfiguration() {
        if (this.process.launchpad) {
          let properties = JSON.parse(this.process.launchpad.properties);
          if ("tabs" in properties && properties.tabs.length > 0) {
            this.tabsList = properties.tabs;
          }
        }
        this.onTabsInput(0);
      },
      saveTabConfiguration() {
        let properties = {};
        if (this.process.launchpad) {
          properties = JSON.parse(this.process.launchpad.properties);
          properties.tabs = this.tabsList;
        } else {
          //If launchpad does not exist, this conversion is necessary. We need to 
          //review this behavior of the inherited methods.
          properties.tabs = this.tabsList;
          properties = JSON.stringify(properties);
        }
        ProcessMaker.apiClient
                .put(`process_launchpad/${this.process.id}`, {
                  properties: properties
                })
                .then((response) => {
                  ProcessMaker.alert(this.$t("The launchpad settings were saved."), "success");
                })
                .catch((error) => {
                  ProcessMaker.alert(this.$t("The launchpad settings could not be saved due to an error."), "danger");
                });
      },
      formatItemName(string) {
        if (string.length > 25) {
          string = string.slice(0, 25) + "...";
        }
        return string;
      },
      verifyTabsLength() {
        this.$nextTick(() => {
          if (this.mobileApp && this.$refs.bTabs.getTabs().length === 0) {
            this.bTabsHide = true;
          }
        });
      }
    }
  };
</script>

<style>
  .pt-popover-body .popover-body{
    padding: 0.5rem 0.75rem !important;
  }
</style>
<style scoped>
  .popover{
    max-width: 375px;
  }
</style>
