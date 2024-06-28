<template>
  <div class="pt-container mt-3">
    <b-card no-body>
      <b-tabs ref="bTabs"
              v-model="activeTab"
              lazy
              @input="onTabsInput"
              @changed="onTabsChanged"
              nav-class="pl-2 pt-nav-class pt-nav-link"
              active-nav-item-class="font-weight-bold pt-nav-class"
              content-class="m-2">
        <b-tab :title="$t('My Cases')"
               active>
          <requests-listing ref="requestList"
                            :filter="filterRequest"
                            :columns="columnsRequest"
                            :pmql="fullPmqlRequest"
                            :autosaveFilter="false">
          </requests-listing>
        </b-tab>
        <b-tab :title="$t('My Tasks')">
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
          <SavedSearchTab :idSavedSearch="item.idSavedSearch">
          </SavedSearchTab>
        </b-tab>
        <template #tabs-end>
          <b-nav-item id="pt-b-nav-item-id"
                      role="presentation"
                      href="#">
            <b>+</b>
          </b-nav-item>
        </template>
      </b-tabs>
    </b-card>
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
  import RequestTab from "./RequestTab.vue";
  import TaskTab from "./TaskTab.vue";
  import RequestsListing from "../../requests/components/RequestsListing.vue";
  import TasksList from "../../tasks/components/TasksList.vue";
  import CreateSavedSearchTab from "./CreateSavedSearchTab.vue";
  import SavedSearchTab from "./SavedSearchTab.vue";
  export default {
    components: {
      RequestTab,
      TaskTab,
      RequestsListing,
      TasksList,
      CreateSavedSearchTab,
      SavedSearchTab
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
        selectedSavedSearch: null
      };
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
        let index = this.$refs.bTabs.tabs.length;
        if (index > 2) {
          this.activeTab = index - 1;
        }
      },
      onCancelCreateSavedSerchTab() {
        this.$refs.ptBPopover.$emit("close");
      },
      onOkCreateSavedSerchTab(tab) {
        this.$refs.ptBPopover.$emit("close");
        tab.meta = this.selectedSavedSearch;
        this.tabsList.push(tab);
      },
      onSelectedOptionCreateSavedSerchTab(option) {
        this.selectedSavedSearch = option.meta;
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
  .pt-container .card {
    border-radius: 0.5em;
  }
  .pt-popover-body .popover-body{
    padding: 0.5rem 0.75rem !important;
  }
  .pt-nav-class {
    background: #EBF1F7 !important;
    font-size: 15px;
    flex-wrap: nowrap;
    text-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    border-top-left-radius: 0.5em;
    border-top-right-radius: 0.5em;
  }
  .pt-nav-link .nav-link {
    border-color: #EBF1F7 !important;
    padding-top: 14px;
    padding-bottom: 16px;
  }
</style>
<style scoped>
  .popover{
    max-width: 375px;
  }
</style>
