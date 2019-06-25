<template>
    <div class="form-group">
        <div>
            <label>{{$t(label)}}</label>
            <button @click="expandEditor" class="btn-sm float-right"><i class="fas fa-expand"></i></button>
        </div>
        <div class="small-editor-container">
            <monaco-editor :options="monacoOptions" v-model="code"
                language="json" class="editor"></monaco-editor>
        </div>
        <small class="form-text text-muted">{{$t(helper)}}</small>
        <b-modal v-model="showPopup" size="lg" centered :title="$t('Script Config Editor')" v-cloak>
            <div class="editor-container">
                <monaco-editor :options="monacoLargeOptions" v-model="code"
                    language="json" class="editor"></monaco-editor>
            </div>
            <div slot="modal-footer">
                <b-button @click="closePopup" class="btn btn-secondary">
                    {{ $t('CLOSE') }}
                </b-button>
            </div>

        </b-modal>
    </div>
</template>

<script>
    import MonacoEditor from "vue-monaco";

    export default {
        props: ["value", "label", "helper", "property"],
        data() {
            const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
            return {
                monacoOptions: {
                    automaticLayout: true,
                    fontSize: 8,
                },
                monacoLargeOptions: {
                    automaticLayout: true,
                },
                code: _.get(node, this.property),
                showPopup: false,
            };
        },
        watch: {
            value() {
                this.code = this.propertyGetter;
            },
            code() {
                const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
                _.set(node, this.property, this.code);
                this.$emit('input', this.value);
            },
        },
        computed: {
            /**
             * Get the value of the edited property
             */
            propertyGetter() {
                const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
                const value = _.get(node, this.property);
                return value;
            }
        },
        methods: {
            /**
             * Open a popup editor
             *
             */
            expandEditor() {
                this.showPopup = true;
            },
            /**
             * Close the popup editor
             *
             */
            closePopup() {
                this.showPopup = false;
            },
        }
    };
</script>

<style lang="scss" scoped>
    .small-editor-container {
        margin-top: 1em;
    }
    .small-editor-container .editor {
        width: 100%;
        height: 12em;
    }
    .editor-container .editor{
        height: 60vh;
    }
</style>
