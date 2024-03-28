<template>
  <div class="pm-inbox-rule pr-3 pl-3">
    <h4>{{$t('New Inbox Rule')}}</h4>
    <div class="d-flex">
      <PMPanelWithCustomHeader 
        v-if="viewIs('main')"
        class="filters"
        :title="$t('Step 1:') + ' ' + $t('Define the filtering criteria')">
        <template v-slot:header-right-content>
          <InboxRuleButtons
            :show-saved-search-selector="showSavedSearchSelector"
            :saved-search-id="savedSearchIdSelected"
            @saved-search-id-changed="savedSearchIdSelected = $event"
            @showColumns="showColumns"
            @reset-filters="resetFilters">
          </InboxRuleButtons>
        </template>
        <InboxRuleFilters
          ref="inboxRuleFilters"
          :savedSearchId="getSavedSearchId"
          :taskId="taskId"
          :show-column-selector-button="false"
          @count="count = $event"
          @saved-search-data="savedSearchData = $event">
        </InboxRuleFilters>
      </PMPanelWithCustomHeader>

      <PMPanelWithCustomHeader 
        v-if="viewIs('nextConfiguration')"
        class="filters"
        :title="$t('Step 3:') + ' ' + $t('Enter form data')">
        <template v-slot:header-right-content>
          <b-button size="sm" @click="resetData">{{ $t('Reset Data') }}</b-button>
        </template>

        <InboxRuleFillData
          ref="inboxRuleFillData"
          :task-id="taskId"
          :inbox-rule-data="data"
          @data="data = $event"
          @submit="submitButton = $event">
        </InboxRuleFillData>

      </PMPanelWithCustomHeader>

      <PMPanelWithCustomHeader
        class="ml-3 actions"
        :title="rightPanelTitle">
        <InboxRuleEdit 
          :count="count" 
          :inbox-rule="inboxRule"
          :saved-search-data="savedSearchData"
          :task-id="taskId"
          :data="data"
          :select-submit-button="submitButton"
          @onChangeViews="viewsTo($event)">
        </InboxRuleEdit>
      </PMPanelWithCustomHeader>
    </div>
  </div>
</template>

<script>
  import PMPanelWithCustomHeader from "../../components/PMPanelWithCustomHeader.vue";
  import InboxRuleEdit from "./InboxRuleEdit.vue";
  import InboxRuleFilters from "./InboxRuleFilters.vue";
  import InboxRuleButtons from "./InboxRuleButtons.vue";
  import InboxRuleFillData from "./InboxRuleFillData.vue";
  import IsViewMixin from "./IsViewMixin.js";
  export default {
    components: {
      PMPanelWithCustomHeader,
      InboxRuleEdit,
      InboxRuleFilters,
      InboxRuleButtons,
      InboxRuleFillData
    },
    mixins: [IsViewMixin],
    props: {
      newSavedSearchId: {
        type: Number,
        default: null
      },
      newTaskId: {
        type: Number,
        default: null
      },
      ruleId: {
        type: Number,
        default: null
      }
    },
    data() {
      return {
        count: 0,
        inboxRule: null,
        savedSearchIdSelected: null,
        savedSearchData: {},
        taskId: null,
        data: {},
        submitButton: null
      };
    },
    computed: {
      rightPanelTitle() {
        if (this.viewIs('main')) {
          return this.$t('Step 2:') + ' ' + this.$t('Rule Configuration');
        }
        if (this.viewIs('nextConfiguration')) {
          return this.$t('Step 4:') + ' ' + this.$t('Submit Configuration');
        }
      },
      getSavedSearchId() {
        // All existing inbox rules have a saved search id.
        // If this is a new inbox rule, we could have a saved search id or a process id and element id
        if (this.inboxRule) {
          return this.inboxRule.saved_search_id;
        }
        if (this.newSavedSearchId) {
          return this.newSavedSearchId;
        }
        if (this.savedSearchIdSelected) {
          return this.savedSearchIdSelected;
        }
        return null;
      },
      isNew() {
        return !this.ruleId;
      },
      showSavedSearchSelector() {
        return this.isNew && !this.newSavedSearchId && !this.taskId;
      }
    },
    mounted() {
      if (this.newTaskId) {
        this.taskId = this.newTaskId;
      }
      if (this.ruleId) {
        ProcessMaker.apiClient.get('/tasks/rules/' + this.ruleId)
                .then(response => {
                  this.inboxRule = response.data;
                  this.submitButton = this.inboxRule.submit_button;
                  this.data = this.inboxRule.data;
                  this.taskId = this.inboxRule.process_request_token_id;
                });
      }
    },
    methods: {
      showColumns() {
        this.$refs.inboxRuleFilters.showColumns();
      },
      resetFilters() {
        this.$refs.inboxRuleFilters.resetFilters();
      },
      resetData() {
        this.data = null;
        this.$refs.inboxRuleFillData.reload();
      }
    },
    watch: {
      inboxRule: {
        deep: true,
        handler() {
        }
      }
    }
  };
</script>

<style scoped>
  .filters {
    flex-grow: 1;
    width: 50%;
  }
  .actions {
    width: 400px;
  }
</style>