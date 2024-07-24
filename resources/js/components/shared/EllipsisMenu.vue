<template>
  <b-dropdown
    v-if="filterActions.length > 0"
    v-b-tooltip.hover="{ placement: 'bottom', title: 'Options', variant: 'secondary', customClass: 'ellipsis-tooltip' }"
    :variant="variant ? variant : 'outlined-secondary'"
    :toggle-class="['static-header', { 'contracted-menu': !lauchpad }, { 'expanded-menu': lauchpad }]"
    no-flip
    lazy
    right
    no-caret
    offset="0"
    class="ellipsis-dropdown-main static-header"
    :popper-opts="{ placement: 'bottom-end' }"
    @show="onShow"
    @hide="onHide"
  >
    <template v-if="customButton" #button-content>
      <i
        class="pr-1 ellipsis-menu-icon no-padding"
        :class="customButton.icon"
      />
      <span>
        {{ customButton.content }} <b v-if="showProgress && data && data.batch"> {{ getTotalProgress(data.batch, data.progress) }}%</b>
      </span>
    </template>
    <template v-else-if="lauchpad" #button-content>
      <i class="fas fa-ellipsis-v ellipsis-menu-icon p-0 ellipsis-icon-v" />
      <span>
        {{ $t('Options') }}
      </span>
    </template>
    <template v-else #button-content>
      <span class="text-capitalize screen-toolbar-button">
        <i class="fas fa-ellipsis-h ellipsis-menu-icon p-0" />
      </span>
    </template>
    <div v-if="divider === true">
      <b-dropdown-item
        v-for="action in filterAboveDivider"
        :key="action.value"
        :href="action.link ? itemLink(action, data) : null"
        class="ellipsis-dropdown-item mx-auto"
        :data-test="action.dataTest"
        @click="!action.link ? onClick(action, data) : null"
      >
        <div class="ellipsis-dropdown-content">
          <i
            class="pr-1 fa-fw"
            :class="action.icon"
          />
          <span>{{ $t(action.content) }}</span>
        </div>
      </b-dropdown-item>
      <b-dropdown-divider />
      <b-dropdown-item
        v-for="action in filterBelowDivider"
        :key="action.value"
        :href="action.link ? itemLink(action, data) : null"
        class="ellipsis-dropdown-item mx-auto"
        @click="!action.link ? onClick(action, data) : null"
      >
        <div class="ellipsis-dropdown-content">
          <i
            class="pr-1 fa-fw"
            :class="action.icon"
          />
          <span>{{ $t(action.content) }}</span>
        </div>
      </b-dropdown-item>
    </div>
    <div v-else>
      <div>
        <b-input-group v-if="searchBar">
          <b-input-group-prepend>
            <b-btn class="btn-search-run px-2" :title="$t('Search for an Asset')" @click="fetch()">
              <i class="fas fa-search search-icon" />
            </b-btn>
          </b-input-group-prepend>
          <b-form-input
            id="search-box"
            class="search-input pl-0 border-0 font-italic"
            :placeholder="$t('Search for an Asset')"
          />
        </b-input-group>
      </div>
      <div v-for="action in filterActions" :key="action.value">
        <div v-if="action.value !== 'edit-launchpad' || isProcessesCatalogueInUrl()">
          <b-dropdown-divider v-if="action.value == 'divider'"/>
          <b-dropdown-item
            v-else
            :key="action.value"
            :href="action.link ? itemLink(action, data) : null"
            class="ellipsis-dropdown-item mx-auto"
            @click="!action.link ? onClick(action, data) : null"
            :data-test="action.dataTest"
          >
            <div class="ellipsis-dropdown-content">
              <i
                v-if="!action.image"
                class="pr-1 fa-fw"
                :class="action.icon"
              />
              <img v-if="action.image" :src="action.image" :alt="$t('Priority')">
              <span>{{ $t(action.content) }}</span>
            </div>
          </b-dropdown-item>
        </div>
      </div>
    </div>
  </b-dropdown>
</template>

<script>
import { Parser } from "expr-eval";
import Mustache from 'mustache';
import PmqlInput from "./PmqlInput.vue";

export default {
  components: { PmqlInput },
  filters: { },
  mixins: [],
  props: ["actions", "permission", "data", "isDocumenterInstalled", "divider", "lauchpad", "customButton", "showProgress", "isPackageInstalled", "searchBar", "variant", "redirectTo", "redirectId"],
  data() {
    return {
      active: false,
      lastProgress: 0,
    };
  },
  computed: {
    filterActions() {
      let btns = this.filterActionsByPermissions();
      btns = this.filterActionsByConditionals(btns);

      return btns;
    },
    filterAboveDivider() {
      const filteredActions = this.filterActions;

      const firstActions = filteredActions.slice(0, -1);

      return firstActions;
    },
    filterBelowDivider() {
      const filteredActions = this.filterActions;

      const lastAction = filteredActions.slice(-1);

      return lastAction;
    },
  },
  methods: {
    onClick(action, data) {
      this.$emit("navigate", action, data);
    },
    itemLink(action, data) {
      if (this.redirectTo === "projects") {
        const href = Mustache.render(action.href, data);
        return `${href}?project_id=${this.redirectId}`;
      }
      return Mustache.render(action.href, data);
    },
    onShow() {
      this.$emit('show');
    },
    onHide() {
      this.$emit('hide');
    },

    getTotalProgress(batchProgress, chunkProgress) {
      const progressSlot = 100 / batchProgress.totalJobs;
      let totalProgress = batchProgress.progress;

      if (chunkProgress?.percentage > 0) {
        totalProgress += ((chunkProgress.percentage * progressSlot) / 100);
      }

      if (totalProgress < this.lastProgress) {
        totalProgress = this.lastProgress;
      } else {
        this.lastProgress = totalProgress;
      }

      return Math.trunc(totalProgress);
    },
    filterActionsByPermissions() {
      return this.actions.filter(action => {
        // Check if the action has a 'permission' property and it's a non-empty string
        if (!action.permission || typeof action.permission === 'string' && action.permission.trim() === '') {
          return true; // No specific permission required or invalid format, so allow the action.
        }
        let requiredPermissions;
        // Check if this.permission is of type string
        if (typeof action.permission === 'string') {
          requiredPermissions = action.permission.split(',');
        } else {
          requiredPermissions = action.permission;
        }

        // Check if this.permission is of type object
        if (typeof this.permission === 'object' && this.permission !== null) {
          const keys = Object.keys(this.permission);
          if (keys[0] === "0") {
            return requiredPermissions.some(permission => Object.values(this.permission).includes(permission));
          } else {
            return requiredPermissions.some(permission => this.permission.hasOwnProperty(permission) && this.permission[permission]);
          }
        }

        // Check if this.permission is a string or an array
        if (typeof this.permission === 'string') {
            return requiredPermissions.some(permission => this.permission.split(',').includes(permission));
        } else if (Array.isArray(this.permission)) {
            return requiredPermissions.some(permission => this.permission.includes(permission));
        }

        // Invalid permission format, exclude the action
        return false;
      });
    },
    filterActionsByConditionals(btns) {
      return btns.filter(btn => {
        if (btn.hasOwnProperty('conditional') && btn.conditional === "isDocumenterInstalled") {
          if (this.isDocumenterInstalled) {
            return true;
          }
        } else if (btn.hasOwnProperty('conditional') && btn.conditional === 'isPackageInstalled') {
          if (this.isPackageInstalled) {
            return true;
          }
        } else if (btn.hasOwnProperty('conditional')) {
          const result = Parser.evaluate(btn.conditional, this.data);
          if (result) {
            return true;
          }
        } else {
          return true;
        }
      });
    },
    isProcessesCatalogueInUrl() {
      const currentUrl = window.location.href;
      const isInUrl = currentUrl.includes("process-browser");
      return isInUrl;
    }
  },
};
</script>

<style lang="scss" scoped>
@import "../../../sass/colors";

.ellipsis-dropdown-main {
  float: right;
}

.ellipsis-dropdown-item {
    border-radius: 4px;
    width: 95%;
}

.ellipsis-dropdown-content {
    color: #42526E;
    font-size: 14px;
    margin-left: -15px;
}

.ellipsis-menu-icon.no-padding {
  padding: 0 !important;
}

.btn-search-run {
  border: none;
  background-color: #ffffff;
}

.btn-search-run:active,
  .btn-search-run:focus {
    border-right-width: 0;
    box-shadow: none !important;
    outline: 0 !important;
  }

.search-input:active,
  .search-input:focus {
    border: none !important;
    box-shadow: none !important;
    outline: 0 !important;
  }
.search-icon {
  color: #6C757D;
}
.screen-toolbar-button {
  color: #556271;
}
</style>
<style>
.static-header {
  position: static !important;
}
.contracted-menu {
  width: 40px;
  height: 40px;
  box-shadow: 0px 4px 6px 0px rgba(0, 0, 0, 0.15);
  border-radius: 4px;
  background-color: #FFFFFF;
}
.static-header:hover {
  background-color: #EBEEF2;
  border-radius: 4px;
}
.contracted-menu:focus {
  box-shadow: 0px 4px 6px 0px rgba(0, 0, 0, 0.15);
}
.expanded-menu {
  color: #556271;
  text-transform: none;
  border-radius: 4px;
  font-size: 16px !important;
}
.ellipsis-icon-v {
  height: 16px;
  width: 16px;
}
.ellipsis-tooltip {
  border-radius: 4px;
}
.ellipsis-tooltip .arrow {
  display: none;
}
</style>
