<template>
    <div class="container">
        <b-row>
            <b-col cols="3" class="border-right">
                <sidebar-navigation ref="sidebar-navigation" :processName="processName"></sidebar-navigation>
            </b-col>
            <b-col cols="6" class="data-container">
                <h2>Export Process: {{ processName }}</h2>
                <hr>
            </b-col>
      <b-col cols="3" />
        </b-row>
    </div>
</template>

<script>

import SidebarNavigation from "../../../components/shared/SidebarNavigation.vue";

export default {
    props: ["processName"],
    components: {
        SidebarNavigation,
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

.container {
    // max-width: 1600px;
    background-color: $light;
}

h2 {
  text-align: left;
}
</style>
