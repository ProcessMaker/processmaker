<template>
  <div class="page-container">
    <BundleSidebar v-model="activeSection" model-value="summary" />
    <router-view></router-view>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import BundleSidebar from './BundleSidebar.vue';

const activeSection = ref('summary');

</script>

<style lang="scss" scoped>
.page-container {
display: flex;
}
</style>
