<template>
  <component
    :is="component"
    :href.prop="href"
    class="sidebar-button m-0 py-2 px-3 rounded-sm d-flex align-items-center"
    :class="{'font-weight-bold': parent, 'active': active}"
    @click="onClick"
  >
    <div
      v-if="parent"
      class="parent-name-container"
    >
      <span><slot /></span>
      <i
        v-if="parent && collapsable"
        class="fa chevron caret-icon"
        :class="showChildTabs ? 'fa-chevron-right' : 'fa-chevron-down'"
        @click="onToggleClick"
      />
    </div>
    <div v-if="child">
      <i
        v-if="child"
        class="mr-1"
        :class="`fas fa-fw ${icon}`"
      />
      <span><slot /></span>
    </div>
  </component>
</template>

<script>
export default {
  components: {},
  props: {
    href: {
      type: String,
      default: null,
    },
    collapsable: {
      type: Boolean,
      default: true,
    },
    // page: { },
    active: { },
    parent: { },
    child: { },
    icon: { },
  },
  data() {
    return {
      component: "button",
      showChildTabs: false,
      // active: this.page.active,
      // parent: this.page.parent,
      // icon: this.page.icon,
    };
  },
  mounted() {
    // if (this.href) {
    //   this.component = 'a';
    // }
    // this.page.button = this;
  },
  methods: {
    is() {
      if (this.href) {
        return "a";
      }
      return "button";
    },
    onClick() {
      this.$emit("click", this);
    },
    setToActive() {
      this.active = true;
    },
    setToInactive() {
      this.active = false;
    },
    onToggleClick() {
      this.$emit("toggleClick", this);
      this.showChildTabs = !this.showChildTabs;
    },
  },
};
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";
.sidebar-button {
  background: transparent;
  border: 0;
  color: $darkneutral;
  text-align: left;
  text-decoration: none;
  width: 100%;

  &:hover {
    background: $grey-bg-light;
  }

  &:active,
  &.active {
    font-weight: 600;
    background: $grey-bg;
  }

  &.active::before {
    position: absolute;
    left: 6px;
    width: 4px;
    height: 24px;
    content: "";
    background-color: #0872c2;
    border-radius: 6px;
  }

  .parent-name-container {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .caret-icon {
    font-size: 12px;
  }

}
</style>
