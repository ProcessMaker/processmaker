<script setup>
import { ref } from "vue";
import { getRequest } from "../variables";

const requestFile = ref(getRequest());
const fileManager = () => window.ProcessMaker.caseFileManager;

</script>

<template>
  <component
    :is="fileManager()"
    v-if="requestFile.id"
    :process-request-id="requestFile.id"
  />
</template>

<style scoped>
</style>
