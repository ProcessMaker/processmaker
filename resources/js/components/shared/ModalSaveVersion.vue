<template>
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
        <button class="btn btn-outline-secondary text-uppercase mr-3" @click.prevent="hideModal()">
          Cancel
        </button>
        <button
          class="btn btn-secondary text-uppercase"
          data-test="btn-save-publish"
          @click.prevent="abPublishVersion"
        >
          Save and Publish
        </button>
      </div>
    </div>
  </modal>
</template>

<script>
import Modal from "./Modal.vue";
import IconDropdown from "./IconDropdown.vue";

export default {
  components: { Modal, IconDropdown },
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
      selectedSavedChart: "",
      dropdownSavedCharts: [],
      maxImages: 4,
      processDescription: "",
      processDescriptionInitial: "",
      btnColorClass: "btn-custom-button",
      isSecondaryColor: false,
      processId: "",
      dataProcess: {},
      // AB Testing
      isABTestingInstalled: false,
      publishVersionComponent: null,
      alternative: null,
    };
  },
  computed: {
    btnStyle() {
      return this.isSecondaryColor
        ? { backgroundColor: "#6a7888", color: "white" }
        : { backgroundColor: "white", color: "#6a7888" };
    },
  },
  mounted() {
    ProcessMaker.EventBus.$on("open-modal-versions", (redirectUrl, nodeId) => {
      this.redirectUrl = redirectUrl;
      this.nodeId = nodeId;
      this.showModal();

      // AB Testing installed
      this.isABTestingInstalled = ProcessMaker.modeler.abPublish;
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
          if(ProcessMaker.modeler.process.description === "") {
            this.processDescription = this.processDescriptionInitial;
          }
        }
      } else {
        this.processDescription = this.descriptionSettings;
        this.processId = this.process.id;
          if(!this.processDescription) {
            this.processDescription = this.processDescriptionInitial;
          }
      }
    },
    hideModal() {
      this.$refs["my-modal-save"].hide();
    },
    /**
     * Method to save modal
     * @param alternativeData {
     * publishedVersion: string A|B|AB,
     * subject: string,
     * description: string,
     * } | null - Alternative data from AB Testing
     *
     * @returns {Promise<void>}
     */
    saveModal(alternativeData = null) {
      const eventType = this.types[this.options.type];

      let publishedVersion = 'A';

      if (eventType === "modeler-save" && alternativeData) {
        publishedVersion = alternativeData.publishedVersion;
      }

      // if method is called from ProcessMaker core
      if (this.origin === "core") {
        this.dataProcess = this.process;
        this.dataProcess.description = this.processDescription;
        this.saveProcessDescription(publishedVersion);
        return;
      }
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

      // Save only process description field using Process API
      this.saveProcessDescription(publishedVersion);
    },
    /**
     * Save description field in Process
     *
     * @param {string} publishedVersion
     */
    saveProcessDescription() {
      if (!this.processDescription) return;

      ProcessMaker.apiClient
        .put(`processes/${this.options.id}`, {
          imagesCarousel: this.dataProcess.imagesCarousel,
          description: this.dataProcess.description,
          launchpad_properties: this.dataProcess.launchpad_properties,
        })
        .then((response) => {
          ProcessMaker.alert(this.$t("The process was saved."), "success", 5, true);
          const params = {
            indexImage: null,
            type: "add",
          };
          ProcessMaker.EventBus.$emit("getChartId");
          this.hideModal();
        })
        .catch((error) => {
          console.error("Error: ", error);
        });
    },
    showModal() {
      this.subject = "";
      this.description = "";
      this.errors = "";
      this.$refs["my-modal-save"].show();
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
