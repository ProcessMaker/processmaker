<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  id: { type: Number, required: true }, 
});

let status = ref('loading');

onMounted(() => {
  window.ProcessMaker.apiClient.get(`/devlink/${props.id}/ping`).then((result) => {
    if (result.status === 200 && result.data.status === "ok") {
      status.value = "ok";
    } else {
      status.value = "error";
    }
  }).catch((e) => {
    status.value = "error";
  });
});
</script>

<template>
  <div>
    <b-spinner v-if="status === 'loading'"></b-spinner>
    <template v-if="status === 'ok'"><span class="badge badge-linked">Linked</span></template>
    <template v-if="status === 'error'"><span class="badge badge-not-available">Not available</span></template>
  </div>
</template>

<style scoped>
.badge-linked {
  background-color: #EAF2FF;
  border-radius: 6px;
  padding: 2px 6px;
  color: #1B54B4;
  font-weight: 500;
  font-size: 14px;
}
.badge-not-available {
  background-color: #F3F5F7;
  border-radius: 6px;
  padding: 2px 6px;
  color: #596372;
  font-weight: 500;
  font-size: 14px;
}
</style>