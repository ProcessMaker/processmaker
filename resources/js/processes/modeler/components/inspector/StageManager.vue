<template>
  <div class="p-4 bg-white rounded shadow-md max-w-md">
    <b class="text-lg font-bold mb-2">{{ $t("Order of stages") }}</b>
    <p class="text-sm mb-4">
      {{ $t("Here you have all the stages already set in this process. Define the order you prefer:") }}
    </p>
    <StageList :initialStages="defaultStages" 
               @change="changeStage"
    ></StageList>
  </div>
</template>

<script setup>
import StageList from './StageList.vue';

const defaultStages = [
  { id: 1, label: 'Request Sent', selected: false },
  { id: 2, label: 'Request Reviewed', selected: true },
  { id: 3, label: 'Manager Reviewed', selected: false },
];

const changeStage = (stages) => {
    //trigger when stages change.

};
</script>
