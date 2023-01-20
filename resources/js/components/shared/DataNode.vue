<template>
  <div v-if="node.isRoot">
    <div class="flex-column d-inline-flex node" :class="{'node': node.children && node.children.length && collapsable}" @click="toggleChildren">
      <span>
        <i v-if="node.children && node.children.length && collapsable" class="fa" 
            :class="showChildren ? 'fa-caret-down' : 'fa-caret-right'"/> 
        <i v-if="icon" class="text-secondary" :class="'fas fa-' + icon"/> {{ node.label }}
      </span>
    </div>

    <ul v-if="node.children && node.children.length && (showChildren || !collapsable)" class="tree ml-4">
      <node v-for="(child, key) in node.children" :key="key" :node="child" :collapsable="collapsable"/>
    </ul>
  </div>

  <li v-else >
    <div class="flex-column d-inline-flex" 
        :id="node.uuid" 
        :class="{'highlight m-0 py-2 px-3 rounded-sm': highlightedNode === node.uuid, 'node': node.children && node.children.length && collapsable}"
        @click="toggleChildren">
      <span :class="highlightedNode === node.uuid ? 'mb-1' : 'mb-2'">
        <i v-if="node.children && node.children.length && collapsable" class="fa" 
          :class="showChildren ? 'fa-caret-down' : 'fa-caret-right'"/>
        <i v-if="icon" class="text-secondary" :class="'fas fa-' + icon"/> {{ node.label }}
      </span>
    </div>

    <ul v-if="node.children && node.children.length && (showChildren || !collapsable)" class="tree ml-4">
      <node v-for="(child, key) in node.children" :key="key" :node="child" :collapsable="collapsable"/>
    </ul>
    <sup v-if="node.link">
      <b-link @click="highlightNode(node.link)" :href="'#' + node.link">
        <i class="mr-1 fas fa-external-link-alt"/>View {{ node.type }}
      </b-link>
    </sup>
  </li>
</template>

<script>

import ImportExportIcons from "./ImportExportIcons";

export default {
  name: "node",
  props: {
    collapsable: Boolean,
    node: {
      type: Object,
      default() {
        return {
          label: "",
          icon: "",
          isRoot: true,
          link: null,
          uuid: null,
          children: [],
        };
      },
    },
  },
  data() {
    return {
      highlightedNode: "",
      showChildren: false,
    };
  },
  created() {
    window.ProcessMaker.EventBus.$on("highlight-node", (link) => {
      this.highlightedNode = "";

      if (link === this.node.uuid) {
        this.showChildren = true;
      }

      this.highlightedNode = link;
    });
  },
  computed: {
    icon() {
      return ImportExportIcons.ICONS[this.node.objectType];
    },
  },
  methods: {
    highlightNode(link) {
      window.ProcessMaker.EventBus.$emit("highlight-node", link);
    },
    toggleChildren() {
      this.showChildren = !this.showChildren;
      if (!this.collapsable) {
        this.showChildren = true;
      }
    },
  },
};
</script>
<style scoped>
.highlight {
    background: #e9edf1;
    animation-name: highlight-animation;
    animation-duration: 0.3s;
}

.node:hover {
    background: #f4f4f5;
    border-radius: 0.2rem;
    cursor: pointer;
}

@keyframes highlight-animation {
  from {background-color: #FFAB00;}
  to {background-color: #e9edf1;}
}
</style>
