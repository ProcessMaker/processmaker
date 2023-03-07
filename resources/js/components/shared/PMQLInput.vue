<template>
  <div class="search-bar-inputs flex-grow w-100">
    <div class="group">
      <input ref="search_input" type="text" class="search-input"
        :aria-label="$t('Advanced Search (PMQL)')"
        v-model="pmql"
        @keyup.enter="runSearch(true)"
        required>
        <label class="float-label">
          <i v-if="assistantLoading" class="fa fa-spinner fa-spin mr-1"></i> 
          <span v-html="searchInputLabel"></span>
        </label>
    </div>
  </div>
</template>

<script>

import isPMQL from "../../modules/isPMQL";

export default {
  props: ["type"],
  data() {
    return {
      assistantEnabled: false,
      assistantLoading: false,
      searchInputLabel: "Search using natural language or PMQL",
      pmql: "",
    };
  },

  methods: {
    runSearch(advanced) {
      if (! advanced) {
        this.buildPmql();
      }

      if (this.pmql.isPMQL()) {
        this.$emit('submit');
      } else {
        this.runNLPToPMQL();
      }
    },
    runNLPToPMQL() {
      let params = { question: this.pmql, type: this.type };

      this.assistantLoading = true;
      this.searchInputLabel = "Generating PMQL query for: " + "<i>"  + this.pmql + "</i>";

      ProcessMaker.apiClient.post("/openai/nlq-to-pmql", params).then(response => {
        this.assistantEnabled = false;
        this.searchInputLabel = '<i class="fa fa-check text-success"></i> ' + this.pmql;
        this.pmql = response.data.result;
        this.assistantLoading = false;
        this.runSearch(true);
        setTimeout(3000);
      });
    },
  },
};
</script>

<style lang="scss">
    .advanced-search {
        .group {
            position: relative;
            background-color: #ffffff;
            color: #b6bfc6;
            border-radius: 2px;
        }

        input.search-input {
            background: none;
            color: gray;
            padding: 0.375rem 0.75rem;
            padding-top: 1.2rem;
            display: block;
            width: 100%;
            border: none;
            border-radius: 3px 0 0 3px;
            border: 1px solid rgba(0, 0, 0, 0.125);
            border-right: none;
            height: 3.5rem;
        }

        input.search-input:focus {
            outline: none;
        }

        input.search-input:focus ~ label, input.search-input:valid ~ label {
            top: 0px;
            font-size: 12px;
            color: #0872C2;
        }

        .float-label {
            color: #c6c6c6;
            font-size: 16px;
            font-weight: normal;
            position: absolute;
            pointer-events: none;
            padding: 0.3rem 0.75rem;
            top: 10px;
            transition: 300ms ease all;
        }
    }
</style>
