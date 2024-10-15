<script setup>
import { ref, getCurrentInstance, onMounted, defineProps } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import { store, loadInstance } from '../common';

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
    <a class="float-left pr-3" style="font-size: 1.5em;" href="#" @click.prevent="router.push({ name: 'index' })">
      <i class="fa fa-chevron-left" aria-hidden="true"></i>
    </a>
    <h3>
      {{ store.selectedInstance.name }}
    </h3>
    <b-tabs content-class="mt-3">
      <b-tab
        :active="route.name === 'instance'"
        @click="router.push({ name: 'instance' })"
        :title="$t('Bundles')">
      </b-tab>
      <b-tab
        :active="route.name === 'assets' || route.name === 'asset-listing'"
        @click="router.push({ name: 'assets' })"
        :title="$t('Assets')">
      </b-tab>
    </b-tabs>
  </div>
</template>