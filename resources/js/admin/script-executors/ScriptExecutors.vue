<template>
    <div class="container">
        <div v-for="(_, lang) in languages" :key="lang">
            <b-button @click="edit(lang)">Edit {{ lang }}</b-button>
        </div>

        <b-modal ref="edit" title="Edit" @shown="scrollToBottom()" @hidden="reset()">
statuss: {{ status }}
            <b-form-textarea
                v-model="formData.appDockerfileContents"
                class="mb-3 dockerfile"
            >
            </b-form-textarea>

            <p>{{ $t("Build Command Output") }} <i v-if="showSpinner" class="fas fa-spinner fa-spin"></i></p>

            <pre ref="pre" class="border command-output pre-scrollable">{{ commandOutput }}</pre>
            <template v-slot:modal-footer>
                <b-button variant="secondary" @click="reset()">
                    Cancel
                </b-button>
                <b-button variant="primary" @click="save()">
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
            showSpinner: false,
            status: 'idle',
        };
    },
    computed: {
        showSpinner() {
            this.status == 'started' || this.status == 'saving';
        }
    },
    methods: {
        output(text) {
            this.commandOutput += text;
            this.scrollToBottom();
        },
        checkStatus(status) {
            if (status) {
                if (status === 'ok') {
                    this.status = 'done';
                }
            }
        },
        scrollToBottom(){
            if (this.$refs.pre) {
                this.$refs.pre.scrollTop = this.$refs.pre.scrollHeight;
            }
        },
        save() {
            this.status = 'saving';
            const path = '/script-executors/' + this.editKey;
            ProcessMaker.apiClient.put(path, this.formData).then(result => {
                this.status = _.get(result, 'data.status', 'error');
            });
        },
        edit(lang) {
            this.formData = this.languages[lang];
            this.editKey = lang;
            this.$refs.edit.show();
        },
        reset() {
            this.formData = {};
            this.editKey = '';
            this.$refs.edit.hide();
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
                    this.checkStatus(event.status);
                    this.output(event.output);
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
</style>