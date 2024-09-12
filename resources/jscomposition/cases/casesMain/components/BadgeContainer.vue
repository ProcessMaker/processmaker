<template>
    <div class="tw-flex tw-justify-center">
        <Badge color="green">
            In progress
        </Badge>
    </div>
</template>

<script>
import { Badge } from '../../../base/ui/index';
import { defineComponent } from 'vue';

export default defineComponent({
    components: {
        Badge
    },
    props: {
    },
    setup() {
        return {};
    }
})
</script>