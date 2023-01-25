<template>
  <component
    :is="component"
    :href.prop="href"
    class="sidebar-button m-0 py-2 px-3 rounded-sm d-flex align-items-center"
    :class="{'font-weight-bold': parent, 'active': active}"
    @click="onClick"
  >
    <i
      v-if="!parent"
      class="mr-1"
      :class="`fas fa-fw fa-${icon}`"
    ></i>
    <span><slot></slot></span>
    <i
      v-if="parent"
      class="ml-auto fas fa-chevron-down caret-icon"
    ></i>
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
    // page: { },
    active: { },
    parent: { },
    icon: { },
  },
  data() {
    return {
      component: 'button',
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
        return 'a';
      } else {
        return 'button';
      }
    },
    onClick() {
      this.$emit('click', this);
    },
    setToActive() {
      this.active = true;
    },
    setToInactive() {
      this.active = false;
    }
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

  .caret-icon {
    font-size: 12px;
  }
}
</style>
