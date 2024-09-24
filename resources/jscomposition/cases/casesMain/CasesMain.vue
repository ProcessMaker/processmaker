<template>
  <div class="tw-w-full tw-space-y-4 tw-flex-col tw-flex tw-grow">
    <Breadcrums :pages="pages" />

    <div
      class="tw-mx-4 tw-p-4 tw-bg-white tw-rounded-2xl
      tw-border-gray-200 tw-border tw-space-y-4 tw-flex
      tw-flex-col tw-overflow-hidden tw-grow tw-shadow-md">
      <AppCounters
        v-model="countersData"
        class="tw-w-full"
        @change="onChangeCounter" />
      <RouterView :key="route.fullPath" />
    </div>
  </div>
</template>
<script>
import { defineComponent, ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router/composables";
import AppCounters from "./components/AppCounters.vue";
import { formatCounters } from "./utils/counters";
import { getCounters } from "./api";
import { Breadcrums } from "../../system";
import { configHomeBreadcrum } from "../../config/index";

export default defineComponent({
  components: {
    AppCounters,
    Breadcrums,
  },
  setup() {
    const countersData = ref([]);
    const router = useRouter();
    const route = useRoute();
    const pages = ref([]);

    const onChangeCounter = (counter) => {
      if (typeof counter.url === "function") {
        return counter.url();
      }

      pages.value.pop();
      pages.value.push({ name: counter.header, current: true });

      router.push({ path: counter.url }).catch((e) => { });
    };

    const initCounters = async () => {
      let currentCounter = [];
      const resCounters = await getCounters();

      countersData.value = formatCounters(resCounters);
      currentCounter = countersData.value.find((counter) => counter.url === route.path) ?? countersData.value[0];

      currentCounter.active = true;
    };

    const initBreadcrums = () => {
      const currentCounter = countersData.value.find((e) => e.active) ?? countersData.value[0];
      pages.value = [
        configHomeBreadcrum(),
        { name: "Cases", href: "/cases", current: false },
      ];

      pages.value.push({ name: currentCounter.header, current: true });
    };

    onMounted(async () => {
      await initCounters();
      initBreadcrums();
    });

    return {
      countersData,
      route,
      onChangeCounter,
      pages,
    };
  },
});
</script>
