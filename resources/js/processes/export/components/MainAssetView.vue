<template>
    <div>
        <h2>{{ $root.operation }} Process: <span class="text-capitalize">{{ processName }}</span></h2>
        <hr>
        <div class="mb-2">
            <h4>Summary</h4>
        </div>
        <div class="mb-2">
            <ul v-if="processInfo" class="process-summary mb-2">
                <li> Description: <span class="fw-semibold">{{ processInfo.description }}</span></li>
                <li> Categories: <span class="fw-semibold">{{ processInfo.categories }}</span></li>
                <li> Process Manager:
                    <span class="fw-semibold">
                        <b-link v-if="processInfo.processManagerId"
                            :href="`/profile/${processInfo.processManagerId}`"
                            target="_blank">{{ processInfo.processManager }}</b-link>
                        <span v-else>{{ processInfo.processManager }}</span>
                    </span>
                </li>
                <li> Created: <span class="fw-semibold">{{ processInfo.createdAt }}</span></li>
                <li> Last Modified: 
                    <span class="fw-semibold">{{ processInfo.updatedAt }}</span> 
                    By:
                    <span class="fw-semibold">
                        <b-link v-if="processInfo.lastModifiedById"
                            :href="`/profile/${processInfo.lastModifiedById}`"
                            target="_blank">{{ processInfo.lastModifiedBy }}</b-link>
                        <span v-else>{{ processInfo.lastModifiedBy }}</span>
                    </span>
                </li>
                <!-- <li v-if="$root.isImport">
                    <a href="#" v-b-modal:asset-dependent-tree>Linked Dependent Assets</a>
                    <AssetDependentTreeModal></AssetDependentTreeModal>
                </li>
                <li>
                    <a href="#" v-b-modal:asset-tree>Linked Assets</a>
                    <AssetTreeModal :groups="groups"></AssetTreeModal>
                </li> -->
            </ul>
        </div>
        <div class="mb-2">
            <b-form-group>
                <b-form-checkbox
                    v-if="!$root.isImport"
                    v-model="passwordProtect"
                    class="fw-semibold"
                    stacked
                    :disabled="$root.forcePasswordProtect"
                >
                Password Protect Export
                <b-form-text class="process-options-helper-text">Define a password to protect your export file.</b-form-text>
                <small v-if="$root.forcePasswordProtect" class="text-danger">
                    Password protect is required because some assets have sensitive data.
                </small>
                </b-form-checkbox>
                <b-form-checkbox
                    :checked="$root.includeAll"
                    @change="change"
                    class="fw-semibold"
                    stacked
                >
                {{ $root.operation }} All Process elements
                <b-form-text v-if="$root.operation === 'Export'" class="process-options-helper-text">Include all Process Elements in your export file.</b-form-text>
                <b-form-text v-else class="process-options-helper-text">{{ $t('All elements related to this process will be Imported.') }}</b-form-text>
                </b-form-checkbox>
            </b-form-group>
        </div>
        <hr>
        <div v-for="group in groups" :key="group.type">
          <data-card v-if="!group.hidden && $root.hasSomeNotDiscardedByParent(group.items)" :exportAllElements="exportAllElements" :info="group" />
        </div>
        <div class="pt-3 card-footer bg-light" align="right">
            <button type="button" class="btn btn-outline-secondary">
                {{ $t("Cancel") }}
            </button>
            <button v-if="$root.isImport" type="button" class="btn btn-primary ml-2" @click="onImport">
                {{ $t("Import") }}
            </button>
            <button v-else type="button" class="btn btn-primary ml-2" @click="onExport">
                {{ $t("Export") }}
            </button>
            <set-password-modal
                ref="set-password-modal"
                :processId="processId"
                :processName="processName"
                @verifyPassword="exportProcess"
                :ask="false"
                :password-protect="passwordProtect"
            />
            <export-success-modal ref="export-success-modal" :processName="processName" :processId="processId" :exportInfo="exportInfo" />
        </div>
    </div>
</template>

<script>

import DataCard from "../../../components/shared/DataCard.vue";
import AssetDependentTreeModal from "../../../components/shared/AssetDependentTreeModal.vue";
import AssetTreeModal from "../../../components/shared/AssetTreeModal.vue";
import SetPasswordModal from "./SetPasswordModal.vue";
import DataProvider from "../DataProvider";
import ExportSuccessModal from "./ExportSuccessModal.vue";

export default {
  props: [
    "processName",
    "groups",
    "processId",
    "processInfo",
    ],
    components: {
        DataCard,
        SetPasswordModal,
        ExportSuccessModal,
        AssetDependentTreeModal,
        AssetTreeModal,
    },
    mixins: [],
    data() {
        return {
            passwordProtect: true,
            exportAllElements: true,
            exportInfo: {},
        }
    },
    watch: {
      "$root.forcePasswordProtect": function (val) {
        if (val) {
          this.passwordProtect = true;
        }
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
            if (this.passwordProtect) {
                this.showSetPasswordModal();
            } else {
                this.exportProcess(null);
            }
        },
        onImport() {
            DataProvider.doImport(this.$root.file, this.$root.exportOptions(), this.$root.password)
                .then((response) => {
                    ProcessMaker.alert(this.$t('Process was successfully imported'), 'success');
                    if (response.data?.processId) {
                        window.location.href = `/modeler/${response.data.processId}`;
                    }
                })
        },
        exportProcess(password = null) {
            DataProvider.exportProcess(this.processId, password, this.$root.exportOptions())
                .then((exportInfo) => {
                    this.exportInfo = exportInfo;
                    this.$refs['export-success-modal'].show();
                    this.$refs["set-password-modal"].hide();
                })
                .catch((error) => {
                    ProcessMaker.alert(error, "danger");
                });
        },
    },
    mounted() {
    },
}
</script>

<style>

.process-summary {
    padding-left: 0;
}

.process-options-helper-text {
    margin-top: 0;
    margin-bottom: 2px;
}

</style>

