<template>
    <div id="editor-container">
        <div class="toolbar">
            <nav class="navbar navbar-expand-md override">
                <span> {{script.title}} ({{script.language}})</span>
                <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                    <ul class="navbar-nav">
                        <li class="nav-item ">
                            <a href="#" title="Save Script" @click="save">
                                <i class="fas fa-save"></i>
                            </a>
                        </li>
                        <li class="nav-item ">
                            <a href="#" @click="onClose" title="Return to Designer">
                                <i class="fas fa-times"></i>
                            </a>
                        </li>
                    </ul>
                </div>

            </nav>
        </div>
        <monaco-editor :options="monacoOptions" v-model="code" :language="script.language" class="editor" :class="{hidden: resizing}"></monaco-editor>
        <div class="editor" v-if="resizing"></div>
        <div class="preview border-top">
            <div class="data border-right">
                <div class=" p-1 bg-secondary border-bottom text-white">Input Data JSON</div>
                <monaco-editor :options="monacoOptions" v-model="preview.data" language="json" class="editor" :class="{hidden: resizing}"></monaco-editor>
            </div>
            <div class="config border-right">
                <div class="p-1 bg-secondary border-bottom text-white">Script Config JSON</div>
                <monaco-editor :options="monacoOptions" v-model="preview.config" language="json" class="editor" :class="{hidden: resizing}"></monaco-editor>

            </div>
            <div class="output">
                <div class="p-1 bg-secondary border-bottom text-white">Script Output</div>
                <div class="btn-group" role="group">
                    <button :disabled="preview.executing" @click="execute" class="btn btn-primary"><i class="fas fa-play-circle"></i></button>
                    <button type="button" class="btn btn-light"></button>
                    <button type="button" class="btn btn-light text-right">
                        <i v-if="preview.executing" class="fas fa-spinner fa-spin"></i>
                        <i v-if="preview.success" class="fas fa-check text-success"></i>
                        <i v-if="preview.failure" class="fas fa-times-circle text-danger"></i>
                    </button>
                </div>
                <div class="content" style="overflow: auto; width: 100%;">
                    <pre v-if="preview.success" v-text="preview.output"></pre>
                    <div v-if="preview.failure" class="w-100">
                        <div class="text-light bg-danger">{{preview.error.exception}}</div>
                        <div class="text-light text-monospace small">{{preview.error.message}}</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    import MonacoEditor from "vue-monaco";
    import _ from "lodash";

    export default {
        props: ["process", "script"],
        data() {
            return {
                resizing: false,
                monacoOptions: {
                    automaticLayout: true
                },
                code: this.script.code,
                preview: {
                    executing: false,
                    data: "{}",
                    config: "{}",
                    output: '',
                    success: false,
                    failure: false,
                }
            };
        },
        components: {
            MonacoEditor
        },
        mounted() {
            window.addEventListener("resize", this.handleResize);
            let userID = document.head.querySelector("meta[name=\"user-id\"]");
            window.Echo.private(`ProcessMaker.Models.User.${userID.content}`)
                .notification((response) => {
                    this.outputResponse(response);
                });
        },
        beforeDestroy: function() {
            window.removeEventListener("resize", this.handleResize);
        },

        methods: {
            outputResponse(response) {
                if (response.status === 200) {
                    this.preview.executing = false;
                    this.preview.output = response.response;
                    this.preview.success = true;
                } else {
                    this.preview.executing = false;
                    this.preview.failure = true;
                    this.preview.output = response.response;
                    this.preview.error = response.response;
                }
            },
            stopResizing: _.debounce(function() {
                this.resizing = false;
            }, 50),
            handleResize() {
                this.resizing = true;
                this.stopResizing();
            },
            execute() {
                this.preview.executing = true;
                this.preview.success = false;
                this.preview.failure = false;
                // Attempt to execute a script, using our temp variables
                ProcessMaker.apiClient
                    .post("scripts/preview", {
                        code: this.code,
                        language: this.script.language,
                        data: this.preview.data,
                        config: this.preview.config,
                        timeout: this.script.timeout,
                    });
            },
            onClose() {
                window.location.href = '/processes/scripts';
            },
            save() {
                ProcessMaker.apiClient
                    .put("scripts/" + this.script.id, {
                        code: this.code,
                        title: this.script.title,
                        language: this.script.language
                    })
                    .then(response => {
                        ProcessMaker.alert("The script was saved.", "success");
                    });
            }
        }
    };
</script>

<style lang="scss">

    .container {
        max-width: 100%;
        padding: 0 0 0 0;
    }

    #editor-container {
        height: calc(100vh - 60px);
        display: flex;
        flex-direction: column;

        .editor {
            flex-grow: 1;

            &.hidden {
                display: none;
            }
        }
        .preview {
            height: 300px;
            display: flex;

            .content {
                flex-grow: 1;
                display: flex;

                .editor {
                    flex-grow: 1;
                }
            }

            .data,
            .config {
                width: 300px;
                display: flex;
                flex-direction: column;
            }

            .output {
                display: flex;
                flex-direction: column;
                flex-grow: 1;

                .content {
                    padding: 8px;
                    background-color: black;

                    pre {
                        color: white;
                        font-weight: bold;
                        font-size: 12px;
                        font-family: monospace;
                    }
                }
            }
        }

        .toolbar .override {
            background-color: #b6bfc6;
            padding: 10px;
            height: 40px;
            font-family: "Open Sans";
            font-size: 18px;
            font-weight: 600;
            font-style: normal;
            font-stretch: normal;
            line-height: normal;
            letter-spacing: normal;
            text-align: left;
            color: #ffffff;
            a {
                color: white;
                padding-right: 15px;
            }
        }
    }
</style>

