<template>
  <div v-if="showSuggestions" class="d-flex suggestions-container flex-column h-100" :class="{'expanded': !showSuggestions}">
    <div class="d-flex flex-column align-items-center mb-0 suggestions-cards-container" @mouseover="stopCarrousel()" @mouseleave="startCarrousel()">
      <div v-if="loading">
        <i class="fa fa-spin fa-spinner mr-1"></i>
      </div>
      <div v-if="loading" class="text-secondary">
        <small>Loading suggestions ...</small>
      </div>
      <div v-for="suggestion, index in currentSuggestions"
        :key="index"
        class="suggestion-card d-flex flex-column align-items-center mb-2 text-center"
        @click="applySuggestion(suggestion.suggestion)">
        <i class="text-primary"><small>If you want to {{ suggestion.title }} try:</small></i>
        <b class="">{{ suggestion.suggestion }}</b>
      </div>
    </div>
    <div class="mt-1 mb-1 w-100">
      <div class="d-flex justify-content-center">
        <div v-for="suggestionsPage, index in suggestionsPages" 
          class="navigation-dot ml-1 mr-1" 
          :key="index" 
          :class="{'active': index === currentSuggestionPageIndex}"
          @click="navigateSuggestion(index)"></div>
      </div>
    </div>
  </div>
</template>

<script>
import _, { debounce } from "lodash";

export default {
  props: ["promptSessionId", "suggestionsPages", "loading", "parentSuggestionsHeight", "minParentSuggestionsHeight", "showSuggestions"],
  data() {
    return {
      currentSuggestionPageIndex: 0,
      currentSuggestions: [],
      interval: 5000,
      timer: null,
      perPage: 3,
    };
  },
  watch: {
    suggestionsPages() {
      this.currentSuggestions = this.suggestionsPages[0];
    },
  },
  mounted() {
    if (this.suggestionsPages.length) {
      this.currentSuggestions = this.suggestionsPages[0];
    }
    this.timer = setInterval(() => {
      this.navigateToNextSuggestions()
    }, this.interval);
  },
  beforeDestroy() {
    clearInterval(this.timer)
  },
  methods: {
    startCarrousel() {
      this.timer = setInterval(() => {
        this.navigateToNextSuggestions()
      }, this.interval);
    },
    stopCarrousel() {
      clearInterval(this.timer);
    },
    navigateSuggestion(index) {
      this.currentSuggestionPageIndex = index;
      this.currentSuggestions = this.suggestionsPages[index];

      clearInterval(this.timer);

      this.timer = setInterval(() => {
        this.navigateToNextSuggestions()
      }, this.interval);
    },
    navigateToNextSuggestions() {
      let pages = this.suggestionsPages.length;
      
      if (this.currentSuggestionPageIndex === pages -1) {
        this.currentSuggestionPageIndex = 0;
      } else {
        this.currentSuggestionPageIndex++;
      }

      this.currentSuggestions = this.suggestionsPages[this.currentSuggestionPageIndex];
    },
    applySuggestion(suggestion) {
      this.$emit('suggestion-applied', suggestion);
    },
  },
};
</script>

<style lang="scss" scoped>
.suggestions-container {
  padding: 0.375rem 0.75rem;
  min-height: 50px;
}
.suggestions-container.expanded {
}
.suggestions-cards-container {
  overflow: auto;
}
.inspiration-label {
  color: #494949;
}
.suggestion-card {
  background-color: #f7f9fb;
  width: 100%;
  padding: 1em 0.5em;
  border-radius: 10px;
  cursor: pointer;
}
.suggestion-card:hover {
  background-color: #edf7ff;
}
.navigation-dot {
  border-radius: 100%;
  background: #B6D3FF;
  height: 10px;
  width: 10px;
  cursor: pointer;
}
.navigation-dot.active {
  background: #1572C2;
}
.cursor-pointer {
  cursor: pointer;
}
</style>
