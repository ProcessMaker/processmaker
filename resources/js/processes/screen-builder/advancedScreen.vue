<template>
  <div class="h-100">
    <b-card no-body class="h-100 bg-white border-top-0" id="app">
      <!-- Card Header -->
      <b-card-header>
        <b-row>
          <b-col>
            <b-button-group size="sm">
              <b-button
                :variant="mode == 'editor' ? 'secondary' : 'outline-secondary'"
                @click="mode = 'editor'"
                class="text-capitalize"
              >
                <i class="fas fa-drafting-compass pr-1"></i>
                {{ $t('Design') }}
              </b-button>
              <b-button
                :variant="mode == 'preview' ? 'secondary' : 'outline-secondary'"
                @click="mode = 'preview'"
                class="text-capitalize"
              >
                <i class="fas fa-cogs pr-1"></i>
                {{ $t('Preview') }}
              </b-button>
            </b-button-group>
          </b-col>

          <b-col class="text-right" v-if="displayBuilder && !displayPreview">
            <button @click="saveScreen(false)" type="button" class="btn btn-secondary btn-sm ml-1">
              <i class="fas fa-save"></i>
            </button>
          </b-col>
        </b-row>
      </b-card-header>

      <!-- Card Body -->
      <b-card-body class="overflow-auto p-0 h-100">
        <monaco-editor
          v-show="mode == 'editor'"
          :options="monacoOptions"
          v-model="config.html"
          language="html"
          class="editor"
        ></monaco-editor>
        <advanced-screen-frame v-show="mode == 'preview'" :config="config"></advanced-screen-frame>
      </b-card-body>
    </b-card>
  </div>
</template>

<script>
import MonacoEditor from "vue-monaco";
import AdvancedScreenFrame from "./advancedScreenFrame";

export default {
  props: ["process", "screen", "permission"],
  data() {
    return {
      monacoOptions: {
        automaticLayout: true
      },
      config: { html: "" },
      mode: "editor",
      showPopup: false
    };
  },
  components: {
    MonacoEditor,
    AdvancedScreenFrame
  },
  watch: {
    screen: {
      deep: true,
      immediate: true,
      handler(screen) {
        screen.config ? (this.config = screen.config) : null;
      }
    },
    config: {
      deep: true,
      immediate: true,
      handler(config) {
        if (JSON.stringify(screen.config) != JSON.stringify(this.config)) {
          this.screen.config = this.config;
        }
      }
    }
  },
  computed: {
    displayBuilder() {
      return this.mode == 'editor';
    },
    displayPreview() {
      return this.mode == 'preview';
    }
  },
  methods: {
    saveScreen() {
      ProcessMaker.apiClient
        .put("screens/" + this.screen.id, {
          title: this.screen.title,
          description: this.screen.description,
          type: this.screen.type,
          config: this.config,
          computed: [],
          custom_css: ""
        })
        .then(response => {
          ProcessMaker.alert(this.$t("Successfully saved"), "success");
          ProcessMaker.EventBus.$emit("save-changes");
        });
    }
  }
};
</script>

<style lang="scss" scoped>
.editor {
  min-height: 200px;
  height: 100%;
}
</style>
