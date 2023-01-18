<template>
    <div class="mb-2">
        <h2>Export Process: {{ processName }}</h2>
        <hr>
        <div>
            <h4>Summary</h4>
            <ul v-if="processInfo" class="process-summary">
                <li> Description: <span class="process-metadata">{{ processInfo.description }}</span></li>
                <li> Categories: <span class="process-metadata">{{ processInfo.categories }}</span></li>
                <li> Process Manager: <span class="process-metadata"><b-link>{{ processInfo.processManager }}</b-link></span></li>
                <li> Created: <span class="process-metadata">{{ processInfo.createdAt }}</span></li>
                <li> Last Modified: <span class="process-metadata">{{ processInfo.updatedAt }}</span></li>
                <li> Modified By: <span class="process-metadata"><b-link>{{ processInfo.lastModifiedBy }}</b-link></span></li>
            </ul>
        </div>
        <div>
            <b-form-group>
                <b-form-checkbox
                    v-model="passwordProtect"
                    class="process-metadata"
                    stacked
                >
                Password Protect Export
                <b-form-text class="process-options-helper-text">Define a password to protect your export file.</b-form-text>
                </b-form-checkbox>
                <b-form-checkbox
                    :checked="$root.includeAll"
                    @change="change"
                    class="process-metadata"
                    stacked
                >
                Export All Elements
                <b-form-text class="process-options-helper-text">Include all Process Elements in your export file.</b-form-text>
                </b-form-checkbox>
            </b-form-group>
        </div>
        <hr>
        <div v-for="group in groups" :key="group.type">
          <data-card :exportAllElements="exportAllElements" :info="group" />
        </div>
        <div class="pt-3 card-footer bg-light" align="right">
            <button type="button" class="btn btn-outline-secondary">
                {{ $t("Cancel") }}
            </button>
            <button type="button" class="btn btn-primary ml-2" @click="onExport">
                {{ $t("Export") }}
            </button>
            <set-password-modal ref="set-password-modal" :processId="processId" :processName="processName" @verifyPassword="exportProcess" />
            <export-success-modal ref="export-success-modal" :processName="processName" :processId="processId" :exportInfo="exportInfo" />
        </div>
    </div>
</template>

<script>

import DataCard from "../../../../components/shared/DataCard.vue";
import SetPasswordModal from "../SetPasswordModal.vue";
import DataProvider from "../../DataProvider";

export default {
  props: ["processName",
    "groups",
    "processId",
    "processInfo"],
    components: {
        DataCard,
        SetPasswordModal
    },
    mixins: [],
    data() {
        return {
            passwordProtect: true,
            exportAllElements: true,
        }
    },
    methods: {
        change(value) {
            this.$root.setIncludeAll(value);
        },
        showSetPasswordModal() {
            this.$refs["set-password-modal"].show();
        },
        onExport() {
            if (!this.passwordProtect) {
                this.exportProcess();
            } else {
                this.showSetPasswordModal();
            }
        },
        exportProcess() {
            DataProvider.exportProcess(this.processId, this.$root.exportOptions())
                .then(() => {
                    this.$refs["set-password-modal"].hide();
                });
        },
    },
    mounted() {
    },
    watch: {
    },
}
</script>

<style>

.process-summary {
    padding-left: 0;
}

.process-metadata {
    font-weight: 600;
}

.process-options-helper-text {
    margin-top: 0;
    margin-bottom: 2px;
}

</style>

