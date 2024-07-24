<template>
  <div class="data-table">
    <div v-if="!loading && !translatedLanguages.length && !translatingLanguages.length">
      <div class="d-flex flew-grow-1 flex-column align-items-center no-results-container">
        <div class="icon-lg text-secondary">
          <font-awesome-icon :icon="['fpm', 'fa-translations']" />
        </div>
        <div class="text-secondary">
          {{ $t("No translations found") }}
        </div>
      </div>
    </div>

    <table v-if="!loading && ((translatedLanguages && translatedLanguages.length) || (translatingLanguages && translatingLanguages.length))"
      id="table-translations"
      class="table table-hover table-responsive-lg "
      data-test="translation-list">
      <thead>
        <tr>
            <th class="notify">{{ $t('Target Language') }}</th>
            <th class="action">{{ $t('Created') }}</th>
            <th class="action">{{ $t('Updated') }}</th>
            <th class="action"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in translatingLanguages" :key="'pending' + index">
          <td class="notify">{{ item.humanLanguage }}</td>
          <td class="action">{{ formatDate(item.created_at) }}</td>
          <td class="action">{{ formatDate(item.updated_at) }}</td>
          <td class="action">
            <div class="translation-in-progress">
              <ellipsis-menu
                :translation="true"
                :actions="actionsInProgress"
                :permission="permission"
                :data="item"
                :divider="true"
                :custom-button="inProgressButton"
                :show-progress="true"
                @navigate="onNavigate"
              />
              <p class="right-aligned-percent m-0" v-if="item.stream && item.stream.data">{{ item.stream.data }}</p>
            </div>
          </td>
        </tr>
        <tr v-for="(item, index) in translatedLanguages" :key="index">
          <td class="notify"><a role="button" class="link" @click="handleEditTranslation(item)">{{ item.humanLanguage }}</a></td>
          <td class="action">{{ formatDate(item.createdAt) }}</td>
          <td class="action">{{ formatDate(item.updatedAt) }}</td>
          <td class="action">
            <ellipsis-menu
              :translation="true"
              :actions="actions"
              :permission="permission"
              :data="item"
              :divider="true"
              :custom-button="loadingItems.includes(item.language) ? inProgressButtonSmall : false"
              @navigate="onNavigate"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import { library } from "@fortawesome/fontawesome-svg-core";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";
import { faTranslations } from "../../../components/shared/customIcons/faTranslations";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { EllipsisMenu },
  mixins: [uniqIdsMixin],
  props: ["filter", "id", "status", "permission", "processId"],
  data() {
    return {
      translatedLanguages: [],
      translatingLanguages: [],
      editTranslation: null,
      orderBy: "language",
      loading: false,
      loadingItems: [],
      socketListeners: [],
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc",
        },
      ],
      actions: [
        {
          value: "edit-translation",
          content: "Edit Translation",
          permission: "edit-process-translations",
          icon: "fas fa-edit",
          dataTest: "translation-list-editfield",
        },
        {
          value: "export-translation",
          content: "Export Translation",
          permission: "export-process-translations",
          icon: "fas fa-file-export",
          dataTest: "translation-export",
          link: true,
          href: "/processes/{{processId}}/export/translation/{{language}}",
        },
        {
          value: "retry-translation",
          content: "Retry Empty Translations",
          permission: "edit-process-translations",
          dataTest: "translation-option-retry",
          icon: "fas fa-redo",
        },
        {
          value: "delete-translation",
          content: "Delete Translation",
          dataTest: "translation-option-delete",
          permission: "delete-process-translations",
          icon: "fas fa-trash",
        },
      ],
      actionsInProgress: [
        {
          value: "cancel-translation",
          content: "Cancel Translation",
          permission: "cancel-process-translations",
          icon: "fas fa-stop-circle",
          dataTest: "translation-option-cancel",
        },
        {
          value: "delete-translation",
          content: "Delete Translation",
          permission: "delete-process-translations",
          icon: "fas fa-trash",
          dataTest: "translation-option-delete",
        },
      ],
      inProgressButton: {
        icon: "fas fa-spinner fa-spin p-0",
        content: "Translation in progress",
      },
      inProgressButtonSmall: {
        icon: "fas fa-spinner fa-spin p-0",
        content: "",
      },
    };
  },

  watch: {
    filter() {
      this.fetch();
      this.fetchPending();
    },
  },

  created() {
    // Add custom translations icon
    library.add(faTranslations);

    this.fetch();
    this.fetchPending();
    ProcessMaker.EventBus.$on("api-data-process-translations", () => {
      this.fetch();
      this.fetchPending();
    });
  },

  mounted() {
    this.getNonce();
    this.fetchHistory();
    this.subscribeToTranslationEvent();
    window.Echo.private(`ProcessMaker.Models.User.${window.ProcessMaker.user.id}`).notification((response) => {
      if (response.processId === this.processId) {
        this.fetchPending();
        this.fetch();
      }
    });
  },

  methods: {
    subscribeToTranslationEvent() {
      const channel = `ProcessMaker.Models.User.${window.ProcessMaker?.user?.id}`;
      const translationEvent = ".ProcessMaker\\Package\\PackageAi\\Events\\GenerateTranslationProgressEvent";
      if (!window.Echo) {
        return;
      }
      window.Echo.private(channel).listen(
        translationEvent,
        (response) => {
          this.translatingLanguages.forEach((translatingLanguage, key) => {
            this.$set(this.translatingLanguages[key], "progress", response.data.progress);
            this.$set(this.translatingLanguages[key].stream, "data", `${response.data.progress.progress}%`);
          });
          if (response.data.progress.status === 'completed' || response.data.progress.status === 'error') {
            this.fetchPending();
            this.fetch();
          }
        },
      );
    },
    getNonce() {
      const max = 999999999999999;
      const nonce = Math.floor(Math.random() * max);
      this.currentNonce = nonce;
      localStorage.currentNonce = this.currentNonce;
    },
    removePromptSessionForUser() {
      // Get sessions list
      let promptSessions = localStorage.getItem('promptSessions');

      // If promptSessions does not exist, set it as an empty array
      promptSessions = promptSessions ? JSON.parse(promptSessions) : [];

      let item = promptSessions.find(item => item.userId === window.ProcessMaker?.modeler?.process?.user_id && item.server === window.location.host);

      if (item) {
        item.promptSessionId = '';
      }

      localStorage.setItem('promptSessions', JSON.stringify(promptSessions));
    },
    setPromptSessions(promptSessionId) {
      let index = 'userId';
      let id = this.process_id;

      // Get sessions list
      let promptSessions = localStorage.getItem('promptSessions');

      // If promptSessions does not exist, set it as an empty array
      promptSessions = promptSessions ? JSON.parse(promptSessions) : [];

      let item = promptSessions.find(item => item[index] === id && item.server === window.location.host);

      if (item) {
        item.promptSessionId = promptSessionId;
      } else {
        promptSessions.push({ [index]: id, server: window.location.host, promptSessionId });
      }

      localStorage.setItem('promptSessions', JSON.stringify(promptSessions));
    },
    getPromptSessionForUser() {
      // Get sessions list
      let promptSessions = localStorage.getItem('promptSessions');

      // If promptSessions does not exist, set it as an empty array
      promptSessions = promptSessions ? JSON.parse(promptSessions) : [];
      let item = promptSessions.find(item => item.userId === window.ProcessMaker?.modeler?.process?.user_id && item.server === window.location.host);

      if (item) {
        return item.promptSessionId;
      }

      return '';
    },
    fetchHistory() {
      let url = '/package-ai/getPromptSessionHistory';

      let params = {
        server: window.location.host,
        processId: null,
      };

      if (this.promptSessionId && this.promptSessionId !== null && this.promptSessionId !== '') {
        params = {
          promptSessionId: this.promptSessionId,
        };
      }

      window.ProcessMaker.apiClient.post(url, params)
        .then(response => {
          this.setPromptSessions((response.data.promptSessionId));
          this.promptSessionId = (response.data.promptSessionId);
          localStorage.promptSessionId = (response.data.promptSessionId);
        })
        .catch((error) => {
          const errorMsg = error.response?.data?.message || error.message;

          this.loading = false;
          if (error.response.status === 404) {
            this.removePromptSessionForUser();
            localStorage.promptSessionId = '';
            this.promptSessionId = '';
            this.fetchHistory();
          } else {
            console.error(errorMsg);
          }
        });
    },
    onNavigate(action, data, index) {
      switch (action.value) {
        case "edit-translation":
          this.handleEditTranslation(data);
          break;
        case "retry-translation":
          this.handleRetryTranslation(data);
          break;
        case "cancel-translation":
          this.handleCancelTranslation(data);
          break;
        case "delete-translation":
          this.handleDeleteTranslation(data);
          break;
        default:
          break;
      }
    },

    formatDate(value, format) {
      format = format || "";
      if (value) {
        return window.moment(value)
          .format(format);
      }
      return "n/a";
    },

    fetch() {
      const url = "process/translations?process_id=" + this.processId;

      // Load from our api client
      ProcessMaker.apiClient
        .get(
          url +
          "&page=" +
          this.page +
          "&per_page=" +
          this.perPage +
          "&filter=" +
          this.filter +
          "&order_by=" +
          this.orderBy +
          "&order_direction=" +
          this.orderDirection +
          "&include="
        )
        .then((response) => {
          this.translatedLanguages = response.data.translatedLanguages;
          this.$emit("translated-languages-changed", this.translatedLanguages);
        });
    },

    fetchPending() {
      const url = "process/translations/pending?process_id=" + this.processId;

      // Load from our api client
      ProcessMaker.apiClient
        .get(
          url +
          "&page=" +
          this.page +
          "&per_page=" +
          this.perPage +
          "&filter=" +
          this.filter +
          "&order_by=" +
          this.orderBy +
          "&order_direction=" +
          this.orderDirection +
          "&include="
        )
        .then((response) => {
          this.translatingLanguages = response.data.translatingLanguages;

          this.translatingLanguages.forEach(lang => {
            lang.stream = {
              data: "1%",
            };
          });

          this.removeSocketListeners();
          this.subscribeToEvent();
        });
    },
    addSocketListener(channel, event, callback) {
      this.socketListeners.push({
        channel,
        event,
      });
      window.Echo.private(channel).listen(
        event,
        callback,
      );
    },
    removeSocketListeners() {
      this.socketListeners.forEach((element) => {
        window.Echo.private(element.channel).stopListening(element.event);
      });
    },
    subscribeToEvent() {
      this.translatingLanguages.forEach((translatingLanguage, key) => {
        const channel = `ProcessMaker.Models.Process.${this.processId}.Language.${translatingLanguage.language}`;
        const streamProgressEvent = ".ProcessMaker\\Events\\ProcessTranslationChunkEvent";
        const batchProgressEvent = ".ProcessMaker\\Events\\ProcessTranslationChunkProgressEvent";

        // Subscribe to streamed responses
        this.addSocketListener(channel, streamProgressEvent, (response) => {
          if (response.stream && this.translatingLanguages[key]) {
            this.$set(this.translatingLanguages[key], "stream", response.stream);
            this.$set(this.translatingLanguages[key], "progress", response.progress);
          }
        });

        // Subscribe to chunk progress
        this.addSocketListener(channel, batchProgressEvent, (response) => {
          if (response.batch && this.translatingLanguages[key]) {
            this.$set(this.translatingLanguages[key], "batch", response.batch);
            if (this.translatingLanguages[key].progress) {
              this.$set(this.translatingLanguages[key].progress, "percentage", 0);
            }
            if (response.batch.progress === 100) {
              this.fetch();
              this.fetchPending();
            }
          }
        });
      });
    },
    handleEditTranslation(data) {
      this.editTranslation = data;
      this.$emit("edit-translation", this.editTranslation);
    },

    handleRetryTranslation(data) {
      this.loadingItems.push(data.language);

      const params = {
        language: data,
        processId: this.processId,
        option: "empty",
        promptSessionId: this.getPromptSessionForUser(),
        nonce: localStorage.getItem("currentNonce"),
        includeImages: true,
      };

      ProcessMaker.apiClient.post("/package-ai/language-translation", params)
        .then((response) => {
          this.screensTranslations = response.data.screensTranslations;
          this.fetch();
          this.fetchPending();
          const index = this.loadingItems.indexOf(data.language);
          if (index > -1) {
            this.loadingItems.splice(index);
          }
        })
        .catch((error) => {
          const $errorMsg = this.$t("An error ocurred while calling OpenAI endpoint.");
          window.ProcessMaker.alert($errorMsg, "danger");
          const index = this.loadingItems.indexOf(data.language);
          if (index > -1) {
            this.loadingItems.splice(index);
          }
        });
    },
    handleCancelTranslation(translation) {
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t(`Are you sure you want to cancel the translations for ${translation.humanLanguage} language? This process will not delete the already translated strings`),
        "",
        () => {
          ProcessMaker.apiClient
            .post(`process/translations/${this.processId}/cancel/translation/${translation.language}`)
            .then(() => {
              this.fetch();
              this.fetchPending();
              ProcessMaker.alert(this.$t(`The ${translation.humanLanguage} translations jobs were canceled.`), "success", 5, true);
            });
        },
      );
    },
    handleDeleteTranslation(translation) {
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t(`Are you sure you want to delete the translations for ${translation.humanLanguage} language?`),
        "",
        () => {
          ProcessMaker.apiClient
            .delete(`process/translations/${this.processId}/${translation.language}`)
            .then(() => {
              this.fetch();
              this.fetchPending();
              ProcessMaker.alert(this.$t(`The ${translation.humanLanguage} translations were deleted.`), 'success', 5, true);
            });
        },
      );
    },
  },
};
</script>

<style lang="scss" scoped>

  .ellipsis-menu-icon {
    padding: 0 !important;
  }
  .icon-lg {
    font-size: 5rem;
  }
  .no-results-container {
    padding: 8rem 0rem;
  }
  :deep(th#_updated_at) {
    width: 14%;
  }
  :deep(th#_created_at) {
    width: 14%;
  }
  td {
    vertical-align: middle;
  }

  .streamText {
    max-height: 4rem;
    overflow-y: hidden;
    margin: 0px;
    width: 32rem;
    height: 100%;
    border: 0;
    font-size: 80%;
  }

  .streamTextBackdrop {
    display: none;
    background: linear-gradient(0deg, rgb(255, 255, 255) 5%, rgba(255, 255, 255, 0) 40%, rgba(255, 255, 255, 0) 70%, rgb(255, 255, 255) 95%);
    height: 100%;
    width: 100%;
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
  }

  .translation-in-progress {
    display: flex;
    flex-grow:1;
    justify-content: flex-end;
    align-items: center;
  }

  .right-aligned-percent {
    float: right;
    margin-left: 5px;
    margin-top: 10px;
    font-weight: bold;
  }
</style>
