<template>
  <div class="data-table">
    <div
      v-show="!shouldShowLoader"
      class="d-flex mb-2"
    >
      <div class="mr-auto" />
      <div>
        <b-button
          :aria-label="$t('Add New Script Executor')"
          type="button"
          @click="add()"
        >
          <i class="fa fa-plus" /> {{ $t("Script Executor") }}
        </b-button>
      </div>
    </div>
    <data-loading
      v-show="shouldShowLoader"
      :for="/script-executors\?page/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="card card-body table-card"
    >
      <vuetable
        :data-manager="dataManager"
        :sort-order="sortOrder"
        :css="css"
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        :no-data-template="$t('No Data Available')"
        pagination-path="meta"
        @vuetable:pagination-data="onPaginationData"
      >
        <template
          slot="title"
          slot-scope="props"
        >
          <span v-uni-id="props.rowData.id.toString()">{{ props.rowData.title }}</span>
        </template>

        <template
          slot="actions"
          slot-scope="props"
        >
          <div class="actions">
            <div class="popout">
              <b-btn
                v-b-tooltip.hover
                v-uni-aria-describedby="props.rowData.id.toString()"
                variant="link"
                :title="$t('Edit')"
                @click="edit(props.rowData)"
              >
                <i class="fas fa-pen-square fa-lg fa-fw" />
              </b-btn>
              <b-btn
                v-b-tooltip.hover
                v-uni-aria-describedby="props.rowData.id.toString()"
                variant="link"
                :title="$t('Delete')"
                @click="deleteExecutor(props.rowData)"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw" />
              </b-btn>
              <b-btn
                v-b-tooltip.hover
                v-uni-aria-describedby="props.rowData.id.toString()"
                variant="link"
                :title="$t('Add to Bundle')"
                @click="onAddToBundle(props.rowData)"
              >
                <i class="fas fa-folder-plus fa-lg fa-fw" />
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <add-to-bundle
        asset-type="script_executors"
        :setting="true"
      />
      <pagination
        ref="pagination"
        :single="$t('Script Executor')"
        :plural="$t('Script Executors')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
    </div>

    <b-modal
      id="edit"
      ref="edit"
      :title="modalTitle"
      size="lg"
      header-close-content="&times;"
      @hidden="reset()"
      @hide="doNotHideIfRunning"
    >
      <b-container class="mb-2">
        <required />
        <b-row>
          <b-col>
            <b-row>
              <b-col class="p-0">
                <b-form-group
                  required
                  :label="$t('Name')"
                  :state="!getError('title')"
                  :invalid-feedback="getError('title') || ''"
                  role="alert"
                >
                  <b-input
                    v-model="formData.title"
                    required
                    name="title"
                  />
                </b-form-group>
                <b-form-group
                  required
                  :label="$t('Language')"
                  :state="!getError('language')"
                  :invalid-feedback="getError('language') || ''"
                >
                  <b-form-select
                    v-model="formData.language"
                    required
                    :options="languagesSelect"
                    name="language"
                  />
                </b-form-group>
              </b-col>
            </b-row>
          </b-col>
          <b-col class="d-flex flex-column">
            <label>{{ $t('Description') }}</label>
            <b-textarea
              v-model="formData.description"
              class="flex-grow-1"
              name="description"
            />
          </b-col>
        </b-row>
      </b-container>

      <p class="mb-0">
        {{ $t("Docker file") }}
      </p>

      <div class="d-flex flex-row mb-1">
        <div class="mr-1">
          <a
            :aria-expanded="showDockerfile ? 'true' : 'false'"
            :title="$t('Display contents of docker file that will be prepended to your customizations below.')"
            @click="showDockerfile = !showDockerfile"
          >
            <i
              class="fa"
              :class="{
                'fa-chevron-right': !showDockerfile,
                'fa-chevron-down': showDockerfile,
              }"
              style="width: 14px"
            />
          </a>
        </div>
        <div class="flex-fill">
          <pre
            class="mt-1 mb-0"
            @click="
              showDockerfile = !showDockerfile
            "
          >{{ initDockerfile.split("\n")[0] }} <template v-if="!showDockerfile">...</template></pre>
          <b-collapse
            id="dockerfile"
            v-model="showDockerfile"
            :aria-hidden="showDockerfile ? 'false' : 'true'"
          >
            <pre>{{ initDockerfile.split("\n").slice(1).join("\n") }}</pre>
          </b-collapse>
        </div>
      </div>

      <b-form-textarea
        v-model="formData.config"
        class="mb-3 dockerfile"
        :disabled="isRunning"
      />

      <div v-if="commandOutput !== '' || isRunning">
        <p>
          {{ $t("Build Command Output") }}
          <i
            v-if="isRunning"
            class="fas fa-spinner fa-spin"
          />
        </p>
        <pre
          ref="pre"
          class="border command-output pre-scrollable"
          :class="{ error: exitCode !== 0 }"
        >{{ commandOutput }}</pre>
      </div>

      <div v-if="status === 'done'">
        <p v-if="exitCode === 0">
          {{
            $t("Executor Successfully Built. You can now close this window. ")
          }}
        </p>
        <div
          v-if="exitCode > 0"
          class="invalid-feedback d-block"
          role="alert"
        >
          {{ $t("Error Building Executor. See Output Above.") }}
        </div>
      </div>

      <template #modal-footer>
        <b-button
          v-if="showClose"
          variant="secondary"
          @click="$bvModal.hide('edit')"
        >
          {{ $t("Close") }}
        </b-button>

        <b-button
          v-if="showCancel"
          :disabled="pidFile === null"
          variant="secondary"
          @click="cancel"
        >
          {{ $t("Cancel") }}
        </b-button>

        <b-button
          v-if="showSave"
          :disabled="isRunning"
          variant="primary"
          @click="save()"
        >
          <template v-if="formData.id">
            {{ $t("Save And Rebuild") }}
          </template>
          <template v-else>
            {{ $t("Save And Build") }}
          </template>
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import AddToBundle from "../../components/shared/AddToBundle.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { AddToBundle },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: [
    "filter",
    "permission",
    "script_microservice_enabled",
    "script_microservice_instance_uuid",
  ],
  data() {
    return {
      commandOutput: "",
      languages: [],
      formData: null,
      emptyFormData: {
        name: "",
        description: "",
        config: "",
        language: null,
      },
      errors: {},
      status: "idle",
      pidFile: null,
      exitCode: 0,
      showDockerfile: false,
      loading: true,

      localLoadOnStart: true,
      orderBy: "language",
      data: [],
      // Our listing of script-executors
      sortOrder: [
        {
          field: "language",
          sortField: "language",
          direction: "asc",
        },
      ],
      fields: [
        {
          title: () => this.$t("ID"),
          name: "id",
          sortField: "id",
        },
        {
          title: () => this.$t("Language"),
          name: "language",
          sortField: "language",
        },
        {
          title: () => this.$t("Title"),
          name: "__slot:title",
          sortField: "title",
        },
        {
          title: () => this.$t("Modified"),
          name: "updated_at",
          sortField: "updated_at",
          callback: "formatDate",
        },
        {
          name: "__slot:actions",
          title: "",
        },
      ],
    };
  },
  computed: {
    modalTitle() {
      if (this.formData.id) {
        return `${this.$t("Edit")} ${this.formData.title}`;
      }
      return this.$t("Add New Script Executor");
    },
    isRunning() {
      return ["started", "starting", "saving", "running"].includes(this.status);
    },
    showClose() {
      return !this.isRunning;
    },
    showCancel() {
      return this.isRunning;
    },
    showSave() {
      return !this.isRunning;
    },
    languagesSelect() {
      return [
        { value: null, text: this.$t("Select a language") },
        ...this.languages,
      ];
    },
    initDockerfile() {
      let content = "";
      if (this.formData.language) {
        content = _.get(
          this.languages.find((l) => l.value === this.formData.language),
          "initDockerfile",
          "",
        );
      }
      return content;
    },
  },
  watch: {
    commandOutput() {
      this.scrollToBottom();
    },
  },
  created() {
    this.reset();
  },
  mounted() {
    this.loadLanguages();

    const userId = _.get(
      document.querySelector("meta[name=\"user-id\"]"),
      "content",
    );
    if (userId && !this.script_microservice_enabled) {
      window.Echo.private(`ProcessMaker.Models.User.${userId}`).listen(
        ".BuildScriptExecutor",
        (event) => {
          const { status } = event;
          this.status = status;

          switch (status) {
            case "starting":
              this.pidFile = event.output;
              this.exitCode = 0;
              break;
            case "done":
              this.pidFile = null;
              this.exitCode = event.output;
              break;
            case "error":
              this.output(event.output);
              this.pidFile = null;
              this.exitCode = 1;
              this.status = "done";
              break;
            default:
              this.output(event.output);
          }
        },
      );
    } else if (this.script_microservice_enabled) {
      window.Echo
        .channel(`build-image-${this.script_microservice_instance_uuid}`)
        .listenToAll((eventName, data) => {
          console.log(`Received event: ${eventName}`, data);
          this.status = this.status === "idle" ? "starting" : this.status;
          switch (eventName) {
            case ".build-image":
              this.output(`${data}\n`);
              break;
            case ".build-finished":
              this.pidFile = null;
              this.exitCode = 0;
              this.status = "done";
              break;
            case ".build-error":
              this.output(data);
              this.pidFile = null;
              this.exitCode = 1;
              this.status = "done";
              break;
          }
        });
    }
  },
  methods: {
    deleteExecutor(row) {
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t("Are you sure you want to delete {{item}}?", {
          item: row.title,
        }),
        "",
        () => {
          const path = `/script-executors/${row.id}`;
          ProcessMaker.apiClient
            .delete(path)
            .then((result) => {
              this.status = _.get(result, "data.status", "error");
              if (this.status === "done") {
                this.fetch();
                this.$refs.edit.hide();
              }
            })
            .catch((e) => {
              ProcessMaker.alert(e.response.data.errors.delete[0], "danger");
            });
        },
      );
    },
    getError(name) {
      return _.get(this.errors, `${name}.0`, false);
    },
    setErrors(errors) {
      this.status = "error";
      this.errors = errors.response.data.errors;
    },
    doNotHideIfRunning(e) {
      if (this.isRunning) {
        e.preventDefault();
      }
    },
    output(text) {
      if (typeof text !== "string") {
        return;
      }
      this.commandOutput += text;
    },
    cancel(e) {
      if (this.pidFile) {
        ProcessMaker.apiClient
          .post("/script-executors/cancel", {
            pidFile: this.pidFile,
          })
          .then((result) => {
            if (_.get(result, "data.status") === "canceled") {
              this.status = "idle";
              this.$refs.edit.hide();
            }
          });
      }
    },
    scrollToBottom() {
      if (this.$refs.pre) {
        // after text has rendered
        setTimeout(() => {
          this.$refs.pre.scrollTop = this.$refs.pre.scrollHeight;
        }, 5);
      }
    },
    save() {
      this.resetProcessInfo();
      this.status = "saving";
      if (this.formData.id) {
        const path = `/script-executors/${this.formData.id}`;
        ProcessMaker.apiClient
          .put(path, this.formData)
          .then((result) => {
            this.status = _.get(result, "data.status", "error");
          })
          .catch((e) => {
            this.setErrors(e);
          });
      } else {
        const path = "/script-executors";
        ProcessMaker.apiClient
          .post(path, this.formData)
          .then((result) => {
            this.status = _.get(result, "data.status", "error");
            if (this.status === "started") {
              this.formData.id = result.data.id;
              this.fetch(); // refresh the table (beneath the modal)
            }
          })
          .catch((e) => {
            this.setErrors(e);
          });
      }
    },
    add() {
      this.$refs.edit.show();
    },
    edit(row) {
      console.log(row);
      this.formData = _.cloneDeep(row);
      this.$refs.edit.show();
    },
    reset() {
      this.formData = _.cloneDeep(this.emptyFormData);
      this.errors = {};
      this.showDockerfile = false;
      (this.status = "idle"), this.resetProcessInfo();
    },
    resetProcessInfo() {
      this.commandOutput = "";
      this.exitCode = 0;
      this.pidFile = null;
    },
    loadLanguages() {
      ProcessMaker.apiClient
        .get("/script-executors/available-languages")
        .then((result) => {
          this.languages = result.data.languages;
        });
    },

    fetch() {
      this.loading = true;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          `script-executors?page=${
            this.page
          }&per_page=${
            this.perPage
          }&filter=${
            this.filter
          }&order_by=${
            this.orderBy
          }&order_direction=${
            this.orderDirection}`,
        )
        .then((response) => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    },
    onAddToBundle(data) {
      this.$root.$emit("add-to-bundle", data);
    },
  },
};
</script>

<style scoped>
.command-output {
  font-size: 0.7em;
  height: 200px;
}

.dockerfile {
  height: 300px;
}

.error {
  border-color: red !important;
}
</style>
