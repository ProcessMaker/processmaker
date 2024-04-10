<template>
  <div>
    <modal
      ref="my-modal-save"
      size="lg"
      class="modal-dialog modal-dialog-centered"
      :title="$t('Publish New Version')"
      :hide-footer="true"
      @ok.prevent="saveModal()"
      @hidden="hideModal()"
    >
      <template
        v-if="isABTestingInstalled && alternative?.version_b_enabled"
      >
        <keep-alive>
          <component
            :is="publishVersionComponent"
            :alternative="alternative"
            @ab-publish-version="abPublishVersion"
            @ab-cancel="hideModal()"
          />
        </keep-alive>
      </template>
      <div
        v-else
        class="form-group"
      >
        <p>{{ $t("Once published, all new requests will use the new process model.") }}</p>
        <div>
          <label for="name">{{ $t("Version Name") }} </label>
          <input
            id="name"
            v-model="subject"
            class="form-control mt-2"
            type="text"
            name="name"
          >
          <div
            v-if="errors.subject"
            class="invalid-feedback d-block"
            role="alert"
          >
            {{ errors.subject[0] }}
          </div>
          <label
            class="mt-2"
            for="additional-details"
          >
            {{ $t("Description") }}
          </label>
          <textarea
            id="additional-details"
            v-model="description"
            class="form-control mt-2"
            type="text"
            rows="8"
            :aria-label="$t('Description')"
          />
        </div>

        <hr class="long-hr mt-4 mb-4">

        <div class="d-flex justify-content-end pv-actions">
          <button
            class="btn btn-outline-secondary text-uppercase mr-3"
            @click.prevent="hideModal()"
          >
            {{ $t("Cancel") }}
          </button>
          <button
            class="btn btn-secondary text-uppercase"
            data-test="btn-save-publish"
            @click.prevent="abPublishVersion"
          >
            {{ $t("Publish") }}
          </button>
        </div>
      </div>
    </modal>
    <launchpad-settings-modal
      ref="launchpad-modal"
      :options="options"
      :filter="filter"
      :origin="origin"
      :description-settings="descriptionSettings"
      :process="process"
    />
  </div>
</template>

<script>
import LaunchpadSettingsModal from "./LaunchpadSettingsModal.vue";
import Modal from "./Modal.vue";

export default {
  components: { Modal, LaunchpadSettingsModal },
  props: {
    options: {
      type: Object,
      default: {
        id: "",
        type: "",
      },
    },
    filter: {
      type: String,
      default: "",
    },
    origin: {
      type: String,
      default: "",
    },
    descriptionSettings: {
      type: String,
      default: "",
    },
    process: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      list: {},
      subject: "",
      description: "",
      errors: "",
      types: {
        Screen: "save-screen",
        Script: "save-script",
        Process: "modeler-save",
      },
      redirectUrl: null,
      nodeId: null,
      processDescription: "",
      processDescriptionInitial: "",
      processId: "",
      dataProcess: {},
      // AB Testing
      isABTestingInstalled: false,
      publishVersionComponent: null,
      alternative: null,
    };
  },
  mounted() {
    ProcessMaker.EventBus.$on("open-modal-versions", (redirectUrl, nodeId) => {
      this.redirectUrl = redirectUrl;
      this.nodeId = nodeId;
      this.showModal();

      // AB Testing installed
      this.isABTestingInstalled = !!window.ProcessMaker.AbTesting;
      // AB Testing component
      if (this.isABTestingInstalled && ProcessMaker?.AbTesting?.PublishVersion) {
        this.alternative = ProcessMaker?.modeler?.process?.alternative_info;
        this.publishVersionComponent = ProcessMaker?.AbTesting?.PublishVersion;
      }
    });
    this.getDescriptionInitial();
    this.getProcessDescription();
  },
  methods: {
    abPublishVersion(alternativeData) {
      this.subject = alternativeData.subject;
      this.description = alternativeData.description;

      this.saveModal(alternativeData);
    },
    /**
     * Method to store initial data from process description field
     */
    getDescriptionInitial() {
      if (this.origin !== "core") {
        if (ProcessMaker.modeler?.process) {
          this.processDescriptionInitial = ProcessMaker.modeler.process.description;
        }
      } else {
        this.processDescriptionInitial = this.descriptionSettings;
      }
    },
    /**
     * Method to retrieve data from process description field
     */
    getProcessDescription() {
      if (this.origin !== "core") {
        if (ProcessMaker.modeler?.process) {
          this.processDescription = ProcessMaker.modeler.process.description;
          this.processId = ProcessMaker.modeler.process.id;
          if (ProcessMaker.modeler.process.description === "") {
            this.processDescription = this.processDescriptionInitial;
          }
        }
      } else {
        this.processDescription = this.descriptionSettings;
        this.processId = this.process.id;
        if (!this.processDescription) {
          this.processDescription = this.processDescriptionInitial;
        }
      }
    },
    hideModal() {
      this.$refs["my-modal-save"].hide();
    },
    /**
     * Method to save modal
     *
     * @returns {Promise<void>}
     */
    saveModal() {
      const eventType = this.types[this.options.type];

      this.dataProcess = ProcessMaker.modeler.process;
      this.dataProcess.description = this.processDescription;

      const promise = new Promise((resolve, reject) => {
        // emit save types
        window.ProcessMaker.EventBus.$emit(
          eventType,
          this.redirectUrl,
          this.nodeId,
          this.options.type === "Screen" ? (false, resolve) : resolve,
          reject,
          eventType === "modeler-save" ? false : null,
          "",
        );
      });

      promise
        .then((response) => {
          ProcessMaker.apiClient
            .post("/version_histories", {
              subject: this.subject,
              description: this.description,
              versionable_id: this.options.id,
              versionable_type: this.options.type,
            })
            .then((response) => {
              ProcessMaker.alert(this.$t("The process version was saved."), "success");
              this.saving = false;
              this.verifyLaunchPad();
              this.hideModal();
            })
            .catch((error) => {
              if (error.response.status && error.response.status === 422) {
                this.errors = error.response.data.errors;
              }
              if (error.response.status === 404) {
                // version_histories route not found because package-versions is not installed
                console.error(err);
              }
            });
        })
        .catch((err) => {
          console.error(err);
        });
    },
    showModal() {
      this.subject = "";
      this.description = "";
      this.errors = "";
      this.$refs["my-modal-save"].show();
    },
    /**
     * Verify if the process has launchpad.
     */
    verifyLaunchPad() {
      ProcessMaker.apiClient
        .get(`process_launchpad/${this.processId}`)
        .then((response) => {
          const alternative = window.ProcessMaker.AbTesting?.alternative;
          const iFrame = window.parent[`alternative${alternative}`];
          const isActive = iFrame ? iFrame.classList.contains("active") : false;
          const isABTestingInstalled = !!window.ProcessMaker.AbTesting;
          if (!response.data?.[0].launchpad && (!isABTestingInstalled || isActive)) {
            this.$refs["launchpad-modal"].showModal();
          }
        });
    },
  },
};
</script>

<style lang="scss">
$iconSize: 29px;
$multiselect-height: 38px;
.no-padding {
  padding: 0;
}

.expanded .dropdown {
  display: none;
}

.btn-custom {
  text-transform: none;
  border-color: #6C757D;
}

.btn-custom-button {
  text-transform: none;
  border-color: rgba(35, 118, 200, 0.33);
  background-color: white;
  color: #212529;
}

.text-black {
  color: #212529;
}

.cursor-default {
  cursor: default;
}

.toolbar-item {
  font-style: normal;
  font-size: 14px;
  letter-spacing: -0.02em;
  line-height: 21px;
}
.custom-button {
  right: 10px;
  top: 20px;
}

.custom-color {
  color: #6a7888;
}

.custom-dropdown {
  width: 100%;
  max-height: 200px;
  overflow-y: auto;
  transform: none;
}

.error-message {
  color: red;
  font-size: 0.8rem;
  margin-top: 5px;
}

.custom-text {
  font-size: 16px;
}

.long-hr {
  margin: auto -24px;
}
</style>
