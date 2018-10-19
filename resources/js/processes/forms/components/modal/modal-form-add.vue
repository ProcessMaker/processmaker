<template>
    <b-modal v-model="opened" size="md" centered @hidden="onClose" @show="onReset" @close="onClose"
             title="Create New Form" v-cloak>
        <create-form ref="fieldForm" v-on:save="afterSave"></create-form>

        <div slot="modal-footer">
            <b-button @click="onClose" class="btn btn-outline-success btn-sm text-uppercase">
                CANCEL
            </b-button>
            <b-button @click="onSave" class="btn btn-success btn-sm text-uppercase">
                SAVE
            </b-button>
        </div>

    </b-modal>
</template>

<script>
    import CreateForm from "../fields-form";

    export default {
        components: {CreateForm},
        props: ['show'],
        data() {
            return {
                'opened': this.show
            }
        },
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
            onReset() {
                this.$refs.fieldForm.resetData();
            },
            onSave() {
                this.$refs.fieldForm.onSave();
            },
            afterSave() {
                this.onClose();
                ProcessMaker.alert('Create Form Successfully', 'success');
                this.$emit('reload');
            }
        }
    };
</script>
<style lang="scss" scoped>
    .inline-input {
        margin-right: 6px;
    }

    .inline-button {
        background-color: rgb(109, 124, 136);
        font-weight: 100;
    }

    .input-and-select {
        width: 212px;
    }
</style>
