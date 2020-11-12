<template>
  <b-button
    class="file-upload-button"
    :block="block"
    :disabled="disabled"
    :size="size"
    :variant="variant"
    :type="type"
    :tag="tag"
    :pill="pill"
    :squared="squared"
    :pressed="pressed"
    @click="onClick"
  >
    <b-form-file
      plain
      v-model="file"
      v-bind="$attrs"
      class="file-upload-control"
      :accept="accept"
      :capture="capture"
      :multiple="multiple"
      :directory="directory"
      :no-traverse="noTraverse"
      :no-drop="noDrop"
      :file-name-formatter="fileNameFormatter"
      @change="onChange"
      @input="onInput"
      ref="fileUpload"
    ></b-form-file>
    <slot></slot>
  </b-button>
</template>

<script>
  export default {
    inheritAttrs: false,
    props: {
      // Component
      value: {
        default: null,
      },
      // Button
      block: {
        type: Boolean,
        default: false
      },
      disabled: {
        type: Boolean,
        default: false
      },
      size: {
        type: String,
        default: 'md'
      },
      variant: {
        type: String,
        default: 'secondary'
      },
      type: {
        type: String,
        default: 'button'
      },
      tag: {
        type: String,
        default: 'button'
      },
      pill: {
        type: Boolean,
        default: false
      },
      squared: {
        type: Boolean,
        default: false
      },
      pressed: {
        type: Boolean,
        default: null
      },
      // File Upload
      accept: {
        type: String,
        default: ''
      },
      capture: {
        type: Boolean,
        default: false
      },
      multiple: {
        type: Boolean,
        default: false
      },
      directory: {
        type: Boolean,
        default: false
      },
      noTraverse: {
        type: Boolean,
        default: false
      },
      noDrop: {
        type: Boolean,
        default: false
      },
      fileNameFormatter: {
        type: Function,
        default: null
      }
    },
    data() {
      return {
        file: null,
      };
    },
    watch: {
      file: {
        handler: function(value) {
          this.$emit('input', value);
        }
      },
      value: {
        handler: function(value) {
          this.file = value;
        },
        deep: true
      }
    },
    methods: {
      onClick() {
        this.$refs.fileUpload.$el.click();
      },
      onChange(event) {
        this.$emit('change', event);
      },
      onInput(file) {
        this.$emit('input', file);
      },
      reset() {
        this.$refs.fileUpload.reset();
      },
      trigger() {
        this.$refs.fileUpload.$el.click();
      }
    }
  };
</script>

<style lang="scss">
  .file-upload-button {
    .file-upload-control {
      display: none;
    }
  }
</style>
