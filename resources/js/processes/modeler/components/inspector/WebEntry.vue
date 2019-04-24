<template>
    <div>
        <label>{{ $t(label) }}</label>
        <div v-if="loading">{{ $t('Loading') }}...</div>
        <div v-else>
            <div class="form-group">
                <select class="form-control" v-model="formData.mode">
                    <option value="null">Disabled</option>
                    <option value="ANONYMOUS">Anonymous</option>
                    <option value="AUTHENTICATED">Authenticated</option>
                </select>
            </div>
            <div v-if="formData.mode !== 'null'">
                <div class="form-group">
                    <select class="form-control" v-model="formData.completed_action">
                        <option value="SCREEN">Screen</option>
                        <option value="URL">Url</option>
                    </select>
                </div>
                <div class="form-group" v-if="formData.completed_action === 'SCREEN'">
                    <select class="form-control" v-model="formData.completed_screen_id">
                        <option value="null">nope</option>
                    </select>
                </div>
                <div class="form-group" v-if="formData.completed_action === 'URL'">
                    <input class="form-control" v-model="formData.completed_url" />
                </div>
            </div>
            <div class="form-group">
                <button @click="save()">Save</button>
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
                formData: { 
                    mode: null,
                    completed_action: 'SCREEN',
                    completed_screen_id: null,
                    completed_url: null
                },
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
                            if (response.data.id) {
                                this.formData = response.data
                            }
                        })
                        .catch(err => {
                        })
                        .finally(() => {
                            this.loading = false;
                        });
            },
            save() {
                console.log("SAVING");
                if (this.loading) {
                    console.log("But loading, so returning")
                    return;
                }
                this.loading = true;
                ProcessMaker.apiClient
                        .post(this.endpoint(), this.formData)
                        .then(response => {
                            this.formData = response.data
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