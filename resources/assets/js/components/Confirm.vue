<template>
    <div class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ title }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onCancel">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body mb-1 text-center">
                    <p :class="'text-'+variant">{{ message }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-success btn-sm text-uppercase" data-dismiss="modal"
                            @click="onCancel">Close
                    </button>
                    <button type="button" class="btn btn-success btn-sm text-uppercase" @click="onConfirm">Confirm
                    </button>

                </div>
            </div>
        </div>
    </div>
</template>


<script>
    export default {
        props: ["title", "message", "variant", "callback"],
        methods: {
            onClose() {
                this.$emit('show', false);
                this.$emit('close');
            },
            onConfirm() {
                this.$emit("confirm", true);
                if (this.callback) {
                    this.callback();
                }
                this.onClose();
            },
            onCancel() {
                this.$emit("confirm", false);
                this.onClose();
            }
        },
        mounted() {
            this.$emit("show");
        }
    }
</script>

<style scoped>

    .modal {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        background: rgba(0, 0, 0, .5);
        z-index: 1060;
        display: flex;
        min-width: 30%;
    }

    .modal-dialog {
        min-width: 400px;
        top: 20%;
    }

</style>