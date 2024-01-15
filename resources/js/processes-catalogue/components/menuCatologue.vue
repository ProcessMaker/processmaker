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
          <i
            class="mr-3"
            :class="preicon"
          />
          {{ $t(title) }}
        </div>
        <i
          class="fas fa-sort-down"
          :class="{
            'fa-sort-up': showCatalogue,
            'fa-sort-down': !showCatalogue,
          }"
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
    <hr class="my-12" />
    <div
      v-b-toggle.collapse-3
      block
      variant="light"
      class="m-1"
      @click="onToggleTemplates"
    >
      <div class="d-flex align-items-center justify-content-between pl-3 pr-3">
        <div class="d-flex align-items-center">
          <img
            class="mr-3"
            src="../../../img/template-icon.svg"
            alt="Template Icon"
          />
          {{ $t("Add From Templates") }}
        </div>
        <i
          class="fas fa-sort-down"
          :class="{
            'fa-sort-up': showGuidedTemplates,
            'fa-sort-down': !showGuidedTemplates,
          }"
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

    <select-template-modal
      :type="$t('Process')"
      :countCategories="countCategories"
      ref="addProcessModal"
      hide-add-btn="true"
    >
    </select-template-modal>
  </div>
</template>

<script>
import SearchCategories from "./utils/SearchCategories.vue";
import SelectTemplateModal from "../../components/templates/SelectTemplateModal.vue";

export default {
  components: {
    SearchCategories,
    SelectTemplateModal,
  },
  props: [
    "data",
    "select",
    "title",
    "preicon",
    "filterCategories",
    "fromProcessList",
  ],
  data() {
    return {
      showTemplateModal: true,
      assetName: null,
      assetId: null,
      modalProcess: true,
      countCategories: 0,
      showCatalogue: false,
      showGuidedTemplates: false,
      selectedProcessItem: null,
      selectedTemplateItem: null,
      templateOptions: [
        {
          label: this.$t("All Templates"),
          selected: false,
        },
        {
          label: this.$t("Guided Templates"),
          selected: false,
        },
      ],
      comeFromProcess: false,
    };
  },
  mounted() {
    const listElm = document.querySelector("#infinite-list");
    listElm.addEventListener("scroll", () => {
      if (
        listElm.scrollTop + listElm.clientHeight + 2 >=
        listElm.scrollHeight
      ) {
        this.loadMore();
      }
    });
    this.comeFromProcess = this.fromProcessList;
    this.getCountCategories();
  },
  updated() {},
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
      if (item.label === "All Templates") {
        this.addNewProcess();
        return;
      }
      this.selectedTemplateItem = item;
      this.selectedProcessItem = null;
      this.select(item);
      this.$emit("wizardLinkSelect");
    },
    addNewProcess() {
      this.$nextTick(() => {
        this.$refs["addProcessModal"].show();
      });
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
    getCountCategories() {
      ProcessMaker.apiClient
        .get("process_categories")
        .then((response) => {
          this.countCategories = response.data.meta.count;
        })
        .catch((error) => {
          console.error(error);
        });
    },
  },
};
</script>

<style lang="scss" scoped>
@import url("../../../sass/_scrollbar.scss");
i {
  font-size: 20px;
  color: #6a7888;
}
#category-menu > .list-group {
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
  padding: 12px 14px 12px 20px;
  margin-left: 1rem;
  color: #4f606d;
  font-size: 15px;
  font-weight: 400;
}
.list-item:hover {
  background: #e5edf3;
}
.list-item-selected {
  background: #e5edf3;
  color: #1572c2;
  font-weight: 700;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter,
.fade-leave-to {
  opacity: 0;
}
</style>
