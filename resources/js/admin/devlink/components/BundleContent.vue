<template>
  <div class="page-container">
    <BundleSidebar 
      :model-value="activeSection"
      @type-change="handleSectionChange" 
    />
    <router-view :key="$route.fullPath"></router-view>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import BundleSidebar from './BundleSidebar.vue';

const router = useRouter();
const route = useRoute();

// Convert the activeSection to a computed property
const activeSection = computed(() => {
  if (route.name === 'bundle-asset-listing' && route.params.type) {
    return route.params.type;
  }
  return 'summary';
});

const handleSectionChange = (newSection) => {
  const bundleId = route.params.id;
  
  const newRoute = newSection === 'summary' 
    ? { name: 'bundle-detail', params: { id: bundleId }}
    : { 
        name: 'bundle-asset-listing',
        params: { 
          id: bundleId,
          type: newSection
        }
      };

  if (route.name !== newRoute.name || 
      route.params.type !== newRoute.params.type) {
    router.push(newRoute);
  }
};
</script>

<style lang="scss" scoped>
.page-container {
  display: flex;
}
</style>
