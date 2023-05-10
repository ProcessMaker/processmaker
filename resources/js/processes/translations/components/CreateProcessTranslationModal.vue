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
            :options="notTranslatedAvailableLanguages"
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
        <div class="mt-3">
          <table class="table table-responsive-lg mb-0">
            <thead>
              <tr>
                  <th class="col-6">{{ $t('String') }}</th>
                  <th class="">{{ selectedLanguage.humanLanguage + ' ' + $t('Translation') }}</th>
              </tr>
            </thead>
            <tbody>
              <!-- <tr v-for="item, index in currentScreenTranslations" :key="index"> -->
              <tr v-for="item, index in currentScreenTranslations_SAMPLE_DATA" :key="index">
                <td class="bg-light">{{ item.key }}</td>
                <td class="py-1 px-2">
                  <b-form-textarea
                    v-model="item.value"
                    class="form-control border-0"
                    :aria-label="$t('Add a translation for ') + item.value"
                    @focus="$event.target.select()"
                    autocomplete="off"
                  ></b-form-textarea>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </modal>
  </div>
</template>

<script>
import { FormErrorsMixin, Modal } from "SharedComponents";
import SelectLanguage from "../../../components/SelectLanguage"
import SelectScreen from "../../../components/SelectScreen"

export default {
  components: { Modal, SelectLanguage, SelectScreen },
  mixins: [FormErrorsMixin],
  props: ["processId", "processName", "translatedLanguages"],
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
      allScreensTranslations: [],
      manualTranslation: false,
      modalTitle: this.$t("Add Process Translation"),
      step: "selectTargetLanguage",
      customModalButtons: [
        {'content': 'Cancel', 'action': 'hide()', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
        {'content': 'Translate Process', 'action': 'translateProcess', 'variant': 'secondary', 'disabled': true, 'hidden': false},
        {'content': 'Save Translation', 'action': 'saveTranslations', 'variant': 'secondary', 'disabled': false, 'hidden': true},
      ],

      currentScreenTranslations_SAMPLE_DATA: [
        { key: "First Name", value: "" },
        { key: "Last Name", value: "" },
        { key: "Email", value: "Correo electrónico" },
        { key: "Complete for client", value: "" },
        { key: "Client primary contact", value: "Contacto principal" },
        { key: "Additional notes to customer", value: "" },
        { key: "Send Blank Application to Client", value: "" },
        { key: "How would you like to complete the application?", value: "" },
        { key: "Here you can optionally add information you want to share with the customer.", value: "" },
      ],
    };
  },
  computed: {
    notTranslatedAvailableLanguages() {
      let { translatedLanguages } = this;
      translatedLanguages = translatedLanguages.map((item) => ({
        humanLanguage: item.human_language,
        language: item.language,
      }));

      return this.availableLanguages.filter((f) => translatedLanguages.every((e) => f.language !== e.language));
    },
  },
  watch: {
    screensTranslations(val) {
      this.allScreensTranslations = [];

      // For each screen add to the general array in the format [{screen_id: screen_id, translations: translationsArr}]
      val.forEach((screenTranslations) => {
        const translations = [];
        if (screenTranslations.translations) {
          screenTranslations.translations.forEach((language) => {
            console.log(language);
            // translations[screenTranslations.id][language.language] = language.strings[0];
            translations.push({ screenId: screenTranslations.id, language: language.language, translations: language.strings[0] });
          });
        }
        console.log("translations");
        console.log(translations);
        this.$set(this.allScreensTranslations, screenTranslations.id, translations);
      });
      console.log("this.allScreensTranslations");
      console.log(this.allScreensTranslations);
    },

    selectedScreen(val) {
      this.currentScreenTranslations = [];

      if (this.selectedLanguage.language in this.allScreensTranslations[val.id]) {
        const translations = this.allScreensTranslations[val.id][this.selectedLanguage.language];

        Object.entries(translations).forEach(([key, value]) => {
          this.currentScreenTranslations.push({ key, value });
        });
      }
    },

    selectedLanguage() {
      this.validateLanguageSelected();
    },

    // currentScreenTranslations(val) {
    //   this.fillScreensTranslationsArr(val);
    // },
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
      this.step = "selectTargetLanguage";
      this.customModalButtons[1].hidden = false;
      this.customModalButtons[2].hidden = true;
    },
    translateProcess() {
      this.step = "translating";
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
          console.log(response.data);
          this.screensTranslations = response.data.screensTranslations;
          // this.translations = response.data.result.translations;
          // this.usage = response.data.usage;
          // this.$emit("submit", this.pmql);
          this.aiLoading = false;
          this.showTranslations();
        })
        .catch(error => {
          const $errorMsg = this.$t("An error ocurred while calling OpenAI endpoint.");
          window.ProcessMaker.alert($errorMsg, "danger");
          this.endpointErrors = $errorMsg;
          this.aiLoading = false;
        });
    },
    saveTranslations() {
    },
    showTranslations() {
      this.step = "showTranslations";
      this.customModalButtons[1].hidden = true;
      this.customModalButtons[2].hidden = false;
      this.modalTitle = this.$t(`${this.processName} ${this.selectedLanguage.humanLanguage} Translation`);
    },
    getAvailableLanguages() {
      this.loading = true;

      // Load from our api client
      ProcessMaker.apiClient
        .get("process/translations/languages")
        .then((response) => {
          this.availableLanguages = JSON.parse(JSON.stringify(response.data.availableLanguages));
          this.loading = false;
        });
    },
  },
};
</script>

<style scoped>
table {
  border: 1px solid #e9edf1;
}
tbody {
  display: block;
  height: 50vh;
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
