<template>
  <div>
    <div class="title-style ml-3">{{$t('Inbox Rules')}}</div>
    <b-tabs class="m-3" content-class="p-3 pm-tab-content">
      <b-tab :title="$t('Rules')" @click="$refs.inboxRules.requestData()"
             active>
        <InboxRules ref="inboxRules"></InboxRules>
      </b-tab>
      <b-tab :title="$t('Execution Log')" @click="$refs.executionLog.load()">
        <ExecutionLog ref="executionLog"></ExecutionLog>
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
      ExecutionLog
    }
  }
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