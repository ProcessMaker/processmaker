<template>
  <div>
    <SearchCategories
      ref="searchCategory"
      :filter-pmql="onFilter"
    />
    <div
      v-b-toggle.category-menu
      block
      variant="light"
      class="m-1"
      @click="onToggleCatalogue"
    >
      <div class="d-flex align-items-center justify-content-between pl-3 pr-3">
        <div class="d-flex align-items-center">
          <i :class="preicon" />
          {{ $t(title) }}
        </div>
        <i
          class="fas fa-sort-down"
          :class="{'fa-sort-up': showCatalogue, 'fa-sort-down': !showCatalogue}"
        />
      </div>
    </div>
    <b-collapse
      id="category-menu"
      visible
    >
      <b-list-group id="infinite-list">
        <b-list-group-item
          v-for="item in data"
          :key="item.id"
          ref="processItems"
          :class="{ 'list-item-selected': isSelectedProcess(item) }"
          class="list-item"
          @click="selectProcessItem(item)"
        >
          {{ item.name }}
        </b-list-group-item>
      </b-list-group>
    </b-collapse>
    <div
      v-b-toggle.collapse-3
      block
      variant="light"
      class="m-1"
      @click="onToggleTemplates"
    >
      <div class="d-flex align-items-center justify-content-between pl-3 pr-3">
        <div class="d-flex align-items-center">
          <img src="../../../img/template-icon.svg" alt="Template Icon">
          {{ $t("Add From Templates") }}
        </div>
        <i
          class="fas fa-sort-down"
          :class="{'fa-sort-up': showGuidedTemplates, 'fa-sort-down': !showGuidedTemplates}"
        />
      </div>
    </div>
    <b-collapse
      id="collapse-3"
      visible
    >
      <b-list-group>
        <b-list-group-item
          v-for="(item, index) in templateOptions"
          :key="index"
          ref="templateItems"
          :class="{ 'list-item-selected': isSelectedTemplate(item) }"
          class="list-item"
          @click="selectTemplateItem(item)"
        >
          {{ item.label }}
        </b-list-group-item>
      </b-list-group>
    </b-collapse>
  </div>
</template>

<script>
import SearchCategories from "./utils/SearchCategories.vue";

export default {
  components: {
    SearchCategories,
  },
  props: ["data", "select", "title", "preicon", "filterCategories", "showDefaultCategory", "fromProcessList"],
  data() {
    return {
      showCatalogue: false,
      showGuidedTemplates: false,
      selectedProcessItem: null,
      selectedTemplateItem: null,
      templateOptions: [
        {
          label: this.$t("Guided Templates"),
          selected: false,
        },
      ],
      showDefault: false,
      comeFromProcess: false,
    };
  },
  mounted() {
    const listElm = document.querySelector("#infinite-list");
    listElm.addEventListener("scroll", () => {
      if (listElm.scrollTop + listElm.clientHeight >= listElm.scrollHeight) {
        this.loadMore();
      }
    });
    this.showDefault = this.showDefaultCategory;
    this.comeFromProcess = this.fromProcessList;
  },
  updated() {
    if (this.showDefault && !this.comeFromProcess) {
      const indexUncategorized = this.data.findIndex((category) => category.name === "Uncategorized");
      this.selectProcessItem(this.data[indexUncategorized]);
      this.showDefault = false;
    }
  },
  methods: {
    /**
     * Adding categories
     */
    loadMore() {
      this.$emit("addCategories");
    },
    markCategory(item) {
      this.comeFromProcess = true;
      this.selectedProcessItem = item;
      this.selectedTemplateItem = null;
      this.$refs.searchCategory.fillFilter(item.name);
    },
    selectProcessItem(item) {
      this.comeFromProcess = false;
      this.selectedProcessItem = item;
      this.selectedTemplateItem = null;
      this.select(item);
    },
    selectTemplateItem(item) {
      this.selectedTemplateItem = item;
      this.selectedProcessItem = null;
      this.select(item);
      this.$emit("wizardLinkSelect");
    },
    isSelectedProcess(item) {
      return this.selectedProcessItem === item;
    },
    isSelectedTemplate(index) {
      return this.selectedTemplateItem === index;
    },
    onToggleCatalogue() {
      this.showCatalogue = !this.showCatalogue;
    },
    onToggleTemplates() {
      this.showGuidedTemplates = !this.showGuidedTemplates;
    },
    /**
     * Filter categories
     */
    onFilter(value) {
      this.filterCategories(value);
    },
  },
};
</script>

<style lang="scss" scoped>
@import url("../../../sass/_scrollbar.scss");
i {
  font-size: 20px;
  color: #6A7888;
}
.list-group {
  max-height: 37vh;
  min-height: 37vh;
  overflow-y: auto;
}
.list-group-item {
  background: #f7f9fb;
  border: none;
}
.list-item {
  cursor: pointer;
  padding-bottom: 0.25rem;
  padding-top: 0.25rem;
  padding-left: 1rem;
  margin-left: 1rem;
  margin-bottom: 0.25rem;
  color: #4F606D;
  font-weight: 400;
}
.list-item:hover {
  background: #E5EDF3;
}
.list-item-selected {
  background: #E5EDF3;
  color: #1572C2;
  font-weight: 700;
}
.fade-enter-active, .fade-leave-active {
  transition: opacity .5s
}
.fade-enter, .fade-leave-to {
  opacity: 0
}
</style>
