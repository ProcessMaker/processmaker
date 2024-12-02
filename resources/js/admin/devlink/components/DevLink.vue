<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import PMTabs from "../../../components/PMTabs.vue";

const router = useRouter();
const route = useRoute();
</script>

<template>
  <div>
    <PMTabs>
        <b-tab
          :title="$t('Linked Instances')"
          :active="route.name === 'index' || route.name === 'instance' || route.name === 'assets' || route.name === 'asset-listing'"
          @click="router.push({ name: 'index' })"
          >
          <router-view></router-view>
        </b-tab>
        <b-tab
          :title="$t('Local Bundles')"
          :active="route.name === 'local-bundles' || route.name === 'bundle-assets' || route.name === 'bundle-detail' || route.name === 'bundle-asset-listing'"
          @click="router.push({ name: 'local-bundles' })"
          >
          <router-view></router-view>
        </b-tab>
        <b-tab
          :title="$t('Shared Assets')"
          :active="route.name === 'assets-shared'"
          @click="router.push({ name: 'assets-shared' })"
          >
          <router-view></router-view>
        </b-tab>
    </PMTabs>
  </div>
</template>

<style>
.pm-tabs-nav-class {
  background: #FFFFFF !important;
}
.pm-tabs-nav-link .nav-link {
  border-color: #FFFFFF !important;
}
</style>