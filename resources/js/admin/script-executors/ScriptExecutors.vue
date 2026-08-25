<template>
  <div class="data-table">
    <div class="d-flex mb-2" v-show="!shouldShowLoader">
      <div class="mr-auto"></div>
      <div>
        <b-button :aria-label="$t('Add New Script Executor')" type="button" @click="add()">
          <i class="fa fa-plus" /> {{ $t("Script Executor") }}
        </b-button>
      </div>
    </div>
    <data-loading
      :for="/script-executors\?page/"
      v-show="shouldShowLoader"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="card card-body table-card">
      <vuetable
        :dataManager="dataManager"
        :sortOrder="sortOrder"
        :css="css"
        :api-mode="false"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        :noDataTemplate="$t('No Data Available')"
        pagination-path="meta"
      >
        <template slot="title" slot-scope="props">
          <span v-uni-id="props.rowData.id.toString()">{{ props.rowData.title }}</span>
        </template>
        <template slot="type" slot-scope="props">
          {{ typeLabel(props.rowData.type) }}
        </template>

        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="edit(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Edit')"
                v-uni-aria-describedby="props.rowData.id.toString()"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                :disabled="!isEditableType(props.rowData.type)"
                @click="deleteExecutor(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Delete')"
                v-uni-aria-describedby="props.rowData.id.toString()"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="onAddToBundle(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Add to Bundle')"
                v-uni-aria-describedby="props.rowData.id.toString()"
              >
                <i class="fas fa-folder-plus fa-lg fa-fw"></i>
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
        :single="$t('Script Executor')"
        :plural="$t('Script Executors')"
        :perPageSelectEnabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      ></pagination>
    </div>

    <b-modal
      ref="edit"
      id="edit"
      :title="modalTitle"
      @hidden="reset()"
      @hide="doNotHideIfRunning"
      size="lg"
      header-close-content="&times;"
    >
      <b-container class="mb-2">
        <required></required>
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
                  required
                  v-model="formData.title"
                  name="title"
                  :disabled="!isEditableType(formData.type)"
                ></b-input>
              </b-form-group>
              <b-form-group
                required
                :label="$t('Language')"
                :state="!getError('language')"
                :invalid-feedback="getError('language') || ''"
              >
              <b-form-select
                required
                v-model="selectedLanguage"
                :options="languagesSelect"
                name="language"
                :disabled="!isEditableType(formData.type)"
                @change="onLanguageChange"
              >
              </b-form-select>
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
              :disabled="!isEditableType(formData.type)"
            ></b-textarea>
          </b-col>
        </b-row>
      </b-container>

      <p class="mb-0">{{ $t("Docker file") }}</p>

      <div v-if="formData.type !== 'realtime'" class="d-flex flex-row mb-1">
        <div class="mr-1">
          <a
            @click="showDockerfile = !showDockerfile"
            :aria-expanded="showDockerfile ? 'true' : 'false'"
            :title="$t('Display contents of docker file that will be prepended to your customizations below.')"
          >
            <i
              class="fa"
              :class="{
                'fa-chevron-right': !showDockerfile,
                'fa-chevron-down': showDockerfile,
              }"
              style="width: 14px"
            ></i>
          </a>
        </div>
        <div class="flex-fill">
          <pre
            class="mt-1 mb-0"
            @click="
              showDockerfile = !showDockerfile
            ">{{ initDockerfile.split("\n")[0] }} <template v-if="!showDockerfile">...</template></pre>
          <b-collapse id="dockerfile" v-model="showDockerfile" :aria-hidden="showDockerfile ? 'false' : 'true'">
            <pre>{{ initDockerfile.split("\n").slice(1).join("\n") }}</pre>
          </b-collapse>
        </div>
      </div>

      <b-form-textarea
        v-model="formData.config"
        class="mb-3 dockerfile"
        :disabled="isRunning || !isEditableType(formData.type)"
      >
      </b-form-textarea>

      <div v-if="commandOutput !== '' || isRunning">
        <p>
          {{ $t("Build Command Output") }}
          <i v-if="isRunning" class="fas fa-spinner fa-spin"></i>
        </p>
        <pre
          ref="pre"
          class="border command-output pre-scrollable"
          :class="{ error: exitCode !== 0 }"
          >{{ commandOutput }}</pre
        >
      </div>

      <div v-if="status === 'done'">
        <p v-if="exitCode === 0">
          {{
            $t("Executor Successfully Built. You can now close this window. ")
          }}
        </p>
        <div v-if="exitCode > 0" class="invalid-feedback d-block" role="alert">
          {{ $t("Error Building Executor. See Output Above.") }}
        </div>
      </div>

      <template v-slot:modal-footer>
        <b-button
          v-if="showClose"
          variant="secondary"
          @click="$bvModal.hide('edit')"
        >
          {{ $t("Close") }}
        </b-button>

        <b-button
          v-if="showCancel && isEditableType(formData.type)"
          :disabled="pidFile === null"
          variant="secondary"
          @click="cancel"
        >
          {{ $t("Cancel") }}
        </b-button>

        <b-button
          v-if="showSave && isEditableType(formData.type)"
          :disabled="isRunning"
          variant="primary"
          @click="save()"
        >
          <template v-if="formData.id">{{ $t("Save And Rebuild") }}</template>
          <template v-else>{{ $t("Save And Build") }}</template>
        </b-button>
      </template>
    </b-modal>
  </div>
</template>


<script>
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import AddToBundle from "../../components/shared/AddToBundle.vue";
import { createUniqIdsMixin } from "vue-uniq-ids";
const uniqIdsMixin = createUniqIdsMixin();

export default {
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  components: { AddToBundle },
  props: [
    "filter",
    "permission",
    "script_microservice_enabled",
    "script_microservice_tenant_id",
  ],
  data() {
    return {
      commandOutput: "",
      languages: [],
      formData: null,
      selectedLanguage: null,
      emptyFormData: {
        name: "",
        description: "",
        config: "",
        language: null,
        type: "custom",
      },
      errors: {},
      status: "idle",
      pidFile: null,
      exitCode: 0,
      showDockerfile: false,
      loading: true,
      activeBuildUuid: null,
      pendingBuildEvents: [],

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
          title: () => this.$t("Type"),
          name: "__slot:type",
          sortField: "type",
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
  created() {
    this.reset();
  },
  mounted() {
    this.loadLanguages();

    const userId = _.get(
      document.querySelector('meta[name="user-id"]'),
      "content"
    );
    if (userId && !this.script_microservice_enabled) {
      window.Echo.private(`ProcessMaker.Models.User.${userId}`).listen(
        ".BuildScriptExecutor",
        (event) => {
          const status = event.status;
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
        }
      );
    }

    if (this.script_microservice_enabled && this.script_microservice_tenant_id) {
      this.subscribeToTenantBuildChannel();
    }
  },
  watch: {
    commandOutput() {
      this.scrollToBottom();
    },
  },
  computed: {
    modalTitle() {
      if (this.formData.id) {
        return this.$t("Edit") + " " + this.formData.title;
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
        ...this.languages.map((lang) => ({
          value: lang.value,
          text: lang.text,
        })),
      ];
    },
    selectedLanguageOption() {
      if (!this.selectedLanguage) {
        return null;
      }
      return this.languages.find((l) => l.value === this.selectedLanguage) || null;
    },
    initDockerfile() {
      let content = "";
      if (this.selectedLanguageOption) {
        content = _.get(this.selectedLanguageOption, "initDockerfile", "");
      }
      return content || "";
    },
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
          const path = "/script-executors/" + row.id;
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
        }
      );
    },
    getError(name) {
      return _.get(this.errors, name + ".0", false);
    },
    setErrors(errors) {
      this.status = "error";
      this.errors = _.get(errors, "response.data.errors", {});
      const messages = Object.values(this.errors)
        .flat()
        .filter((message) => typeof message === "string" && message !== "");
      if (messages.length) {
        this.output(messages.join("\n") + "\n");
      }
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
      this.activeBuildUuid = this.formData.uuid || null;
      this.pendingBuildEvents = [];
      if (this.formData.id) {
        const path = "/script-executors/" + this.formData.id;
        ProcessMaker.apiClient
          .put(path, this.formData)
          .then((result) => {
            this.status = _.get(result, "data.status", "error");
            this.setActiveBuildUuid(_.get(result, "data.uuid", this.formData.uuid));
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
            this.setActiveBuildUuid(_.get(result, "data.uuid"));
            if (this.status === "started") {
              this.formData.id = result.data.id;
              this.formData.uuid = result.data.uuid;
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
      this.formData = _.cloneDeep(row);
      this.selectedLanguage = row.type === "realtime"
        ? `${row.language}-realtime`
        : row.language;
      this.activeBuildUuid = row.uuid || null;
      this.$refs.edit.show();
    },
    reset() {
      this.formData = _.cloneDeep(this.emptyFormData);
      this.selectedLanguage = null;
      this.errors = {};
      this.showDockerfile = false;
      this.status = "idle";
      this.activeBuildUuid = null;
      this.pendingBuildEvents = [];
      this.resetProcessInfo();
    },
    onLanguageChange(value) {
      const option = this.languages.find((l) => l.value === value);
      if (!option) {
        this.formData.language = null;
        this.formData.type = "custom";
        return;
      }
      this.formData.language = option.language || option.value;
      this.formData.type = option.realtime ? "realtime" : "custom";
      if (!this.formData.id) {
        this.formData.config = option.realtime
          ? (option.configExample || "")
          : "";
      } else if (option.realtime && !this.formData.config && option.configExample) {
        this.formData.config = option.configExample;
      }
    },
    isEditableType(type) {
      return type === "custom" || type === "realtime";
    },
    typeLabel(type) {
      if (type === "realtime") {
        return "Realtime";
      }
      if (type === "custom") {
        return "Custom";
      }
      return "Base";
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
          "script-executors?page=" +
            this.page +
            "&per_page=" +
            this.perPage +
            "&filter=" +
            this.filter +
            "&order_by=" +
            this.orderBy +
            "&order_direction=" +
            this.orderDirection
        )
        .then((response) => {
          this.data = this.transform(response.data);
          this.loading = false;
        });
    },
    onAddToBundle(data) {
      this.$root.$emit('add-to-bundle', data);
    },
    subscribeToTenantBuildChannel() {
      if (!window.ScriptMicroserviceEcho) {
        return;
      }
      const channel = `tenant-${this.script_microservice_tenant_id}-builds`;
      window.ScriptMicroserviceEcho.channel(channel).listen(
        ".executor-build",
        (data) => {
          this.handleExecutorBuildEvent(data);
        }
      );
    },
    setActiveBuildUuid(uuid) {
      if (!uuid) {
        return;
      }
      this.activeBuildUuid = uuid;
      const pending = this.pendingBuildEvents.filter(
        (event) => event.executor_id === uuid
      );
      this.pendingBuildEvents = [];
      pending.forEach((event) => this.applyExecutorBuildEvent(event));
    },
    handleExecutorBuildEvent(data) {
      if (!data || typeof data !== "object") {
        return;
      }
      if (!this.isRunning) {
        return;
      }
      if (!this.activeBuildUuid) {
        this.pendingBuildEvents.push(data);
        return;
      }
      if (data.executor_id !== this.activeBuildUuid) {
        return;
      }
      this.applyExecutorBuildEvent(data);
    },
    applyExecutorBuildEvent(data) {
      if (this.status === "saving") {
        this.status = "starting";
      } else if (this.status === "idle") {
        this.status = "starting";
      }

      if (data.type === "status") {
        this.output(`[${data.phase}] ${data.message || ""}\n`);
      } else if (data.message) {
        const message = data.message.endsWith("\n")
          ? data.message
          : `${data.message}\n`;
        this.output(message);
      }

      if (data.phase === "completed" && data.type === "status") {
        this.pidFile = null;
        this.exitCode = 0;
        this.status = "done";
        return;
      }

      if (data.phase === "error" || data.type === "error") {
        this.pidFile = null;
        this.exitCode = 1;
        this.status = "done";
      }
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