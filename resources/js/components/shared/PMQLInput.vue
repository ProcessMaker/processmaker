<template>
  <div>
    <div class="">
      <div class="d-flex align-items-start">
        <div class="search-bar-buttons d-flex ml-md-0 flex-column flex-md-row">
          <slot name="left-buttons"></slot>
          <button v-if="showFilter" class="btn btn-outline-secondary mr-1 d-flex align-items-center">
            <i class="fa fa-sliders-h mr-1"></i>
            <span class="text-capitalize">Filter</span>
          </button>
        </div>
        <div class="search-bar flex-grow w-100" 
          :class="{'is-invalid': validations}"
          :style="styles?.container">
          <div class="search-bar-container d-flex align-items-center">
            <i v-if="!aiLoading" class="fa fa-search ml-3 pmql-icons" :style="styles?.icons"></i>
            <i v-if="aiLoading" class="fa fa-spinner fa-spin ml-3 pmql-icons" :style="styles?.icons"></i> 

            <textarea ref="search_input" type="text" class="pmql-input"
              :aria-label="inputAriaLabel"
              :placeholder="placeholder"
              :id="id"
              v-model="query"
              rows="1"
              :style="styles?.input"
              @input="onInput()"
              @keydown.enter.prevent @keyup.enter="runSearch()"></textarea>

            <div v-if="showPmqlSection" class="separator align-items-center" :style="styles?.separators"></div>
            <code v-if="showPmqlSection" class="w-100 d-block input-right-section" :style="styles?.pmql">{{ pmql }}</code>

            <div v-if="showAiIndicator" class="separator align-items-center" :style="styles?.separators"></div>
            <span v-if="showAiIndicator" class="badge badge-pill badge-success">AI</span>

            <div v-if="showUsage" class="separator align-items-center" :style="styles?.separators"></div>
            <label v-if="showUsage" class="badge badge-primary badge-pill usage-label"
              v-b-tooltip.hover :title="'Prompt tokens: ' + usage.promptTokens + ' - Completion tokens: ' + usage.completionTokens + ' - Total: ' + usage.totalTokens + ' tokens'">
                {{ usage.totalTokens }} tokens
                <i class="fa fa-info-circle ml-1 pmql-icons"></i>
            </label>

            <div class="separator align-items-center" :style="styles?.separators"></div>
            <i class="fa fa-times pl-1 pr-3 pmql-icons" role="button" @click="clearQuery" :style="styles?.icons"></i>

          </div>
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

export default {
  props: ["searchType", "value", "aiEnabled", "ariaLabel", "id", "validations", "styles"],
  data() {
    return {
      aiLoading: false,
      inputAriaLabel: "",
      placeholder: "",
      showUsage: false,
      showAiIndicator: false,
      showFilter: false,
      pmql: "",
      query: "",
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
  },

  watch: {
    query() {
      this.calcInputHeight();
    },
  },

  mounted() {
    this.query = this.value;
    this.inputAreaLabel = this.areaLabel;
    Vue.nextTick().then(() => {
      this.$refs.search_input.focus();
    });
  },

  methods: {
    calcInputHeight() {
      this.$refs.search_input.style.height = "auto";
      this.$nextTick(() => {
        this.$refs.search_input.style.height = `${this.$refs.search_input.scrollHeight}px`;
        this.$emit("inputresize", this.$refs.search_input.scrollHeight);
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
      } else if (this.aiEnabled) {
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

      ProcessMaker.apiClient.post("/openai/nlq-to-pmql", params).then(response => {
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
  min-height: calc(1.5em + 0.75rem + 2px);
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

.badge-success {
    color: #00875A;
    background-color: #00875a26;
}

</style>
