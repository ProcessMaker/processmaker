<template>
  <div class="pm-inbox-rule pr-3 pl-3">
    <h4>{{$t('New Inbox Rule')}}</h4>
    <div class="d-flex">
      <PMPanelWithCustomHeader 
        class="filters"
        :title="$t('Step 1:') + ' ' + $t('Define the filtering criteria')">
        <template v-slot:header-right-content>
          <InboxRuleButtons
            @showColumns="showColumns"
            :show-saved-search-selector="showSavedSearchSelector"
            :saved-search-id="newSavedSearchIdFromSelector"
            @saved-search-id-changed="newSavedSearchIdFromSelector = $event"
            >
          </InboxRuleButtons>
        </template>
        <InboxRuleFilters
          v-if="inboxRule || isNew"
          ref="inboxRuleFilters"
          :saved-search-id="savedSearchIdForFilters"
          :task-id="newTaskId"
          @count="count = $event"
          :show-column-selector-button="false"
          @saved-search-data="savedSearchData = $event"
          >
        </InboxRuleFilters>
      </PMPanelWithCustomHeader>

      <PMPanelWithCustomHeader
        class="ml-3 actions"
        :title="$t('Step 2:') + ' ' + $t('Rule Configuration')">
        <InboxRuleEdit 
          :count="count" 
          :inbox-rule="inboxRule"
          :saved-search-data="savedSearchData"
          :new-task-id="newTaskId"
          >
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
  export default {
    components: {
      PMPanelWithCustomHeader,
      InboxRuleEdit,
      InboxRuleFilters,
      InboxRuleButtons
    },
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
        newSavedSearchIdFromSelector: null,
        savedSearchData: {},
      };
    },
    computed: {
      savedSearchIdForFilters() {
        // All existing inbox rules have a saved search id.
        // If this is a new inbox rule, we could have a saved search id or a process id and element id
        if (this.inboxRule) {
          return this.inboxRule.saved_search_id
        }
        if (this.newSavedSearchId) {
          return this.newSavedSearchId
        }
        if (this.newSavedSearchIdFromSelector) {
          return this.newSavedSearchIdFromSelector
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
      if (this.ruleId) {
        window.ProcessMaker.apiClient.get('/tasks/rules/' + this.ruleId)
                .then(response => {
                  this.inboxRule = response.data;
                });
      }
    },
    methods: {
      showColumns() {
        this.$refs.inboxRuleFilters.showColumns();
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