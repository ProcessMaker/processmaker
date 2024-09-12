<template>
    <div class="tw-w-full tw-space-y-4">
        <Breadcrums />

        <AppCounters class="tw-w-full" v-model="countersData" @change="onChangeCounter" />

        <RouterView :key="route.fullPath" />
    </div>
</template>
<script>
import { defineComponent, ref, onMounted } from 'vue';
import AppCounters from './components/AppCounters.vue';
import { formatCounters } from './utils/counters';
import { getCounters } from './api';
import { Breadcrums } from '../../system';
import { useRouter, useRoute } from 'vue-router/composables';


export default defineComponent({
    components: {
        AppCounters,
        Breadcrums
    },
    setup() {
        const countersData = ref([])
        const router = useRouter();
        const route = useRoute();

        const onChangeCounter = (counter) => {
            if (typeof counter.url == "function") {
                return counter.url();
            }

            router.push({ path: counter.url }).catch((e) => { })
        }

        onMounted(async () => {
            const resCounters = await getCounters();
            countersData.value = formatCounters(resCounters);
        })

        return {
            countersData,
            route,
            onChangeCounter
        };
    }
});
</script>