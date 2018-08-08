<template>
  <b-modal ref="modal" size="lg" @hidden="onHidden" centered v-bind:title="popupTitle">
    <form>
      <div class="form-group">
        <label>{{titleLabel}}</label>
        <input type="text" class="form-control" v-model="title">
      </div>

      <div class="form-group">
        <label>{{descriptionLabel}}</label>
        <textarea class="form-control" v-model="description"></textarea>
      </div>

      <div class="form-group">
        <div class="code-variable">
          <div>
            <div><label>{{codeLabel}}</label></div>
            <div><label>{{variableLabel}}</label></div>
          </div>
          <div>
            <div class="code-variable-editor">
              <monaco-editor
                  height="300"
                  language="php"
                  :code="code"
                  :editorOptions="editorOptions"
                  @mounted="onMounted"
                  @codeChange="onCodeChange"
                  >
              </monaco-editor>
            </div>
            <div>
              <select size="10">
                <option v-for="variable in variables" draggable="true" @dragstart="dragVariable(variable, $event)">{{variable.attributes.name}}</option>
              </select>
            </div>
          </div>
          <div>
            <div><button type="button">{{testButtonLabel}}</button></div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="code-variable">
          <div>
            <div><label for="variableType">{{inputVariablesJsonLabel}}</label></div>
            <div></div>
            <div><label for="variableType">{{outputVariablesJsonLabel}}</label></div>
          </div>
          <div>
            <div class="code-variable-editor">
              <vue-json-pretty
                  :data="inputVariablesJson"></vue-json-pretty>
            </div>
            <div class="code-variable-play"><i class="fas fa-play-circle"></i></div>
            <div>
              <vue-json-pretty
                  :data="outputVariablesJson"></vue-json-pretty>
            </div>
          </div>
        </div>
      </div>
    </form>

    <template slot="modal-footer">
      <b-button @click="onCancel" class="btn-outline-secondary btn-md">
        CANCEL
      </b-button>
      <b-button @click="onSave" class="btn-secondary text-light btn-md">
        SAVE
      </b-button>
    </template>

  </b-modal>
</template>

<script>
  import MonacoEditor from 'vue-monaco-editor'
          import VueJsonPretty from 'vue-json-pretty';

  export default {
      components: {
          MonacoEditor,
          VueJsonPretty
      },
      data() {
          return {
              // form models here
              'popupTitle': 'Create Custom Script',
              'titleLabel': 'Title',
              'descriptionLabel': 'Description',
              'codeLabel': 'Code',
              'variableLabel': 'Variables',
              'testButtonLabel': 'TEST CODE',
              'inputVariablesJsonLabel': 'Input Variables (JSON)',
              'outputVariablesJsonLabel': 'Output Variables (JSON)',

              'editorOptions': {},

              'title': '',
              'description': '',
              'code': '',
              'variables': [],
              'inputVariablesJson': {},
              'outputVariablesJson': {},
          }
      },
      methods: {
          onHidden() {
              this.$emit('hidden');
          },
          onCancel() {
              this.$refs.modal.hide();
          },
          onSave() {
              this.$refs.modal.hide();
          },
          dragVariable(variable, event) {
              event.dataTransfer.effectAllowed = "copy";
              event.dataTransfer.dropEffect = "copy";
              event.dataTransfer.setData("text", variable.attributes.name);
              return false;
          },
          onMounted(editor) {
              this.editor = editor;
          },
          onCodeChange(editor) {
              this.code = this.editor.getValue();
          }
      },
      mounted() {
          // Show our modal as soon as we're created
          this.$refs.modal.show();
      }
  };
</script>
<style lang="scss" scoped>
  .form-check-input{
      margin-top: 8px;
  }
  .bottom-label{
      font-size: 10px;
  }
  .bottom-label-form{
      margin-bottom: -6px;
  }
  .code-variable {
      display: table;
      min-height: 12em;
  }
  .code-variable > div {
      display: table-row;
  }
  .code-variable > div > div {
      display: table-cell;
  }
  .code-variable-editor {
      min-width: 40em;
  }
  .code-variable-play {

  }
</style>
