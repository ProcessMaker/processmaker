<template>
  <div class="mt-3">
    <PMTabs ref="bTabs"
            v-model="activeTab"
            @input="onTabsInput"
            @changed="onTabsChanged">
      <b-tab :title="$t('My Cases')"
             active>
        <PMSearchBar v-model="filter">
          <template v-slot:right-content>
            <TabOptions @onTabSettings="onTabSettings"
                         @onDelete="onDelete">
            </TabOptions>
          </template>
        </PMSearchBar>
        <requests-listing ref="requestList"
                          :filter="filterRequest"
                          :columns="columnsRequest"
                          :pmql="fullPmqlRequest"
                          :autosaveFilter="false">
        </requests-listing>
      </b-tab>

      <b-tab :title="$t('My Tasks')">
        <PMSearchBar v-model="filter">
          <template v-slot:right-content>
            <TabOptions @onTabSettings="onTabSettings"
                         @onDelete="onDelete">
            </TabOptions>
          </template>
        </PMSearchBar>
        <tasks-list ref="taskList"
                    :filter="filterTask"
                    :pmql="fullPmqlTask" 
                    :columns="columnsTask"
                    :disable-tooltip="false"
                    :disable-quick-fill-tooltip="false"
                    :fetch-on-created="false"
                    :autosaveFilter="false">
        </tasks-list>
      </b-tab>

      <b-tab v-for="(item, index) in tabsList"
             :key="index"
             :title="item.name">
        <PMSearchBar v-model="filter">
          <template v-slot:right-content>
            <TabOptions @onTabSettings="onTabSettings"
                         @onDelete="onDelete">
            </TabOptions>
          </template>
        </PMSearchBar>
        <tasks-list :filter="''"
                    :pmql="''"
                    :columns="[]"
                    @in-overdue="setInOverdueMessage"
                    :saved-search="item.idSavedSearch">
        </tasks-list>
      </b-tab>

      <template #tabs-end>
        <b-nav-item id="pt-b-nav-item-id"
                    role="presentation"
                    href="#">
          <b>+</b>
        </b-nav-item>
      </template>
    </PMTabs>
    <b-popover ref="ptBPopover" 
               :target="'pt-b-nav-item-id'"
               :triggers="'click'"
               :container="''"
               :boundary="'viewport'"
               :placement="'bottom'"
               :custom-class="'pt-popover-body'"
               @shown="onShown">
      <CreateSavedSearchTab ref="ptCreateSavedSearchTab"
                            @onCancel="onCancelCreateSavedSerchTab"
                            @onOk="onOkCreateSavedSerchTab"
                            @onSelectedOption="onSelectedOptionCreateSavedSerchTab">
      </CreateSavedSearchTab>
    </b-popover>    
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
        type: Object,
      },
      process: {
        type: Object,
      },
    },
    data() {
      return {
        filterRequest: "",
        fullPmqlRequest: `(user_id = ${ProcessMaker.user.id}) AND (process_id = ${this.process.id})`,
        columnsRequest: [
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
        filterTask: "",
        fullPmqlTask: `(user_id = ${ProcessMaker.user.id}) AND (process_id = ${this.process.id})`,
        columnsTask: window.Processmaker.defaultColumns || null,
        tabsList: [],
        activeTab: 0,
        selectedSavedSearch: null,
        filter: ""
      };
    },
    watch: {
      filter() {
        console.log(this.filter);
      }
    },
    methods: {
      onTabsInput(activeTabIndex) {
        if (activeTabIndex === 1) {
          this.$nextTick(() => {
            if (this.$refs.taskList) {
              this.$refs.taskList.fetch();
            }
          });
        }
      },
      onTabsChanged() {
        let index = this.$refs.bTabs.getTabs().length;
        if (index > 2) {
          this.activeTab = index - 1;
        }
      },
      onCancelCreateSavedSerchTab() {
        this.$refs.ptBPopover.$emit("close");
      },
      onOkCreateSavedSerchTab(tab) {
        this.$refs.ptBPopover.$emit("close");
        console.log(tab)
        tab.meta = this.selectedSavedSearch;
        this.tabsList.push(tab);
      },
      onSelectedOptionCreateSavedSerchTab(option) {
        this.selectedSavedSearch = option.meta;
      },
      onTabSettings() {
      },
      onDelete() {
      },
      deleteTab() {
        if (this.activeTab >= 2) {
          this.tabsList.splice(this.activeTab - 2, 1);
        }
      },
      setInOverdueMessage() {
        return "";
      },
      onShown() {
        this.closeOnBlur();
      },
      closeOnBlur() {
        let area = this.$refs.ptCreateSavedSearchTab.$el.parentNode;
        area.addEventListener('mouseenter', () => {
          window.removeEventListener('click', this.onCancelCreateSavedSerchTab);
        });
        area.addEventListener('mouseleave', () => {
          window.addEventListener('click', this.onCancelCreateSavedSerchTab);
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
