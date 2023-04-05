<template>
  <div>
    <div class="">
      <div class="d-flex align-items-start">
        <div class="search-bar flex-grow">

          <div class="search-bar-container d-flex align-items-center">
            <i v-if="!aiLoading" class="fa fa-search ml-3 pmql-icons"></i>
            <i v-if="aiLoading" class="fa fa-spinner fa-spin ml-3 pmql-icons"></i> 

            <input ref="search_input" type="text" class="pmql-input"
              :aria-label="inputAriaLabel"
              :placeholder="placeholder"
              :id="inputId"
              v-model="query"
              rows="1"
              @input="onInput()"
              @keydown.enter.prevent @keyup.enter="search()">
            </input>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>

import isPMQL from "../../modules/isPMQL";

export default {
  props: [
    "searchType",
    "value",
    "aiEnabled",
    "ariaLabel",
    "inputId",
    "placeholder",
    "inputLabel",
  ],
  data() {
    return {
      aiLoading: false,
      inputAriaLabel: "",
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
    aiEnabledLocal() {
      if (!window.ProcessMaker.openAi.enabled || window.ProcessMaker.openAi.enabled === "") {
        return false;
      }

      return this.aiEnabled;
    },
  },

  watch: {
  },

  mounted() {
    this.query = this.value;
    this.inputAriaLabel = this.ariaLabel;
  },

  methods: {
    onInput() {
      this.$emit("pmqlchange", this.query);
    },
    clearQuery() {
      this.query = "";
      this.search();
    },
    search() {
      const params = {
        question: this.query,
        // type: this.searchType,
      };

      this.aiLoading = true;

      ProcessMaker.apiClient.post("/openai/nlq-to-category", params)
        .then((response) => {
          this.pmql = response.data.result;
          this.usage = response.data.usage;
          this.$emit("submit", this.pmql);
          this.aiLoading = false;
        })
        .catch(error => {
          console.log(error);
          window.ProcessMaker.alert(this.$t("An error ocurred while calling OpenAI endpoint."), "danger");
          const fullTextSearch = `(fulltext LIKE "%${params.question}%")`;
          this.pmql = fullTextSearch;
          this.$emit("submit", fullTextSearch);
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
  width: 500px;
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
  font-family: 'Open Sans';
  font-weight: 600;
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
  min-width: 30rem;
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.125);
  box-shadow: 0 6px 12px 2px rgba(0, 0, 0, 0.168627451);
  position: absolute;
  left: 0;
  top: 2.5rem;
  border-radius: 3px;
  z-index: 1;
  max-width: 40rem;
}

.selected-filter-item {
  background: #DEEBFF;
  padding: 4px 9px 4px 9px;
  color: #104A75;
  border: 1px solid #104A75;
  border-radius: 4px;
  margin-right: 0.5em;
}

.selected-filter-key {
  text-transform: capitalize;
  font-weight: 700;
}

.filter-counter {
  background: #EBEEF2 !important;
  font-weight: 400;
}
</style>
