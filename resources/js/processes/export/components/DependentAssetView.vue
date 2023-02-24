<template>
    <div class="mb-2">
        <h2>{{ $root.operation }} Process: <span class="text-capitalize">{{ processName }}</span></h2>
        <hr>
        <div>
            <div class="mb-2">
                <h4>{{ group.typeHumanPlural }}</h4>
            </div>
            <div v-if="$root.includeAllByGroup[group.type]" class="mb-2">
                <h6 class="mb-0 fw-semibold">{{ $root.operation }} Status:
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
                <h6 class="mb-0 fw-semibold">{{ $root.operation }} Status:
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
            <div class="mb-2">
                <b-link @click="returnToSummaryClick">Return to Summary</b-link>
            </div>
        </div>
        <hr>
        <div v-for="(item, i) in group.items.filter(i => !i.hidden)" :key="i">
            <b-card class="low-elevation mt-4 mb-5 data-card">
                <template #header>
                    <h6 class="mb-0 data-card-header font-weight-bold text-capitalize">
                        {{ item.name }}
                        <b-badge pill variant="primary" class="text-capitalize">
                            <span v-if="item.importMode === 'update'">{{ $t('Updated') }}</span>
                            <span v-if="item.importMode === 'copy'">{{ $t('New') }}</span>
                        </b-badge>
                    </h6>
                </template>
                <b-card-text>
                    <ul class="process-element-metadata">
                        <li v-if="item.description">Description: <span class="fw-semibold">{{ item.description }}</span></li>
                        <li>Categories: <span class="fw-semibold">{{ item.categories }}</span></li>
                        <li v-for="(attribute, i) in item.extraAttributes" :key="i">
                            {{ i[0].toUpperCase() + i.substring(1) }}: <span class="fw-semibold">{{ attribute }}</span>
                        </li>
                        <!-- <li>Language: <span class="process-metadata"></span></li> -->
                        <li>Created Date: <span class="fw-semibold">{{ item.createdAt }}</span></li>
                        <li>Last Modified Date: <span class="fw-semibold">{{ item.updatedAt }}</span></li>
                    </ul>
                    <!-- TODO: Complete Change Log-->
                    <!-- <change-log :existingData="existingAssets" :newData="item"></change-log> -->
                </b-card-text>
            </b-card>
        </div>
    </div>
</template>

<script>
import ChangeLog from "../../../components/shared/ChangeLog.vue";

export default {
    props: ["type", "group", "processName", "existingAssets"],
    components: {
        ChangeLog
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
        showCard(item) {
            return !this.$root.ioState.find((i) => i.uuid === item.uuid).hidden;
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
