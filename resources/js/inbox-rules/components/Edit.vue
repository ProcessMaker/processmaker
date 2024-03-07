<template>
  <div class="pm-inbox-rule pr-3 pl-3">
    <h4>{{$t('New Inbox Rule')}}</h4>
    <div class="d-flex">
      <PMPanelWithCustomHeader class="filters"
                               :title="$t('Step1:') + ' ' + $t('Define the filtering criteria')">
        <template v-slot:header-right-content>
          <InboxRuleButtons>
          </InboxRuleButtons>
        </template>
        <InboxRuleFilters
          :saved-search-id="savedSearchId"
          :process-id="processId"
          :element-id="elementId"
          @count="count = $event"
          >
        </InboxRuleFilters>
      </PMPanelWithCustomHeader>
      <PMPanelWithCustomHeader class="ml-3 actions"
                               :title="$t('Step2:') + ' ' + $t('Rule Configuration')">
        <InboxRuleEdit :count="count">
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
      savedSearchId: {
        type: Number,
        default: null
      },
      processId: {
        type: Number,
        default: null
      },
      elementId: {
        type: String,
        default: null
      }
    },
    data() {
      return {
        count: 0
      };
    },
    mounted() {
    },
    methods: {
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