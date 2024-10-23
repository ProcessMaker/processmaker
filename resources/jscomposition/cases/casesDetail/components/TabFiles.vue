<script setup>
import { ref } from 'vue';
import { getRequest } from "../variables";

const requestFile = ref(getRequest());
const fileManager = () => window.ProcessMaker.caseFileManager;

</script>

<template>
  <component v-if="requestFile.id" :is="fileManager()" :process-request-id="requestFile.id"></component>
</template>

<style scoped>
</style>
