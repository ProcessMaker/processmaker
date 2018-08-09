<template>
    <div id="editor-container">
        <div class="toolbar">
         <nav class="navbar navbar-expand-md override">
            <span>{{process.name}} - {{script.title}}</span>
            <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                <ul class="navbar-nav">
                    <li class="nav-item ">
                        <a href="#" title="Save Script" @click="save"><i class="fas fa-save"></i></a>
                    </li>
                    <li class="nav-item ">
                        <a href="#" @click="onClose" title="Return to Designer"><i class="fas fa-times"></i></a>
                    </li>
                </ul>
            </div>

        </nav>
        </div>
        <monaco-editor :options="monacoOptions" v-model="code" :language="script.language" class="editor"></monaco-editor>
        <div class="preview border-top">
            <div class="data border-right">
                <div class=" p-1 bg-secondary border-bottom text-white">Input Data JSON</div>
                <monaco-editor :options="monacoOptions" v-model="preview.data" language="json" class="editor"></monaco-editor>
            </div>
            <div class="config border-right">
                <div class="p-1 bg-secondary border-bottom text-white">Script Config JSON</div>
                <monaco-editor :options="monacoOptions" v-model="preview.config" language="json" class="editor"></monaco-editor>

            </div>
            <div class="output">
                <div class="p-1 bg-secondary border-bottom text-white">Script Output</div>
                <button @click="execute" class="btn btn-primary">Execute</button>
                <div class="content">
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import MonacoEditor from 'vue-monaco'

export default {
  props: [, "process", "script"],
  data() {
    return {
        monacoOptions: {
            automaticLayout: true
        },
        code: this.script.code,
        preview: {
            data: '{}',
            config: '{}'
        }
    };
  },
  components: {
      MonacoEditor
  },
  methods: {
    execute() {
        // Attempt to execute a script, using our temp variables
        ProcessMaker.apiClient.post('script/preview', {
            code: this.code,
            language: this.script.language,
            data: this.preview.data,
            config: this.preview.config
        })
        .then((response) => {
            this.preview.output = response.data.output;
        });
    },
    onClose() {
      window.location.href = "/designer/" + this.process.uid;
    },
    save() {
      ProcessMaker.apiClient
        .put("process/" + this.process.uid + "/script/" + this.script.uid, {
          code: this.code
        })
        .then(response => {
          ProcessMaker.alert(" Successfully saved", "success");
        });
    }
  }
};
</script>

<style lang="scss">
#editor-container {
    height: calc(100vh - 60px);
    display: flex;
    flex-direction: column;


    .editor {
        flex-grow: 1;
    }

    .preview {
        height: 200px;
        display: flex;

        .content {
            flex-grow: 1;
            display: flex;

            .editor {
                flex-grow: 1;
            }
        }

        .data, .config {
            width: 200px;
            display: flex;
            flex-direction: column;
        }

        .output {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
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
