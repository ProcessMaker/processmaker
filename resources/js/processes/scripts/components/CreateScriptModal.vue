<template>
  <div>
    <b-button
      v-if="!hideAddBtn && !callFromAiModeler"
      ref="createScriptModalButton"
      v-b-modal.createScript
      :aria-label="$t('Create Script')"
      class="mb-3 mb-md-0 ml-md-2"
    >
      <i class="fas fa-plus" /> {{ $t("Script") }}
    </b-button>
    <b-modal
      id="createScript"
      size="xl"
      scrollable
      centered
      no-close-on-backdrop
      :ok-disabled="disabled"
      :title="modalSetUp"
      @hidden="onClose"
    >
      <b-row>
        <b-col cols="4">
          <language-script
            :languages="scriptExecutors"
            :select="onSelect"
            :invalid_feedback="errorMessage('script_executor_id', addError)"
          />
        </b-col>
        <b-col
          cols="8"
          class="script-form"
        >
          <template v-if="countCategories">
            <b-form-group
              :description="
                formDescription(
                  'The script name must be unique.',
                  'title',
                  addError
                )
              "
              :invalid-feedback="errorMessage('title', addError)"
              :label="$t('Name')"
              :state="errorState('title', addError)"
              required
            >
              <b-form-input
                v-model="title"
                :state="errorState('title', addError)"
                autocomplete="off"
                autofocus
                name="title"
                required
              />
            </b-form-group>
            <b-form-group
              :invalid-feedback="errorMessage('description', addError)"
              :label="$t('Description')"
              :state="errorState('description', addError)"
              required
            >
              <b-form-textarea
                v-model="description"
                :state="errorState('description', addError)"
                autocomplete="off"
                name="description"
                required
                rows="2"
              />
            </b-form-group>
            <project-select
              v-if="isProjectsInstalled"
              v-model="projects"
              :errors="addError.projects"
              :label="$t('Project')"
              :required="isProjectSelectionRequired"
              api-get="projects"
              api-list="projects"
              name="project"
              :project-id="projectId"
            />
            <category-select
              v-model="script_category_id"
              :errors="addError.script_category_id"
              :label="$t('Category')"
              api-get="script_categories"
              api-list="script_categories"
              name="script_category_id"
            />
            <div class="d-flex justify-content-end w-100">
              <button
                class="btn btn-link text-capitalize weight-600 collapsed"
                type="button"
                data-toggle="collapse"
                data-target="#collapseAdvancedOptions"
                aria-expanded="false"
                aria-controls="collapseAdvancedOptions"
              >
                <p class="closed">
                  {{ $t('Advanced Options') }}
                  <i class="fas fa-angle-double-down px-2" />
                </p>
                <p class="opened">
                  {{ $t('Less Options') }}
                  <i class="fas fa-angle-double-up" />
                </p>
              </button>
            </div>
            <div
              id="collapseAdvancedOptions"
              class="collapse"
            >
              <b-form-group
                :invalid-feedback="errorMessage('run_as_user_id', addError)"
                :label="$t('Run Script As')"
                :state="errorState('run_as_user_id', addError)"
                required
              >
                <select-user
                  v-model="selectedUser"
                  :class="{
                    'is-invalid': errorState('run_as_user_id', addError) == false,
                  }"
                  :multiple="false"
                  name="run_as_user_id"
                />
              </b-form-group>
              <b-form-group
                class="form-group-border"
                :invalid-feedback="errorMessage('timeout', addError)"
                :error="
                  errorState('timeout', addError)
                    ? null
                    : errorMessage('timeout', addError)
                "
              >
                <div class="d-flex d-flex justify-content-between align-items-center mb-3">
                  <div class="label-description">
                    <label
                      class="weight-600"
                      for="inputTimeout"
                    >
                      {{ $t('Timeout') }}
                    </label>
                    <p class="text-muted m-0">
                      {{ $t('How many seconds the script should be allowed to run (0 is unlimited).') }}
                    </p>
                  </div>
                  <b-form-input
                    id="inputTimeout"
                    v-model="timeout"
                    class="input-number"
                    type="number"
                    :max="300"
                    :min="0"
                    name="timeout"
                  />
                </div>
              </b-form-group>
              <b-form-group
                class="form-group-border"
                :invalid-feedback="errorMessage('retry_attempts', addError)"
                :error="
                  errorState('retry_attempts', addError)
                    ? null
                    : errorMessage('retry_attempts', addError)
                "
              >
                <div class="d-flex d-flex justify-content-between align-items-center mb-3">
                  <div class="label-description">
                    <label
                      class="weight-600"
                      for="inputRetryAttempts"
                    >
                      {{ $t('Retry Attempts') }}
                    </label>
                    <p class="text-muted m-0">
                      {{ $t('Number of times to retry. Leave empty to use script default. Set to 0 for no retry attempts.') }}
                    </p>
                  </div>
                  <b-form-input
                    id="inputRetryAttempts"
                    v-model="retry_attempts"
                    class="input-number"
                    type="number"
                    :max="10"
                    :min="0"
                    name="retry_attempts"
                  />
                </div>
              </b-form-group>
              <b-form-group
                class="form-group-border"
                :invalid-feedback="errorMessage('retry_wait_time', addError)"
                :error="
                  errorState('retry_wait_time', addError)
                    ? null
                    : errorMessage('retry_wait_time', addError)
                "
              >
                <div class="d-flex d-flex justify-content-between align-items-center mb-3">
                  <div class="label-description">
                    <label
                      class="weight-600"
                      for="inputRetryWaitTime"
                    >
                      {{ $t('Retry Wait Time') }}
                    </label>
                    <p class="text-muted m-0">
                      {{ $t('Seconds to wait before retrying. Leave empty to use script default. Set to 0 for no retry wait time.') }}
                    </p>
                  </div>
                  <b-form-input
                    id="inputRetryWaitTime"
                    v-model="retry_wait_time"
                    class="input-number"
                    type="number"
                    :max="3600"
                    :min="0"
                    name="retry_wait_time"
                  />
                </div>
              </b-form-group>
            </div>
            <component
              :is="cmp"
              v-for="(cmp, index) in createScriptHooks"
              :key="`create-script-hook-${index}`"
              ref="createScriptHooks"
              :script="script"
            />
          </template>
          <template v-else>
            <div>{{ $t("Categories are required to create a script") }}</div>
            <a
              class="btn btn-primary container mt-2"
              href="/designer/scripts/categories"
            >
              {{ $t("Add Category") }}
            </a>
          </template>
        </b-col>
      </b-row>
      <div
        slot="modal-footer"
        class="w-100 m-0 d-flex d-flex align-items-center"
      >
        <required />
        <button
          type="button"
          class="btn btn-outline-secondary ml-auto"
          @click="onClose"
        >
          {{ $t('Cancel') }}
        </button>
        <button
          type="button"
          class="btn btn-secondary ml-3"
          @click="onSubmit"
        >
          {{ $t('Save') }}
        </button>
      </div>
    </b-modal>
  </div>
</template>

<script>
import FormErrorsMixin from "../../../components/shared/FormErrorsMixin";
import AssetRedirectMixin from "../../../components/shared/AssetRedirectMixin";
import Modal from "../../../components/shared/Modal.vue";
import Required from "../../../components/shared/Required.vue";
import ProjectSelect from "../../../components/shared/ProjectSelect.vue";
import SliderWithInput from "../../../components/shared/SliderWithInput.vue";
import { isQuickCreate as isQuickCreateFunc } from "../../../utils/isQuickCreate";
import LanguageScript from "./LanguageScript.vue";

const channel = new BroadcastChannel("assetCreation");

export default {
  components: {
    Modal,
    Required,
    SliderWithInput,
    ProjectSelect,
    LanguageScript,
  },
  mixins: [FormErrorsMixin, AssetRedirectMixin],
  props: [
    "countCategories",
    "scriptExecutors",
    "isProjectsInstalled",
    "hideAddBtn",
    "copyAssetMode",
    "projectAsset",
    "assetName",
    "callFromAiModeler",
    "isProjectSelectionRequired",
    "projectId",
    "assetData",
    "runAsUserDefault"
  ],
  data() {
    return {
      title: "",
      language: "",
      script_executor_id: null,
      description: "",
      script_category_id: "",
      category_type_id: "",
      code: "",
      addError: {},
      selectedUser: "",
      users: [],
      timeout: 60,
      retry_attempts: 0,
      retry_wait_time: 5,
      disabled: false,
      createScriptHooks: [],
      script: null,
      projects: [],
      isQuickCreate: isQuickCreateFunc(),
    };
  },
  computed: {
    modalSetUp() {
      if (this.copyAssetMode) {
        this.title = `${this.assetName} ${this.$t("Copy")}`;
        this.script_executor_id = this.assetData.script_executor_id;
        this.description = this.assetData.description;
        this.script_category_id = this.assetData.script_category_id;
        this.run_as_user_id = this.assetData.selectedUser
          ? this.assetData.selectedUser.id
          : null;
        this.projects = this.assetData.projects;
        this.code = this.assetData.code;
        this.timeout = this.assetData.timeout;
        this.retry_attempts = this.assetData.retry_attempts;
        this.retry_wait_time = this.assetData.retry_wait_time;
        return this.$t("Copy of Asset");
      }
      this.title = "";
      return this.$t("Create Script");
    },
  },
  mounted() {
    this.$nextTick(() => {
      this.selectedUser = this.runAsUserDefault ? this.runAsUserDefault : null;
    });
  },
  methods: {
    show() {
      this.$bvModal.show("createScript");
    },
    onClose() {
      this.title = "";
      this.language = "";
      this.script_executor_id = null;
      this.description = "";
      this.script_category_id = "";
      this.category_type_id = "";
      this.code = "";
      this.timeout = 60;
      this.retry_attempts = 0;
      this.retry_wait_time = 5;
      this.addError = {};
      this.projects = [];
      this.close();
    },
    close() {
      this.$bvModal.hide("createScript");
      this.disabled = false;
      this.$emit("reload");
    },
    onSubmit() {
      this.errors = {
        name: null,
        description: null,
        status: null,
        script_category_id: null,
      };
      // single click
      if (this.disabled) {
        return;
      }
      this.disabled = true;

      ProcessMaker.apiClient
        .post("/scripts", {
          title: this.title,
          script_executor_id: this.script_executor_id,
          description: this.description,
          script_category_id: this.script_category_id,
          run_as_user_id: this.selectedUser ? this.selectedUser.id : null,
          projects: this.projects,
          code: "[]",
          timeout: this.timeout,
          retry_attempts: this.retry_attempts,
          retry_wait_time: this.retry_wait_time,
        })
        .then(({ data }) => {
          ProcessMaker.alert(this.$t("The script was created."), "success");
          (this.$refs.createScriptHooks || []).forEach((hook) => {
            hook.onsave(data);
          });

          const url = new URL(`/designer/scripts/${data.id}/builder`, window.location.origin);
          this.appendProjectIdToURL(url, this.projectId);
          this.handleRedirection(url, data);
        })
        .catch((error) => {
          this.disabled = false;
          if (_.get(error, "response.status") === 422) {
            this.addError = error.response.data.errors;
          } else {
            throw error;
          }
        });
    },
    handleRedirection(url, data) {
      if (this.callFromAiModeler) {
        this.$emit("script-created-from-modeler", url, data.id, data.title);
      } else if (this.copyAssetMode) {
        this.close();
      } else {
        if (this.isQuickCreate === true) {
          channel.postMessage({
            assetType: "script",
            asset: data,
          });
        }

        window.location.href = url;
      }
    },
    /**
     * Set the ID of the language selected
     */
    onSelect(langId) {
      this.script_executor_id = langId;
    },
  },
};
</script>

<style scoped>
  .label-description {
    width: 80%;
  }
  .input-number {
    width: 15%;
  }
  .weight-600{
    font-weight: 600;
  }
  .form-group-border{
    border-bottom: 1px solid #CDDDEE;
  }
  .collapsed > .opened,
  :not(.collapsed) > .closed {
      display: none;
  }
  .script-form {
    display: block;
    height: 100%;
    max-height: 446px;
  }
</style>
