<template>
    <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Blank Form">
        <form-input :error="errors.title" v-model="title" label="Title" required="required"></form-input>
        <form-text-area :error="errors.description" :rows="3" v-model="description"
                        label="Description"></form-text-area>

        <div slot="modal-footer">
            <b-button @click="onClose" class="btn btn-outline-success btn-md">
                CANCEL
            </b-button>
            <b-button @click="onSave(true)" class="btn btn btn-success btn-sm text-uppercase">
                SAVE & OPEN
            </b-button>
            <b-button @click="onSave(false)" class="btn btn btn-success btn-sm text-uppercase">
                SAVE
            </b-button>
        </div>

    </b-modal>

</template>

<script>
    import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";
    import FormTextArea from "@processmaker/vue-form-elements/src/components/FormTextArea";
    export default {
        components: {FormTextArea, FormInput},
        data() {
            return {
                'title': '',
                'description': '',
                'errors': {
                    'title': null,
                    'description': null
                },
            }
        },
        props: [ 'processUid'],
        methods: {
            onHidden() {
                this.$emit('hidden')
            },
            onClose() {
                this.$refs.modal.hide()
            },
            onSave(open) {
                ProcessMaker.apiClient
                    .post(
                        'process/' +
                        this.processUid +
                        '/form',
                        {
                            title: this.title,
                            description: this.description
                        }
                    )
                    .then(response => {
                        ProcessMaker.alert('New Form Successfully Created', 'success');
                        this.onClose();
                        if (open) {
                            //Change way to open the designer
                            window.location.href = '/designer/' + this.processUid + '/form/' + response.data.uid;
                        }
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
        },
        mounted() {
            // Show our modal as soon as we're created
            this.$refs.modal.show();
        }
    };
</script>
<style lang="scss" scoped>

</style>
