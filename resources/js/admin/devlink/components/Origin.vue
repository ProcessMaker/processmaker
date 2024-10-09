<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import { store } from '../common';

const router = useRouter();

const props = defineProps({
  devLink: { type: Object }, 
});

const select = (devlink) => {
  store.selectedInstance = devlink;
  router.push({ name: 'instance', params: { id: devlink.id } });
}

</script>

<template>
  <div>
    <a v-if="props.devLink" href="#" @click.prevent="select(props.devLink)">{{ props.devLink.name }}</a>
    <span v-if="props.devLink === null">Local</span>
  </div>
</template>