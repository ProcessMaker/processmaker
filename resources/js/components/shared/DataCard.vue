<template>
    <div class="mt-4 mb-5 data-card-container high-elevation">
        <b-card>
            <template #header>
                <div class="header">
                    <i class="text-secondary data-card-header fas nav-icon fa-code" />
                    <h5 class="mb-0 data-card-header">Scripts</h5>
                    <b-form-checkbox
                        class="data-card-header export-all"
                        v-model="exportAll"
                    >
                        Export All
                    </b-form-checkbox>
                </div>
            </template>
            <b-card-text>
                <ul class="data-card-metadata">
                    <li>Status:
                    <b-badge
                    v-if="exportAll"
                    pill
                    variant="success"
                    >
                    <i class="fas fa-check-circle export-status-label" />
                    Full Export
                    </b-badge>
                    <b-badge
                    v-else
                    pill
                    variant="warning"
                    >
                    <i class="fas fa-exclamation-triangle export-status-label" />
                    Not Exporting
                    </b-badge>
                    </li>
                    <li>Total Elements: 18 Scripts</li>
                </ul>
                <div>
                    <b-link>
                        <i class="fas fa-info-circle fa-fw mr-0"></i>
                        Details
                    </b-link>
                </div>
            </b-card-text>
        </b-card>
    </div>
</template>

<script>
export default {
    props: ["exportAll"],
    components: {},
    mixins: [],
    data() {
        return {
            exportAll: true,
        }
    },
    methods: {      
    },
    mounted() {
    }
}
</script>

<style lang="scss" scoped>

.high-elevation {
    box-shadow: 0px 8px 10px 1px rgb(0 0 0 / 14%), 0px 3px 14px 2px rgb(0 0 0 / 12%), 0px 5px 5px -3px rgb(0 0 0 / 20%);
}

.low-elevation {
    box-shadow: 0px 1px 2px 0px rgb(60 64 67 / 25%), 0px 2px 6px 2px rgb(60 64 67 / 10%);
}

.data-card-header {
    display: inline;
}

.data-card-metadata {
    padding-left: 0;
}

.export-all {
    float: right;
}

.export-status-label {
    padding-right: 0;
}
</style>
