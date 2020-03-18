<template>
    <div class="container">
        <b-table
            :fields="languagesFields"
            :items="languagesTable"
        >
            <template v-slot:cell(edit)="data">
                <b-btn
                    variant="link"
                    @click="edit(data.item.language)"
                    v-b-tooltip.hover
                    :title="$t('Edit')"
                >
                    <i class="fas fa-pen-square fa-lg fa-fw"></i>
                </b-btn>
            </template>
        </b-table>

        <b-modal ref="edit" id="edit" :title="$t('Edit') + ' ' + editKey + ' Dockerfile'" @hidden="reset()" @hide="doNotHideIfRunning" size="lg">
            <pre>{{ formData.initDockerfile }}</pre>
            <b-form-textarea
                v-model="formData.appDockerfileContents"
                class="mb-3 dockerfile"
                :disabled="isRunning"
            >
            </b-form-textarea>

            <p>{{ $t("Build Command Output") }}: <i v-if="isRunning" class="fas fa-spinner fa-spin"></i></p>

            <pre
                ref="pre"
                class="border command-output pre-scrollable"
                :class="{ error: exitCode !== 0 }"
            >{{ commandOutput }}</pre>

            <div v-if="status === 'done'">
                <p v-if="exitCode === 0">
                    {{ $t('Executor Successfully Built. You can now close this window. ')}}
                </p>
                <p v-else>
                    {{ $t('Error Building Executor. See Output Above.')}}
                </p>
            </div>

            <template v-slot:modal-footer>
                <b-button v-if="showClose" variant="secondary" @click="$bvModal.hide('edit')">
                    {{ $t('Close')}}
                </b-button>

                <b-button v-if="showCancel" :disabled="this.pidFile === null" variant="secondary" @click="cancel">
                    {{ $t('Cancel')}}
                </b-button>

                <b-button v-if="showSave" :disabled="isRunning" variant="primary" @click="save()">
                    {{ $t('Save And Rebuild')}}
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
            languages: {},
            formData: {},
            editKey: '',
            status: 'idle',
            pidFile: null,
            exitCode: 0,
            languagesFields: ['language', 'modified', 'edit']
        };
    },
    computed: {
        isRunning() {
            return ['started', 'saving', 'running'].includes(this.status);
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
        languagesTable() {
            return Object.keys(this.languages).map(key => {
                const mtime = this.languages[key].mtime;
                return {
                    language: key,
                    modified: mtime ? moment.unix(mtime).format() : '',
                }
            })
        }
    },
    methods: {
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
            // e.preventDefault();
            if (this.pidFile) {
                ProcessMaker.apiClient.post('/script-executors/cancel', {
                    pidFile: this.pidFile
                }).then((result) => {
                    if (_.get(result, 'data.status') === 'canceled') {
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
            const path = '/script-executors/' + this.editKey;
            ProcessMaker.apiClient.put(path, this.formData).then(result => {
                this.status = _.get(result, 'data.status', 'error');
            });
        },
        edit(lang) {
            this.formData = _.cloneDeep(this.languages[lang]);
            this.editKey = lang;
            this.$refs.edit.show();
        },
        reset() {
            this.formData = {};
            this.editKey = '';
            this.resetProcessInfo();
        },
        resetProcessInfo() {
            this.commandOutput = '';
            this.exitCode = 0;
            this.pidFile = null;
        }
    },
    watch: {
        commandOutput() {
            this.scrollToBottom();
        }
    },
    mounted() {
        ProcessMaker.apiClient.get('/script-executors').then(result => {
            this.languages = result.data.languages;
        });

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