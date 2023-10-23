<template>
  <div class="project">
    <b-navbar type="faded">
      <b-navbar-brand class="text-uppercase">
        {{ $t("Recent Assets from my Projects") }}
      </b-navbar-brand>
      <div class="d-flex" align="end">
        <div class="dropdown">
          <button
            v-if="!showInput"
            id="dropdownMenu"
            type="button"
            class="btn btn-outline-primary border-0 text-capitalize dropdown-toggle"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
          >
            {{ $t("Filter by Type") }}
          </button>
          <div
            class="dropdown-menu dropdown-menu-right px-3"
            aria-labelledby="dropdownMenu"
          >
            <form>
              <div
                v-for="option in optionsType"
                :id="`type-${option.asset_label}`"
                :key="option.asset_label"
                class="dropdown-item form-check"
              >
                <input
                  v-model="selectedTypes"
                  class="form-check-input"
                  type="checkbox"
                  :value="option.asset_type"
                >
                <label class="form-check-label">
                  <i
                    class="fas fa-circle small"
                    :style="`color: ${option.asset_color}`"
                  />
                  {{ option.asset_label }}
                </label>
              </div>
            </form>
          </div>
        </div>
        <div class="d-flex justify-content-end">
          <button
            class="btn btn-outline-primary border-0 ml-1"
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
      </div>
    </b-navbar>
    <recent-assets-list
      ref="recentAssetsList"
      :types="selectedTypes"
      :current-user-id="currentUserId"
      :permission="permission"
      :is-documenter-installed="isDocumenterInstalled"
      :project="project"
    />
  </div>
</template>

<script>
import RecentAssetsList from './RecentAssetsList.vue';

Vue.component("RecentAssetsList", RecentAssetsList);

export default {
  props: ["currentUserId", "project", "permission", "isDocumenterInstalled"],
  data() {
    return {
      searchCriteria: "",
      showInput: false,
      optionsType: [],
      selectedTypes: [],
    };
  },
  mounted() {
    this.getOptionsType();
  },
  updated() {
    this.performSearch();
    this.$refs.recentAssetsList.fetch(this.pmql);
  },
  methods: {
    getOptionsType() {
      if (this.project) {
        window.ProcessMaker.apiClient
          .get("projects/assets/type")
          .then((response) => {
            this.optionsType = response.data.data;
            Object.keys(this.optionsType).forEach((type) => {
              this.selectedTypes.push(this.optionsType[type].asset_type);
            });
          });
      }
    },
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
  height: 815px;
}
.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.image {
  width: 244px;
  height: 219px;
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
