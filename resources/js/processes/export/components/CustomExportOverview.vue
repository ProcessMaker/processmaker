<template>
    <div>
        <!-- TO DO: ADD IN COMPONENTS FOR PROCESS, SCRIPT, SCREENS, ETC. -->
        <component :is="processElements"></component>
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

