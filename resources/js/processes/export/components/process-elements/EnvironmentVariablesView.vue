<template>
       <div class="mb-2">
        <h2>Export Process: {{ processName }}</h2>
        <hr>
        <div>
            <h4>Environment Variables</h4>
            <div v-if="exportAll">
            <h6>Export Status:
                <b-badge
                pill
                variant="success"
                >
                <i class="fas fa-check-circle export-status-label" />
                Full Export
                </b-badge>
            </h6>
            <p><span class="font-weight-bold">All</span> Environment Variables will be included in this export.</p>
            </div>
            <div v-else>
            <h6>Export Status:
            <b-badge
                pill
                variant="warning"
                >
                <i class="fas fa-exclamation-triangle export-status-label" />
                Not Exporting
                </b-badge>
            </h6>
            <p>All Environment Variables will <span class="font-weight-bold">not</span> be included in this export.</p>
            </div>
        </div>
            <b-link @click="returnToSummary">Return to Summary</b-link>
        </div>
    </div>
</template>

<script>

export default {
    props: ["processName","exportAll"],
    components: {
    },
    mixins: [],
    data() {
        return {

        }
    },
    methods: {
        returnToSummary() {
            this.$emit("processesView");
        },
    mounted() {
    },
    }
}
</script>

<style>
.export-status-label {
    padding-right: 0;
}
</style>

