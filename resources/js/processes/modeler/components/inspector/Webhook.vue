<template>
    <div class="form-group">
        <label>{{ $t(label) }}</label>
        <span class="float-right">
            <a v-if="!enabled" href="#" @click="enable">{{ $t('Enable') }}</a>
            <a v-if="enabled" href="#" @click="disable">{{ $t('Disable') }}</a>
        </span>
        <div v-if="enabled">
            <div v-if="loading">{{ $t('Loading...') }}</div>
            <div v-else>
                <input class="form-control" :readonly="fieldDisabled" v-model="url" ref="webhookUrlInput" />
                <a href="#" @click="copy">{{ $t('Copy To Clipboard') }}</a>
            </div>
        </div>
    </div>
</template>


<script>
    export default {
        props: ["value", "label", "helper", "params"],
        data() {
            return {
                content: "",
                loading: true,
                url: null,
                fieldDisabled: true,
            };
        },
        computed: {
            enabled() {
                return this.url !== null
            }
        },
        mounted() {
            this.load();
        },
        methods: {
            node() {
                return this.$parent.$parent.highlightedNode.definition.id;
            },
            processId() {
                return window.ProcessMaker.modeler.process.id;
            },
            enable() {
                this.createNewApiCall();
            },
            disable() {
                this.revoke();
            },
            endpoint() {
                return "/processes/" + this.processId() + '/webhooks/?node=' + this.node();
            },
            load() {
                this.loading = true;
                ProcessMaker.apiClient
                        .get(this.endpoint())
                        .then(response => {
                            if (response.data.webhook === null) {
                                this.url = null;
                            } else {
                                this.url = response.data.webhook.url;
                            }
                        })
                        .catch(err => {
                        })
                        .finally(() => {
                            this.loading = false;
                        });
            },
            revoke() {
                ProcessMaker.confirmModal(
                    "Caution!",
                    "<p>Are you sure you want to disable this webhook? If you re-enable the webhook later, it will have a different URL.</p>",
                    "",
                    () => { this.revokeApiCall() }
                );
            },
            copy() {
                this.fieldDisabled = false;
                this.$refs.webhookUrlInput.select()
                document.execCommand('copy')
                this.fieldDisabled = true;
            },
            revokeApiCall() {
                this.loading = true;
                ProcessMaker.apiClient
                        .delete(this.endpoint())
                        .then(response => {
                            this.url = null;
                        })
                        .catch(err => {
                        })
                        .finally(() => {
                            this.loading = false;
                        });
            },
            createNewApiCall() {
                this.loading = true;
                ProcessMaker.apiClient
                        .post(this.endpoint())
                        .then(response => {
                            this.url = response.data.webhook.url;
                        })
                        .catch(err => {
                        })
                        .finally(() => {
                            this.loading = false;
                        });
            },
        }
    };
</script>

<style lang="scss" scoped>
</style>