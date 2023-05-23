<template>
    <b-modal dialog-class="top-20" v-model="showModal" @hide="onClose" :title="title">
        <div class="my-3" :class="classMessage" v-html="message"></div>
        <template #modal-footer>
            <b-button class="m-0" @click="onClose">Ok</b-button>
        </template>
    </b-modal>
</template>


<script>
    export default {
        props: ["title", "message", "variant", "callback", "show"],
        data() {
            return {
                'classMessage': '',
                'classButtonCancel': '',
                'classButtonConfirm': '',
                'showModal': false,
            }
        },
        watch: {
            variant(value) {
                this.styles()
            },
            show(value) {
                this.showModal = value;
            }
        },
        methods: {
            styles() {
                this.classMessage = '';
                this.classButtonCancel = 'btn btn-outline-success btn-sm text-uppercase';
                this.classButtonConfirm = 'btn btn-success btn-sm text-uppercase';
                if (this.variant) {
                    this.classMessage += ' text-' + this.variant;
                    this.classButtonCancel += ' btn-outline-' + this.variant;
                    this.classButtonConfirm += ' btn-' + this.variant;
                }
            },
            onClose() {
                if (this.callback) {
                    this.callback();
                }
                this.$emit('close');
            },
        },
        mounted() {
            this.$emit("show");
            this.styles();
        }
    }
</script>

<style>
    .top-20 {
        top: 20%;
    }
</style>
