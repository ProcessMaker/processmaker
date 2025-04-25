<template>
  <div class="tw-flex tw-flex-col tw-gap-4">
    <BaseCardButtonGroup :data="data" />
    <PercentageCardButtonGroup :data="percentageData" />
    <ArrowButtonGroup
      :data="arrowData"
      color="emerald" />
  </div>
</template>

<script setup>
import { ref } from "vue";
import BaseCardButtonGroup from "./ButtonGroup/BaseCardButtonGroup.vue";
import PercentageCardButtonGroup from "./PercentageButtonGroup/PercentageCardButtonGroup.vue";
import ArrowButtonGroup from "./ArrowButtonGroup/ArrowButtonGroup.vue";

const data = ref([
  {
    id: "1",
    header: "Max amount available",
    body: "Across 10 aplicants",
    icon: "fas fa-reply",
    content: "84K",
    active: true,
  },
  {
    id: "2",
    header: "Application awarded",
    body: "30% of all submitted",
    icon: "fas fa-user",
    content: "3",
    color: "amber",
    active: false,
  },
  {
    id: "3",
    header: "Total amount awarded",
    body: "Across 3 aplicants",
    icon: "fas fa-user",
    content: "46K+",
    color: "green",
    active: false,
  },
]);

const percentageData = ref([
  {
    id: "1",
    header: "Grants",
    body: "40%",
    percentage: 40,
    content: "28,678",
    color: "amber",
  },
  {
    id: "2",
    header: "Scholarships",
    body: "20%",
    percentage: 20,
    content: "4,678",
    color: "green",
  },
  {
    id: "3",
    header: "Loans",
    body: "15%",
    percentage: 15,
    content: "11,678",
    color: "blue",
  },
  {
    id: "4",
    header: "Out of pocket remaining",
    body: "25%",
    percentage: 25,
    content: "17,649",
    color: "red",
  },
]);

const arrowData = ref([
  {
    id: "1",
    header: "Grants",
    body: "40%",
    percentage: 40,
    content: "28,678",
    color: "amber",
  },
  {
    id: "2",
    header: "Scholarships",
    body: "20%",
    percentage: 20,
    content: "4,678",
    color: "green",
  },
  {
    id: "3",
    header: "Loans",
    body: "15%",
    percentage: 15,
    content: "11,678",
    color: "blue",
  },
  {
    id: "4",
    header: "Out of pocket remaining",
    body: "25%",
    percentage: 25,
    content: "17,649",
    color: "red",
  },
  {
    id: "4",
    header: "Out of pocket remaining",
    body: "25%",
    percentage: 25,
    content: "17,649",
    color: "red",
  },
  {
    id: "4",
    header: "Out of pocket remaining",
    body: "25%",
    percentage: 25,
    content: "17,649",
    color: "red",
  },
]);
</script>
