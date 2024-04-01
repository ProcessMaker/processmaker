<template>
  <div class="pm-inbox-rule pr-3 pl-3">
    <h4>{{$t('New Inbox Rule')}}</h4>
    <div class="d-flex">
      <PMPanelWithCustomHeader 
        v-if="isViewName(1)"
        class="filters"
        :title="$t('Step 1:') + ' ' + $t('Define the filtering criteria')">
        <template v-slot:header-right-content>
          <InboxRuleButtons
            :show-saved-search-selector="showSavedSearchSelector"
            :saved-search-id="newSavedSearchIdFromSelector"
            @saved-search-id-changed="newSavedSearchIdFromSelector = $event"
            @showColumns="showColumns"
            @reset-filters="resetFilters">
          </InboxRuleButtons>
        </template>
        <InboxRuleFilters
          v-if="inboxRule || isNew"
          ref="inboxRuleFilters"
          :saved-search-id="savedSearchIdForFilters"
          :task-id="taskId"
          :show-column-selector-button="false"
          @count="count = $event"
          @saved-search-data="savedSearchData = $event">
        </InboxRuleFilters>
      </PMPanelWithCustomHeader>

      <PMPanelWithCustomHeader 
        v-if="isViewName(2)"
        class="filters"
        :title="$t('Step 3:') + ' ' + $t('Enter form data')">
        <template v-slot:header-right-content>
          <div class="custom-button-container">
            <button
              type="button"
              class="button-actions"
              v-b-tooltip.hover title="Erase Draft"
              @click="eraseQuickFill()"
            >
              <img src="/img/smartinbox-images/eraser.svg" :alt="$t('No Image')">
              {{ $t('Clear Task') }}
            </button>
            <button
              type="button"
              v-b-tooltip.hover title="Use content from previous task to fill this one quickly."
              class="button-actions"
              @click="showQuickFillPreview = true"
            >
            <img
            src="/img/smartinbox-images/fill.svg"
            :alt="$t('No Image')"
            /> {{ $t('Quick Fill') }}
            </button>
          </div>
        </template>

        <InboxRuleFillData
          ref="inboxRuleFillData"
          :task-id="taskId"
          :inbox-rule-data="data"
          :prop-inbox-quick-fill="propInboxData"
          @data="data = $event"
          @submit="submitButton = $event">
        </InboxRuleFillData>
        <div>
        <splitpane-container v-if="showQuickFillPreview" :size="50">
          <quick-fill-preview
            class="quick-fill-preview"
            :task="task"
            :prop-from-button ="'inboxRules'"
            :prop-columns="columns"
            :prop-filters="filter"
            @close="showQuickFillPreview = false"
            @quick-fill-data-inbox="fillWithQuickFillData"
          ></quick-fill-preview>
           </splitpane-container>
        </div>
       

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
          @view-name="viewName($event)">
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
  import QuickFillPreview from "../../tasks/components/QuickFillPreview.vue";
  import SplitpaneContainer from "../../tasks/components/SplitpaneContainer.vue";
  import _ from "lodash";

  export default {
    components: {
      PMPanelWithCustomHeader,
      InboxRuleEdit,
      InboxRuleFilters,
      InboxRuleButtons,
      InboxRuleFillData,
      QuickFillPreview,
      SplitpaneContainer,
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
      },
      elementId: {
        type: String,
        default: null
      },
      processId: {
        type: Number,
        default: null
      },
    },
    data() {
      return {
        propInboxData: {},
        task: {},
        showQuickFillPreview: false,
        count: 0,
        inboxRule: null,
        newSavedSearchIdFromSelector: null,
        savedSearchData: {},
        taskId: null,
        data: {},
        submitButton: null,
        pmql: `(user_id = ${ProcessMaker.user.id} and status="Completed" and process_id=${this.processId})`,
        filter: {
          order: { by: 'created_at', direction: 'desc' },
          filters: [
            {
              subject: { type: "Field", value: "process_id" },
              operator: "=",
              value: this.processId,
            },
            {
              subject: { type: "Field", value: "element_id" },
              operator: "=",
              value: this.elementId,
            },
          ],
        },
        columns: [
          {
            label: "Case #",
            field: "case_number",
            filter_subject: {
              type: "Relationship",
              value: "processRequest.case_number",
            },
            order_column: "process_requests.case_number",
          },
          {
            label: "Case title",
            field: "case_title",
            name: "__slot:case_number",
            filter_subject: {
              type: "Relationship",
              value: "processRequest.case_title",
            },
            order_column: "process_requests.case_title",
          },
          {
            label: "Completed",
            field: "completed_at",
            format: "datetime",
            filter_subject: {
              type: "Field",
              value: "completed_at",
            },
          }
        ],
      };
    },
    computed: {
      rightPanelTitle() {
        if (this.view_name === 1) {
          return this.$t('Step 2:') + ' ' + this.$t('Rule Configuration');
        }
        if (this.view_name === 2) {
          return this.$t('Step 4:') + ' ' + this.$t('Submit Configuration');
        }
      },
      savedSearchIdForFilters() {
        // All existing inbox rules have a saved search id.
        // If this is a new inbox rule, we could have a saved search id or a process id and element id
        if (this.inboxRule) {
          return this.inboxRule.saved_search_id;
        }
        if (this.newSavedSearchId) {
          return this.newSavedSearchId;
        }
        if (this.newSavedSearchIdFromSelector) {
          return this.newSavedSearchIdFromSelector;
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
      this.task = { 
        "process_id" : this.processId,
        "element_id" : this.elementId,
        "id" : this.newTaskId,
      };
      if (this.newTaskId) {
        this.taskId = this.newTaskId;
      }
      if (this.ruleId) {
        ProcessMaker.apiClient.get('/tasks/rules/' + this.ruleId)
                .then(response => {
                  this.inboxRule = response.data;
                  this.data = this.inboxRule.data;
                  this.taskId = this.inboxRule.process_request_token_id;
                });
      }
    },
    methods: {
      eraseQuickFill() {
        this.propInboxData = {};
      },
      fillWithQuickFillData(data) {
        const message = this.$t('Task Filled succesfully');
        this.propInboxData = data;
        ProcessMaker.alert(message, 'success');
      },
      verifyURL(string) {
        const currentUrl = window.location.href;
        const isInUrl = currentUrl.includes(string);
        return isInUrl;
      },
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
  .button-actions {
    color: #556271;
    text-transform: capitalize;
    font-family: Open Sans;
    font-size: 15px;
    font-weight: 400;
    line-height: 24px;
    letter-spacing: -0.02em;
    text-align: left;
    border: 1px solid #CDDDEE;
    border-radius: 4px;
    box-shadow: 0px 0px 3px 0px #0000001a;
    background-color: white;
  }
  .button-actions:hover {
    color: #556271;
    background-color: #f3f5f8;
  }
  .custom-button-container {
    display: inline-block;
    padding: 0px;
    justify-content: center;
    align-items: center;
    vertical-align: unset;
  }
</style>