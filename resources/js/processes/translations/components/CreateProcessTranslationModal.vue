<template>
  <div>
    <modal
      id="createProcessTranslation"
      :title="$t('Add Process Translation')"
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
      <div v-if="!aiLoading">
        <div>
          {{ $t('By default, all process screens will be auto translated via our AI tooling.') }}
        </div>
        <div class="mt-3">
          <label class="mb-0">Select a target language</label>
          <select-language
            v-model="selectedLanguage"
            :multiple="false"
            :aria-label="$t('Select a target language')"
            class="w-50"
          />
          <small class="text-muted">{{ $t("Language the translator will translate to.") }}</small>
        </div>
        <div class="mt-3">
          <div class="form-group">
            <b-form-checkbox value="manual_translation">
              <div>{{ $t("Manual translation") }}</div>
              <small class="text-muted">{{ $t("Disables auto translate and manually translate screen content.") }}</small>
            </b-form-checkbox>
          </div>
        </div>
      </div>

      <!-- Processing translations section -->
      <div>
        <div v-if="aiLoading" class="d-flex justify-content-center align-items-center flex-column my-3">
          <span class="power-loader mt-3 mb-2" />
          <span class="ml-2 text-muted small">
            {{ $t("Translation in progress ...") }}
          </span>
        </div>
      </div>

      <!-- Show translations section -->

    </modal>
  </div>
</template>

<script>
import { FormErrorsMixin, Modal } from "SharedComponents";
import SelectLanguage from "../../../components/SelectLanguage"

export default {
  components: { Modal, SelectLanguage },
  mixins: [FormErrorsMixin],
  props: ["processId"],
  data() {
    return {
      showModal: false,
      disabled: false,
      aiLoading: false,
      availableLanguages: [],
      selectedLanguage: null,
      customModalButtons: [
        {'content': 'Cancel', 'action': 'hide()', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
        {'content': 'Translate Process', 'action': 'translateProcess', 'variant': 'secondary', 'disabled': false, 'hidden': false},
      ],
    };
  },
  methods: {
    show() {
      this.$bvModal.show("createProcessTranslation");
    },
    onClose() {

    },
    translateProcess() {
      this.aiLoading = true;
      this.endpointErrors = false;

      console.log("this.processId");
      console.log(this.processId);
      const params = {
        language: this.selectedLanguage,
        processId: this.processId,
        type: "screen",
      };

      ProcessMaker.apiClient.post("/openai/language-translation", params)
        .then((response) => {
          console.log(response.data);
          // this.translations = response.data.result.translations;
          // this.usage = response.data.usage;
          // this.$emit("submit", this.pmql);
          // this.aiLoading = false;
        })
        .catch(error => {
          const $errorMsg = this.$t("An error ocurred while calling OpenAI endpoint.");
          window.ProcessMaker.alert($errorMsg, "danger");
          this.endpointErrors = $errorMsg;
          this.aiLoading = false;
        });
    },
  },
};
</script>

<style scoped>
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
