<template>
  <b-dropdown
    variant="ellipsis"
    no-caret
    no-flip
    lazy
    class="dropdown-right ellipsis-dropdown-main"
  >
    <template v-if="customButton" #button-content>
      <i
        class="pr-1 ellipsis-menu-icon"
        :class="customButton.icon"
      />
      <span>{{ customButton.content }}</span>
    </template>
    <template v-else #button-content>
      <i class="fas fa-ellipsis-h ellipsis-menu-icon" />
    </template>
    <div v-if="divider === true">
      <b-dropdown-item
        v-for="action in filterAboveDivider"
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
          <span>{{ action.content }}</span>
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
          <span>{{ action.content }}</span>
        </div>
      </b-dropdown-item>
    </div>
    <div v-else>
      <b-dropdown-item
        v-for="action in filterActions"
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
          <span>{{ action.content }}</span>
        </div>
      </b-dropdown-item>
    </div>
  </b-dropdown>
</template>

<script>
import { Parser } from "expr-eval";
import Mustache from 'mustache';

export default {
  components: { },
  filters: { },
  mixins: [],
  props: ["actions", "permission", "data", "isDocumenterInstalled", "divider", "customButton"],
  data() {
    return {
      active: false,
    };
  },
  computed: {
    filterActions() {
      let btns = this.actions.filter(action => {
        if (!action.hasOwnProperty('permission') || action.hasOwnProperty('permission') && this.permission.includes(action.permission)) {
          return action;
        } 
      });

      btns = btns.filter(btn => {
        if (btn.hasOwnProperty('conditional') && btn.conditional === "isDocumenterInstalled") {
          if (this.isDocumenterInstalled) {
            return btn;
          }
        } else if (btn.hasOwnProperty('conditional') ) {
          const result = Parser.evaluate(btn.conditional, this.data);
          if (result) {
            return btn;
          }
        } else {
          return btn;
        }
      });
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
      console.log('ITEM LINK', action, data);
      return Mustache.render(action.href, data);
    }
  },
  mounted() {
    console.log('ACTIONS', this.actions);
  }
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

</style>
