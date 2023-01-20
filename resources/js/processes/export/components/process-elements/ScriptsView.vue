<template>
    <div class="mb-2">
        <h2>{{ $root.operation }} Process: <span class="text-capitalize">{{ processName }}</span></h2>
        <hr>
        <div>
            <h4>{{ group.typeHumanPlural }}</h4>
            <div v-if="$root.includeAllByGroup[group.type]" class="mb-2">
                <h6 class="mb-0">{{ $root.operation }} Status:
                    <b-badge
                    pill
                    variant="success"
                    >
                    <i class="fas fa-check-circle export-status-label" />
                    Full {{ $root.operation }}
                    </b-badge>
                </h6>
                <small class="text-muted"><span class="font-weight-bold">All</span> {{ group.typeHumanPlural }} will be included in this {{ $root.operation.toLowerCase() }}.</small>
            </div>
            <div v-else class="mb-2">
                <h6 class="mb-0">{{ $root.operation }} Status:
                <b-badge
                    pill
                    variant="warning"
                    >
                    <i class="fas fa-exclamation-triangle export-status-label" />
                    Not {{ $root.operation }}ing
                    </b-badge>
                </h6>
                <small class="text-muted">{{ group.typeHumanPlural }} will <span class="font-weight-bold">Not</span> be included in this {{ $root.operation.toLowerCase() }}.</small>
            </div>
            <b-link @click="returnToSummaryClick">Return to Summary</b-link>
        </div>
        <hr>
        <div v-for="(item, i) in group.items" :key="i">
            <b-card class="high-elevation mb-4">
                <template #header>
                    <h6 class="mb-0 data-card-header font-weight-bold text-capitalize">{{ item.name }}</h6>
                </template>
            <b-card-text>
                <ul class="process-element-metadata">
                    <li>Description: <span class="fw-semibold">{{ item.description }}</span></li>
                    <li>Categories: <span class="fw-semibold">{{ item.categories }}</span></li>
                    <!-- <li>Language: <span class="fw-semibold"></span></li> -->
                    <li>Created Date: <span class="fw-semibold">{{ item.createdAt }}</span></li>
                    <li>Last Modified Date: <span class="fw-semibold">{{ item.updatedAt }}</span></li>
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
        returnToSummaryClick() {
            window.ProcessMaker.EventBus.$emit("return-to-summary-click");
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
