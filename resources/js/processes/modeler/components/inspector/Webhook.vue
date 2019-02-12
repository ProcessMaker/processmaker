<template>
    <div class="form-group">
        <label>{{label}}</label>
        <span class="float-right">
            <a v-if="!enabled" href="#" @click="enable">Enable</a>
            <a v-if="enabled" href="#" @click="disable">Disable</a>
        </span>
        <div v-if="enabled">
            <div v-if="loading">Loading...</div>
            <div v-else>
                <input class="form-control" disabled="true" v-model="url" />
                <a href="#" @click="createNew">Create New</a>
                &bull;
                <a href="#" @click="revoke" :disabled="url != null">Revoke</a>
                &bull;
                <a href="#" @click="copy">Copy</a>
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
            };
        },
        computed: {
            // node() {
            //     return this.$parent.$parent.highlightedNode.definition;
            // }
            enabled() {
                return this.url !== null
            }
        },
        mounted() {
            this.load();
        },
        methods: {
            enable() {
                this.createNewApiCall();
            },
            disable() {
                this.revokeApiCall();
            },
            load() {
                this.loading = true;
                let params = Object.assign({type:'FORM'}, this.params);
                ProcessMaker.apiClient
                        .get("/screens", {
                            params: params
                        })
                        .then(response => {
                            this.screens = response.data.data;
                            this.loading = false;
                        })
                        .catch(err => {
                            this.loading = false;
                        });
            },
            createNew() {
                if (this.url != null) {
                    ProcessMaker.confirmModal(
                        "Caution!",
                        "<b>Are you sure to re-create this webhook? The current one will no longer work.</b>",
                        "",
                        () => { this.createNewApiCall() }
                    );
                }
            },
            revoke() {
                ProcessMaker.confirmModal(
                    "Caution!",
                    "<b>Are you sure to revoke this webhook? It will not longer work.</b>",
                    "",
                    () => { this.revokeApiCall() }
                );
            },
            copy() {
            },
            revokeApiCall() {
            },
            createNewApiCall() {
            },
        }
    };
</script>

<style lang="scss" scoped>
</style>