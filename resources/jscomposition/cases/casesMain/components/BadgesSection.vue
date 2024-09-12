<template>
    <div class="tw-flex tw-space-x-1">
        <Badge
            class="tw-text-xs"
            color="gray"
            v-for="(item, index) in data" :key="index">
            {{ item.label }}
        </Badge>
    </div>
</template>
<script>
import { Badge } from '../../../base/ui/index';
import { defineComponent, computed } from 'vue';

export default defineComponent({
    props: {
        value: {
            type: Array,
            default: () => []
        }
    },
    components: {
        Badge
    },
    setup(props, { emit }) {
        const data = computed(() => {
            return props.value
        })

        return {
            data
        };
    }
});
</script>