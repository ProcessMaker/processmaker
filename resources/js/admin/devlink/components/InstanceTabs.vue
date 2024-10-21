<script setup>
import { ref, getCurrentInstance, onMounted, defineProps } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import { store, loadInstance } from '../common';
import PMTabs from "../../../components/PMTabs.vue";
import Header from './Header.vue';

loadInstance();

const props = defineProps({
  instance: Object
});

const vue = getCurrentInstance().proxy;
const route = useRoute();
const router = useRouter();

</script>

<template>
  <div>
    <Header back="index">{{ store.selectedInstance.name }}</Header>

    <PMTabs content-class="mt-3">
      <b-tab
        :active="route.name === 'instance'"
        @click="router.push({ name: 'instance' })"
        :title="$t('Bundles')">
        <slot name="bundles"></slot>
      </b-tab>
      <b-tab
        :active="route.name === 'assets' || route.name === 'asset-listing'"
        @click="router.push({ name: 'assets' })"
        :title="$t('Assets')">
        <slot name="assets"></slot>
      </b-tab>
    </PMTabs>
  </div>
</template>

<style lang="scss" scoped>
.pm-tabs-nav-class {
  background: #FFFFFF !important;
}
.pm-tabs-nav-link .nav-link {
  border-color: #FFFFFF !important;
}
</style>