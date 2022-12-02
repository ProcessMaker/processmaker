<template>
    <div>
        <b-row>
            <b-col cols="4">
                <sidebar-navigation ref="sidebar-navigation" :processName="processName"></sidebar-navigation>
            </b-col>
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

<style>

</style>