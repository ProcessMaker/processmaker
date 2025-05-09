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
import { ref, watch, reactive, toRefs, onMounted, computed, getCurrentInstance } from 'vue';

const props = defineProps({
  value: Object
});
const config = reactive({
  stage: {
    id: '',
    label: ''
  }
});
const defaultStages = ref([]);
const currentInstance = getCurrentInstance();

const loadStagesFromApi = () => {
  let stages = [
    { id: 1, label: 'Request Sent', selected: false },
    { id: 2, label: 'Request Reviewed', selected: true },
    { id: 3, label: 'Manager Reviewed', selected: false }
  ];
  stages.forEach(item => {
      defaultStages.value.push(item);
  });  
};

const changeStage = (stages) => {
    
};

onMounted(() => {
  console.log('onMounted');
  loadStagesFromApi();
});
</script>
