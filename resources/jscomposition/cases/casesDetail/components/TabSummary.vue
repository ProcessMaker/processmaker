<template>
  <div
    class="tw-flex tw-w-full">
    <component
      :is="screens[request.status]"
      :request="request" />
  </div>
</template>

<script setup>
import { ref } from "vue";
import { getRequest } from "../variables";
import ActiveSummary from "./summary/ActiveSummary.vue";
import CompletedSummary from "./summary/CompletedSummary.vue";
import CanceledSummary from "./summary/CanceledSummary.vue";

const screens = {
  ACTIVE: ActiveSummary,
  COMPLETED: CompletedSummary,
  CANCELED: CanceledSummary,
};

/**
 * USE CASES
 * 1. If the request is in progress, has not request detail screen, show default message "Request In Progress"
 * 2. If the request is completed, without any screens in tasks, show the message "No Data Found"
 * 3. If the request is completed, with any screens in tasks, show the data table
 * 4. If the request is canceled, has not cancel screen, without any screens in tasks, show the message "No Data Found"
 * 5. If the request is canceled, has not cancel screen, with any screens in tasks, show the data table
 * 6. If the request is completed, has end event screen, show end event screen
 * 7. If the request is in progress, has request detail screen, show request detail screen
 * 8. If the request is canceled, has cancel screen, show cancel screen
 */

const request = ref(getRequest());
</script>
