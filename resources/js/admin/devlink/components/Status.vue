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
    <template v-if="status === 'ok'">✅</template>
    <template v-if="status === 'error'">❌</template>
  </div>
</template>