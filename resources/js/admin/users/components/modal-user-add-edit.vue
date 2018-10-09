<template>
    <b-modal v-model="opened" size="md" centered @hidden="onClose" @close="onClose"
             title="Create New User" v-cloak>
        <form-users ref="formUser" :input-data="data" :errors="errors" v-on:save="afterSave"></form-users>
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
    import FormUsers from "./fields-users";

    export default {
        components: {FormUsers},
        props: ['show', 'data', 'errors'],
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
                this.$refs.formUser.reset();
            },
            onSave() {
                this.$refs.formUser.onSave();
            },
            afterSave() {
                this.onClose();
                ProcessMaker.alert('Create User Successfully', 'success');
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
