<template>
  <div class="form-group">
    <label>{{ $t(label) }}</label>
    <multiselect
      v-model="content"
      :aria-label="$t(label)"
      :class="{ 'is-invalid': error }"
      :internal-search="false"
      :loading="loading"
      :multiple="false"
      :options="scripts"
      :placeholder="$t('type here to search')"
      :required="required"
      :searchable="true"
      :show-labels="false"
      label="title"
      track-by="id"
      @open="load()"
      @search-change="load"
    >
      <template slot="noResult">
        {{ $t("No elements found. Consider changing the search query.") }}
      </template>
      <template slot="noOptions">
        {{ $t("No Data Available") }}
      </template>
    </multiselect>
    <div
      v-if="error"
      class="invalid-feedback"
      role="alert"
    >
      <div>{{ error }}</div>
    </div>
    <small
      v-if="helper"
      class="form-text text-muted"
    >{{ $t(helper) }}</small>
    <modeler-asset-quick-create
      v-if="!content.id"
      label="script"
      @asset="processAssetCreation"
    />
    <a
      v-if="content.id"
      :href="`/designer/scripts/${content.id}/builder`"
      target="_blank"
    >
      {{ $t("Open Script") }}
      <i class="ml-1 fas fa-external-link-alt" />
    </a>
  </div>
</template>

<script>
import ModelerAssetQuickCreate from "./ModelerAssetQuickCreate.vue";
import "@processmaker/vue-multiselect/dist/vue-multiselect.min.css";

export default {
  components: {
    ModelerAssetQuickCreate,
  },
  props: ["value", "label", "helper", "params", "required"],
  data() {
    return {
      content: "",
      loading: false,
      scripts: [],
      error: "",
    };
  },
  computed: {
    node() {
      return this.$root.$children[0].$refs.modeler.highlightedNode;
    },
    definition() {
      return this.node.definition;
    },
  },
  watch: {
    content: {
      handler() {
        this.validate();
        if (this.content && this.content.id !== this.value) {
          this.error = "";
          this.$emit("input", this.content.id);
        }
      },
    },
    value: {
      immediate: true,
      handler() {
        this.validate();
        // Load selected item.
        if (this.value) {
          this.loading = true;
          ProcessMaker.apiClient
            .get(`scripts/${this.value}`)
            .then((response) => {
              this.loading = false;
              this.content = response.data;
              this.error_handling = {
                timeout: this.content.timeout,
                retry_wait_time: this.content.retry_wait_time,
                retry_attempts: this.content.retry_attempts,
              };
              this.$root.$emit("contentChanged", this.error_handling);
            })
            .catch((error) => {
              this.loading = false;
              if (error.response.status === 404) {
                this.content = "";
                this.error = this.$t("Selected script not found");
              }
            });
        } else {
          this.content = "";
        }
      },
    },
  },
  mounted() {
    if (this.node) {
      this.checkScriptRefExists();
    }

    this.validate();
  },
  methods: {
    /**
     *
     * @param {Object=} filter - The filters to apply for the GET request
     * @returns {Promise<void>}
     */
    async load(filter) {
      const params = {
        order_direction: "asc",
        selectList: true,
        filter: typeof filter === "string" ? filter : "",
      };
      this.loading = true;
      try {
        const { data } = await ProcessMaker.apiClient.get("scripts", {
          params,
        });
        this.loading = false;
        this.scripts = data.data;
      } catch (err) {
        console.error("There was an error loading the scripts", err);
        this.loading = false;
      }
    },
    checkScriptRefExists() {
      if (this.definition.scriptRef) {
        return;
      }
      this.$set(this.definition, "scriptRef", "");
    },
    validate() {
      if (!this.required || this.value) {
        return;
      }

      this.error = this.$t("A script selection is required");
    },
    /**
     * @param {Object} data - The response we get from the emitter
     * @param {string} data.asset - the screen
     * @param {string} data.assetType - The Asset type, ex: screen
     */
    processAssetCreation({ asset, assetType }) {
      if (assetType === "script") {
        this.content = asset;
      }
    },
  },
};
</script>
