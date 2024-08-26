<template>
  <div class="form-group">
    <label>{{ $t(label) }}</label>
    <multiselect
      ref="screen-select"
      v-model="content"
      :aria-label="$t(label)"
      :class="{ 'is-invalid': error }"
      :internal-search="false"
      :loading="loading"
      :multiple="false"
      :options="screens"
      :placeholder="placeholder || $t('type here to search')"
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
      v-if="!content?.id"
      :screen-select-id="uniqId"
      :screen-type="params.type"
      label="screen"
      @asset="processAssetCreation"
    />
    <a
      v-if="content && content.id"
      :href="`/designer/screen-builder/${content.id}/edit`"
      target="_blank"
    >
      {{ $t("Open Screen") }}
      <i class="ml-1 fas fa-external-link-alt" />
    </a>
  </div>
</template>

<script>
import { uniqueId } from "lodash";
import ModelerAssetQuickCreate from "./ModelerAssetQuickCreate.vue";
import "@processmaker/vue-multiselect/dist/vue-multiselect.min.css";

export default {
  components: {
    ModelerAssetQuickCreate,
  },
  props: [
    "value",
    "label",
    "helper",
    "params",
    "required",
    "placeholder",
    "defaultKey",
  ],
  data() {
    return {
      content: "",
      loading: false,
      screens: [],
      error: null,
      localValue: this.value,
      uniqId: uniqueId("screen-select-"),
    };
  },
  watch: {
    content: {
      handler() {
        this.validate();
        let selected = "";
        if (this.content) {
          this.error = "";
          selected = this.content.id;
        }
        this.$emit("input", selected);
      },
    },
    value: {
      handler() {
        // Load selected item.
        if (this.value) {
          if (!(this.content && this.content.id === this.value)) {
            this.loadScreen(this.value);
          }
        } else {
          this.content = "";
        }
      },
    },
  },
  mounted() {
    this.validate();
    if (this.value) {
      this.loadScreen(this.value);
    }
    this.setDefault();
  },
  methods: {
    type() {
      if (this.params && this.params.type) {
        return this.params.type;
      }
      return "FORM";
    },
    interactive() {
      if (this.params && this.params.interactive) {
        return this.params.interactive;
      }
      return false;
    },
    /**
     * Loads the screen
     * @param {Object} value - Screen
     */
    loadScreen(value) {
      this.loading = true;
      ProcessMaker.apiClient
        .get(`screens/${value}`)
        .then(({ data }) => {
          this.loading = false;
          this.content = data;
        })
        .catch((error) => {
          this.loading = false;
          if (error.response.status === 404) {
            this.content = "";
            this.error = this.$t("Selected screen not found");
          }
        });
    },
    /**
     *
     * @param {Object=} filter - The filters to apply for the GET request
     * @returns {Promise<void>}
     */
    async load(filter) {
      const params = {
        type: this.type(),
        interactive: this.interactive(),
        order_direction: "asc",
        status: "active",
        selectList: true,
        filter: typeof filter === "string" ? filter : "",
        ...this.params,
      };
      this.loading = true;
      try {
        const { data } = await ProcessMaker.apiClient.get(
          "screens?exclude=config",
          { params },
        );
        this.loading = false;
        this.screens = data.data;
      } catch (err) {
        console.error("There was a problem getting the screens", err);
        this.loading = false;
      }
    },
    setDefault() {
      if (!this.defaultKey || this.value) {
        // No need to set a default
        return;
      }

      ProcessMaker.apiClient
        .get("screens", { params: { 
          key: this.defaultKey,
          order_by: "id",
          order_direction: "ASC"
        }})
        .then(({ data }) => {
          this.content = data.data[0];
        });
    },
    /**
     * @param {Object} data - The response we get from the emitter
     * @param {string} data.asset - the screen
     * @param {string} data.assetType - The Asset type, ex: screen
     * @param {string} data.screenSelectId - Identifier for the screen select component that started the call
     */
    processAssetCreation({ asset, assetType, screenSelectId }) {
      if (assetType === "screen" && this.uniqId === screenSelectId) {
        this.loadScreen(asset.id);
      }
    },
    validate() {
      if (!this.required || (this.value && this.value !== undefined)) {
        return;
      }

      this.error = this.$t("A screen selection is required");
    },
  },
};
</script>
