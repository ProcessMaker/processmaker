<template>
    <div class="p-3">
        <b-card-group deck v-if="!showCssPreview" class="template-preview-card">
            <b-card
                class="preview-card"
                header-tag="header"
                footer-tag="footer"
            >
                <template #header>
                    <h4>
                        <b-button v-if="!hideBackArrow" @click="hidePreview" variant="link" class="p-0 back-btn mr-2">
                            <i class="fas fa-arrow-circle-left text-secondary"></i>
                        </b-button>
                        {{ templateData?.name }}
                    </h4>
                </template>
                <div class="thumbnail-preview">
                    <div v-if="templateHasThumbnails" class="text-center">
                        <img 
                            v-for="thumbnail in templateData?.thumbnails"
                            class="thumb mb-2"
                            :src="thumbnail"
                            fluid
                            :alt="templateData?.name + ' thumbnail preview'"
                        />
                    </div> 
                    <div v-else>
                        {{ $t('No preview images available for this template.') }}
                    </div>
                </div>
                <template #footer>
                    <b-row class="d-flex p-2" align-h="between" align-v="center">
                        <b-button variant="outline-secondary" @click="showTemplateCss" class="text-uppercase"> 
                            <i class="fas fa-file-code"></i> 
                            {{ $t('View CSS') }}
                        </b-button>

                        <b-form-group v-if="!hideTemplateOptions" class="template-options-group">
                            <b-form-checkbox-group
                                id="template-options"
                                v-model="selectedTemplateOptions"
                                :options="templateOptions"
                                name="template-options"
                            ></b-form-checkbox-group>
                        </b-form-group>
                    </b-row>
                </template>
            </b-card>
        </b-card-group>

        <b-card-group deck v-if="showCssPreview" class="css-preview-card p-0">
            <b-card
                class="preview-card"
                header-tag="header"
            >
                <template #header>
                    <b-row class="d-flex m-0" align-h="between" align-v="center">
                       <h4>{{ $t('Custom CSS') }}</h4>
                        <b-button @click="hideTemplateCss" variant="outline-secondary" class="text-uppercase">
                            <i class="fas fa-cubes"></i> 
                            {{ $t('Templates') }}
                        </b-button>
                    </b-row>
                </template>
                <div class="css-preview">
                    <monaco-editor
                        v-if="templateHasCss"
                        ref="monacoEditor"
                        v-model="code"
                        :options="monacoOptions"
                        language="css"
                        class="editor"
                    />
                    <div v-else>
                        {{ $t('There\'s no custom CSS for this template.') }}
                    </div>
                </div>
            </b-card>
        </b-card-group>
    </div>
</template>

<script>
    export default {
        components: {},
        props: ["template", "hideBackArrow", "hideTemplateOptions"],
        data: function() {
            return {
                type: null,
                templateData: null,
                selectedTemplateOptions: ['CSS', 'Layout', 'Fields'],
                templateOptions: [ 'CSS', 'Layout', 'Fields'],
                showCssPreview: false,
                code: "",
                monacoOptions: {
                    automaticLayout: true,
                    fontSize: 12,
                    readOnly: true,
                },
            }
        },
        computed: {
            templateHasCss() {
                return this.templateData.screen_custom_css !== null;
            },
            templateHasThumbnails() {
                return this.templateData?.thumbnails.length > 0;
            }
        },
        methods: {
            hidePreview() {
                this.$emit('hide-template-preview');
            },
            showTemplateCss() {
                this.showCssPreview = true;
                this.code = this.templateData?.screen_custom_css;
            },
            hideTemplateCss() {
                this.showCssPreview = false;
            }
        },
        mounted() {
            this.templateData = this.template.template ? this.template.template : this.template;
            this.type = this.template.type;
        }
    }
</script>

<style type="text/css" scoped>

    .template-preview-card .card-body {
        overflow-y:auto;
    }
    .back-btn {
        font-size: 25px;
    }
    .preview-card {
        height: 100vh;
        max-height: 600px;
        border-radius: 7px;
    }

    .card-header,
    .card-footer {
        background: #fff;
    }

    .thumbnail-preview {
        overflow: hidden;
    }

    .thumbnail-preview img.thumb {
        max-width: 100%;
    }

    .template-options-group {
        margin: 0!important;
        border: 1px solid #6A7888;
        padding: 6px 15px!important;
        border-radius: 7px;
    }

    .template-options-group .template-options:last-child {
        margin:0;
    }

    .css-preview-card .card-body {
        overflow: hidden;
    }

    .css-preview-card .css-preview {
        height: 100vh;
    }

    .css-preview-card .css-preview .editor {
        height: inherit;
    }
</style>