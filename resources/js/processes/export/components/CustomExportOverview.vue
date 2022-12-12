<template>
    <div>
        <!-- TO DO: ADD IN COMPONENTS FOR PROCESS, SCRIPT, SCREENS, ETC. -->
        <component :is="processElements" :processName="processName"></component>
        <div class="pt-3 card-footer bg-light" align="right">
            <button type="button" class="btn btn-outline-secondary">
              {{ $t("Cancel") }}
            </button>
            <button type="button" class="btn btn-primary ml-2">
              {{ $t("Export") }}
            </button>
        </div>
    </div>
</template>

<script>

import ProcessesView from "./process-elements/ProcessesView.vue";

export default {
    props: ["processName"],
    components: {
        ProcessesView,
    },
    mixins: [],
    data() {
        return {
            processElements: "processes-view",
        }
    },
    methods: {      
    },
    mounted() {
    }
}
</script>

<style>

</style>

