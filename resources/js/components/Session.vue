<template>
    <b-modal
        id="sessionModal"
        ref="sessionModal"
        :title="title"
        footer-class="pm-modal-footer"
        no-close-on-backdrop
        centered
    >
        <span v-html="message"></span>
        <div class="progress">
            <div class="progress-bar progress-bar-striped" role="progressbar" :style="{width: percentage + '%'}">
                <span align="left" class="pl-2">{{moment().startOf('day').seconds(time).format('mm:ss')}}</span>
            </div>
        </div>
        <template #modal-footer>
            <a role="button" class="btn btn-outline-secondary ml-2" href="/logout" :disabled="disabled">{{('LogOut')}}</a>
            <button type="button" class="btn btn-secondary ml-2" @click="keepAlive" :disabled="disabled">{{('Stay Connected')}}</button>
        </template>
    </b-modal>
</template>


<script>

    export default {
        props: ["title", "message", "time", "warnSeconds", "shown"],
        data() {
            return {
              errors: {},
              disabled: false
            }
        },
        watch: {
            shown(value) {
                if (value) {
                    this.$refs.sessionModal.show();
                } else {
                    this.$refs.sessionModal.hide();
                }
            }
        },
        computed: {
            percentage() {
                if (this.time === "" || this.warnSeconds === "") {
                    return 0;
                }
                return Math.round((this.time / this.warnSeconds) * 100);
            }
        },
        methods: {
            onClose() {
                this.$emit('close');
            },
            keepAlive() {
                this.disabled = true;
                ProcessMaker.apiClient
                  .post("/keep-alive", {}, {baseURL: ''})
                  .then(() => {
                    this.disabled = false;
                    ProcessMaker.AccountTimeoutWorker.postMessage({
                        method: 'start',
                        data: {
                            timeout: ProcessMaker.AccountTimeoutLength,
                            warnSeconds: ProcessMaker.AccountTimeoutWarnSeconds,
                        }
                    });
                    this.onClose();
                  })
                  .catch(error => {
                    this.disabled = false;
                    this.errors = error.response.data.errors;
                  });
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
        background: rgba(0, 0, 0, .5);
        z-index: 1060;
        display: flex;
    }


</style>
