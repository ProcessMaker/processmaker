<template>
  <b-modal
    ref="modal"
    size="xl"
    :title="modalTitle"
    data-test="subprocess-preview-modal"
    body-class="subprocess-preview-modal-body p-0"
    @hidden="onHidden"
  >
    <div class="subprocess-preview-stage">
      <b-alert
        v-if="errorMessage"
        show
        variant="warning"
        class="m-3"
        data-test="subprocess-preview-error"
      >
        {{ errorMessage }}
      </b-alert>

      <template v-else-if="processId && embedPreviewSrc">
        <iframe
          :key="embedPreviewSrc"
          :src="embedPreviewSrc"
          class="subprocess-preview-iframe"
          :title="modalTitle"
          data-test="subprocess-preview-iframe"
          @load="onEmbedLoaded"
        />
        <div
          v-if="loading"
          class="subprocess-preview-stage__loading text-center"
          data-test="subprocess-preview-loading"
        >
          <i class="fas fa-spinner fa-spin fa-2x text-muted" />
          <p class="text-muted mt-3 mb-0">
            {{ $t("Loading process preview...") }}
          </p>
        </div>
      </template>
    </div>

    <template #modal-footer>
      <b-button
        variant="secondary"
        @click="hide"
      >
        {{ $t("Close") }}
      </b-button>
      <b-button
        v-if="processId"
        variant="primary"
        :href="modelerHref"
        target="_blank"
        rel="noopener noreferrer"
        data-test="subprocess-preview-open-modeler"
      >
        {{ $t("Open subprocess in new window") }}
        <i class="ml-1 fas fa-external-link-alt" />
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import { fetchSubProcessPreviewName } from "../subprocessPreview";

export default {
  name: "SubProcessPreviewModal",
  data() {
    return {
      processId: null,
      processName: "",
      loading: false,
      errorMessage: null,
    };
  },
  computed: {
    modalTitle() {
      if (this.processName) {
        return `${this.$t("Subprocess preview")}: ${this.processName}`;
      }
      return this.$t("Subprocess preview");
    },
    modelerHref() {
      return this.processId ? `/modeler/${this.processId}` : "#";
    },
    embedPreviewSrc() {
      return this.processId ? `/modeler/${this.processId}/subprocess-preview-embed` : null;
    },
  },
  methods: {
    /**
     * @param {{ processId: number|null, processName?: string }} payload
     */
    show(payload = {}) {
      const nextProcessId = payload.processId || null;
      this.errorMessage = null;

      if (!nextProcessId) {
        this.processId = null;
        this.processName = "";
        this.errorMessage = this.$t("Select a subprocess on this Sub Process node first.");
        this.loading = false;
        this.$refs.modal.show();
        return;
      }

      // Same embed URL stays mounted; iframe @load will not fire again.
      // Re-arming loading here would leave the overlay stuck until hide.
      if (nextProcessId === this.processId) {
        this.$refs.modal.show();
        return;
      }

      this.processId = nextProcessId;
      this.processName = "";
      this.loading = true;
      this.$refs.modal.show();
      this.loadMetadata();
    },
    hide() {
      this.$refs.modal.hide();
    },
    onHidden() {
      this.loading = false;
      this.processId = null;
      this.processName = "";
      this.errorMessage = null;
    },
    loadMetadata() {
      const processId = this.processId;
      fetchSubProcessPreviewName(processId)
        .then((name) => {
          if (this.processId !== processId) {
            return;
          }
          if (name) {
            this.processName = name;
          }
        })
        .catch(() => {
          // Iframe still loads BPMN from the embed route.
        });
    },
    onEmbedLoaded() {
      this.loading = false;
    },
  },
};
</script>

<style scoped>
.subprocess-preview-stage {
  position: relative;
  min-height: 320px;
}

.subprocess-preview-stage__loading {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(248, 249, 250, 0.92);
  z-index: 1;
}

.subprocess-preview-iframe {
  display: block;
  width: 100%;
  height: 70vh;
  min-height: 320px;
  border: 0;
  background: #f8f9fa;
}
</style>

<style>
.subprocess-preview-modal-body {
  min-height: 320px;
}
</style>
