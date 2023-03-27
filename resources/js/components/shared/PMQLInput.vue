<template>
  <div>
    <div class="">
      <div v-if="condensed">
        <label>{{inputLabel ? inputLabel : "PMQL"}}</label>
        <mustache-helper/>
        <i v-if="aiLoading" class="fa fa-spinner fa-spin pmql-icons" :style="styles?.icons"></i>
      </div>

      <div class="d-flex align-items-start">
        <div class="search-bar-buttons d-flex ml-md-0 flex-column flex-md-row">
          <slot name="left-buttons"></slot>
          <!-- <button v-if="showFilter" class="btn btn-outline-secondary mr-1 d-flex align-items-center">
            <i class="fa fa-sliders-h mr-1"></i>
            <span class="text-capitalize">Filter</span>
          </button> -->
          <div class="position-relative">
            <button class="btn btn-outline-secondary mr-1 d-flex align-items-center">
              <i class="fa fa-sliders-h mr-1"></i>
              <span class="text-capitalize">Filter</span>
            </button>
            <div class="filter-dropdown-panel-container card">
              <slot name="filters"></slot>
            </div>
          </div>
        </div>
        <div class="search-bar flex-grow w-100"
          :class="{'is-invalid': validations}"
          :style="styles?.container">

          <div class="search-bar-container d-flex align-items-center">
            <i v-if="!aiLoading && !condensed" class="fa fa-search ml-3 pmql-icons" :style="styles?.icons"></i>
            <i v-if="aiLoading && !condensed" class="fa fa-spinner fa-spin ml-3 pmql-icons" :style="styles?.icons"></i> 

            <textarea ref="search_input" type="text" class="pmql-input"
              :class="{'overflow-auto': showScrollbars}"
              :aria-label="inputAriaLabel"
              :placeholder="placeholder"
              :id="inputId"
              v-model="query"
              rows="1"
              :style="styles?.input"
              @input="onInput()"
              @keydown.enter.prevent @keyup.enter="runSearch()"></textarea>

            <div v-if="showPmqlSection && !condensed" class="separator align-items-center" :style="styles?.separators"></div>
            <code v-if="showPmqlSection && !condensed" class="w-100 d-block input-right-section" :style="styles?.pmql">{{ pmql }}</code>

            <div v-if="showAiIndicator && !condensed" class="separator align-items-center" :style="styles?.separators"></div>
            <span v-if="showAiIndicator && !condensed" class="badge badge-pill badge-success">AI</span>

            <div v-if="showUsage && !condensed" class="separator align-items-center" :style="styles?.separators"></div>
            <label 
              v-if="showUsage && !condensed" 
              v-b-tooltip.hover
              class="badge badge-primary badge-pill usage-label" 
              :title="usageText">
              {{ usage.totalTokens }} tokens
              <i class="fa fa-info-circle ml-1 pmql-icons" />
            </label>

            <div v-if="!condensed" class="separator align-items-center" :style="styles?.separators" />
            <i v-if="!condensed" class="fa fa-times pl-1 pr-3 pmql-icons" role="button" @click="clearQuery" :style="styles?.icons"/>
          </div>

          <div v-if="showPmqlSection && condensed">
            <div class="separator-horizontal align-items-center"></div>
          </div>
          <code v-if="showPmqlSection && condensed" class="w-100 d-block input-right-section mb-1 mt-1 pr-2 pl-2" :style="styles?.pmql">
            {{ pmql }}
          </code>
        </div>
        <div class="search-bar-buttons d-flex ml-md-0 flex-column flex-md-row">
          <slot name="right-buttons"></slot>
        </div>
      </div>
    </div>
  </div>
</template>
<script>

import isPMQL from "../../modules/isPMQL";
import MustacheHelper from '@processmaker/screen-builder/src/components/inspector/mustache-helper'

export default {
  components: { MustacheHelper },
  props: [
    "searchType",
    "value",
    "aiEnabled",
    "ariaLabel",
    "inputId",
    "validations",
    "styles",
    "condensed",
    "placeholder",
    "collectionId",
    "inputLabel",
  ],
  data() {
    return {
      aiLoading: false,
      inputAriaLabel: "",
      showUsage: false,
      showAiIndicator: false,
      showFilter: false,
      showScrollbars: false,
      pmql: "",
      query: "",
      textAreaLines: 4,
      usage: {
        completionTokens: 0,
        promptTokens: 0,
        totalTokens: 0,
      },
    };
  },

  computed: {
    showPmqlSection() {
      return this.pmql && this.pmql.isPMQL() && !this.query.isPMQL();
    },
    usageText() {
      const promptTokens = `Prompt tokens: ${this.usage.promptTokens}`;
      const completionTokens = ` - Completion tokens: ${this.usage.completionTokens}`;
      const totalTokens = ` - Total: ${this.usage.totalTokens} tokens`;
      return promptTokens + completionTokens + totalTokens;
    },
    aiEnabledLocal() {
      if (!this.searchType || this.searchType === "") {
        return false;
      }

      return this.aiEnabled;
    },
  },

  watch: {
    query() {
      this.calcInputHeight();
    },
    value() {
      if (!this.query || this.query === "") {
        this.query = this.value;
      }
    },
  },

  mounted() {
    this.query = this.value;
    this.inputAriaLabel = this.ariaLabel;
    // Vue.nextTick().then(() => {
    //   this.$refs.search_input.focus();
    // });
  },

  methods: {
    calcInputHeight() {
      this.$refs.search_input.style.height = "auto";
      // Font size * line height in rems (1.5)
      const fontSize = parseFloat(getComputedStyle(this.$refs.search_input).fontSize);
      const lineHeight = fontSize * 1.5;

      // Padding top and bottom (0.4rem each)
      const padding = fontSize * 0.4 * 2;
      const currentHeight = padding + (this.textAreaLines * lineHeight);

      this.showScrollbars = false;
      this.$nextTick(() => {
        if (currentHeight <= this.$refs.search_input.scrollHeight) {
          this.showScrollbars = true;
          this.$refs.search_input.style.height = `${currentHeight}px`;
          this.$emit("inputresize", currentHeight);
        } else {
          this.$refs.search_input.style.height = `${this.$refs.search_input.scrollHeight}px`;
          this.$emit("inputresize", this.$refs.search_input.scrollHeight);
        }
      });
    },
    onInput() {
      this.$emit("pmqlchange", this.query);
    },
    runSearch() {
      this.pmql = "";
      if (this.query === "") {
        this.$emit("submit", "");
        return;
      }

      if (this.query.isPMQL()) {
        this.$emit("submit", this.query);
      } else if (this.aiEnabledLocal) {
        this.runNLQToPMQL();
      }
      this.calcInputHeight();
    },
    clearQuery() {
      this.query = "";
      this.runSearch();
    },
    runNLQToPMQL() {
      const params = {
        question: this.query,
        type: this.searchType,
      };

      this.aiLoading = true;

      ProcessMaker.apiClient.post("/openai/nlq-to-pmql", params).then((response) => {
        this.pmql = response.data.result;
        this.usage = response.data.usage;
        this.$emit("submit", this.pmql);
        this.aiLoading = false;
        this.calcInputHeight();
      });
    },
  },
};
</script>

<style lang="scss" scoped>

.search-bar {
  border: 1px solid rgba(0, 0, 0, 0.125);
  border-radius: 3px;
  background: #ffffff;
}

.search-bar.is-invalid {
  border-color: #E50130;
}

.pmql-input {
  background: none;
  color: gray;
  padding: 0.4rem 0.75rem;
  display: block;
  width: 100%;
  border: none;
  padding-left: 0.75rem;
  overflow: hidden;
  resize: none;
  min-height: calc(1.5em + 0.75rem);
}

.pmql-input:focus {
  outline: none;
}

input.pmql-input:focus ~ label, input.pmql-input:valid ~ label {
    top: 3px;
    font-size: 12px;
    color: #0872C2;
}

.input-right-section {
  color: #0872C2;
}

.pmql-icons {
  color: #6C757D;
}

.usage-label {
    background: #1c72c224;
    color: #0872C2;
    right: 29px;
    top: 0;
    margin-right: 0.5rem;
    font-weight: 300;
    margin-bottom: 0;
}

.separator {
    border-right: 1px solid rgb(227, 231, 236);
    height: 1.6rem;
    margin-left: 0.5rem;
    margin-right: 0.5rem;
    right: 0;
    top: 15%;
}

.separator-horizontal {
  border: 0;
  border-bottom: 1px dashed rgb(227, 231, 236);
  height: 0;
  margin: 5px 7px 0 8px;
}

.badge-success {
    color: #00875A;
    background-color: #00875a26;
}

.filter-dropdown-panel-container {
  min-width: 20rem;
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.125);
  box-shadow: 0 6px 12px 2px rgba(0, 0, 0, 0.168627451);
  position: absolute;
  left: 0;
  top: 2.5rem;
  border-radius: 3px;
  z-index: 1;
  max-width: 30rem;
}
</style>
