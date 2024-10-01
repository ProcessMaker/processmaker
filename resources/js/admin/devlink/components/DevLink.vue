
<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';

const router = useRouter();
const route = useRoute();
</script>

<template>
  <div>
    <b-tabs>
        <b-tab
          :title="$t('Linked Instances')"
          :active="route.name === 'index' || route.name === 'instance' || route.name === 'assets' || route.name === 'asset-listing'"
          @click="router.push({ name: 'index' })"
        />
        <b-tab
          :title="$t('Local Bundles')"
          :active="route.name === 'local-bundles'"
          @click="router.push({ name: 'local-bundles' })"
        />
        <b-tab
          :title="$t('Shared Assets')"
          :active="route.name === 'assets-shared'"
          @click="router.push({ name: 'assets-shared' })"
        />
    </b-tabs>

    <b-card>
      <router-view></router-view>
    </b-card>
  </div>
</template>