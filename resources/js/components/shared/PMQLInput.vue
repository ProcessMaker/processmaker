<template>
  <div class="search-bar-inputs flex-grow w-100">
    <div class="group">
      <input ref="search_input" type="text" class="search-input"
        :aria-label="inputAreaLabel"
        v-model="pmql"
        @keyup.enter="runSearch()"
        required>
        <label class="float-label">
          <i v-if="aiLoading" class="fa fa-spinner fa-spin mr-1"></i> 
          <span v-html="searchInputLabel"></span>
        </label>

        <label class="badge badge-primary badge-pill usage-label"
          v-b-tooltip.hover :title="'Prompt tokens: ' + usage.promptTokens + ' - Completion tokens: ' + usage.completionTokens + ' - Total: ' + usage.totalTokens + ' tokens'">
          {{ usage.totalTokens }} tokens
          <i class="fa fa-info-circle ml-1"></i>
        </label>
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
      pmql: "",
      usage: {
        completionTokens: 0,
        promptTokens: 0,
        totalTokens: 0,
      },
    };
  },

  mounted() {
    this.pmql = this.value;
    this.searchInputLabel = this.searchLabel;
    this.inputAreaLabel = this.areaLabel;
    Vue.nextTick().then(() => {
      this.$refs.search_input.focus();
    });
  },

  methods: {
    runSearch() {
      if (this.pmql.isPMQL()) {
        this.$emit("submit", this.pmql);
      } else if (this.aiEnabled) {
        this.runNLQToPMQL();
      }
    },
    runNLQToPMQL() {
      const params = {
        question: this.pmql,
        type: this.searchType,
      };

      this.aiLoading = true;
      this.searchInputLabel = `Generating PMQL query for: <i>${this.pmql}</i>`;

      ProcessMaker.apiClient.post("/openai/nlq-to-pmql", params).then(response => {
        this.searchInputLabel = `<i class="fa fa-check text-success"></i> ${this.pmql}`;
        this.pmql = response.data.result;
        this.usage = response.data.usage;
        this.$emit("submit", this.pmql);
        this.aiLoading = false;
      });
    },
  },
};
</script>

<style lang="scss">
.group {
    position: relative;
    background-color: #ffffff;
    color: #b6bfc6;
    border-radius: 2px;
}

input.search-input {
    background: none;
    color: gray;
    padding: 0.2rem 0.75rem;
    padding-top: 1.2rem;
    display: block;
    width: 100%;
    border: none;
    border-radius: 3px;
    border: 1px solid rgba(0, 0, 0, 0.125);
}

input.search-input:focus {
    outline: none;
}

input.search-input:focus ~ label, input.search-input:valid ~ label {
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
    background: #dfdfdf;
    color: #212529;
    position: absolute;
    right: 0;
    top: 0;
    margin: 0.5rem 0.5rem;
    font-weight: 300;
}
</style>
