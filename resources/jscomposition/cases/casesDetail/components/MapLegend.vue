<template>
  <div
    id="map-legend"
    class="tw-absolute tw-top-12 tw-right-12 tw-rounded-md tw-shadow-md tw-bg-white tw-z-10"
  >
    <div class="tw-p-4 tw-flex tw-flex-col">
      <p
        v-for="(status, index) in statusLegend"
        :key="index"
        class="tw-font-bold tw-mb-2 tw-flex tw-items-center tw-gap-2"
      >
        <span
          :style="{ backgroundColor: status.color }"
          class="tw-inline-block tw-w-1 tw-h-8 tw-mr-4 tw-border-r-3 tw-border-r-gray-400 tw-transform tw-rotate-45"
        />
        {{ status.name }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const translate = ProcessMaker.i18n;

const statusLegend = ref(
  [
    {
      name: translate.t("In Progress"),
      color: "#3FA6FF",
    },
    {
      name: translate.t("Completed"),
      color: "#00BA7C",
    },
    {
      name: translate.t("Pending / Not Executed"),
      color: "#CCCCCC",
    },
  ],
);

</script>
