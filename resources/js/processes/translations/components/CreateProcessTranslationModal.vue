<template>
  <div>
    <modal
      id="createProcessTranslation"
      :title="modalTitle"
      :subtitle="$t('Translate process screens to a desired language.')"
      :ok-disabled="disabled"
      :size="'lg'"
      @ok.prevent="onSubmit"
      @hidden="onClose"
      @translate="translate"
      @showSelectTargetLanguage="showSelectTargetLanguage"
      @saveTranslations="saveTranslations"
      :hasTitleButtons="hasTitleButtons"
      :hasHeaderButtons="hasHeaderButtons"
      :headerButtons="headerButtons"
      :customButtons="customModalButtons"
      :setCustomButtons="true"
      :show-ai-slogan="true"
    >
      <!-- Select target language section -->
      <div v-if="step === 'selectTargetLanguage'">
        <div>
          {{ $t('By default, all process screens will be auto translated via our AI tooling.') }}
        </div>
        <div class="mt-3">
          <label class="mb-0">Select a target language</label>
          <select-language
            v-model="selectedLanguage"
            :multiple="false"
            :options="availableLanguages"
            :aria-label="$t('Select a target language')"
            class="w-50"
          />
          <small class="text-muted">{{ $t("Language the translator will translate to.") }}</small>
        </div>
        <div class="mt-3">
          <div class="form-group">
            <b-form-checkbox v-model="manualTranslation" data-test="translation-manual-option">
              <div>{{ $t("Manual translation") }}</div>
              <small class="text-muted">{{ $t("Disables auto translate and manually translate screen content.") }}</small>
            </b-form-checkbox>
          </div>
        </div>

        <div class="mt-3" v-if="showLanguageWarning">
          <p class="alert alert-warning m-0">
            {{ $t("Since there is no interface translation for this language, translations for these screens will only render for anonymous users in web entries.") }}
          </p>
        </div>
      </div>

      <!-- Processing translations section -->
      <div v-if="step === 'translating'">
        <div v-if="aiLoading" class="d-flex justify-content-center align-items-center flex-column my-3">
          <span class="power-loader mt-3 mb-2" />
          <span class="ml-2 text-muted small">
            {{ $t("Translation in progress ...") }}
          </span>
        </div>
      </div>

      <!-- Show translations section -->
      <div v-if="step === 'showTranslations'">
        <div>
          <label class="mb-0">Screen</label>
          <select-screen
            v-model="selectedScreen"
            :options="screensTranslations"
            :multiple="false"
            :aria-label="$t('Select a screen')"
            class="w-50"
            data-test="translation-screen-option"
          />
          <small class="text-muted">{{ $t("Select a screen from the process to review and perform translations.") }}</small>
        </div>
        <div
          v-if="stringsWithTranslations
          && Object.keys(stringsWithTranslations).length !== 0
          && (permission.includes('create-process-translations') || permission.includes('edit-process-translations'))">
          <translate-options-popup  @retranslate="onReTranslate"/>
        </div>

        <div class="mt-3 position-relative">
          <div v-if="step === 'showTranslations'" class="d-flex justify-content-center align-items-center">
            <div v-if="aiLoading"
              class="d-flex justify-content-center align-items-center flex-column h-100 position-absolute preloader-container">
              <span class="power-loader mt-3 mb-2" />
              <span class="ml-2 text-muted small">
                {{ $t("Re Translation in progress ...") }}
              </span>
            </div>
          </div>

          <table v-if="stringsWithTranslations && Object.keys(stringsWithTranslations).length !== 0" 
            class="table table-responsive-lg mb-0"
            data-test="translation-string-list">
            <thead>
              <tr>
                  <th class="col-6">{{ $t('String') }}</th>
                  <th class="">{{ selectedLanguage.humanLanguage + ' ' + $t('Translation') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(value, key, index)  in stringsWithTranslations" :key="index">
                <td class="bg-light">{{ key }}</td>
                <td class="py-1 px-2">
                  <b-form-textarea
                    v-model="stringsWithTranslations[key]"
                    :value="value"
                    class="form-control border-0"
                    :aria-label="$t('Add a translation for ') + key"
                    @focus="$event.target.select()"
                    autocomplete="off"
                    @keyup="updateGlobalString(value, key)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
          <div v-else class="text-muted small text-center py-5">{{ $t("Select a screen to show the translations.") }}</div>
        </div>
      </div>
    </modal>
  </div>
</template>

<script>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FormErrorsMixin, Modal } from "SharedComponents";
import SelectLanguage from "../../../components/SelectLanguage"
import SelectScreen from "../../../components/SelectScreen"
import TranslateOptionsPopup from './TranslateOptionsPopup.vue';

Vue.component("font-awesome-icon", FontAwesomeIcon);

export default {
  components: {
    Modal,
    SelectLanguage,
    SelectScreen,
    TranslateOptionsPopup,
  },
  mixins: [FormErrorsMixin],
  props: ["processId", "processName", "translatedLanguages", "editTranslation", "permission"],
  data() {
    return {
      currentNonce: null,
      promptSessionId: "",
      showModal: false,
      disabled: false,
      aiLoading: false,
      availableLanguages: [],
      availablePmLanguages: [],
      selectedLanguage: null,
      selectedScreen: null,
      screensTranslations: [],
      currentScreenTranslations: [],
      stringsWithTranslations: {},
      allScreensTranslations: [],
      availableStrings: [],
      manualTranslation: false,
      modalTitle: this.$t("Add Process Translation"),
      step: "selectTargetLanguage",
      hasHeaderButtons: false,
      hasTitleButtons: false,
      headerButtons: [
        {'content': '< Back', 'action': 'showSelectTargetLanguage', 'variant': 'link', 'disabled': false, 'hidden': true, 'ariaLabel': 'Back to select language'},
      ],
      customModalButtons: [
        {'content': 'Close', 'action': 'hide()', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
        {'content': 'Translate Process', 'action': 'translate', 'variant': 'secondary', 'disabled': true, 'hidden': false, 'dataTest': 'translation-translate-process'},
        {'content': 'Save Translation', 'action': 'saveTranslations', 'variant': 'secondary', 'disabled': false, 'hidden': true, 'dataTest': 'translation-save-translation-button'},
      ],
    };
  },

  computed: {
    showLanguageWarning() {
      if (!this.selectedLanguage) {
        return false;
      }

      if (this.availablePmLanguages[this.selectedLanguage.language] === undefined) {
        return true;
      }
      return false;
    },
  },
  watch: {
    selectedScreen(val) {
      this.currentScreenTranslations = [];
      this.availableStrings = [];

      if (!val) {
        return;
      }

      this.availableStrings = val.availableStrings;

      if (!val.translations) {
        return;
      }

      if (!this.selectedLanguage) {
        return;
      }

      if (this.selectedLanguage.language in val.translations) {
        const translations = val.translations[this.selectedLanguage.language];

        if (!translations.strings) {
          this.currentScreenTranslations = {};
          return;
        }

        Object.keys(translations.strings).forEach((key) => {
          this.currentScreenTranslations.push(translations.strings[key]);
        });
      }
    },

    selectedLanguage() {
      this.validateLanguageSelected();
    },

    currentScreenTranslations(val) {
      this.stringsWithTranslations = {};

      // For each string look into the translations if some exists
      this.availableStrings.forEach((string) => {
        this.$set(this.stringsWithTranslations, string, "");

        if (!val.length) {
          return;
        }

        val.forEach((translation) => {
          if (translation.key === string) {
            this.$set(this.stringsWithTranslations, string, translation.string);
          }
        });
      });
    },

    editTranslation(val) {
      if (val) {
        this.selectedLanguage = val;
        this.manualTranslation = true;
        this.translate(false);
      }
    },

  },
  mounted() {
    this.getAvailableLanguages();

    if (!this.permission.includes("create-process-translations") && !this.permission.includes("edit-process-translations")) {
      this.customModalButtons[2].disabled = true;
      this.customModalButtons[2].hidden = true;
    }
  },
  methods: {
    // This method updates the corresponding string changed in the variable "screensTranslations", so when changing the screen
    // it will remember the changes made by the user
    updateGlobalString(value, key) {
      const screenIndex = this.screensTranslations.findIndex((screen) => screen.id === this.selectedScreen.id);

      if (screenIndex === -1) {
        return;
      }

      if (
        !this.screensTranslations[screenIndex].translations
        || !this.screensTranslations[screenIndex].translations[this.selectedLanguage.language]
        || !this.screensTranslations[screenIndex].translations[this.selectedLanguage.language].strings
      ) {
        this.screensTranslations[screenIndex].translations = { [this.selectedLanguage.language]: { strings: [] } };
      }

      const stringIndex = this.screensTranslations[screenIndex].translations[this.selectedLanguage.language].strings.findIndex(item => item.key === key);

      if (stringIndex !== -1) {
        this.$nextTick(() => {
          this.$set(this.screensTranslations[screenIndex].translations[this.selectedLanguage.language].strings[stringIndex], "string", value);
        });
      } else {
        this.screensTranslations[screenIndex].translations[this.selectedLanguage.language].strings.push({ key, string: value });
      }
    },
    validateLanguageSelected() {
      if (!this.selectedLanguage) {
        this.customModalButtons[1].disabled = true;
      } else {
        this.customModalButtons[1].disabled = false;
      }
    },
    show() {
      this.$bvModal.show("createProcessTranslation");
    },
    onClose() {
      this.showSelectTargetLanguage();
      this.$emit("create-process-translation-closed");
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
    translate(includeImages = true) {
      this.step = "translating";
      this.headerButtons[0].hidden = true;
      this.customModalButtons[1].hidden = true;
      this.customModalButtons[2].hidden = true;
      this.aiLoading = true;
      this.endpointErrors = false;
      this.modalTitle = this.$t(`${this.processName} Translate`);

      const params = {
        language: this.selectedLanguage,
        processId: this.processId,
        manualTranslation: this.manualTranslation,
        promptSessionId: this.getPromptSessionForUser(),
        nonce: localStorage.getItem("currentNonce"),
        includeImages: includeImages,
        justStored: !includeImages,
      };

      ProcessMaker.apiClient.post("/package-ai/language-translation", params)
        .then((response) => {
          if (response.data.error) {
            window.ProcessMaker.alert(response.data.error, "danger");
            this.endpointErrors = response.data.error;
            this.aiLoading = false;
            this.$bvModal.hide("createProcessTranslation");
            return;
          }

          this.screensTranslations = response.data.screensTranslations;

          this.aiLoading = false;

          if (!this.manualTranslation) {
            this.$bvModal.hide("createProcessTranslation");
            this.$emit("translating-language");
            this.showSelectTargetLanguage();
            this.selectedLanguage = null;
          } else {
            this.showTranslations();
          }
          this.getAvailableLanguages();
        })
        .catch(error => {
          const $errorMsg = this.$t("An error ocurred while calling OpenAI endpoint.");
          window.ProcessMaker.alert($errorMsg, "danger");
          this.endpointErrors = $errorMsg;
          this.aiLoading = false;
          this.$bvModal.hide("createProcessTranslation");
        });
    },

    onReTranslate(option) {
      this.$bvModal.hide("createProcessTranslation");
      this.aiLoading = true;

      const params = {
        language: this.selectedLanguage,
        processId: this.processId,
        manualTranslation: this.manualTranslation,
        promptSessionId: this.getPromptSessionForUser(),
        nonce: localStorage.getItem("currentNonce"),
        includeImages: true,
        justStored: false,
        option,
      }

      ProcessMaker.apiClient.post("/package-ai/language-translation", params)
        .then((response) => {
          this.screensTranslations = response.data.screensTranslations;
          this.aiLoading = false;
          this.showSelectTargetLanguage();
          this.$emit("translating-language");
        })
        .catch((error) => {
          const $errorMsg = this.$t("An error ocurred while calling OpenAI endpoint.");
          window.ProcessMaker.alert($errorMsg, "danger");
          this.endpointErrors = $errorMsg;
          this.aiLoading = false;
          this.$bvModal.hide("createProcessTranslation");
        });
    },

    showSelectTargetLanguage() {
      if (!this.permission.includes("create-process-translations") && !this.permission.includes("edit-process-translations")) {
        this.$bvModal.hide("createProcessTranslation");
        return;
      }
      this.step = "selectTargetLanguage";
      this.hasHeaderButtons = false;
      this.hasTitleButtons = false;
      this.headerButtons[0].hidden = true;
      this.customModalButtons[1].hidden = false;
      this.customModalButtons[2].hidden = true;
    },
    showTranslations() {
      this.step = "showTranslations";
      this.selectedScreen = this.screensTranslations[0];
      this.hasHeaderButtons = true;
      this.hasTitleButtons = true;
      this.headerButtons[0].hidden = false;
      this.customModalButtons[1].hidden = true;
      if (!this.permission.includes("create-process-translations") && !this.permission.includes("edit-process-translations")) {
        this.customModalButtons[2].hidden = true;
      } else {
        this.customModalButtons[2].hidden = false;
      }
      this.modalTitle = this.$t(`${this.processName} ${this.selectedLanguage.humanLanguage} Translation`);
    },
    getAvailableLanguages() {
      this.loading = true;
      const params = {
        process_id: this.processId,
      };

      // Load from our api client
      ProcessMaker.apiClient.post("/process/translations/languages", params)
        .then((response) => {
          this.availableLanguages = JSON.parse(JSON.stringify(response.data.availableLanguages));
          this.availablePmLanguages = JSON.parse(JSON.stringify(response.data.availablePmLanguages));
          this.loading = false;
        });
    },
    saveTranslations() {
      this.loading = true;
      this.customModalButtons[2].disabled = true;
      const params = {
        process_id: this.processId,
        screens_translations: this.screensTranslations,
        language: this.selectedLanguage.language,
      };

      // Load from our api client
      ProcessMaker.apiClient.put("/process/translations/update", params)
        .then((response) => {
          this.loading = false;
          this.customModalButtons[2].disabled = false;
          this.$bvModal.hide("createProcessTranslation");
          this.$emit("language-saved");
          ProcessMaker.alert(this.$t('The process translations were saved.'), 'success', 5, true);
        })
        .catch((error) => {
          if (error.response && error.response.data && error.response.data.error) {
            let message = this.$t(error.response.data.error);
            ProcessMaker.alert(message, "danger");
          }
          this.loading = false;
          this.customModalButtons[2].disabled = false;
        });
    },
  },
};
</script>

<style scoped>
.preloader-container {
  left: 0;
  right: 0;
  top: 0;
  z-index: 1;
  background: #ffffffd6;
}

table {
  border: 1px solid #e9edf1;
}
tbody {
  display: block;
  height: 43vh;
  overflow-y: auto;
}
thead, tbody tr {
  display: table;
  width: 100%;
  table-layout: fixed;
}
td.bg-light {
  background-color: #F7F9FB !important;
}
td > .form-control:focus {
  border: 2px solid #1572C2 !important;
  box-shadow: none !important;
}

td {
  position: relative;
}

td textarea {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  border-color: #1572C2 !important;
}

textarea {
  resize: none;
}

.power-loader .text {
  color: #42516e;
  font-size: .8rem;
}
.power-loader {
    width: 55px;
    height: 55px;
    border-radius: 10%;
    position: relative;
    animation: rotate 1s linear infinite
  }
  .power-loader::before , .power-loader::after {
    content: "";
    box-sizing: border-box;
    position: absolute;
    inset: 0px;
    border-radius: 50%;
    border: 5px solid #0871c231;
    animation: prixClipFix 2s linear infinite ;
  }
  .power-loader::after{
    border-color: #0872C2;
    animation: prixClipFix 2s linear infinite , rotate 0.5s linear infinite reverse;
    inset: 6px;
  }

  @keyframes rotate {
    0%   {transform: rotate(0deg)}
    100%   {transform: rotate(360deg)}
  }

  @keyframes prixClipFix {
      0%   {clip-path:polygon(50% 50%,0 0,0 0,0 0,0 0,0 0)}
      25%  {clip-path:polygon(50% 50%,0 0,100% 0,100% 0,100% 0,100% 0)}
      50%  {clip-path:polygon(50% 50%,0 0,100% 0,100% 100%,100% 100%,100% 100%)}
      75%  {clip-path:polygon(50% 50%,0 0,100% 0,100% 100%,0 100%,0 100%)}
      100% {clip-path:polygon(50% 50%,0 0,100% 0,100% 100%,0 100%,0 0)}
  }
</style>
