<template>
    <div class="mb-2">
        <hr>
        <div>
            <h4>{{ item.name }}</h4>
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
                <p><span class="font-weight-bold">All</span> Scripts will be included in this export.</p>
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
                <p>All Scripts will <span class="font-weight-bold">not</span> be included in this export.</p>
            </div>
            <b-link @click="returnToSummary">Return to Summary</b-link>
        </div>
        <hr>
        <div v-for="(item, i) in items" :key="i">
            <b-card class="high-elevation">
                <template #header>
                    <h6 class="mb-0 data-card-header font-weight-bold">[[ Script Name ]]</h6>
                </template>
            <b-card-text>
                <ul class="process-element-metadata">
                    <li>Description: </li>
                    <li>Categories: </li>
                    <li>Language: </li>
                    <li>Created: </li>
                    <li>Last Modified: </li>
                </ul>   
            </b-card-text>
            </b-card>
        </div>
    </div>
</template>

<script>

export default {
    props: ["items"],
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
    },
    mounted() {
    }
}
</script>

<style lang="scss" scoped>

.export-status-label {
    padding-right: 0;
}

.process-element-metadata {
    padding-left: 0;
}

.high-elevation {
    box-shadow: 0px 8px 10px 1px rgb(0 0 0 / 14%), 0px 3px 14px 2px rgb(0 0 0 / 12%), 0px 5px 5px -3px rgb(0 0 0 / 20%);
}

.low-elevation {
    box-shadow: 0px 1px 2px 0px rgb(60 64 67 / 25%), 0px 2px 6px 2px rgb(60 64 67 / 10%);
}

</style>
