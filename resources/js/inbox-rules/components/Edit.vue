<template>
  <div class="pm-inbox-rule d-flex">

    <div class="pl-3 filters">
      <InboxRuleFilters
        :saved-search-id="savedSearchId"
        :process-id="processId"
        :element-id="elementId"
        @count="count = $event"
      >
      </InboxRuleFilters>
    </div>

    <div class="pl-3 pr-3 actions">
      <NewInboxRule
        :count="count"
      >
      </NewInboxRule>
    </div>

  </div>
</template>

<script>
  import NewInboxRule from "./NewInboxRule.vue";
  import InboxRuleFilters from "./InboxRuleFilters.vue";
  export default {
    components: {
      NewInboxRule,
      InboxRuleFilters
    },
    props: {
      savedSearchId: {
        type: Number,
        default: null,
      },
      processId: {
        type: Number,
        default: null,
      },
      elementId: {
        type: String,
        default: null,
      }
    },
    data() {
      return {
        count: 0,
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