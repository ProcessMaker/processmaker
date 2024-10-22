<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  devlink: { type: Object, required: true }, 
});

let status = ref('loading');

onMounted(() => {
  window.ProcessMaker.apiClient.get(`/devlink/${props.devlink.id}/ping`).then((result) => {
    if (result.status === 200 && result.data.status === "ok") {
      status.value = "ok";
    } else {
      status.value = "error";
    }
  }).catch((e) => {
    status.value = "error";
  });
});

const reconnect = () => {
  const params = {
    devlink_id: props.devlink.id,
    redirect_uri: props.devlink.redirect_uri,
  };
  window.location.href = `${props.devlink.url}/admin/devlink/oauth-client?${new URLSearchParams(params).toString()}`;
};
</script>

<template>
  <div>
    <b-spinner v-if="status === 'loading'"></b-spinner>
    <template v-if="status === 'ok'"><span class="badge badge-linked">{{ $t('Linked') }}</span></template>
    <template v-if="status === 'error'">
      <span class="badge badge-not-available">{{ $t('Not available') }}</span>
      <b-button variant="primary" size="sm" @click="reconnect">{{ $t('Reconnect') }}</b-button>
    </template>
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