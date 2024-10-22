<template>
  <div class="tw-w-full tw-space-y-4 tw-flex-col tw-flex tw-grow">
    <Breadcrums :pages="pages" />

    <div
      class="tw-mx-4 tw-p-4 tw-bg-white tw-rounded-2xl
      tw-border-gray-200 tw-border tw-space-y-4 tw-flex
      tw-flex-col tw-overflow-hidden tw-grow tw-shadow-md">
      <AppCounters
        :data="countersData"
        :active="indexCounter"
        class="tw-w-full"
        @change="onChangeCounter" />
      <RouterView :key="route.fullPath" />
    </div>
  </div>
</template>
<script setup>
import {
  ref, onMounted, watch, onUnmounted,
} from "vue";
import { useRouter, useRoute } from "vue-router/composables";
import AppCounters from "./components/AppCounters.vue";
import { formatCounters } from "./utils/counters";
import { getCounters } from "./api";
import { Breadcrums } from "../../system";
import { configHomeBreadcrum } from "../../config/index";
import { user } from "./variables";

const countersData = ref([]);
const router = useRouter();
const route = useRoute();
const pages = ref([]);
const indexCounter = ref(0);

const onChangeCounter = (counter) => {
  if (typeof counter.url === "function") {
    return counter.url();
  }

  router.push({ path: counter.url }).catch((e) => { });
};

const initCounters = async () => {
  const resCounters = await getCounters({
    params: {
      userId: user.id,
    },
  });

  countersData.value = formatCounters(resCounters);
};

const updateBreadcrum = () => {
  const index = indexCounter.value ?? 0;
  pages.value = [
    configHomeBreadcrum(),
    { name: "Cases", href: "/cases", current: false },
  ];

  pages.value.push({ name: countersData.value[index].header, current: true });
};

const updateCounter = () => {
  indexCounter.value = countersData.value.findIndex((counter) => counter.url === route.path) ?? 0;
};

const unwatchRoute = watch(() => route.path, () => {
  updateCounter();
  updateBreadcrum();
});

onUnmounted(() => {
  unwatchRoute();
});

onMounted(async () => {
  await initCounters();
  updateBreadcrum();
  updateCounter();
});

</script>
