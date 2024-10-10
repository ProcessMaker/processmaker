<script setup>
import { ref, onMounted, onBeforeMount } from 'vue';
import { getRequestId } from "../variables";

const caseId = getRequestId();
const request = ref({});
const state = ref(0);
const fileManager = ref(window.ProcessMaker.caseFileManager);

const getRequest = () => {
  ProcessMaker.apiClient
    .get("parent-request-by-case", {
      params: {
        case_number: caseId,
      }
    })
    .then((response) => {
      request.value = response.data;
      state.value += state.value + 1;
    });
};

onBeforeMount(() => {
  getRequest();
});

</script>

<template>
  <div>
    <component v-if="request.id" :is="fileManager" :process-request-id="request.id"></component>
  </div>
</template>

<style scoped>
</style>
