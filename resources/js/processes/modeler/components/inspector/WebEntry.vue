<template>
    <div class="form-group">
        <label>{{ $t(label) }}</label>
        <div v-if="loading">{{ $t('Loading...') }}</div>
        <div v-else>
            <input class="form-control" :readonly="fieldDisabled" v-model="url" ref="webEntryUrlInput" />
            <a href="#" @click="copy">{{ $t('Copy To Clipboard') }}</a>
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
            endpoint() {
                return "/processes/" + this.processId() + '/web_entries/?node=' + this.node();
            },
            load() {
                this.loading = true;
                ProcessMaker.apiClient
                        .get(this.endpoint())
                        .then(response => {
                            if (response.data.web_entry === null) {
                                this.url = null;
                            } else {
                                this.url = response.data.web_entry.url;
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
                    "<p>Are you sure you want to disable this web entry? If you re-enable the web entry later, it will have a different URL.</p>",
                    "",
                    () => { this.revokeApiCall() }
                );
            },
            copy() {
                this.fieldDisabled = false;
                this.$refs.webEntryUrlInput.select()
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
                            this.url = response.data.web_entry.url;
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