<template>
  <div>
    <div v-if="aiEnabledLocal" class="">
      <div class="d-flex align-items-start position-relative">
        <div class="search-bar flex-grow"
             v-click-outside="hidePopUp"
             :class="{'expanded': expanded}">
          <div class="row m-0">
            <div class="search-bar-container col-12 d-flex align-items-center p-0">
              <i @click="showPopUp()"
                class="fa fa-search ml-3 pmql-icons" />

              <input ref="search_input" 
                     type="text" 
                     class="pmql-input"
                     :aria-label="inputAriaLabel"
                     :placeholder="placeholder"
                     :id="inputId"
                     v-model="query"
                     rows="1"
                     @click="showPopUp()"
                     @input="onInput()"
                     @keydown.enter.prevent
                     @keyup.enter="search()">

              <i class="fa fa-times pl-1 pr-3 pmql-icons" 
                role="button"
                @click="clearQuery" />
            </div>
          </div>
          <div class="col-12 search-popup border-top">
            <div class="container d-flex flex-column p-0">
              <div class="section-title p-2 w-100">
                {{ $t("Search result") }}
              </div>
              <div v-if="endpointErrors" class="alert alert-danger mx-2 small px-3">
                <i class="fa fa-ban mr-1"></i>{{ endpointErrors }}
              </div>
              <div v-if="!aiLoading && pmql === '' && !lastSearch && !endpointErrors"
                    class="p-2 w-100 text-muted pt-1 pb-3 no-results">
                    {{ $t("Nothing searched yet") }}
              </div>

              <div v-if="aiLoading" class="d-flex justify-content-center align-items-center pb-2">
                <span class="power-loader" />
                <span class="ml-2 text-muted small">
                  {{ $t("Please wait ...") }}
                </span>
              </div>

              <div v-if="!aiLoading && pmql !== '' && lastSearch" 
                   class="section-item w-100 p-2"
                  @click="redirect(lastSearch)">
                <span class="text-primary">
                  {{ lastSearch.search }}
                </span>
                <div v-if="errors(lastSearch)" class="alert alert-warning small mb-1">
                  <i class="fa fa-exclamation-triangle text-warning mr-1" />
                  {{ errors(lastSearch) }}
                  <code class="text-info">{{ getPmql(lastSearch) }}</code>
                </div>
                <div class="path text-secondary">
                  {{ getPath(lastSearch) }}
                </div>
              </div>

              <div class="section-title p-2 mt-2 border-top w-100 d-flex justify-content-between align-items-center">
                <span>{{ $t("Recently searched") }}</span>
                <span role="button" @click="clearHistory">
                  <span>{{ $t("Clear") }}</span>
                </span>
              </div>

              <div v-if="!recentSearches || recentSearches.length === 0"
                      class="p-2 w-100 text-muted pt-1 pb-3 no-results">
                      {{ $t("The history is empty") }}
                </div>
              <div v-for="(recentSearch, index) in recentSearches"
                :key="index"
                class="section-item w-100 p-2"
                @click="redirect(recentSearch)">
                <span class="text-primary">
                  {{ recentSearch.search }}
                </span>
                <div v-if="errors(recentSearch)" class="alert alert-warning small mb-1">
                  <i class="fa fa-exclamation-triangle text-warning mr-1" />
                  {{ errors(recentSearch) }}
                  <code class="text-info">{{ getPmql(recentSearch) }}</code>
                </div>
                <div class="path text-secondary">
                  {{ getPath(recentSearch) }}
                </div>
              </div>

              <!-- <div class="w-100 p-2 mb-2">
                <a href="#">Show more</a>
              </div> -->

              <div class="section-footer d-flex pt-2 pb-0 px-0 mt-3 w-100 align-items-center border-top justify-content-between">
                <div>
                  <div><img src="/img/favicon.svg"> {{ $t("Powered by ProcessMaker AI") }}</div>
                </div>
                <div class="">
                  <button class="btn d-lg-none ml-2 close-button" 
                    @click="hidePopUp">
                    <i class="fa fa-times"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>

import isPMQL from "../../modules/isPMQL";

let myEvent;
export default {
  directives: {
    clickOutside: {
      bind(el, binding, vnode) {
        myEvent = function (event) {
          if (!(el === event.target || el.contains(event.target))) {
            vnode.context[binding.expression](event);
          }
        };
        document.body.addEventListener("click", myEvent);
      },
      unbind() {
        document.body.removeEventListener("click", myEvent);
      },
    },
  },
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
      expanded: false,
      lastSearch: null,
      recentSearches: [],
      endpointErrors: false,
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

      return true;
    },
  },

  mounted() {
    this.query = this.value;
    this.inputAriaLabel = this.ariaLabel;
    this.getRecentSearches();

    if (localStorage.globalSearchValue && localStorage.globalSearchValue !== "") {
      this.query = localStorage.globalSearchValue.slice();
      this.$nextTick(() => {
        localStorage.globalSearchValue = "";
      });
    }
  },

  methods: {
    getPath(item) {
      if (item.type === "collections" && !JSON.parse(item.response).collectionError) {
        return `Home / ${this.capitalize(item.type)} / ${JSON.parse(item.response).collection.name}`;
      }

      if (!item.type || item.type === "") {
        return "";
      }

      return `Home / ${this.capitalize(item.type)}`;
    },
    redirect(search) {
      const url = this.getUrl(search);
      let pmql = search.response;

      if (search.type === "collections") {
        pmql = JSON.parse(search.response).pmql;
      }
      window.location.href = `${url}?pmql=${pmql}`;
    },
    getUrl(item) {
      if (item.type === "collections" && !JSON.parse(item.response).collectionError) {
        return `/${item.type}/${JSON.parse(item.response).collection.id}`;
      }

      if (item.type === "requests") {
        return `/${item.type}/all`;
      }

      return `/${item.type}`;
    },
    capitalize(str) {
      return str.charAt(0).toUpperCase() + str.slice(1);
    },
    showPopUp() {
      this.expanded = true;
    },
    hidePopUp() {
      this.expanded = false;
    },
    getContext() {
      return window.location.pathname.split("/")[1];
    },
    onInput() {
      this.$emit("pmqlchange", this.query);
    },
    clearQuery() {
      this.query = "";
      localStorage.globalSearchValue = "";
    },
    getRecentSearches() {
      ProcessMaker.apiClient.get('/openai/recent-searches?quantity=5')
        .then((response) => {
          if (response.data && response.data.recentSearches) {
            this.recentSearches = response.data.recentSearches;
          }
        });
    },
    clearHistory() {
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t("Are you sure you want to clear the history?"),
        "",
        () => {
          ProcessMaker.apiClient
            .delete("/openai/recent-searches")
            .then(() => {
              this.recentSearches = [];
              this.showPopUp();
            });
        },
      );
    },
    errors(search) {
      try {
        return JSON.parse(search.response).collectionError;
      } catch (e) {
        return false;
      }
    },
    getPmql(search) {
      try {
        return JSON.parse(search.response).pmql;
      } catch (e) {
        return search.response;
      }
    },
    search() {
      this.showPopUp();
      const params = {
        question: this.query,
        type: this.getContext(),
      };

      localStorage.globalSearchValue = this.query;

      this.aiLoading = true;
      this.endpointErrors = false;

      ProcessMaker.apiClient.post("/openai/nlq-to-category", params)
        .then((response) => {
          this.pmql = response.data.result;
          this.usage = response.data.usage;
          this.recentSearches = response.data.recentSearches;
          this.lastSearch = response.data.lastSearch;
          this.$emit("submit", this.pmql);
          this.aiLoading = false;
        })
        .catch(error => {
          const $errorMsg = this.$t("An error ocurred while calling OpenAI endpoint.");
          window.ProcessMaker.alert($errorMsg, "danger");
          this.endpointErrors = $errorMsg;
          this.aiLoading = false;
        });
    },
  },
};
</script>

<style lang="scss" scoped>

.search-bar {
  border: 1px solid rgba(0, 0, 0, 0.125);
  box-shadow: none;
  border-radius: 3px;
  background: #ffffff;
  width: 200px;
  transition:
    max-height 0.25s cubic-bezier(0.4, 0, 0.2, 1),
    width 0.55s 0.15s cubic-bezier(0.4, 0, 0.2, 1),
    box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  max-height: 40px;
  position: absolute;
  right: 0;
  top: -20px;
  z-index: 100;
}

.small-screen .search-bar{
  position: relative;
  top: 0;
  right: 0;
  left: 0;
  width: 100%;
  margin-top: 0.25rem;
}
.search-bar.expanded {
  width: 608px;
  max-height: 900px;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1), 0 6px 6px rgba(0, 0, 0, 0.16);
  z-index: 100;
  background: #ffffff;
  transition:
    max-height 1s 0.15s cubic-bezier(0.4, 0, 0.2, 1),
    width 0.15s cubic-bezier(0.4, 0, 0.2, 1),
    box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: absolute;
  right: 0;
  top: -20px;
  overflow-y: auto;
}

.small-screen .search-bar.expanded {
  width: 100%;
  position: relative;
  right: 0;
  top: 0px;
}

.search-popup {
  opacity: 0;
  padding: 0;
}

.expanded .search-popup {
  opacity: 1;
  height: 100%;
  padding: 0.5rem;
  transition: opacity .5s .4s cubic-bezier(0.4, 0, 0.2, 1);
}
.search-popup .container {
  height: 0px;
  display: none !important;
}
.expanded .search-popup .container {
  height: auto;
  display: block !important;
}
.search-bar.is-invalid {
  border-color: #E50130;
}
.section-item {
  font-size: 1rem;
}

.section-item:hover {
  background-color: #F5F6F8;
  border-radius: 3px;
  cursor: pointer;
}

.section-title {
  font-weight: 600;
  color: #42526E
}

.section-footer {
  font-size: 80%;
  font-weight: 600;
}

.section-footer img {
  display: inline-block;
  height: 16px;
}

.no-results {
  font-size: .8rem;
  font-weight: 100;
}

.path {
  font-size: 80%;
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
.close-button {
  margin-right: -0.5em;
  font-size: 1.5rem;
}
// .usage-label {
//   background: #1c72c224;
//   color: #0872C2;
//   right: 29px;
//   top: 0;
//   margin-right: 0.5rem;
//   font-weight: 300;
//   margin-bottom: 0;
// }

// .separator {
//   border-right: 1px solid rgb(227, 231, 236);
//   height: 1.6rem;
//   margin-left: 0.5rem;
//   margin-right: 0.5rem;
//   right: 0;
//   top: 15%;
// }

// .separator-horizontal {
//   border: 0;
//   border-bottom: 1px dashed rgb(227, 231, 236);
//   height: 0;
//   margin: 5px 7px 0 8px;
// }

// .badge-success {
//   color: #00875A;
//   background-color: #00875a26;
// }

// .filter-dropdown-panel-container {
//   min-width: 30rem;
//   background: #ffffff;
//   border: 1px solid rgba(0, 0, 0, 0.125);
//   box-shadow: 0 6px 12px 2px rgba(0, 0, 0, 0.168627451);
//   position: absolute;
//   left: 0;
//   top: 2.5rem;
//   border-radius: 3px;
//   z-index: 1;
//   max-width: 40rem;
// }

// .selected-filter-item {
//   background: #DEEBFF;
//   padding: 4px 9px 4px 9px;
//   color: #104A75;
//   border: 1px solid #104A75;
//   border-radius: 4px;
//   margin-right: 0.5em;
// }

// .selected-filter-key {
//   text-transform: capitalize;
//   font-weight: 700;
// }

// .filter-counter {
//   background: #EBEEF2 !important;
//   font-weight: 400;
// }
.power-loader .text {
  color: #42516e;
  font-size: .8rem;
}
.power-loader {
    width: 34px;
    height: 34px;
    border-radius: 10%;
    position: relative;
    animation: rotate 1s linear infinite
  }
  .power-loader::before , .power-loader::after {
    content: "";
    box-sizing: border-box;
    position: absolute;
    inset: 0px;
    border-radius: 50%;
    border: 5px solid #0871c231;
    animation: prixClipFix 2s linear infinite ;
  }
  .power-loader::after{
    border-color: #0872C2;
    animation: prixClipFix 2s linear infinite , rotate 0.5s linear infinite reverse;
    inset: 6px;
  }

  @keyframes rotate {
    0%   {transform: rotate(0deg)}
    100%   {transform: rotate(360deg)}
  }

  @keyframes prixClipFix {
      0%   {clip-path:polygon(50% 50%,0 0,0 0,0 0,0 0,0 0)}
      25%  {clip-path:polygon(50% 50%,0 0,100% 0,100% 0,100% 0,100% 0)}
      50%  {clip-path:polygon(50% 50%,0 0,100% 0,100% 100%,100% 100%,100% 100%)}
      75%  {clip-path:polygon(50% 50%,0 0,100% 0,100% 100%,0 100%,0 100%)}
      100% {clip-path:polygon(50% 50%,0 0,100% 0,100% 100%,0 100%,0 0)}
  }
</style>
