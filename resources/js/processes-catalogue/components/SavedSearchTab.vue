<template>
  <div>
    <tasks-list :columns="columns"
                :pmql="pmql"
                :filter="filter"
                @in-overdue="setInOverdueMessage"
                :saved-search="idSavedSearch">
    </tasks-list>
  </div>
</template>

<script>
  import { TasksList } from "SharedComponents";
  export default {
    components: {
      TasksList
    },
    props: {
      idSavedSearch: {
        type: Number,
        default: null
      }
    },
    data() {
      return {
        filter: "",
        pmql: "",
        columns: []
      };
    },
    methods: {
      setInOverdueMessage() {
        return "";
      }
    }
  }
</script>
