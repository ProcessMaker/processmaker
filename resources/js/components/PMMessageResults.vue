<!--
When importing this component, you must also import the following mix:
./resources/js/components/common/mixins/apiDataLoading.js
-->
<template>
  <DataLoading v-show="shouldShowLoader"
               :for="new RegExp(baseURL)"
               :data-loading-id="dataLoadingId"
               empty-icon="noTasks">
    <template v-slot:no-results-title>
      <span></span>
    </template>
    <template v-slot:no-results-message>
      <b>
        {{ message }}
      </b>
      <span class="pm-message-description">
        {{ description }}  
      </span>
    </template>
  </DataLoading>
</template>

<script>
  import DataLoading from "./common/DataLoading.vue";
  export default{
    components: {DataLoading},
    props: {
      message: {
        type: String,
        default: ""
      },
      description: {
        type: String,
        default: ""
      },
      baseURL: {
        type: String,
        default: ""
      },
      shouldShowLoader: null,
      dataLoadingId: null
    }
  };
</script>

<style scoped>
  .pm-message-description{
    font-size: 12px;
  }
</style>