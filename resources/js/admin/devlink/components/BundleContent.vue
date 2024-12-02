<template>
  <div class="page-container">
    <BundleSidebar 
      :model-value="activeSection"
      @type-change="handleSectionChange" 
    />
    <router-view></router-view>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import BundleSidebar from './BundleSidebar.vue';

const router = useRouter();
const route = useRoute();
const activeSection = ref('summary');

const handleSectionChange = (newSection) => {

  activeSection.value = newSection;
  const bundleId = route.params.id;
  
  if (newSection === 'summary') {
    router.push({ name: 'bundle-detail', params: { id: bundleId }});
  } else {
    router.push({ 
      name: 'bundle-asset-listing',
      params: { 
        id: bundleId,
        type: newSection
      }
    });
  }
};
</script>

<style lang="scss" scoped>
.page-container {
display: flex;
}
</style>
