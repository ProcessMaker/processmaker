<template>
  <div class="mt-3">
    <PMTabs ref="bTabs"
            v-model="activeTab"
            @input="onTabsInput"
            @changed="onTabsChanged">
      <b-tab v-for="(item, index) in tabsList"
             :key="index"
             :title="item.name"
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
                            :autosaveFilter="false">
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
                      :disable-quick-fill-tooltip="false">
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
                      @in-overdue="setInOverdueMessage">
          </tasks-list>
        </template>
      </b-tab>
      <template #tabs-end>
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
                            @onCancel="onCancelCreateSavedSerchTab"
                            @onOk="onOkCreateSavedSerchTab">
      </CreateSavedSearchTab>
    </b-popover>
    <b-modal :ref="'tabSetting'"
             :title="$t('Tab Settings')"
             :button-size="'sm'"
             :centered="true"
             @ok="$refs.tabSettingForm.onOk()">
      <CreateSavedSearchTab :ref="'tabSettingForm'"
                            :hideFormsButton="true"
                            :showOptionSeeTabOnMobile="true"
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
export default {
  components: {
    RequestsListing,
    TasksList,
    CreateSavedSearchTab,
    PMTabs,
    PMSearchBar,
    TabOptions
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
          ]
        }, {
          type: "myTasks",
          name: this.$t("My Tasks"),
          filter: "",
          pmql: `(user_id = ${ProcessMaker.user.id}) AND (process_id = ${this.process.id})`,
          columns: window.Processmaker.defaultColumns || null
        }
      ],
      activeTab: 0,
      selectTab: "",
      removalMessage: ""
    };
  },
  mounted() {
    this.requestTabConfiguration();
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
    onOkTabSetting(tab) {
      this.$set(this.tabsList, this.activeTab, tab);
      this.saveTabConfiguration();
    },
    onTabSettings() {
      this.$refs.tabSetting.show();
      this.$refs.tabSetting.$nextTick(() => {
        this.$refs.tabSettingForm.set(this.tabsList[this.activeTab]);
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
          this.$nextTick(() => {
            this.activeTab = index;
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
      let properties = JSON.parse(this.process.launchpad.properties);
      if ("tabs" in properties) {
        this.tabsList = properties.tabs;
      }
    },
    saveTabConfiguration() {
      this.process.properties.tabs = this.tabsList;
      ProcessMaker.apiClient
              .put(`process_launchpad/${this.process.id}`, {
                properties: this.process.properties
              })
              .then((response) => {
                ProcessMaker.alert(this.$t("The launchpad settings were saved."), "success");
              })
              .catch((error) => {
                ProcessMaker.alert(this.$t("The launchpad settings could not be saved due to an error."), "danger");
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
