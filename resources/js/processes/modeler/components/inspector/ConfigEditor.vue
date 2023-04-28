<template>
  <div class="form-group">
    <div>
      <label>{{ $t(label) }}</label>
      <button
        type="button"
        :aria-label="$t('Expand Editor')"
        class="btn-sm float-right"
        @click="expandEditor"
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
    <b-modal
      v-cloak
      v-model="showPopup"
      size="lg"
      centered
      :title="$t('Script Config Editor')"
      header-close-content="&times;"
    >
      <div class="editor-container">
        <monaco-editor
          v-model="code"
          :options="monacoLargeOptions"
          language="json"
          class="editor"
        />
      </div>
      <div slot="modal-footer">
        <b-button
          class="btn btn-secondary"
          @click="closePopup"
        >
          {{ $t('Close') }}
        </b-button>
      </div>
    </b-modal>
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
      showPopup: false,
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
    },
  },
  methods: {
    expandEditor() {
      this.showPopup = true;
    },
    closePopup() {
      this.showPopup = false;
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
        height: 60vh;
    }
</style>
