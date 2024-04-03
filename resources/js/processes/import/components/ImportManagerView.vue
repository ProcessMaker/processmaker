<template>
   <div class="container mb-3" id="importProcess" v-cloak>
        <div class="row">
            <div class="col">
                <div class="card text-center">
                    <div class="card-header bg-light" align="left">
                        <h5 class="mb-0">{{ title() }}</h5>
                        <small class="text-muted">{{ subtitle() }}</small>
                    </div>
                    <b-alert v-if="importIsRunning" show variant="warning">{{ $t('An import is currently in progress. Only one import can run at a time.') }}</b-alert>
                    <div class="card-body">
                        <div id="pre-import" v-if="! importing && ! imported && ! importIsRunning">
                            <draggable-file-upload v-if="!file || file && !fileIsValid" ref="file" v-model="file" :options="{singleFile: true}" :displayUploaderList="false" :accept="['.spark', 'application/json']"></draggable-file-upload>
                            <div v-else class="text-left">
                               <h5> {{ $t("You are about to import") }} <strong>{{processName}}</strong></h5>
                                <div class="border-dotted p-3 col-4 text-center font-weight-bold my-3">
                                    {{file.name}} 
                                    <b-button 
                                        variant="link" 
                                        @click="removeFile" 
                                        class="p-0"
                                        aria-describedby=""
                                    >
                                        <i class="fas fa-times-circle text-danger"></i>
                                    </b-button>
                                </div>
                                <b-form-group>
                                    <h6>{{ $t('Select Import Type') }}</h6>
                                    <div class="alert alert-warning" v-if="showWarning">
                                        {{ $t('The file you are importing was made with an older version of ProcessMaker. Advanced import is not available. All assets will be copied.') }}
                                    </div>
                                    <div class="alert alert-warning" v-if="showTemplateWarning">
                                        {{ $t('The file you are importing is a Template. Advanced import is not available. All assets will be copied.') }}
                                    </div>
                                    <b-form-radio 
                                        v-for="(item, index) in importTypeOptions" 
                                        v-model="selectedImportOption" 
                                        v-uni-aria-describedby="index.toString()"
                                        :key="item.value" 
                                        :value="item.value"
                                        :disabled="item.disabled"
                                    >
                                        <span class="fw-medium">{{ item.content }}</span>
                                        <div>
                                            <small v-uni-id="index.toString()" class="text-muted">{{item.helper}}</small>
                                        </div>
                                    </b-form-radio>
                                </b-form-group>
                            </div>
                            <enter-password-modal ref="enter-password-modal" @password="passwordEntered" :password-error="passwordError"></enter-password-modal>
                            <import-process-modal ref="import-process-modal" :existingAssets="existingAssets" :processName="processName" :userHasEditPermissions="true" @import-new="setCopyAll" @update-process="setUpdateAll"></import-process-modal>                            
                        </div>
                        <old-process-importer v-if="showOldImporter" :options="options" :assignable="assignable" :processId="processId"></old-process-importer>
                    </div>
                    <div id="card-footer-pre-import" class="card-footer bg-light" align="right"
                         v-if="! importing && ! imported">
                        <button type="button" class="btn btn-outline-secondary" @click="onCancel">
                            {{$t('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-primary ml-2"
                            :class="{'disabled': loading}"
                            :disabled="fileIsValid === false || loading"
                            @click="checkForPassword">
                                <span v-if="!loading">{{$t('Import')}}</span>
                                <i v-if="loading" class="fas fa-spinner fa-spin p-0" />
                                <span v-if="loading">{{$t('Importing')}}</span>
                        </button>
                        <import-log :log-entries="$root.queueLog" :allow-download-debug="$root.allowDownloadDebug"></import-log>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
const importingCode = window.location.hash.match(/#code=(.+)/);
import DraggableFileUpload from '../../../components/shared/DraggableFileUpload';
import EnterPasswordModal from '../components/EnterPasswordModal.vue';
import ImportProcessModal from '../components/ImportProcessModal.vue';
import OldProcessImporter from '../components/OldProcessImporter';
import { createUniqIdsMixin } from "vue-uniq-ids";
import DataProvider from '../../export/DataProvider';
import ImportLog from '../components/ImportLog';
const uniqIdsMixin = createUniqIdsMixin();

export default {
    props: [''],
    components: {DraggableFileUpload, EnterPasswordModal, ImportProcessModal, OldProcessImporter, ImportLog},
    mixins: [uniqIdsMixin],
    data() {
        return {
            file: '',
            uploaded: false,
            submitted: importingCode ? true : false,
            options: [],
            assignable: null,
            importing: importingCode ? true : false,
            imported: false,
            selectedUser: null,
            usersAndGroups: [],
            users: [],
            processes: [],
            manager: null,
            cancelRequest: [],
            processEditData: [],
            importingCode: importingCode ? importingCode[1] : null,
            dataSources: [],
            dataSourcesInstalled: true,
            loading: false,
            status: 'ACTIVE',
            importTypeOptions: [
                {"value": "basic", "content": "Basic", "helper": this.$t(`Import all assets from the uploaded package.`), "disabled": false},
                {"value": "custom", "content": "Custom", "helper": this.$t(`Select which assets from the uploaded package should be imported to this environment.`), "disabled": false},
            ],
            fileIsValid: false,
            selectedImportOption: "basic",
            processName: null,
            passwordEnabled: false,
            assetsExist: false,
            processVersion: null,
            password: '',
            passwordError: null,
            showWarning:false,
            showTemplateWarning: false,
            showOldImporter: false,
            importIsRunning: false,
        }
    },
    filters: {
        titleCase: function (value) {
            value = value.toString();
            return value.charAt(0).toUpperCase() + value.slice(1);
        }
    },
    watch: {
        file() {
            this.fileIsValid = false;
            if (!this.file) {
                return
            }
            this.validateFile();
            this.processName = this.file.name.split('.').slice(0,-1).toString();
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
        },
        importType() {
            const assetType = document.querySelector("meta[name='import-template-asset-type']") ? _.get(document.querySelector('meta[name="import-template-asset-type"]'),"content") : null;
            return assetType  && assetType === 'process' ? 'process_templates' : 'processes';
        }
    },
    methods: {
        reload() {
            window.location.reload();
        },
        onCancel() {
            window.location = '/processes';
        },
        importFile(action) {
            this.assetsExist = this.existingAssets.length > 0 && action !== 'update-all' ? true : false;
            switch (this.selectedImportOption) {
                case 'basic':
                    this.handleBasicImport();
                    break;
            
                default:
                    this.$router.push({name: 'custom'});
                    break;
            }
        },
        handleBasicImport() {
            // TODO: IMPORT/EXPORT check if process already exists. and users have edit permissions
            if (this.assetsExist) {
                this.$nextTick(() => {    
                    this.$refs['enter-password-modal'].hide();  
                    this.$refs['import-process-modal'].show();
                });
            } else {
                if (this.submitted) {
                    return;
                }             
                if (this.processVersion) {
                    this.handleImport();
                } else {
                    this.handleOldVersionImport();
                }
            }  
        },
        checkForPassword() {
            // if (!this.passwordEnabled) {
               this.importFile();
            // } else {
            //     this.showEnterPasswordModal();
            // }
        },
        showEnterPasswordModal() {
            this.$refs['enter-password-modal'].show();
        },
        importReady(response) {
            let message = this.$t("Unable to import the process.");
            if (!response.data.status) {
                ProcessMaker.alert(message, 'danger');
                return;
            }

            this.options = response.data.status;
            this.processId = response.data.process.id;
            this.importing = false;
            this.imported = true;
            this.showOldImporter = true;

            if (!response.data.process.id) {
                ProcessMaker.alert(message, 'danger');
                return;
            }
            this.assignable = response.data.assignable;
            this.processId = response.data.process.id;

            if (_.get(response, 'data.process.properties.manager_can_cancel_request', false)) {
                this.cancelRequest.push(this.managerOption);
            }

            message = this.$t('The process was imported.');
            let variant = 'success';
            for (let item in this.options) {
                if (!this.options[item].success) {
                message = this.$t('The process was imported, but with errors.');
                variant = 'warning'
                }
            }
            ProcessMaker.alert(message, variant);
        },
        checkForExistingRoute(item) {
            if (!item.value) {
                item.error = 'Segment is required';
                return
            }
            item.value = item.value.replace(/\s+/g, '-').toLowerCase();

            ProcessMaker.apiClient.get(`/webentry/custom_route/check/${item.value}`)
                .then(response => {
                item.error = null;
                })
                .catch(error => {
                item.error = error.response.data.error;
                });
        },
        validateFile() {
            if (!this.file) {
                return;
            }
            this.$root.file = this.file;

            let formData = new FormData();
            formData.append('file', this.file);
            if (this.password) {
                formData.append('password', this.password);
            }
            if (this.$root.queue) {
                formData.append('queue', 1);
            }


            switch (this.importType) {
                case 'process_templates':
                    this.validateProcessTemplateFile(formData);
                break;
            
                default:
                this.validateProcessFile(formData);
            }


        },
        validateProcessFile(formData) {
            ProcessMaker.apiClient.post('/processes/import/validation', formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }
            )
            .then(response => {
                if (_.get(response, 'data.queued', false)) {
                    this.$root.hash = response.data.hash;
                } else {
                    this.handleValidationResponse(response);
                }
            }).catch(error => {
                if (error.response?.data?.error === 'password required') {
                    this.showEnterPasswordModal();
                } else if (error.response?.data?.error === 'incorrect password') {
                  this.passwordError = "Incorrect Password";
                } else {
                    const message = error.response?.data?.error || error.response?.data?.message || error.message;
                    ProcessMaker.alert(message, 'danger');
                }
            });
        },
        handleValidationResponse(response) {
            if (typeof response.data === 'object') {
                this.$root.manifest = response.data.manifest;
                this.$root.rootUuid = response.data.rootUuid;
                this.processVersion = response.data.processVersion;
            }  
            
            if (this.processVersion === null) {
                // disable 'custom' import type for older process versions
                this.importTypeOptions[1].disabled = true;
                this.showWarning = true;
            }
            
            this.fileIsValid = true;
            this.$root.setInitialState(this.$root.manifest, this.$root.rootUuid);
            this.$refs['enter-password-modal'].hide();
        },
        validateProcessTemplateFile(formData) {
            ProcessMaker.apiClient.post('/templates/process/import/validation', formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }
            )
            .then(response => {   
                if (typeof response.data === 'object') {
                    this.$root.manifest = response.data.manifest;
                    this.$root.rootUuid = response.data.rootUuid;
                    this.processVersion = response.data.processVersion;
                    
                    // disable 'custom' import type for templates
                    this.importTypeOptions[1].disabled = true;
                    this.showTemplateWarning = true;
                }  
               
                this.fileIsValid = true;
                this.$root.setInitialState(this.$root.manifest, this.$root.rootUuid);

            }).catch(error => {
                const message = error.response?.data?.error || error.response?.data?.message || error.message;
                ProcessMaker.alert(message, 'danger');
            });       
        },
         removeFile() {
            this.file = '';
        },
        importAsNew() {
            this.$router.push({name: 'import-new-process', params: {file: this.file}})
        },
        setCopyAll() {
            this.assetsExist = false;
            this.$root.setModeForAll('copy');
            this.handleBasicImport();
        },
        setUpdateAll() {
            this.assetsExist = false;
            this.$root.setModeForAll('update');
            this.handleBasicImport();
        },
        passwordEntered(password) {
          this.password = password;
          this.$root.password = password;
          this.validateFile();
          ;
        },
        handleOldVersionImport() {
            DataProvider.importOlderVersion(this.file)
            .then(response => {
                window.location.hash = `#code=${response.data.code}`;
                this.importingCode = response.data.code;
                this.reload();
            })
            .catch(error => {
                this.submitted = false;
                ProcessMaker.alert(this.$t('Unable to import the process.')  + (error.response.data.message ? ': ' + error.response.data.message : ''), 'danger');
            });
        },
        handleImport() {
            this.loading = true;
            this.submitted = true;

            switch (this.importType) {
                case 'process_templates':
                    this.handleProcessTemplateImport();
                break;
            
                default:
                    this.handleProcessImport();
            }

        },
        handleProcessImport() {
            console.log("HANDLE PROCESS IMPORT", this.$root.queue);
            let request;
            if (this.$root.queue) {
                request = DataProvider.doImportQueued(this.$root.exportOptions(), this.password, this.$root.hash);
            } else {
                request = DataProvider.doImport(this.file, this.$root.exportOptions(), this.password)
            }
            request.then((response) => {
                if (response?.data) {
                    if (!this.$root.queue) {
                        this.handleOnComplete(response.data)
                    }
                } else {
                    // the request was successful but did not return expected data
                    throw new Error(this.$t('Unknown error while importing the Process.'));
                }
                console.log("IMPORT RESPONSE", response);
            })
            .catch((error) => {
                this.handleError(error); // a shared method that displays the error message and resets loading/submitted
            });
        },
        handleOnComplete(data) {
            const { processId } = data;
            const successMessage = this.$t('Process was successfully imported');

            if (data.message && data.message.type === "warning" && data.message.serviceTasksNames.length) {
                const message = data.message;
                let taskList = "";

                message.serviceTasksNames.forEach(taskName => {
                    taskList = taskList + `<p><b>${taskName}<b></p>`;
                });

                let messageHtml = "<p>The following tasks in the process are configured to an email server that does not exist in this environment. The tasks have been <b>reconfigured to use the default server.</b></p>";
                messageHtml = messageHtml + taskList;

                ProcessMaker.messageModal(
                    this.$t("Warning"),
                    messageHtml,
                    "",
                    () => {
                        ProcessMaker.alert(successMessage, 'success');
                        window.location.href = processId ? `/modeler/${processId}` : '/processes/';
                        this.submitted = false; // the form was successfully submitted
                    });
            } else {
                ProcessMaker.alert(successMessage, 'success');
                window.location.href = processId ? `/modeler/${processId}` : '/processes/';
                this.submitted = false; // the form was successfully submitted
            }
        },
        handleProcessTemplateImport() {
            DataProvider.doImportTemplate(this.file, this.$root.exportOptions(), 'process')
            .then((response) => {
                if (response?.data) {
                    const { processId } = response.data;
                    const successMessage = this.$t('Process Template was successfully imported');

                    ProcessMaker.alert(successMessage, 'success');
                    window.location.href = processId ? `/modeler/${processId}` : '/processes/';
                    this.submitted = false; // the form was successfully submitted
                } else {
                    // the request was successful but did not return expected data
                    throw new Error(this.$t('Unknown error while importing the Process Template.'));
                }
            })
            .catch((error) => {
                this.handleError(error); // a shared method that displays the error message and resets loading/submitted
            });
        },
        // A shared method for handling errors across the app:
        handleError(error) {
            const message = error.response?.data?.message || this.$t('Unable to import the process.');
            ProcessMaker.alert(`${message}.`, 'danger');
            this.submitted = false;
            this.loading = false;
        },
        title() {
                if (window.location.pathname === '/template/process/import') {
                    return this.$t('Import Process Template');
                }
                return this.$t('Import Process');
        },
        subtitle() {
            if (window.location.pathname === '/template/process/import') {
                return this.$t('Import a Process Template and its associated assets into this ProcessMaker environment');
            }
            return this.$t('Import a Process and its associated assets into this ProcessMaker environment');
        },
    },
    mounted() {
        let received = false;
        window.Echo.private(`ProcessMaker.Models.User.${window.ProcessMaker.user.id}`).notification((response) => {
            if (!received && response.type === 'ProcessMaker.Notifications.ImportReady' && this.importingCode === response.code) {
                received = true;
                this.importReady(response);
            }
        });
        if (this.importingCode) {
            ProcessMaker.apiClient.get(`/processes/import/${this.importingCode}/is_ready`)
            .then(response => {
                if (response.data.ready) {
                    received = true;
                    this.importReady(response);
                }
            });
        }

        this.$root.queue = window.ProcessMaker.queueImports;
        this.importIsRunning = window.ProcessMaker.importIsRunning;

        const userId = window.ProcessMaker.user.id;
        this.$root.log({type: 'init', message: 'Ready for import'});
        window.Echo.private(`ProcessMaker.Models.User.${userId}`).listen(
            '.ImportLog',
            (response) => {
                this.$root.log({type: response.type, message: response.message});

                if (_.has(response, 'additionalParams.processId')) {
                    this.handleOnComplete(response.additionalParams);
                }

                if (response.message === 'preview') {
                    DataProvider.getImportManifest().then((manifestResponse) => {
                        this.handleValidationResponse({
                            data: {
                                manifest: manifestResponse.data,
                                rootUuid: response.additionalParams.rootUuid,
                                processVersion: response.additionalParams.processVersion,
                            },
                        });
                    });
                }

                if (response.message === 'ProcessMaker\\Exception\\ImportPasswordException: password required') {
                    this.showEnterPasswordModal();
                } else if (response.message === 'ProcessMaker\\Exception\\ImportPasswordException: incorrect password') {
                    this.passwordError = "Incorrect password";
                } else if (response.type === 'error') {
                    this.$root.allowDownloadDebug = true;
                    ProcessMaker.alert(response.message, 'danger');
                }
            }
        );
    },
}
</script>

<style type="text/css" scoped>
    [v-cloak] {
        display: none;
    }

    strong {
        font-weight: 700;
    }

    .card-body {
        transition: all 1s;
    }

    .border-dotted {
        border: 3px dotted #e0e0e0;
    }

    .fw-medium {
        font-weight:500;
    }
</style>