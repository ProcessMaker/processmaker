<template>
    <div class="container">
        <div class="d-flex mb-2">
            <div class="mr-auto">
                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
            </div>
            <div>
                <b-button type="button" @click="add()">
                    <i class="fa fa-plus"/> {{ $t("Script Executor") }}
                </b-button>
            </div>
        </div>
        <b-table
            :fields="scriptRunnerFields"
            :items="scriptRunners"
        >
            <template v-slot:cell(edit)="data">
                <b-btn
                    variant="link"
                    @click="edit(data.item.id)"
                    v-b-tooltip.hover
                    :title="$t('Edit')"
                >
                    <i class="fas fa-pen-square fa-lg fa-fw"></i>
                </b-btn>
            </template>

            <template v-slot:cell(delete)="data">
                <b-btn
                    variant="link"
                    @click="deleteExecutor(data.item.id)"
                    v-b-tooltip.hover
                    :title="$t('Delete')"
                >
                    <i class="fas fa-trash-alt fa-lg fa-fw"></i>
                </b-btn>
            </template>
        </b-table>

        <b-modal ref="edit" id="edit" :title="modalTitle" @hidden="reset()" @hide="doNotHideIfRunning" size="lg" header-close-content="&times;">

            <b-container class="mb-2">
                <b-row>
                    <b-col>
                        <b-row class="mb-1">
                            <b-input
                                :class="{'is-invalid':getError('title')}"
                                v-model="formData.title"
                                :placeholder="$t('Name')">
                            </b-input>
                            <div v-if="getError('title')" class="invalid-feedback">{{ getError('title') }}</div>
                        </b-row>
                        <b-row>
                            <b-form-select
                                :class="{'is-invalid':getError('language')}"
                                v-model="formData.language"
                                :options="languagesSelect">
                            </b-form-select>
                            <div v-if="getError('language')" class="invalid-feedback">{{ getError('language') }}</div>
                        </b-row>
                    </b-col>
                    <b-col class="d-flex flex-column">
                        <b-textarea v-model="formData.description" :placeholder="$t('Description')" class="flex-grow-1"></b-textarea>
                    </b-col>
                </b-row>
            </b-container>

            <p class="mb-0">Dockerfile</i></p>

            <div class="d-flex flex-row mb-1">
                <div class="mr-1">
                    <a @click="showDockerfile = !showDockerfile">
                        <i class="fa" :class="{'fa-chevron-right': !showDockerfile, 'fa-chevron-down': showDockerfile}" style="width:14px"></i>
                    </a>
                </div>
                <div class="flex-fill">
                    <pre class="mt-1 mb-0" @click="showDockerfile = !showDockerfile">{{ initDockerfile.split("\n")[0] }} <template v-if="!showDockerfile">...</template></pre>
                    <b-collapse id="dockerfile" v-model="showDockerfile">
                        <pre>{{ initDockerfile.split("\n").slice(1).join("\n") }}</pre>
                    </b-collapse>
                </div>
            </div>

            <b-form-textarea
                v-model="formData.config"
                class="mb-3 dockerfile"
                :disabled="isRunning"
            >
            </b-form-textarea>

            <div v-if="commandOutput !== '' || isRunning">
                <p>{{ $t("Build Command Output") }} <i v-if="isRunning" class="fas fa-spinner fa-spin"></i></p>
                <pre
                    ref="pre"
                    class="border command-output pre-scrollable"
                    :class="{ 'error': exitCode !== 0 }"
                >{{ commandOutput }}</pre>
            </div>

            <div v-if="status === 'done'">
                <p v-if="exitCode === 0">
                    {{ $t('Executor Successfully Built. You can now close this window. ')}}
                </p>
                <div v-if="exitCode > 0" class="invalid-feedback d-block">
                    {{ $t('Error Building Executor. See Output Above.')}}
                </div>
            </div>

            <template v-slot:modal-footer>
                <b-button v-if="showClose" variant="secondary" @click="$bvModal.hide('edit')">
                    {{ $t('Close')}}
                </b-button>

                <b-button v-if="showCancel" :disabled="this.pidFile === null" variant="secondary" @click="cancel">
                    {{ $t('Cancel')}}
                </b-button>

                <b-button v-if="showSave" :disabled="isRunning" variant="primary" @click="save()">
                    <template v-if="formData.id">{{ $t('Save And Rebuild')}}</template>
                    <template v-else>{{ $t('Save And Build')}}</template>
                </b-button>
            </template>
        </b-modal>

    </div>
</template>

<script>
export default {
    data() {
        return {
            commandOutput: "",
            languages: [],
            scriptRunners: [],
            formData: null,
            emptyFormData: {
                name: '',
                description: '',
                config: '',
                language: null,
            },
            errors: {},
            status: 'idle',
            pidFile: null,
            exitCode: 0,
            scriptRunnerFields: ['id', 'language', 'title', 'updated_at', 'edit', 'delete'],
            showDockerfile: false,
            loading: true,
        };
    },
    created() {
        this.reset();
    },
    computed: {
        modalTitle() {
            if (this.formData.id) {
                return this.$t('Edit') + ' ' + this.formData.title;
            }
            return this.$t("Add New Script Executor");
        },
        isRunning() {
            return ['started', 'starting', 'saving', 'running'].includes(this.status);
        },
        showClose() {
            return !this.isRunning;
        },
        showCancel() {
            return this.isRunning;
        },
        showSave() {
            return !this.isRunning;
        },
        languagesSelect() {
            return [
                { value: null, text: this.$t("Select a language") },
                ...this.languages
            ];
        },
        initDockerfile() {
            let content = '';
            if (this.formData.language) {
                content = _.get(
                    this.languages.find(l => l.value === this.formData.language),
                    'initDockerfile',
                    '',
                );
            }
            return content;
        }
    },
    methods: {
        // canDelete(id) {
        //     return true;
        // },
        deleteExecutor(id) {
            ProcessMaker.confirmModal(
                this.$t("Caution!"),
                this.$t(
                    "Are you sure you want to delete {{item}}?",
                    {
                        item: this.scriptRunners.find(sr => sr.id === id).title
                    }
                ),
                '',
                () => {
                    const path = '/script-executors/' + id;
                    ProcessMaker.apiClient.delete(path).then(result => {
                        this.status = _.get(result, 'data.status', 'error');
                        if (this.status === 'done') {
                            this.load();
                            this.$refs.edit.hide();
                        }
                    }).catch(e => {
                        ProcessMaker.alert(e.response.data.errors.delete[0], "danger");
                    });
                }
            );

        },
        getError(name) {
            return _.get(this.errors, name + '.0', false);
        },
        setErrors(errors) {
            this.status = 'error';
            this.errors = errors.response.data.errors;
        },
        doNotHideIfRunning(e) {
            if (this.isRunning) {
                e.preventDefault();
            }
        },
        output(text) {
            if (typeof text !== 'string') {
                return;
            }
            this.commandOutput += text;
        },
        cancel(e) {
            if (this.pidFile) {
                ProcessMaker.apiClient.post('/script-executors/cancel', {
                    pidFile: this.pidFile
                }).then((result) => {
                    if (_.get(result, 'data.status') === 'canceled') {
                        this.status = 'idle';
                        this.$refs.edit.hide();
                    }
                });
            }
        },
        scrollToBottom(){
            if (this.$refs.pre) {
                // after text has rendered
                setTimeout(() => {
                    this.$refs.pre.scrollTop = this.$refs.pre.scrollHeight;
                }, 5);
            }
        },
        save() {
            this.resetProcessInfo();
            this.status = 'saving';
            if (this.formData.id) {
                const path = '/script-executors/' + this.formData.id;
                ProcessMaker.apiClient.put(path, this.formData).then(result => {
                    this.status = _.get(result, 'data.status', 'error');
                }).catch(e => { this.setErrors(e); });
            } else {
                const path = '/script-executors';
                ProcessMaker.apiClient.post(path, this.formData).then(result => {
                    this.status = _.get(result, 'data.status', 'error');
                    if (this.status === 'started') {
                        this.formData.id = result.data.id;
                        this.load(); // refresh the table (beneath the modal)
                    }
                }).catch(e => { this.setErrors(e); });
            }
        },
        add() {
            this.$refs.edit.show();
        },
        edit(id) {
            this.formData = _.cloneDeep(this.scriptRunners.find(i => i.id === id));
            this.$refs.edit.show();
        },
        reset() {
            this.formData = _.cloneDeep(this.emptyFormData);
            this.errors = {};
            this.showDockerfile = false;
            this.status = 'idle',
            this.resetProcessInfo();
        },
        resetProcessInfo() {
            this.commandOutput = '';
            this.exitCode = 0;
            this.pidFile = null;
        },
        load() {
            this.loading = true;
            ProcessMaker.apiClient.get('/script-executors').then(result => {
                this.scriptRunners = result.data.data;
                this.loading = false;
            });
        },
        loadLanguages() {
            ProcessMaker.apiClient.get('/script-executors/available-languages').then(result => {
                this.languages = result.data.languages;
            });
        }
    },
    watch: {
        commandOutput() {
            this.scrollToBottom();
        }
    },
    mounted() {

        this.load();
        this.loadLanguages();

        const userId = _.get(document.querySelector('meta[name="user-id"]'), 'content');
        if (userId) {
            window.Echo.private(`ProcessMaker.Models.User.${userId}`)
                .listen('.BuildScriptExecutor', (event) => {
                    const status = event.status;
                    this.status = status;

                    switch(status) {
                        case 'starting' :
                            this.pidFile = event.output;
                            this.exitCode = 0;
                            break;
                        case 'done' :
                            this.pidFile = null;
                            this.exitCode = event.output;
                            break;
                        case 'error' :
                            this.output(event.output);
                            this.pidFile = null;
                            this.exitCode = 1;
                            this.status = 'done';
                            break;
                        default:
                            this.output(event.output);
                    }
                });
        }
    }
}
</script>
<style scoped>
.command-output {
    font-size: 0.7em;
    height: 200px;
}
.dockerfile {
    height: 300px;
}
.error {
    border-color: red !important;
}
</style>
