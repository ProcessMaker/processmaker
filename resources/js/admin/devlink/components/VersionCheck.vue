<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';

const router = useRouter();

const checkNewVersion = ref(false);

const props = defineProps({
  devLink: { type: Object },
});

const remote = () => {
  ProcessMaker.apiClient
    .get(`/devlink/${props.devLink.dev_link_id}/remote-version/${props.devLink.remote_id}`)
    .then((response) => {
      if (Number(response.data.version) > Number(props.devLink.version)) {
        checkNewVersion.value = true;
      }
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
    v-if="checkNewVersion"
    class="badge badge-update"
  >
    Update Available
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
</style>
