<template>
    <div class="custom-export-container p-5">
        <b-row>
            <b-col cols="3" class="border-right">
                <sidebar-navigation ref="sidebar-navigation" :processName="processName"></sidebar-navigation>
            </b-col>
            <b-col cols="6" class="data-container">
                <custom-export-overview :processName="processName"></custom-export-overview>
            </b-col>
      <b-col cols="3" />
        </b-row>
    </div>
</template>

<script>

import CustomExportOverview from "./CustomExportOverview.vue";
import SidebarNavigation from "../../../components/shared/SidebarNavigation.vue";

export default {
    props: ["processName"],
    components: {
        SidebarNavigation,
        CustomExportOverview
    },
    mixins: [],
    data() {
        return {

        }
    },
    methods: {      
    },
    mounted() {
    }
}
</script>

<style lang="scss" scoped>
@import "../../../../sass/variables";

.custom-export-container {
    max-width: 1600px;
    margin: auto;
    background-color: $light;
}

h2 {
  text-align: left;
}
</style>
