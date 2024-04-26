<template>
  <div class="form-group">
    <div>
      <label>{{ $t(label) }}</label>
      <button
        v-b-toggle.tree-view-sidebar
        @click="openSidebar()"
        type="button"
        :aria-label="$t('Expand Editor')"
        class="btn-sm float-right"
      >
        <i class="fas fa-expand" />
      </button>
    </div>
    <div class="small-editor-container">
      <monaco-editor
        ref="monacoEditor"
        v-model="code"
        :options="monacoOptions"
        language="json"
        class="editor"
      />
    </div>
    <small class="form-text text-muted">{{ $t(helper) }}</small>
    <b-sidebar
      id="tree-view-sidebar"
      ref="sidebar"
      class="sidebar-tree-view"
      sidebar-class="border"
      no-header
      right
    >
      <b-card no-body>
        <b-card-text>
          <div class="sidebar-header">
            <b-row>
              <b-col md="auto">
                <div class="sidebar-title">
                  <h6>{{ $t('Data Browser') }}</h6>
                </div>
              </b-col>
              <b-col class="text-right">
                <div class="mb-2 custom-control custom-switch">
                  <input id="tree_view" type="checkbox" v-model="treeView"  class="custom-control-input">
                  <label for="tree_view" class="custom-control-label">{{ $t('Tree View') }}</label>
                </div>
              </b-col>
              <b-col md="auto" class="text-right">
                <button
                  class="close"
                  @click="closeSidebar"
                >
                  ×
                </button>
              </b-col>
            </b-row>
          </div>
        </b-card-text>
      </b-card>
      <b-row>
        <b-col>
          <b-card-group>
            <b-card no-body>
              <template #header>
                <div class="sidebar-subtitle">
                  <h6>{{ $t('Script Config Editor') }}</h6>
                </div>
              </template>
              <b-card-body>
                <div class="editor-container">
                  <monaco-editor
                    v-model="code"
                    :options="monacoLargeOptions"
                    data-cy="editorViewFrame"
                    language="json"
                    class="editor"
                    @focusout.native="handleBlur"
                  />
                </div>
              </b-card-body>
            </b-card>
            <b-card v-if="treeView" no-body>
              <template #header>
                <div class="sidebar-subtitle">
                  <h6>{{ $t('Tree View') }}</h6>
                </div>
              </template>
              <b-card-text>
                <div class="editor-container">
                  <tree-view v-model="code" :key="componentKey" style="border:1px; solid gray; min-height:700px;"></tree-view>
                </div>
              </b-card-text>
            </b-card>
          </b-card-group>
        </b-col>
      </b-row>
    </b-sidebar>
  </div>
</template>

<script>
export default {
  props: ["value", "label", "helper", "property"],
  data() {
    return {
      monacoOptions: {
        automaticLayout: true,
        fontSize: 8,
      },
      monacoLargeOptions: {
        automaticLayout: true,
      },
      code: "",
      treeView: false,
      componentKey: 0,
      valid: false,
    };
  },
  watch: {
    value: {
      handler() {
        this.code = this.value ? this.value : "";
      },
      immediate: true,
    },
    code() {
      this.$emit("input", this.code);
      try {
        JSON.parse(this.code);
        this.componentKey += 1;
      } catch (e) {
        this.valid = false;
      }
    },
  },
  methods: {
    handleBlur() {
      // Update the undoStack when the modal is hidden to trigger the autosave.
      const child = this.$root.$children.find((c) => c.$refs.modeler);
      child.$refs.modeler.pushToUndoStack();
    },
    closeSidebar() {
      this.$refs.sidebar.hide();
    },
    openSidebar() {
      this.treeView = false;
    },
  },
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
        height: 700px;
    }
    .sidebar-tree-view::v-deep .b-sidebar {
      margin-top: 128px;
      margin-right: 315px;
      width: 65%;
    }
    .sidebar-header {
      padding: 10px;
    }
    .sidebar-title {
      font-weight: bold !important;
      color: black;
    }
    .sidebar-subtitle {
      color: rgba(0, 0, 0, 0.65);
    }
</style>
