<template>
  <div class="pm-inbox-rule d-flex">

    <div class="pl-3">
      <NewInboxRule>
      </NewInboxRule>
    </div>

    <div class="pl-3">
      <h4>Self Service1</h4>
      <PMSearchBar>
      </PMSearchBar>
      <PMTable>
      </PMTable>
    </div>

  </div>
</template>

<script>
  import NewInboxRule from "./NewInboxRule.vue";
  import PMTable from "../../components/PMTable.vue";
  import PMSearchBar from "../../components/PMSearchBar.vue";
  export default {
    components: {
      NewInboxRule,
      PMSearchBar,
      PMTable
    },
    props: {
    },
    data() {
      return {
      };
    },
    mounted() {
    },
    methods: {
    }
  };
</script>