<template>
  <div>
    <h4 class="ml-3">
      {{ $t('Inbox Rules') }}
    </h4>
    <b-tabs
      class="m-3"
      content-class="p-3 pm-tab-content"
    >
      <b-tab
        :title="$t('Rules')"
        active
        data-cy="rules"
        @click="$refs.inboxRules.requestData()"
      >
        <InboxRules ref="inboxRules" />
      </b-tab>
      <b-tab
        :title="$t('Execution Log')"
        data-cy="executionLog"
        @click="$refs.executionLog.load()"
      >
        <ExecutionLog ref="executionLog" />
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
import InboxRules from "./InboxRules.vue";
import ExecutionLog from "./ExecutionLog.vue";

export default {
  components: {
    InboxRules,
    ExecutionLog,
  },
};
</script>

<style>
  .pm-tab-content {
    border-top: 0px;
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    border-left: 1px solid #dee2e6;
    background-color: white;
  }

  .title-style {
    font-size: 27px;
    color: #556271;
  }
</style>
