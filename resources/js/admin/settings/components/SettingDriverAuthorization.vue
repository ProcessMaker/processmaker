<template>
  <div>
    <div v-if="hasAuthorizedBadge">
      <b-badge
        pill
        :variant="isAuthorized ? 'success' : 'warning'"
      >
        <span v-if="isAuthorized">{{ $t("Authorized") }}</span>
        <span v-else>{{ $t("Not Authorized") }}</span>
      </b-badge>
    </div>
    <div v-else>
      {{ $t("Empty") }}
    </div>
    <b-modal
      v-model="showModal"
      class="setting-object-modal"
      size="lg"
      centered
      @hidden="onModalHidden"
    >
      <template
        #modal-header
        class="d-block"
      >
        <div>
          <h5 class="mb-0">
            <span v-if="setting.name">{{ $t(setting.name) }}</span>
            <span v-else>{{ $t(setting.key) }}</span>
          </h5>
          <small class="form-text text-muted">
            {{ $t("Configure the driver connection properties.") }}
          </small>
        </div>
        <button
          type="button"
          class="close"
          :aria-label="$t('Close')"
          @click="onCancel"
        >
          &times;
        </button>
      </template>
      <div>
        <component
          :is="authSchemeToComponent(setting.config.AuthScheme)"
          :form-data="formData"
          @updateFormData="updateFormData"
        />

        <additional-driver-connection-properties
          :driver-key="setting?.key"
          :form-data="formData"
          @updateFormData="updateFormData"
        />
      </div>
      <div
        slot="modal-footer"
        class="w-100 m-0 d-flex"
      >
        <button
          type="button"
          class="btn btn-outline-secondary ml-auto"
          data-cy="cancel-button"
          @click="onCancel"
        >
          {{ $t("Cancel") }}
        </button>
        <button
          type="button"
          class="btn btn-secondary ml-3"
          data-cy="authorize-button"
          :disabled="isButtonDisabled"
          @click="onSave"
        >
          {{ $t("Authorize") }}
        </button>
      </div>
    </b-modal>

    <b-modal
      v-model="showAuthorizingModal"
      class="setting-object-modal"
      size="md"
      hide-footer
      hide-header
      centered
      no-fade
    >
      <div class="text-center">
        <h3>{{ $t("Connecting Driver") }}</h3>
        <i class="fas fa-circle-notch fa-spin fa-3x p-0 text-primary" />
      </div>
    </b-modal>
  </div>
</template>

<script>
// eslint-disable-next-line import/no-unresolved
import { FormErrorsMixin, Required } from "SharedComponents";
import settingMixin from "../mixins/setting";
import AdditionalDriverConnectionProperties from "./AdditionalDriverConnectionProperties.vue";
import OauthConnectionProperties from "./cdata/OauthConnectionProperties.vue";
import NoneConnectionProperties from "./cdata/NoneConnectionProperties.vue";

export default {
  components: {
    AdditionalDriverConnectionProperties,
    OauthConnectionProperties,
    NoneConnectionProperties,
  },
  mixins: [settingMixin, FormErrorsMixin, Required],
  props: {
    setting: {
      type: [Object, null],
      default: null,
    },
    value: {
      type: Object,
      default: null,
    },
  },
  data() {
    return {
      input: "",
      formData: {},
      selected: null,
      showModal: false,
      showAuthorizingModal: false,
      transformed: null,
      errors: {},
      isInvalid: true,
      resetData: true,
      componentsMap: {
        OAuth: "oauth-connection-properties",
        None: "none-connection-properties",
      },
    };
  },
  computed: {
    hasAuthorizedBadge() {
      if (!this.setting) {
        return false;
      }
      const hasAuthorizedBadge = !!_.has(this.setting, "ui.authorizedBadge");
      return hasAuthorizedBadge;
    },
    isAuthorized() {
      if (this.hasAuthorizedBadge) {
        return Boolean(this.setting.ui.authorizedBadge);
      }
      return false;
    },
    changed() {
      return JSON.stringify(this.formData) !== JSON.stringify(this.transformed);
    },
    isButtonDisabled() {
      return this.isInvalid || (this.isAuthorized && !this.changed);
    },
  },
  watch: {
    formData: {
      handler() {
        this.isInvalid = this.validateData();
      },
      deep: true,
    },
  },
  mounted() {
    if (this.value === null) {
      this.resetFormData();
    } else {
      this.formData = this.value;
    }
    this.isInvalid = this.validateData();
    this.transformed = this.copy(this.formData);
  },
  methods: {
    authSchemeToComponent(scheme) {
      return this.componentsMap[scheme] || null;
    },
    validateData() {
      // Check if client_id and client_secret are empty
      const clientIdEmpty = _.isEmpty(this.formData.client_id);
      const clientSecretEmpty = _.isEmpty(this.formData.client_secret);

      return _.isEmpty(this.formData) || clientIdEmpty || clientSecretEmpty;
    },
    onCancel() {
      this.showModal = false;
    },
    onEdit(row) {
      if (this.value !== null) {
        this.formData = this.value;
      }
      this.generateCallbackUrl(row.item);
      this.$nextTick(() => {
        this.showModal = true;
      });
    },
    onModalHidden() {
      this.resetFormData();
    },
    authorizeConnection() {
      this.showAuthorizingModal = true;
      this.showModal = false;
      this.resetData = false;
      ProcessMaker.apiClient
        .post(`settings/${this.setting.id}/get-oauth-url`, this.formData)
        .then((response) => {
          window.location = response.data?.url;
        })
        .catch((error) => {
          const errorMessage = error.response?.data?.message || error.message;
          ProcessMaker.alert(errorMessage, "danger");
          this.showModal = true;
          this.showAuthorizingModal = false;
        });
    },
    onSave() {
      const driver = this.setting.key.split("cdata.")[1];

      this.formData.driver = driver;
      this.transformed = { ...this.formData };
      this.authorizeConnection();
    },
    generateCallbackUrl(data) {
      const name = data.key.split("cdata.")[1];
      const appUrl = document.head.querySelector("meta[name=\"app-url\"]").content;

      this.formData.callback_url = `${appUrl}/external-integrations/${name}`;
    },
    resetFormData() {
      if (this.resetData) {
        this.formData = {
          client_id: "",
          client_secret: "",
          callback_url: "",
        };
      }
    },
    updateFormData(val) {
      this.formData = { ...this.formData, ...val };
    },
  },
};
</script>
