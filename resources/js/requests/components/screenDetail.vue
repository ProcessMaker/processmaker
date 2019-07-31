<template>
    <div class="card">
        <div class="card-body" >
            <vue-form-renderer ref="print" v-model="formData" :config="json"/>
        </div>
        <!--<div class="card-footer">
            <button type="button" class="btn btn-outline-success" @click="print">
                <i class="fas fa-print"></i> Print
            </button>
        </div>-->

    </div>
</template>

<script>
    export default {
        inheritAttrs: false,
        props: {
            rowData: {
                type: Object,
                required: true
            },
            rowIndex: {
                type: Number
            }
        },
        computed: {
            json() {
                return this.disableForm(this.rowData.config);
            },
            formData() {
                return this.rowData.data ? this.rowData.data : {};
            }
        },
        methods: {
            /**
             * Disable the form items.
             *
             * @param {array|object} json
             * @returns {array|object}
             */
            disableForm(json) {
                if (json instanceof Array) {
                    for (let item of json) {
                        if (item.component==='FormButton' && item.config.event==='submit') {
                            json.splice(json.indexOf(item), 1);
                        } else {
                            this.disableForm(item);
                        }
                    }
                }
                if (json.config !== undefined) {
                    json.config.disabled = true;
                    json.config.readonly = true;
                }
                if (json.items !== undefined) {
                    this.disableForm(json.items);
                }
                return json;
            },
            onClick (event) {
                console.log('my-detail-row: on-click', event.target)
            },
            print() {
                let content = this.$refs.print.$el.outerHTML;
                let w = window.open();
                w.document.write(content);
                w.document.close();
                w.focus();
                w.print();
                w.close();
                return true;
            }
        },
    }
</script>