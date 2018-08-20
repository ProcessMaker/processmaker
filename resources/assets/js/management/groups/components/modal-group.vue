<template>
    <b-modal v-model="opened" size="md" centered @hidden="onClose" @show="onShow" @close="onClose" :title="labels.panel" v-cloak>
        <form-input :error="errors.title" v-model="title" :label="labels.title"
                    helper="Title group must be distinct"></form-input>
        <form-select :error="errors.status" :label="labels.status" v-model="status"
                     :options="statusOptions"></form-select>
        <div slot="modal-footer">
            <b-button @click="onClose" class="btn btn-outline-success btn-sm text-uppercase">
                Cancel
            </b-button>
            <b-button @click="onSave" class="btn btn-success btn-sm text-uppercase">
                Save
            </b-button>
        </div>
    </b-modal>
</template>

<script>
    import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";
    import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";

    export default {
        components: {FormSelect, FormInput},
        data() {
            return {
                'title': '',
                'status': '',
                'errors': {
                    'title': null,
                    'description': null
                },
                'statusOptions': [
                    {
                        'value': 'ACTIVE',
                        'content': 'Active'
                    },
                    {
                        'value': 'DISABLED',
                        'content': 'Disabled'
                    }
                ],
                'opened': this.show
            }
        },
        props: ['show', 'groupUid', 'labels'],
        watch: {
            show(value) {
                this.opened = value;
            }
        },
        methods: {
            onHidden() {
                this.$emit('hidden')
            },
            onClose() {
                this.$emit('close');
            },
            fetch() {
                ProcessMaker.apiClient.get("groups/" + this.groupUid)
                    .then(response => {
                        this.title = response.data.title;
                        this.status = response.data.status;
                    })
            },
            onShow() {
                this.title = '';
                this.status = 'ACTIVE';
                if (this.groupUid) {
                    this.fetch();
                }
            },
            onUpdate() {
                ProcessMaker.apiClient.put('groups/' + this.groupUid, {
                    'title': this.title,
                    'status': this.status
                })
                    .then((response) => {
                        // Close modal
                        this.onClose();
                        this.groupUid = null;
                        this.$emit('reload');
                    })
                    .catch(error => {
                        //define how display errors
                        if (error.response.status === 422) {
                            // Validation error
                            let fields = Object.keys(error.response.data.errors);
                            for (let field of fields) {
                                this.errors[field] = error.response.data.errors[field][0];
                            }
                        }
                    });
            },
            onSave() {
                if (this.groupUid) {
                    this.onUpdate();
                    return;
                }
                ProcessMaker.apiClient.post('groups', {
                    'title': this.title,
                    'status': this.status
                })
                    .then((response) => {
                        // Close modal
                        this.onClose();
                        this.$emit('reload');
                    })
                    .catch(error => {
                        //define how display errors
                        if (error.response.status === 422) {
                            // Validation error
                            let fields = Object.keys(error.response.data.errors);
                            for (let field of fields) {
                                this.errors[field] = error.response.data.errors[field][0];
                            }
                        }
                    });
            }
        }
    };
</script>
<style lang="scss" scoped>

</style>
