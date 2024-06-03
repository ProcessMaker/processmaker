<template>
  <div>
    <breadcrumbs
      ref="breadcrumb"
      :category="category ? category.name : ''"
      :process="selectedProcess ? selectedProcess.name : ''"
      :template="guidedTemplates ? 'Guided Templates' : ''"
    />
    <div class="menu-mask" :class="{ 'menu-open' : showMenu }"></div>
    <div class="main" :class="{ 'menu-open' : showMenu }" v-show="hideLaunchpad">
      <div class="menu">
        <span class="pl-3 menu-title"> {{ $t('Process Browser') }} </span>
        <MenuCatologue
          ref="categoryList"
          title="Available Processes"
          preicon="fas fa-play-circle"
          class="pt-3 menu-catalog"
          show-bookmark="true"
          :category-count="categoryCount"
          :data="listCategories"
          :from-process-list="fromProcessList"
          :select="selectCategorie"
          :filter-categories="filterCategories"
          :permission="permission"
          @wizardLinkSelect="wizardTemplatesSelected"
          @addCategories="addCategories"
          @selectedCategoryName="selectedCategoryName = $event"
        />
        <div class="mobile-slide-close">
          <b-button variant="light" @click="showMenu = false" size="lg">
            <i class="fa fa-times"></i>
          </b-button>
        </div>
      </div>
      <div class="slide-control">
        <a href="#" @click="showMenu = !showMenu">
          <i class="fa" :class="{ 'fa-caret-right' : !showMenu, 'fa-caret-left' : showMenu }"></i>
        </a>
      </div>
      <div class="processes-info">

        <!-- TODO: reimplement
        <div
          v-if="!showWizardTemplates && !showCardProcesses && !showProcess && !showProcessScreen"
          class="d-flex justify-content-center py-5"
        >
          <CatalogueEmpty />
        </div>
        <div> -->
          <div class="mobile-menu-control">
            <span @click="showMenu = !showMenu">
              <i class="fa fa-bars"></i>
              {{ selectedCategoryName }}
            </span>
          </div>
        
          <router-view :permission="permission"></router-view>

          <!-- TODO: add to routes
          <CardProcess
            v-if="showCardProcesses && !showWizardTemplates && !showProcess"
            :key="key"
            :category="category"
            @openProcess="openProcess"
            @wizardLinkSelect="wizardTemplatesSelected"
          />
          <ProcessInfo
            v-if="showProcess && !showWizardTemplates && !showCardProcesses && !showProcessScreen"
            :process="selectedProcess"
            :current-user-id="currentUserId"
            :current-user="currentUser"
            :permission="permission"
            :is-documenter-installed="isDocumenterInstalled"
            @goBackCategory="returnedFromInfo"
          />
          <ProcessScreen
            v-if="showProcessScreen && !showCardProcesses && !showWizardTemplates"
            :process="selectedProcess"
            :current-user-id="currentUserId"
            :permission="permission"
            :is-documenter-installed="isDocumenterInstalled"
            @goBackCategory="returnedFromInfo"
          />
          <wizard-templates
            v-if="showWizardTemplates"
            :template="guidedTemplates"
          />
        </div> -->
      </div>
    </div>
  </div>
</template>

<script>
import MenuCatologue from "./menuCatologue.vue";
import CatalogueEmpty from "./CatalogueEmpty.vue";
import CardProcess from "./CardProcess.vue";
import Breadcrumbs from "./Breadcrumbs.vue";

export default {
  components: {
    MenuCatologue, CatalogueEmpty, Breadcrumbs, CardProcess,
  },
  props: ["permission", "isDocumenterInstalled", "currentUserId", "process", "currentUser"],
  data() {
    return {
      showMenu: false,
      selectedCategoryName: '',
      isMobile: window.screen.width < 650,
      listCategories: [],
      defaultOptions: [
        {
          id: 'recent',
          name: this.$t("Recent Cases"),
        },
        {
          id: -1,
          name: this.$t("All Processes"),
        },
        {
          id: 0,
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
      numCategories: 15,
      page: 1,
      key: 0,
      totalPages: 1,
      filter: "",
      markCategory: false,
      fromProcessList: false,
      categoryCount: 0,
      hideLaunchpad: true,
    };
  },
  mounted() {
    const url = new URL(window.location.href);
    this.getCategories();
    setTimeout(() => {
      this.checkSelectedProcess();
      if (this.hasGuidedTemplateParamsOnly(url) || this.hasTemplateParams(url)) {
        // Loaded from URL with guided template parameters to show guided templates
        // Dynamically load the component
        this.$refs.categoryList.selectTemplateItem();
      }
    }, 500);
    this.$root.$on("clickCarouselImage", (val) => {
        this.hideLaunchpad = !val.hideLaunchpad;
    });

    // Show the menu by default when not on mobile
    if (!this.isMobile) {
      this.showMenu = true;
    }
  },
  watch: {
    selectedCategoryName() {
      // Only hide the menu automatically if we are on mobile
      if (this.isMobile) {
        this.showMenu = false;
      }
    }
  },
  methods: {
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
      this.listCategories = [];
      this.filter = filter;
      this.getCategories();
    },
    /**
     * Get list of categories
     */
    getCategories() {
      if (this.page <= this.totalPages) {
        ProcessMaker.apiClient
          .get("process_bookmarks/categories?status=active"
            + "&order_by=name"
            + "&order_direction=asc"
            + `&page=${this.page}`
            + `&per_page=${this.numCategories}`
            + `&filter=${this.filter}`)
          .then((response) => {
            if (!this.checkDefaultOptions()) {
              this.listCategories = [...this.defaultOptions, ...this.listCategories];
            }
            this.listCategories = [...this.listCategories, ...response.data.data];
            this.totalPages = response.data.meta.total_pages !== 0 ? response.data.meta.total_pages : 1;
            this.categoryCount = response.data.meta.total;
            if (this.markCategory) {
              const indexCategory = this.listCategories.findIndex((category) => category.name === this.category.name);
              this.$refs.categoryList.markCategory(this.listCategories[indexCategory]);
              this.markCategory = false;
            }
          });
      }
    },
    /**
     * Check if listCatefgories have the default options
     */
    checkDefaultOptions() {
      return this.defaultOptions.every(v => this.listCategories.includes(v));
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
          categoryId = typeof categories === "string" ? categories.split(",")[0] : categories;
        }
        if (categoryId !== "0" && categoryId !== "-1") {
          ProcessMaker.apiClient
            .get(`process_bookmarks/${categoryId}`)
            .then((response) => {
              this.category = response.data;
              this.markCategory = true;
              this.filterCategories(this.category.name);
            });
        } else {
          this.category = this.getDefaultCategory(categoryId);
          this.$refs.categoryList.markCategory(this.category, false);
        }
      }
    },
    /**
     * Get the values of a default category from an id
     */
    getDefaultCategory(id) {
      return this.defaultOptions.filter((item) => item.id === parseInt(id, 10))[0];
    },
    /**
     * Select a category and show display
     */
    selectCategorie(value) {
      // const url = new URL(window.location.href);

      // // If url has Template Params, don't replace state.
      // if (!this.hasTemplateParams(url)) {
      //   window.history.replaceState(null, null, "/process-browser");
      //   this.key += 1;
      //   this.category = value;
      //   this.selectedProcess = null;
      //   this.showCardProcesses = true;
      //   this.guidedTemplates = false;
      //   this.showWizardTemplates = false;
      // }

      // this.showProcess = false;

      if (!value) {
        return;
      }
      this.$router.push({ name: 'index', query: { categoryId: value.id } });
      this.showMenu = false;
    },
    /**
     * Select a wizard templates and show display
     */
    wizardTemplatesSelected(hasUrlParams = false) {
      if (!hasUrlParams) {
        // Add the params if the guided template link was selected
        const url = new URL(window.location.href);
        if (!url.search.includes("?guided_templates=true")) {
          url.searchParams.append("guided_templates", true);
          // history.pushState(null, "", url); // Update the URL without triggering a page reload
        }
      }

      // Update state variables
      this.showWizardTemplates = true;
      this.guidedTemplates = true;
      this.showCardProcesses = false;
      this.showProcess = false;
      this.selectedProcess = null;
      this.category = null;
    },
    /**
     * Select a process and show display
     */
    openProcess(process) {
      this.showCardProcesses = false;
      this.guidedTemplates = false;
      // if (this.verifyScreen(process)) {
      //   this.showProcess = false;
      //   this.showProcessScreen = true;
      // } else {
      //   this.showProcess = true;
      //   this.showProcessScreen = false;
      // }
      this.selectedProcess = process;
    },
    /**
     * Return a process cards from process info
     */
    returnedFromInfo() {
      this.selectCategorie(this.category);
    },
    hasGuidedTemplateParamsOnly(url) {
      return url.search.includes("?guided_templates=true") && !url.search.includes("?guided_templates=true&template=");
    },
    hasTemplateParams(url) {
      return url.search.includes("&template=");
    },
  },
};
</script>

<style scoped lang="scss">
@import '~styles/variables';


@media (max-width: $lp-breakpoint) {
  .breadcrum-main {
    display: none;
  }
}

.main {
  display: flex;

  @media (max-width: $lp-breakpoint) {
      display: block;
  }
}

.menu {
  left: -100%;
  height: calc(100vh - 145px);
  overflow: hidden;
  margin-top: 15px;
  transition: flex 0.3s;
  flex: 0 0 315px;

  .menu-catalog {
    background-color: #F7F9FB;
    flex: 1;
    width: 315px;
  }

  @media (max-width: $lp-breakpoint) {
    position: absolute;
    z-index: 4; // above pagination
    display: flex;
    margin-top: 0;
    width: 85%;
    transition: left 0.3s;
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
  flex: 0 0 0px;
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
  border-left: 0;
  border-right: 1px solid #DEE0E1;
  margin-left: 10px;
  
  @media (max-width: $lp-breakpoint) {
    display: none;
  }

  a {
    position: relative;
    left: 10px;
    top: 40px;
    z-index: 5;

    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 60px;
    background-color: #ffffff;
    border-radius: 10px;
    border: 1px solid #DEE0E1;
  }
}

.menu-open .slide-control {
  border-right: 0;
  border-left: 1px solid #DEE0E1;

  a {
    left: -10px;
  }
  
}

.mobile-menu-control {
  display: none;
  color: #6A7887;
  font-size: 1.3em;
  margin-top: 10px;
  i {
    margin-right: 3px;
  }
  @media (max-width: $lp-breakpoint) {
    display: block;
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
.processes-info {
  width: 100%;
  margin-right: 16px;
  height: calc(100vh - 145px);
  padding-left: 32px;
  
  @media (max-width: $lp-breakpoint) {
    padding-left: 0;
  }
}
// @media (width <= 1024px) {
//   .menu {
//     min-width: 0;
//     width: 0;
//   }
// }
</style>
