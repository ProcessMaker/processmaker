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
        <div class="search-bar flex-grow w-100">
          <div class="search-bar-container d-flex align-items-center">
            <i v-if="!aiLoading" class="fa fa-search ml-3 text-muted"></i>
            <i v-if="aiLoading" class="fa fa-spinner fa-spin ml-3"></i> 

            <textarea ref="search_input" type="text" class="pmql-input"
              :aria-label="inputAreaLabel"
              v-model="query"
              rows="1"
              @keydown.enter.prevent @keyup.enter="runSearch()"></textarea>

            <div v-if="showPmqlSection" class="separator align-items-center"></div>
            <code v-if="showPmqlSection" class="w-100 d-block text-primary">{{ pmql }}</code>

            <div v-if="showAiIndicator" class="separator align-items-center"></div>
            <span v-if="showAiIndicator" class="badge badge-pill badge-success">AI</span>

            <!-- <label class="float-label">
              <i v-if="aiLoading" class="fa fa-spinner fa-spin mr-1"></i> 
              <span v-html="searchInputLabel"></span>
            </label> -->

            <div v-if="showUsage" class="separator align-items-center"></div>
            <label v-if="showUsage" class="badge badge-primary badge-pill usage-label"
              v-b-tooltip.hover :title="'Prompt tokens: ' + usage.promptTokens + ' - Completion tokens: ' + usage.completionTokens + ' - Total: ' + usage.totalTokens + ' tokens'">
                {{ usage.totalTokens }} tokens
                <i class="fa fa-info-circle ml-1"></i>
            </label>

            <div class="separator align-items-center"></div>
            <i class="fa fa-times pl-1 pr-3 text-secondary" role="button" @click="clearQuery"></i>

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
  props: ["searchType", "value", "aiEnabled", "searchLabel", "areaLabel"],
  data() {
    return {
      aiLoading: false,
      searchInputLabel: "",
      inputAreaLabel: "",
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
    this.searchInputLabel = this.searchLabel;
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
      });
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
}

.pmql-input:focus {
  outline: none;
}

input.pmql-input:focus ~ label, input.pmql-input:valid ~ label {
    top: 3px;
    font-size: 12px;
    color: #0872C2;
}

.float-label {
    color: #c6c6c6;
    font-size: 16px;
    font-weight: normal;
    position: absolute;
    pointer-events: none;
    padding: 0.1rem 0.75rem;
    top: 10px;
    transition: 300ms ease all;
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
