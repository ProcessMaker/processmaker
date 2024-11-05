<template>
  <div class="tw-flex tw-flex-col tw-gap-2">
    <data-summary :summary="dataSummary" />
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { moment } from "moment";
import { getRequest } from "../variables";
import DataSummary from "../../../../js/requests/components/DataSummary.vue";

const dataSummary = computed(() => {
  const options = {};
  const request = getRequest();
  request.summary.forEach((option) => {
    if (option.type === "datetime") {
      options[option.key] = moment(option.value)
        .tz(window.ProcessMaker.user.timezone)
        .format("MM/DD/YYYY HH:mm");
    } else if (option.type === "date") {
      options[option.key] = moment(option.value)
        .tz(window.ProcessMaker.user.timezone)
        .format("MM/DD/YYYY");
    } else {
      options[option.key] = option.value;
    }
  });
  return options;
});
</script>
