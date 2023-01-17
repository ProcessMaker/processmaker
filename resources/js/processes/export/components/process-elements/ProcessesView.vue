<template>
    <div class="mb-2">
        <h2>Export Process: {{ processName }}</h2>
        <hr>
        <div>
            <h4>Summary</h4>
            <ul v-if="processInfo" class="process-summary">
                <li> Description: <span class="process-metadata">{{ processInfo.description }}</span></li>
                <li> Categories: <span class="process-metadata">{{ processInfo.categories }}</span></li>
                <li> Process Manager: <span class="process-metadata"><b-link>{{ processInfo.process_manager }}</b-link></span></li>
                <li> Created: <span class="process-metadata">{{ processInfo.created_at }}</span></li>
                <li> Last Modified: <span class="process-metadata">{{ processInfo.updated_at }}</span></li>
                <li> Modified By: <span class="process-metadata"><b-link>{{ processInfo.last_modified_by }}</b-link></span></li>
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
                    v-model="exportAllElements"
                    class="process-metadata"
                    stacked
                >
                Export All Elements
                <b-form-text class="process-options-helper-text">Include all Process Elements in your export file.</b-form-text>
                </b-form-checkbox>
            </b-form-group>
        </div>
        <hr>
        <div>
            <data-card :exportAll="exportAll" />
        </div>
        <div class="pt-3 card-footer bg-light" align="right">
            <button type="button" class="btn btn-outline-secondary">
                {{ $t("Cancel") }}
            </button>
            <button type="button" class="btn btn-primary ml-2" @click="onExport">
                {{ $t("Export") }}
            </button>
            <set-password-modal ref="set-password-modal" :processId="processId" :processName="processName" @verifyPassword="exportProcess" />
        </div>
    </div>
</template>

<script>

import DataCard from "../../../../components/shared/DataCard.vue";
import SetPasswordModal from "../SetPasswordModal.vue";

export default {
  props: ["processName",
    "processId",
    "exportAll",
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
        showSetPasswordModal() {
            this.$refs["set-password-modal"].show();
        },
        onExport() {
            this.showSetPasswordModal();
            console.log(this.processId);
        },
        exportProcess() {
        
    },
    },
    mounted() {
    },
    watch: {
      exportAllElements() {
        if (this.exportAllElements === true) {
            this.exportAll = true;
        }
      }
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

