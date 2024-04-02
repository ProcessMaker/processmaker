<template>
  <div>
    <!-- Authorize badge -->
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

    <!-- Connection properties modal -->
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
          :is="authSchemeToComponent(setting.config?.AuthScheme)"
          :form-data="formData"
          :auth-scheme="setting.config?.AuthScheme"
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
          @click="authorizeConnection()"
        >
          {{ $t("Authorize") }}
        </button>
      </div>
    </b-modal>

    <!-- Authorizing modal -->
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
import PasswordConnectionProperties from "./cdata/PasswordConnectionProperties.vue";

export default {
  components: {
    AdditionalDriverConnectionProperties,
    OauthConnectionProperties,
    NoneConnectionProperties,
    PasswordConnectionProperties,
  },
  mixins: [settingMixin, FormErrorsMixin, Required],
  props: {
    setting: {
      type: [Object, null],
      default: () => ({}),
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
      errors: {},
      resetData: true,
      componentsMap: {
        OAuth: "oauth-connection-properties",
        None: "none-connection-properties",
        Password: "password-connection-properties",
        Basic: "password-connection-properties",
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
  },
  mounted() {
    this.formData = this.value;
    if (this.value === null) {
      this.resetFormData();
    }
  },
  methods: {
    authSchemeToComponent(scheme) {
      return this.componentsMap[scheme] || null;
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
    generateCallbackUrl(item) {
      if (item.config.AuthScheme === "OAuth") {
        const name = item.key.split("cdata.")[1];
        const appUrl = document.head.querySelector("meta[name=\"app-url\"]").content;

        this.formData.callback_url = `${appUrl}/external-integrations/${name}`;
      }
    },
    authorizeConnection() {
      this.showAuthorizingModal = true;
      this.showModal = false;
      this.resetData = false;

      if (this.setting.config.AuthScheme === "OAuth") {
        this.authorizeOAuthConnection();
      } else {
        this.authorizeNoneConnection();
      }
    },
    authorizeOAuthConnection() {
      ProcessMaker.apiClient
        .post(`settings/${this.setting.id}/get-oauth-url`, this.formData)
        .then((response) => {
          window.location = response.data?.url;
        })
        .catch((error) => {
          const errorMessage = error.response?.data?.message || error.message;
          ProcessMaker.alert(errorMessage, "danger");
        })
        .finally(() => {
          this.showModal = true;
          this.showAuthorizingModal = false;
        });
    },
    authorizeNoneConnection() {
      ProcessMaker.apiClient
        .post(`settings/${this.setting.id}/authorize-driver`, this.formData)
        .then((response) => {
          window.location = response.data.url;
          this.showModal = false;
          this.showAuthorizingModal = true;
        })
        .catch((error) => {
          const errorMessage = error.response?.data?.message || error.message;
          ProcessMaker.alert(errorMessage, "danger");
          this.showModal = true;
          this.showAuthorizingModal = false;
        });
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
    updateFormData(values) {
      this.formData = { ...this.formData, ...values };
    },
  },
};
</script>
