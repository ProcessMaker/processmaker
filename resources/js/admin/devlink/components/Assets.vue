<script setup>
import { ref, onMounted } from 'vue';
import InstanceTabs from './InstanceTabs.vue';
import { useRouter, useRoute } from 'vue-router/composables';
import types from './assetTypes';

const router = useRouter();

const navigate = (typeConfig) => {
  router.push({ name: 'asset-listing', params: { type: typeConfig.type } });
}

</script>

<template>
  <div>
    <instance-tabs />
    <div v-for="(type, index) in types" :key="index">
      type: {{ type.type }}
      <a
        href="#"
        @click.prevent="navigate(type)"
      >
        {{ type.name }}
      </a>
    </div>
  </div>
</template>

<style scoped>
</style>