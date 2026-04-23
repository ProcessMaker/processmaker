<template>
  <li
    id="Sidebaricon"
    v-b-tooltip.hover.right="{ animation: false, disabled: expanded(), boundary: 'viewport', delay: { show: 0, hide: 0 }, title: item.title }"
    class="nav-item filter-bar justify-content-between"
    :data-cy="item.title"
  >
    <a
      :href="item.url"
      class="nav-link"
      :target="item.attributes.target"
      :aria-label="ariaLabel"
      @click="toggle"
    >
      <i
        v-if="item.attributes.icon"
        class="fas nav-icon"
        :class="item.attributes.icon"
      />
      <i
        v-if="item.attributes.customicon"
        :class="item.attributes.customicon"
      />
      <span
        v-if="item.attributes.file"
        id="custom_icon"
        class="nav-icon custom-icon"
        :style="maskStyle(item.attributes.file)"
      />
      <span
        v-show="expanded()"
        v-cloak
        class="nav-text"
      >
        {{ item.title }}
        <i
          v-if="item.children && item.children.length"
          class="float-right fas"
          :class="{'fa-caret-right': !isOpen, 'fa-caret-down': isOpen}"
        />
        <span
          v-if="count !== null"
          class="nav-badge float-right"
        >{{ count }}</span>
      </span>
    </a>
    <ul
      v-if="item.children && item.children.length"
      v-show="isOpen"
      class="nav nav-list flex-column"
    >
      <li
        v-for="item in item.children"
        :key="item.id"
        class="nav-item nav-pl"
      >
        <a
          v-show="item.attributes.icon"
          :href="item.url"
          class="nav-link"
        >
          <i
            class="fas nav-icon"
            :class="item.attributes.icon"
          />
          <span
            v-if="expanded()"
            v-cloak
            class="nav-text"
          >{{ item.title }}
            <span
              v-if="count !== null"
              class="nav-badge float-right"
              :aria-label="ariaLabel"
            >{{ count }}</span>
          </span>
        </a>
        <a
          v-show="item.attributes.file"
          :href="item.url"
          class="nav-link"
        >
          <span
            id="custom_icon"
            :style="maskStyle(item.attributes.file)"
            class="nav-icon custom-icon"
          />
          <span
            v-if="expanded()"
            v-cloak
            class="nav-text"
          >{{ item.title }}<span
            v-if="count !== null"
            class="nav-badge float-right"
          >{{ count }}</span></span>
          <span
            v-if="expanded()"
            v-cloak
            class="nav-text"
          >{{ item.title }}
            <span
              v-if="count !== null"
              class="nav-badge float-right"
              :aria-label="ariaLabel"
            >{{ count }}</span>
          </span>
        </a>
      </li>
    </ul>
  </li>
</template>

<script>
export default {
  props: [
    "item",
  ],
  data() {
    return {
      count: null,
      isOpen: false,
    };
  },
  computed: {
    ariaLabel() {
      if (this.item.attributes.count !== undefined) {
        return `${this.item.title}, ${this.pluralize(this.count)}`;
      }
      return this.item.title;
    },
  },
  mounted() {
    if (this.item.attributes.count !== undefined) {
      this.count = this.item.attributes.count;
    }

    if (this.item.attributes.countId !== undefined) {
      ProcessMaker.EventBus.$on(`sidebar-count-updated-${this.item.attributes.countId}`, (count) => {
        this.count = count;
      });
    }
  },
  methods: {
    pluralize(count) {
      if (count == 1) {
        return this.$t("{{count}} Item", { count });
      }
      return this.$t("{{count}} Items", { count });
    },
    toggle() {
      this.isOpen = !this.isOpen;
    },
    expanded() {
      return this.$parent.expanded;
    },
    maskStyle(file) {
      return {
        backgroundColor: "currentColor",
        WebkitMaskImage: `url(${file})`,
        maskImage: `url(${file})`,
        WebkitMaskRepeat: "no-repeat",
        maskRepeat: "no-repeat",
        WebkitMaskPosition: "center",
        maskPosition: "center",
        WebkitMaskSize: "contain",
        maskSize: "contain",
      };
    },
  },
};
</script>

<style>

</style>
