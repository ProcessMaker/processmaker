<template>
    <b-container class="h-100">
        <b-card no-body class="h-100">
            <b-card-header class="text-right">
                <b-button title="Save Script" @click="save" size="sm">
                    <i class="fas fa-save"/>
                </b-button>
            </b-card-header>

            <b-card-body class="overflow-hidden p-4">
                <b-row class="h-100">
                    <b-col cols="9" class="h-100">
                        <monaco-editor :options="monacoOptions" v-model="code" :language="script.language" class="h-100" :class="{hidden: resizing}"/>
                    </b-col>
                    <b-col cols="3" class="h-100">
                        <b-list-group class="w-100 h-100 overflow-auto">
                            <b-list-group-item class="card-header">
                                <b-row class="d-flex align-items-center">
                                    <b-col>{{ $t('Debugger') }}</b-col>
                                    <b-col align-self="end" class="text-right">
                                        <b-button
                                            class="text-capitalize pl-3 pr-3"
                                            :disabled="preview.executing"
                                            @click="execute"
                                            size="sm"
                                        >
                                            <i class="fas fa-caret-square-right"/>
                                            {{ $t('Run') }}
                                        </b-button>
                                    </b-col>
                                </b-row>
                            </b-list-group-item>

                            <b-list-group-item class="card-header">
                                <b-row v-b-toggle.configuration>
                                    <b-col>
                                        <i class="fas fa-cog"/>
                                        Configuration
                                    </b-col>
                                    <b-col align-self="end" cols="1" class="mr-2">
                                    <i class="fas fa-chevron-down accordion-icon"/>
                                    </b-col>
                                </b-row>
                            </b-list-group-item>
                            <b-list-group-item class="border-bottom-0 p-0">
                                <b-collapse id="configuration">
                                    <monaco-editor :options="monacoOptions" v-model="preview.config" language="json" class="editor-inspector" :class="{hidden: resizing}"/>
                                </b-collapse>
                            </b-list-group-item>

                            <b-list-group-item class="card-header">
                                <b-row v-b-toggle.input>
                                    <b-col>
                                        <i class="fas fa-sign-in-alt"/>
                                        Sample Input
                                    </b-col>
                                    <b-col align-self="end" cols="1" class="mr-2">
                                    <i class="fas fa-chevron-down accordion-icon"/>
                                    </b-col>
                                </b-row>
                            </b-list-group-item>
                            <b-list-group-item class="border-bottom-0 p-0">
                                <b-collapse id="input">
                                    <monaco-editor :options="monacoOptions" v-model="preview.data" language="json" class="editor-inspector" :class="{hidden: resizing}"/>
                                </b-collapse>
                            </b-list-group-item>

                            <b-list-group-item class="card-header">
                                <b-row v-b-toggle.output>
                                    <b-col>
                                        <i class="far fa-caret-square-right"/>
                                        Output
                                    </b-col>
                                    <b-col align-self="end" cols="1" class="mr-2">
                                    <i class="fas fa-chevron-down accordion-icon"/>
                                    </b-col>
                                </b-row>
                            </b-list-group-item>
                            <b-list-group-item class="border-bottom-0 p-0 h-100">
                                <b-collapse id="output" class="bg-dark h-100">
                                    <div class="output text-white">
                                        <pre v-if="preview.success" class="text-white"><samp>{{ preview.output }}</samp></pre>
                                        <div v-if="preview.failure">
                                            <div class="text-light bg-danger">{{preview.error.exception}}</div>
                                            <div class="text-light text-monospace small">{{preview.error.message}}</div>
                                        </div>
                                    </div>
                                </b-collapse>
                            </b-list-group-item>
                        </b-list-group>
                    </b-col>
                </b-row>
            </b-card-body>

            <b-card-footer>
                Language: <span class="text-uppercase">{{ script.language }}</span>
            </b-card-footer>
        </b-card>
    </b-container>
</template>

<script>
    import MonacoEditor from "vue-monaco";
    import _ from "lodash";

    export default {
        props: ["process", "script", "scriptFormat"],
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
                    .post("scripts/" + this.script.id + "/preview", {
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
                        language: this.script.language,
                        run_as_user_id: this.script.run_as_user_id
                    })
                    .then(response => {
                        ProcessMaker.alert(__("The script was saved."), "success");
                    });
            }
        }
    };
</script>

<style lang="scss" scoped>
.container {
    max-width: 100%;
    padding: 0 0 0 0;
}

.card-header {
  background: #f7f7f7;
}

.accordion-icon {
  transition: all 200ms;
}

.collapsed .accordion-icon {
  transform: rotate(-90deg);
}

.editor-inspector {
    width: 300px;
    height: 300px;
}

.output {
    min-height: 300px;
}
</style>

