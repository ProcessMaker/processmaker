<template>
    <div id="form-container">
        <div id="form-toolbar">
            <nav class="navbar navbar-expand-md override">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item active">
                        <a class="nav-link" @click="mode = 'editor'" href="#">Editor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" @click="mode = 'preview'" href="#">Preview</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" @click="openComputedProperties" href="#">Computed Properties</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" @click="openCustomCSS" href="#">Custom CSS</a>
                    </li>
                </ul>

                <ul class="navbar-nav  pull-right">
                    <li class="nav-item">
                        <a class="nav-link" @click="saveScreen" href="#"><i class="fas fa-save"></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" @click="onClose" href="#"><i class="fas fa-times"></i></a>
                    </li>
                </ul>
            </nav>
        </div>

        <computed-properties v-model="computed" ref="computedProperties"></computed-properties>
        <custom-CSS v-model="customCSS" ref="customCSS" :css-errors="cssErrors" />
        <vue-form-builder :class="{invisible: mode != 'editor'}" @change="updateConfig" ref="screenBuilder"
            v-show="mode === 'editor'" config="config" computed="computed"/>
            <div id="preview" :class="{invisible: mode != 'preview'}">
             <div id="data-input">
                    <div class="card-header">
                        Data Input
                    </div>
                    <div class="alert" :class="{'alert-success': previewInputValid, 'alert-danger': !previewInputValid}">
                         <span v-if="previewInputValid">Valid JSON Data Object</span>
                        <span v-else>Invalid JSON Data Object</span>
                    </div>
                    <form-text-area rows="20" v-model="previewInput"></form-text-area>

                </div>

                <div id="renderer-container">
                    <div class="container">
                        <div class="row">
                            <div class="col-sm">
                                <vue-form-renderer ref="renderer" @submit="previewSubmit" v-model="previewData"
                                                   :config="config" :computed="computed"  :custom-css="customCSS" v-on:css-errors="cssErrors = $event" />
                            </div>
                        </div>
                    </div>
                </div>
                <div id="data-preview">
                    <div class="card-header">
                        Data Preview
                    </div>
                    <vue-json-pretty :data="previewData"></vue-json-pretty>
                </div>
            </div>
    </div>
</template>

<script>
    import ComputedProperties from "@processmaker/vue-form-builder/src/components/computed-properties";
    import CustomCSS from "@processmaker/vue-form-builder/src/components/custom-css.vue";
    import VueFormBuilder from "@processmaker/vue-form-builder/src/components/vue-form-builder";
    import VueFormRenderer from "@processmaker/vue-form-builder/src/components/vue-form-renderer";
    import VueJsonPretty from "vue-json-pretty";
    import {FormTextArea} from "@processmaker/vue-form-elements/src/components";

    export default {
        data() {
            return {
                mode: "editor",
                computed: [],
                customCSS: "",
                cssErrors: "",
                config: [
                    {
                        name: "Default",
                        items: [],
                        computed: []
                    }
                ],
                previewData: null,
                previewInput: "{}"
            };
        },
        components: {
            CustomCSS,
            ComputedProperties,
            VueFormRenderer,
            VueFormBuilder,
            VueJsonPretty,
            FormTextArea
        },
        watch: {
            previewInput() {
                if (this.previewInputValid) {
                    // Copy data over
                    this.previewData = JSON.parse(this.previewInput);
                } else {
                    this.previewData = {}
                }
            }
        },
        computed: {
            previewInputValid() {
                try {
                    if (typeof this.previewInput === 'string' && this.previewInput.length === 0) {
                        return false
                    }
                    if (typeof this.previewInput === 'object' && Object.keys(this.previewInput).length === 0) {
                        return true;
                    }
                    JSON.parse(this.previewInput);
                    return true;
                } catch (err) {
                    return false;
                }
            }
        },
        props: ["process", "screen"],
        mounted() {
            // Add our initial controls
            // Iterate through our initial config set, calling this.addControl
            // Call our init lifecycle event
            ProcessMaker.EventBus.$emit('screen-builder-init', this);
            this.$refs.screenBuilder.config = this.screen.config
                    ? this.screen.config
                    : [
                        {
                            name: "Default",
                            items: []
                        }
                    ];

            this.computed = this.screen.computed ? this.screen.computed : [];
            this.customCSS = this.screen.custom_css ? this.screen.custom_css : '';

            this.$refs.screenBuilder.computed = this.screen.computed
                    ? this.screen.computed
                    : [];


            if (this.screen.title) {
                this.$refs.screenBuilder.config[0].name = this.screen.title;
            }
            this.updatePreview(new Object());
            this.previewInput = "{}";
            ProcessMaker.EventBus.$emit('screen-builder-start', this);
        },
        methods: {
            openComputedProperties() {
                this.$refs.computedProperties.show();
            },
            openCustomCSS() {
                this.$refs.customCSS.show();
            },
            addControl(
                    control,
                    rendererComponent,
                    rendererBinding,
                    builderComponent,
                    builderBinding
                    ) {
                // Add it to the renderer
                this.$refs.renderer.$options.components[
                        rendererBinding
                ] = rendererComponent;
                // Add it to the screen builder
                this.$refs.screenBuilder.addControl(control);
                this.$refs.screenBuilder.$options.components[
                        builderBinding
                ] = builderComponent;
            },
            updateConfig(newConfig) {
                this.config = newConfig;
            },
            updatePreview(data) {
                console.log(typeof data);
                this.previewData = data;
            },
            previewSubmit() {
                alert("The preview screen was submitted.");
            },
            onClose() {
                window.location.href = "/processes/screens";
            },
            saveScreen() {
                ProcessMaker.apiClient
                        .put("screens/" + this.screen.id, {
                            title: this.screen.title,
                            description: this.screen.description,
                            type: this.screen.type,
                            config: this.config,
                            computed: this.computed,
                            custom_css: this.customCSS,
                        })
                        .then(response => {
                            ProcessMaker.alert("The screen was saved.", "success");
                        });
            }
        }
    };
</script>

<style lang="scss">
    div.main {
        position: relative;
    }

    #screen-container {
        position: absolute;
        width: 100%;
        height: 100%;
    }

    #form-container {
        height: 100%;
        display: flex;
        flex-direction: column;

        .dynaform-builder {
            height: auto;
            flex-grow: 1;

            .invisible {
                display: none;
            }

        }
    }

    #preview {
        display: flex;
        flex-grow: 1;

        #renderer-container {
            flex-grow: 1;
            padding-top: 32px;
        }

        #data-input {
            min-width: 340px;
            width: 340px;
            max-width: 340px;
            border-right: 1px solid #e9edf1;
            overflow: auto;
        }

        #data-preview {
            height: 100%;
            min-width: 340px;
            width: 340px;
            max-width: 340px;
            border-left: 1px solid #e9edf1;
            overflow: auto;
        }
    }

    .inspector-container {
        min-width: 340px;
        width: 340px;
        max-width: 340px;
        border-left: 1px solid #e9edf1;
        overflow: auto;
    }

    #form-toolbar .override {
        background-color: #b6bfc6;
        padding: 10px;
        height: 40px;
        font-size: 18px;
        font-style: normal;
        font-stretch: normal;
        line-height: normal;
        letter-spacing: normal;
        text-align: left;
        color: #ffffff;

        .nav-item {
            padding-top: 0;
        }

        a.nav-link,
        a.nav-link:hover {
            color: white !important;
            padding-right: 15px;
            font-weight: 400;
        }
    }

    .inspector-container {
        .container-fluid {
            padding: 5px 10px;
        }

        label {
            font-size: 12px;
        }

        .small {
            font-size: 10px;
        }
    }

    .dynaform-builder {
        .palette-container {
            #controls {
                .control {
                    .icon {
                        width: 42px;
                        margin: 0 8px;
                        display: flex;
                    }
                }
            }
        }
    }
</style>