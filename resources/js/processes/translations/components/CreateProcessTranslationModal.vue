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
      :customButtons="customModalButtons"
    >
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
        >
        </select-language>
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
    </modal>
  </div>
</template>

<script>
import { FormErrorsMixin, Modal } from "SharedComponents";
import SelectLanguage from "../../../components/SelectLanguage"

export default {
  components: { Modal, SelectLanguage },
  mixins: [FormErrorsMixin],
  props: [],
  data() {
    return {
      showModal: false,
      disabled: false,
      availableLanguages: [],
      selectedLanguage: null,
      customModalButtons: [
        {
          content: "Cancel", action: "hide()", variant: "outline-secondary", disabled: false, hidden: false,
        },
        {
          content: "Translate process", action: "translateProcess()", variant: "secondary", disabled: false, hidden: false,
        },
      ],
      manager: "",
    };
  },
  methods: {
    show() {
      this.$bvModal.show("createProcessTranslation");
    },
    onClose() {

    },
    translateProcess() {
      console.log("translate process");
    },
  },
};
</script>

<style scoped>
</style>
