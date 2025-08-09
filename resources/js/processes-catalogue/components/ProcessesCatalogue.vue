<template>
  <div class="tw-flex tw-flex-col tw-h-full tw-w-full">
    <breadcrumbs
      v-if="!mobileApp"
      ref="breadcrumb"
      :category="category ? category.name : ''"
      :process="selectedProcess ? selectedProcess.name : ''"
      :template="guidedTemplates ? 'Guided Templates' : ''" />
    <div class="tw-flex tw-h-full">
      <CollapsableContainer
        v-model="showMenu"
        class="tw-w-80"
        position="right"
        @change="hideMenu">
        <template>
          <MenuCatologue
            ref="categoryList"
            title="Available Processes"
            preicon="fas fa-play-circle"
            class="pt-3 menu-catalog"
            show-bookmark="true"
            :category-count="categoryCount"
            :data="listCategories"
            :from-process-list="fromProcessList"
            :filter-categories="filterCategories"
            @categorySelected="selectCategory"
            @addCategories="addCategories" />
        </template>
      </CollapsableContainer>

      <div
        ref="processInfo"
        class="tw-overflow-hidden tw-flex-1">
        <div
          v-show="showMobileMenuControl"
          class="mobile-menu-control">
          <div
            class="menu-button"
            @click="hideMenu">
            <i class="fa fa-bars" />
            {{ category?.name || "" }}
          </div>
          <div
            class="bookmark-button"
            @click="showBookmarks">
            <i class="fas fa-bookmark" />
          </div>
          <div
            class="search-button"
            @click="
              $root.mobileSearchVisible = !$root.mobileSearchVisible
            ">
            <i class="fas fa-search" />
          </div>
        </div>

        <router-view @goBackCategory="goBackCategory" />
      </div>
    </div>
  </div>
</template>

<script>
import MenuCatologue from "./menuCatologue.vue";
import CatalogueEmpty from "./CatalogueEmpty.vue";
import Breadcrumbs from "./Breadcrumbs.vue";
import CollapsableContainer from "../../../jscomposition/base/ui/CollapsableContainer.vue";

export default {
  components: {
    MenuCatologue,
    CatalogueEmpty,
    Breadcrumbs,
    CollapsableContainer,
  },
  props: ["currentUserId", "process", "currentUser", "userConfig"],
  data() {
    return {
      showMenu: false,
      isMobile: window.screen.width < 650,
      listCategories: [],
      defaultOptions: [
        {
          id: "recent",
          name: this.$t("Recent Cases"),
        },
        {
          id: "all_processes",
          name: this.$t("All Processes"),
        },
        {
          id: "bookmarks",
          name: this.$t("My Bookmarks"),
        },
      ],
      fields: [],
      wizardTemplates: [],
      showWizardTemplates: false,
      showCardProcesses: false,
      showProcess: false,
      showProcessScreen: false,
      category: null,
      selectedProcess: null,
      guidedTemplates: false,
      numCategories: 100,
      page: 1,
      key: 0,
      totalPages: 1,
      filter: "",
      fromProcessList: false,
      categoryCount: 0,
      hideLaunchpad: true,
      currentWidth: 0,
      mobileApp: window.ProcessMaker.mobileApp,
      userConfiguration: this.userConfig,
      urlConfiguration: "users/configuration",
    };
  },
  computed: {
    showMobileMenuControl() {
      return this.$route.name === "index";
    },
  },
  watch: {
    category: {
      deep: true,
      handler() {
        // Only hide the menu automatically if we are on mobile
        if (this.isMobile) {
          this.showMenu = false;
        }
      },
    },
  },
  mounted() {
    const url = new URL(window.location.href);
    this.getCategories();

    // Show the menu by default when not on mobile
    if (!this.isMobile) {
      this.showMenu = true;
    }

    this.$root.$on("filter-categories", (filter) => {
      this.filterCategories(filter);
    });
    this.defineUserConfiguration();
  },
  methods: {
    defineUserConfiguration() {
      this.showMenu = this.userConfiguration.launchpad.isMenuCollapse;
    },
    hideMenu(value) { // value is the new value of the menu
      this.showMenu = value;
      this.$root.$emit("sizeChanged", value);
      this.updateUserConfiguration();
    },
    updateUserConfiguration() {
      this.userConfiguration.launchpad.isMenuCollapse = this.showMenu;
      ProcessMaker.apiClient
        .put(this.urlConfiguration, {
          ui_configuration: this.userConfiguration,
        })
        .catch((error) => {
          console.error("Error", error);
        });
    },
    /**
     * Add new page of categories
     */
    addCategories() {
      this.page += 1;
      this.getCategories();
    },
    /**
     * Filter categories
     */
    filterCategories(filter = "") {
      this.page = 1;
      this.filter = filter;
      if (filter === null) {
        this.$root.filteredCategories = null;
      }
      this.getCategories();
    },
    /**
     * Get list of categories
     */
    getCategories() {
      if (this.page <= this.totalPages) {
        ProcessMaker.apiClient
          .get(
            "process_bookmarks/categories?status=active"
              + "&order_by=name"
              + "&order_direction=asc"
              + `&page=${this.page}`
              + `&per_page=${this.numCategories}`
              + `&filter=${this.filter || ""}`,
          )
          .then((response) => {
            if (this.filter) {
              this.$root.filteredCategories = response.data.data;
              return;
            }

            const loadedCategories = response.data.data.filter(
              (category) =>
                // filter if category exists in the default options
                !this.defaultOptions.some(
                  (defaultOption) => defaultOption.name === category.name,
                ),

            );
            this.listCategories = [
              ...this.defaultOptions,
              ...loadedCategories,
            ];

            this.totalPages = response.data.meta.total_pages !== 0
              ? response.data.meta.total_pages
              : 1;
            this.categoryCount = response.data.meta.total;
          });
      }
    },
    /**
     * Check if listCatefgories have the default options
     */
    checkDefaultOptions() {
      return this.defaultOptions.every((v) => this.listCategories.includes(v));
    },
    /**
     * Check if there is a pre-selected process
     */
    checkSelectedProcess() {
      if (this.process) {
        this.openProcess(this.process);
        this.fromProcessList = true;
        const { searchParams } = new URL(window.location);
        let categoryId;
        if (searchParams.get("categorySelected") !== null) {
          categoryId = searchParams.get("categorySelected");
        } else {
          const categories = this.process.process_category_id;
          categoryId = typeof categories === "string"
            ? categories.split(",")[0]
            : categories;
        }
      }
    },
    /**
     * Get the values of a default category from an id
     */
    getDefaultCategory(id) {
      return this.defaultOptions.filter(
        (item) => item.id === parseInt(id, 10),
      )[0];
    },
    /**
     * Select a category and show display
     */
    selectCategory(value) {
      if (!value) {
        return;
      }

      this.category = value;

      // Do not set the query if we are already on the route
      if (String(this.$route.query.categoryId) === String(value.id)) {
        return;
      }

      this.$router.push({
        name: "index",
        query: { categoryId: value.id },
      });
    },
    /**
     * Select a process and show display
     */
    openProcess(process) {
      this.showCardProcesses = false;
      this.guidedTemplates = false;
      this.selectedProcess = process;
    },
    /**
     * Return a process cards from process info
     */
    goBackCategory() {
      const categoryId = this.category ? this.category.id : "recent";
      this.$router.push({ name: "index", query: { categoryId } });
    },
    hasGuidedTemplateParamsOnly(url) {
      return (
        url.search.includes("?guided_templates=true")
        && !url.search.includes("?guided_templates=true&template=")
      );
    },
    hasTemplateParams(url) {
      return url.search.includes("&template=");
    },
    showBookmarks() {
      if (this.$route.query.categoryId !== "bookmarks") {
        this.$router.push({
          name: "index",
          query: { categoryId: "bookmarks" },
        });
      }
    },
  },
};
</script>

<style scoped lang="scss">
@import "~styles/variables";

@media (max-width: $lp-breakpoint) {
  .breadcrum-main {
    display: none;
  }
}

.menu {
  left: -100%;
  height: calc(100vh - 145px);
  overflow: hidden;
  margin-top: 15px;
  transition: flex 0.3s;
  flex: 0 0 0px;

  .menu-catalog {
    background-color: #f7f9fb;
    flex: 1;
    width: 315px;
    height: 95%;
    overflow-y: scroll;
  }
}

.menu-mask {
  display: none;
  position: absolute;
  left: -100%;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0);
  z-index: 3;
  transition: background-color 0.3s;

  @media (max-width: $lp-breakpoint) {
    display: block;
  }
}

.menu-mask.menu-open {
  @media (max-width: $lp-breakpoint) {
    left: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: block;
  }
}

.menu-open .menu {
  flex: 0 0 315px;

  @media (max-width: $lp-breakpoint) {
    left: 0%;
  }
}

.mobile-slide-close {
  display: none;
  padding-left: 10px;
  padding-top: 10px;

  @media (max-width: $lp-breakpoint) {
    display: block;
  }
}

.slide-control {
  border-left: 1px solid #dee0e1;
  margin-left: 10px;
  width: 29px;

  @media (max-width: $lp-breakpoint) {
    display: none;
  }

  a {
    position: relative;
    left: -11px;
    top: 40px;
    z-index: 5;

    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 60px;
    background-color: #ffffff;
    border-radius: 10px;
    border: 1px solid #dee0e1;
    color: #6a7888;
  }
}

.menu-open .slide-control {
  border-left: 1px solid #dee0e1;

  a {
    left: -11px;
    display: none;
  }
}

.slide-control:hover {
  border-left: 1px solid rgba(72, 145, 255, 0.4);
  box-shadow: -1px 0 0 rgba(72, 145, 255, 0.5);
}

.menu-open .slide-control:hover {
  border-left: 1px solid rgba(72, 145, 255, 0.4);
  box-shadow: -1px 0 0 rgba(72, 145, 255, 0.5);

  a {
    display: flex;
  }
}

.slide-control a:hover {
  background-color: #eaeef2;
}

.mobile-menu-control {
  display: none;
  color: #6a7887;
  font-size: 1.3em;
  margin-top: 10px;
  margin-left: 1em;
  margin-right: 1em;
  align-items: center;

  .menu-button {
    flex-grow: 1;

    i {
      margin-right: 3px;
    }
  }

  .bookmark-button {
    display: flex;
    padding: 10px;
    margin-right: 10px;
    font-size: 1.1em;
  }

  .search-button {
    display: flex;
    padding: 10px;
    font-size: 1.1em;
  }

  @media (max-width: $lp-breakpoint) {
    display: flex;
  }
}

.menu-title {
  color: #556271;
  font-size: 22px;
  font-style: normal;
  font-weight: 600;
  line-height: 46.08px;
  letter-spacing: -0.44px;
  display: block;
  width: 315px;

  @media (max-width: $lp-breakpoint) {
    display: none;
  }
}
</style>
