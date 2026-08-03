<script setup>
import { ref, onMounted, defineEmits } from 'vue';

const emit = defineEmits(['updateAvailable', 'availabilityChanged']);

const checkNewVersion = ref(false);
const loading = ref(false);
const connectionAvailable = ref(null);

const props = defineProps({
  devLink: { type: Object },
});

const remote = () => {
  loading.value = true;
  ProcessMaker.apiClient
    .get(`/devlink/${props.devLink.dev_link_id}/remote-version/${props.devLink.remote_id}`)
    .then((response) => {
      if (response.data.available === false) {
        connectionAvailable.value = false;
        checkNewVersion.value = false;
        emit('updateAvailable', false);
        emit('availabilityChanged', false);
        return;
      }

      connectionAvailable.value = true;
      emit('availabilityChanged', true);
      if (Number(response.data.version) > Number(props.devLink.version)) {
        checkNewVersion.value = true;
        emit('updateAvailable', true);
      } else {
        checkNewVersion.value = false;
        emit('updateAvailable', false);
      }
    })
    .catch(() => {
      connectionAvailable.value = false;
      checkNewVersion.value = false;
      emit('updateAvailable', false);
      emit('availabilityChanged', false);
    })
    .finally(() => {
      loading.value = false;
    });
};

onMounted(() => {
  if (props.devLink.dev_link_id) {
    remote();
  }
});


</script>

<template>
  <span
    v-if="!loading && connectionAvailable === false"
    class="badge badge-unavailable"
  >
    {{ $t('Connection unavailable') }}
  </span>
  <span
    v-else-if="!loading && checkNewVersion"
    class="badge badge-update"
  >
    {{ $t('Update Available') }}
  </span>
</template>

<style scoped>
.badge-update {
    background-color: #D1F4D7;
    color: #06723A;
    font-size: 14px;
    font-weight: 500;
    border-radius: 6px;
    height: 24px;
}

.badge-unavailable {
    background-color: #F3F5F7;
    color: #596372;
    font-size: 14px;
    font-weight: 500;
    border-radius: 6px;
    height: 24px;
}
</style>
