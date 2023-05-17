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
      @translateProcess="translateProcess"
      @showSelectTargetLanguage="showSelectTargetLanguage"
      :hasTitleButtons="hasTitleButtons"
      :hasHeaderButtons="hasHeaderButtons"
      :headerButtons="headerButtons"
      :customButtons="customModalButtons"
      :setCustomButtons="true"
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
            <b-form-checkbox v-model="manualTranslation">
              <div>{{ $t("Manual translation") }}</div>
              <small class="text-muted">{{ $t("Disables auto translate and manually translate screen content.") }}</small>
            </b-form-checkbox>
          </div>
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
          />
          <small class="text-muted">{{ $t("Select a screen from the process to review and perform translations.") }}</small>
        </div>
        <div v-if="stringsWithTranslations && Object.keys(stringsWithTranslations).length !== 0">
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

          <table v-if="stringsWithTranslations && Object.keys(stringsWithTranslations).length !== 0" class="table table-responsive-lg mb-0">
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
  props: ["processId", "processName", "translatedLanguages", "editTranslation"],
  data() {
    return {
      showModal: false,
      disabled: false,
      aiLoading: false,
      availableLanguages: [],
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
        {'content': 'Cancel', 'action': 'hide()', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
        {'content': 'Translate Process', 'action': 'translateProcess', 'variant': 'secondary', 'disabled': true, 'hidden': false},
        {'content': 'Save Translation', 'action': 'saveTranslations', 'variant': 'secondary', 'disabled': false, 'hidden': true},
      ],
    };
  },

  watch: {
    // screensTranslations(val) {
    //   this.allScreensTranslations = [];

    //   // For each screen add to the general array in the format [{screen_id: screen_id, translations: translationsArr}]
    //   val.forEach((screenTranslations) => {
    //     const translations = [];
    //     console.log(screenTranslations);
    //     console.log("screenTranslations.translations");
    //     console.log(screenTranslations.translations);
    //     if (screenTranslations.translations) {
    //       console.log(screenTranslations.translations);
    //       Object.keys(screenTranslations.translations).forEach((key) => {
    //       // screenTranslations.translations.forEach((language) => {
    //         const language = screenTranslations.translations[key];
    //         console.log(key, language);
    //         // translations[screenTranslations.id][language.language] = language.strings[0];
    //         translations.push({ screenId: screenTranslations.id, language: language.language, translations: language.strings[0] });
    //       });
    //     }
    //     console.log("translations");
    //     console.log(translations);
    //     this.$set(this.allScreensTranslations, screenTranslations.id, translations);
    //   });
    //   console.log("this.allScreensTranslations");
    //   console.log(this.allScreensTranslations);
    // },

    selectedScreen(val) {
      this.currentScreenTranslations = [];
      this.availableStrings = [];

      if (!val || !val.translations) {
        return;
      }

      this.availableStrings = val.availableStrings;

      if (this.selectedLanguage.language in val.translations) {
        const translations = val.translations[this.selectedLanguage.language];

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
        this.translateProcess();
      }
    },
  },
  mounted() {
    this.getAvailableLanguages();
  },
  methods: {
    validateLanguageSelected() {
      if (!this.selectedLanguage) {
        this.customModalButtons[1].disabled = true;
      }
      this.customModalButtons[1].disabled = false;
    },
    show() {
      this.$bvModal.show("createProcessTranslation");
    },
    onClose() {
      this.showSelectTargetLanguage();
      this.$emit("create-process-translation-closed");
    },
    translateProcess() {
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
        type: "screen",
      };

      ProcessMaker.apiClient.post("/openai/language-translation", params)
        .then((response) => {
          this.screensTranslations = response.data.screensTranslations;

          this.aiLoading = false;

          if (!this.manualTranslation) {
            this.showSelectTargetLanguage();
            this.$bvModal.hide("createProcessTranslation");
            this.$emit("translating-language");
          }

          this.showTranslations();
        })
        .catch(error => {
          const $errorMsg = this.$t("An error ocurred while calling OpenAI endpoint.");
          window.ProcessMaker.alert($errorMsg, "danger");
          this.endpointErrors = $errorMsg;
          this.aiLoading = false;
        });
    },

    onReTranslate(option) {
      this.aiLoading = true;

      // REMOVE FUNCTION!
      // REMOVE FUNCTION
      // REMOVE FUNCTION
      this.sleep(2000).then(() => {
        this.aiLoading = false;
      });
    },
    
    // REMOVE FUNCTION
    // REMOVE FUNCTION
    // REMOVE FUNCTION
    sleep(time) {
      // eslint-disable-next-line no-promise-executor-return
      return new Promise((resolve) => setTimeout(resolve, time));
    },

    showSelectTargetLanguage() {
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
      this.customModalButtons[2].hidden = false;
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
          this.loading = false;
        });
    },
    saveTranslations() {
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
