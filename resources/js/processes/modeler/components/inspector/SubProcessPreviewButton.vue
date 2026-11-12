<template>
  <div class="form-group mb-0">
    <label>{{ $t(label) }}</label>
    <b-button
      variant="outline-primary"
      size="sm"
      block
      :disabled="!subProcessId"
      data-test="subprocess-preview-inspector-btn"
      @click="openPreview"
    >
      <i class="fas fa-search-plus mr-1" />
      {{ $t("Preview subprocess diagram") }}
    </b-button>
    <small
      v-if="helper"
      class="form-text text-muted"
    >{{ $t(helper) }}</small>
    <small
      v-if="!subProcessId"
      class="form-text text-warning"
    >
      {{ $t("Choose a child process in Properties before previewing.") }}
    </small>
  </div>
</template>

<script>
import { resolveSubProcessId } from "../../subprocessPreview";

export default {
  name: "SubProcessPreviewButton",
  props: {
    label: {
      type: String,
      default: "Subprocess diagram",
    },
    helper: {
      type: String,
      default: "Opens the child process in a modal so the parent diagram stays compact.",
    },
  },
  computed: {
    node() {
      const modelerApp = this.$root.$children[0];
      return modelerApp?.$refs?.modeler?.highlightedNode?.definition;
    },
    subProcessId() {
      return resolveSubProcessId(this.node);
    },
  },
  methods: {
    openPreview() {
      window.ProcessMaker.EventBus.$emit("subprocess-preview-open", {
        processId: this.subProcessId,
      });
    },
  },
};
</script>
