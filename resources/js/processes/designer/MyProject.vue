<template>
  <div class="project">
    <b-navbar type="faded">
      <b-navbar-brand class="text-uppercase">
        {{ $t("My Projects") }}
      </b-navbar-brand>
      <b-navbar-nav align="end">
        <div class="d-flex justify-content-end">
          <button
            class="btn btn-outline-primary border-0 mr-1"
            @click="toggleInput"
          >
            <i class="fas fa-search" />
          </button>
          <input
            v-if="showInput"
            ref="input"
            v-model="searchCriteria"
            type="text"
            class="form-control narrow-input"
            @keyup.enter="performSearch"
          >
          <button
            v-if="showInput"
            class="btn btn-clear"
            @click="clearSearch"
          >
            <i class="fas fa-times" />
          </button>
        </div>
        <b-nav-item
          v-if="!showInput"
          href="/designer/projects"
        >
          <i class="fas fa-external-link-alt" />
        </b-nav-item>
      </b-navbar-nav>
    </b-navbar>
    <projects-last-modified-listing
      ref="projectsLastModifiedListing"
      :status="status"
      :project="project"
    />
  </div>
</template>

<script>
import ProjectsLastModifiedListing from './ProjectsLastModifiedListing';

Vue.component("ProjectsLastModifiedListing", ProjectsLastModifiedListing);

export default {
  props: ["status", "project"],
  data() {
    return {
      searchCriteria: "",
      showInput: false,
      pmql: "",
    };
  },
  methods: {
    /**
     * This boolean method shows or hide elements
     */
    toggleInput() {
      if (this.showInput) {
        this.performSearch();
      }
      this.showInput = !this.showInput;
    },
    /**
     * This method sends users's input criteria to filter specific tasks or requests
     */
    performSearch() {
      this.pmql = `(fulltext LIKE "%${this.searchCriteria}%")`;
      this.$refs.projectsLastModifiedListing.fetch(this.pmql);
    },
    clearSearch() {
      this.searchCriteria = "";
      this.toggleInput();
    },
  },
};
</script>

<style scoped>
.project {
  background-color: #F9F9F9;
  height: 500px;
}
.card {
  border-radius: 8px;
}
.navbar-expand {
  flex-flow: row nowrap;
  justify-content: space-between;
}
.container {
  display: flex;
  justify-content: center;
  align-items: center;
  flex: 1 0 0;
  align-self: stretch;
  width: 100%;
}
.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.image {
  width: 214px;
  height: 194px;
}
.content-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}
.title {
  color: var(--secondary-800, #44494E);
  font-size: 32px;
  font-style: normal;
  font-weight: 600;
  line-height: 38px;
  letter-spacing: -1.28px;
}
</style>
