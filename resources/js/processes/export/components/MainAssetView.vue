<template>
    <div>
        <h2>{{ $t($root.operation) }} {{ $t('Process') }}: <span class="text-capitalize">{{ processName }}</span></h2>
        <hr>
        <div class="mb-2">
            <h4>{{ $t('Summary') }}</h4>
        </div>
        <div class="mb-2">
            <ul v-if="processInfo" class="process-summary mb-2">
                <li>{{ $t('Description') }}: <span class="fw-semibold">{{ processInfo.description }}</span></li>
                <li>{{ $t('Categories') }}: <span class="fw-semibold">{{ processInfo.categories }}</span></li>
                <li>{{ $t('Process Manager') }}:
                    <span class="fw-semibold">
                        <b-link v-if="processInfo.processManagerId"
                            :href="`/profile/${processInfo.processManagerId}`"
                            target="_blank">{{ processInfo.processManager }}</b-link>
                        <span v-else>{{ processInfo.processManager }}</span>
                    </span>
                </li>
                <li>{{ $t('Created') }}: <span class="fw-semibold">{{ processInfo.created_at }}</span></li>
                <li>{{ $t('Last Modified') }}:
                    <span class="fw-semibold">{{ processInfo.updated_at }}</span>
                    {{ $t('By') }}:
                    <span class="fw-semibold">
                        <b-link v-if="processInfo.lastModifiedById"
                            :href="`/profile/${processInfo.lastModifiedById}`"
                            target="_blank">{{ processInfo.lastModifiedBy }}</b-link>
                        <span v-else>{{ processInfo.lastModifiedBy }}</span>
                    </span>
                </li>
                <!-- <li v-if="$root.isImport">
                    <a href="#" v-b-modal:asset-dependent-tree>{{ $t('Linked Dependent Assets') }}</a>
                    <AssetDependentTreeModal></AssetDependentTreeModal>
                </li> -->
                <li>
                    <a href="#" v-b-modal:linked-assets-modal>{{ $t('Linked Assets') }}</a>
                    <AssetTreeModal :groups="groups" :asset-name="processName"></AssetTreeModal>
                </li>
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
                {{ $t('Password Protect Export') }}
                <b-form-text class="process-options-helper-text">{{ $t('Define a password to protect your export file.') }}</b-form-text>
                <small v-if="$root.forcePasswordProtect" class="text-danger">
                    {{ $t('Password protect is required because some assets may have sensitive data.') }}
                </small>
                </b-form-checkbox>
                <b-form-checkbox
                    :checked="$root.includeAll"
                    @change="change"
                    class="fw-semibold"
                    stacked
                >
                {{ $t($root.operation) }} {{ $t('All Process Elements') }}
                <b-form-text v-if="$root.operation === 'Export'" class="process-options-helper-text">{{ $t('Include all elements related to this process in your export file.') }}</b-form-text>
                <b-form-text v-else class="process-options-helper-text">{{ $t('All elements related to this process will be imported.') }}</b-form-text>
                </b-form-checkbox>
            </b-form-group>
        </div>
        <hr>
        <div class="pb-2" v-if="groups.length === 0">
            <p class="fw-semibold">{{ $t('This process contains no dependent assets to') }} {{ $t($root.operation.toLowerCase()) }}.</p>
        </div>
        <div v-for="group in groups" :key="group.type">
            <data-card v-if="!group.hidden" :info="group" :isEnabled="$root.hasSomeNotDiscardedByParent(group.items)" :class="!$root.hasSomeNotDiscardedByParent(group.items) ? 'card-disabled' : ''"/>
        </div>
        <div class="p-0 pt-3 pb-3 card-footer bg-light" align="right">
            <button type="button" class="btn btn-outline-secondary" @click="onCancel">
                {{ $t("Cancel") }}
            </button>
            <button v-if="$root.isImport" type="button" class="btn btn-primary ml-2"
                :class="{'disabled': loading}" 
                :disabled="loading"
                @click="onImport">
                    <span v-if="!loading">{{$t('Import')}}</span>
                    <i v-if="loading" class="fas fa-spinner fa-spin p-0" />
                    <span v-if="loading">{{$t('Importing')}}</span>
            </button>
            <button v-else :disabled="!$root.canExport" type="button" class="btn btn-primary ml-2" @click="onExport">
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
            <import-process-modal ref="import-process-modal" :existingAssets="existingAssets" :processName="processName" :userHasEditPermissions="true" @import-new="setCopyAll" @update-process="setUpdateAll"></import-process-modal>                            
            <export-success-modal ref="export-success-modal" :processName="processName" :processId="processId" :exportInfo="exportInfo" :info="groups" :projectId="projectId"/>
            <import-log :log-entries="$root.queueLog" :allow-download-debug="$root.allowDownloadDebug"></import-log>
        </div>
    </div>
</template>

<script>

import DataCard from "../../../components/shared/DataCard.vue";
import AssetDependentTreeModal from "../../../components/shared/AssetDependentTreeModal.vue";
import AssetTreeModal from "../../../components/shared/AssetTreeModal.vue";
import ImportProcessModal from '../../import/components/ImportProcessModal';
import SetPasswordModal from "./SetPasswordModal.vue";
import DataProvider from "../DataProvider";
import ExportSuccessModal from "./ExportSuccessModal.vue";
import ImportLog from "../../import/components/ImportLog.vue";

export default {
  props: [
    "processName",
    "groups",
    "processId",
    "processInfo",
    "projectId",
    ],
    components: {
        DataCard,
        SetPasswordModal,
        ExportSuccessModal,
        AssetDependentTreeModal,
        AssetTreeModal,
        ImportProcessModal,
        ImportLog,
    },
    mixins: [],
    data() {
        return {
            passwordProtect: true,
            exportAllElements: true,
            exportInfo: {},
            assetsExist:false,
            loading: false,
        }
    },
    computed: {
        existingAssets() {
            if (this.$root.manifest) {
                return Object.entries(this.$root.ioState).filter(([uuid, settings]) => {
                    const asset = this.$root.manifest[uuid];        
                    return asset && asset.existing_id !== null && settings.mode !== 'discard' && !settings.discardedByParent;
                }).map(([uuid, _]) => {
                    const asset = this.$root.manifest[uuid];  
                    return {
                        type: asset.type,
                        typeHuman: asset.type_human,
                        typeHumanPlural: asset.type_human_plural,
                        existingName: asset.existing_name, 
                        importingName: asset.name,
                        existingId: asset.existing_id,
                        matchedBy: asset.matched_by,
                        existingUpdatedAt: asset.existing_attributes?.updated_at,
                        importingUpdatedAt: asset.attributes?.updated_at,
                    };
                });
            }
            return [];
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
        onCancel() {
            const projectId = this.$route.params.id;
            if (this.$route.name === "projectCustomAssetImport") {
                window.location.href = `/designer/projects/${projectId}`;
            } else {
                window.location.href = '/processes';
            }
        },
        onExport() {
            if (this.passwordProtect) {
                this.showSetPasswordModal();
            } else {
                this.exportProcess(null);
            }
        },
        onImport() {
            this.checkForExistingAssets();
            if (this.assetsExist) {
                this.$refs['import-process-modal'].show();
            } else {
                this.$route.name === "projectCustomAssetImport" ? this.handleProjectAssetsImport(this.$route.params.id) : this.handleImport();
            }
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
        checkForExistingAssets() {
            this.assetsExist = this.existingAssets.length > 0 ? true : false;
        },
        setCopyAll() {
            this.assetsExist = false;
            this.$root.setModeForAll('copy');           
            this.$route.name === "projectCustomAssetImport" ? this.handleProjectAssetsImport(this.$route.params.id) : this.handleImport();
        },
        setUpdateAll() {
            this.assetsExist = false;
            this.$root.setModeForAll('update');
            this.$route.name === "projectCustomAssetImport" ? this.handleProjectAssetsImport(this.$route.params.id) : this.handleImport();

        },
        handleImport() {
            this.loading = true;
            let request = null;
            if (this.$root.queue) {
                request = DataProvider.doImportQueued(this.$root.exportOptions(), this.$root.password, this.$root.hash);
            } else {
                request = DataProvider.doImport(this.$root.file, this.$root.exportOptions(), this.$root.password, this.$root.hash)
            }
            request.then((response) => {
                if (response?.data?.processId && !this.$root.queue) {
                    const message = this.$t('Process was successfully imported');
                    ProcessMaker.alert(message, 'success');
                    window.location.href = `/modeler/${response.data.processId}`;
                }
            }).catch(error => {
                const message = `${this.$t('Unable to import the process.')}${error.response.data.message 
                    ? ': ' + error.response.data.message 
                    : ''}`;
                ProcessMaker.alert(message, 'danger');
                this.loading = false;
            });
        },
        handleProjectAssetsImport(projectId) {
            this.loading = true;
            DataProvider.doImportProjectAssets(this.$root.file, this.$root.exportOptions(), projectId, this.$root.password)
            .then((response) => {
                if (response?.data) {
                    const projectId = response.data.projectId;
                    const successMessage = this.$t('Asset was successfully imported');

                    ProcessMaker.alert(successMessage, 'success');
                    window.location.href = projectId ? `/designer/projects/${projectId}` : '/designer/projects/';
                    this.submitted = false; // the form was successfully submitted
                } else {
                    // the request was successful but did not return expected data
                    throw new Error(this.$t('Unknown error while importing the Asset.'));
                }
            })
            .catch((error) => {
                this.handleError(error); // a shared method that displays the error message and resets loading/submitted
            });  
        },
        handleError(error) {
            const message = error.response?.data?.message || this.$t('Unable to import the Asset.');
            ProcessMaker.alert(`${message}.`, 'danger');
            this.submitted = false;
            this.loading = false;
        },
    },
    mounted() {
    },
}
</script>

<style>

.process-summary {
    padding-left: 0;
    list-style: none;
}

.process-options-helper-text {
    margin-top: 0;
    margin-bottom: 2px;
}

.card-disabled {
    opacity: 0.5;
}

</style>

