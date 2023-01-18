<template>
    <div class="mb-2">
        <h2>Export Process: {{ processName }}</h2>
        <hr>
        <div>
            <h4>{{ group.typePlural }}</h4>
            <div v-if="$root.includeAllByGroup[group.type]">
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
        <div v-for="(item, i) in group.items" :key="i">
            <b-card class="high-elevation mb-4">
                <template #header>
                    <h6 class="mb-0 data-card-header font-weight-bold">{{ item.name }}</h6>
                </template>
            <b-card-text>
                <ul class="process-element-metadata">
                    <li>Description: <span class="process-metadata">{{ item.description }}</span></li>
                    <li>Categories: <span class="process-metadata">{{ item.categories }}</span></li>
                    <li>Language: <span class="process-metadata"></span></li>
                    <li>Created: <span class="process-metadata">{{ item.createdAt }}</span></li>
                    <li>Last Modified: <span class="process-metadata">{{ item.updatedAt }}</span></li>
                </ul>
            </b-card-text>
            </b-card>
        </div>
    </div>
</template>

<script>

export default {
    props: ["type", "group", "processName"],
    components: {
    },
    mixins: [],
    data() {
        return {
            exportAll: false,
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
