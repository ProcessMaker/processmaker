<template>
  <div v-if="node.isRoot">
    <div class="flex-column d-inline-flex">
      <span><i v-if="node.icon" class="text-secondary" :class="node.icon" /> {{ node.label }}</span>
    </div>

    <ul v-if="node.children && node.children.length" class="tree ml-4">
      <node v-for="(child, key) in node.children" :key="key" :node="child" />
    </ul>
  </div>

  <li v-else >
    <div class="flex-column d-inline-flex" :id="node.uuid" :class="highlightedNode === node.uuid ? 'highlight' : ''">
      <span :class="highlightedNode === node.uuid ? 'mb-1' : 'mb-2'"><i v-if="node.icon" class="text-secondary" :class="node.icon" /> {{ node.label }}</span>
    </div>

    <ul v-if="node.children && node.children.length" class="tree ml-4">
      <node v-for="(child, key) in node.children" :key="key" :node="child" />
    </ul>
    <div v-if="node.link">
      <b-link @click="highlightNode(node.link)" :href="'#' + node.link">
        <i class="mr-1 fas fa-external-link-alt"/>View {{ node.type }}
      </b-link>
    </div>
  </li>
</template>

<script>
export default {
  name: "node",
  props: {
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
    };
  },
  created() {
    window.ProcessMaker.EventBus.$on("highlight-node", (link) => {
      this.highlightedNode = "";
      this.highlightedNode = link;
    });
  },
  methods: {
    highlightNode(link) {
      window.ProcessMaker.EventBus.$emit("highlight-node", link);
    },
  },
};
</script>
<style scoped>
.highlight {
    background: #e9edf1;
    border-radius: 5px;
    padding: 3px 5px 0px 5px;
    animation-name: highlight-animation;
    animation-duration: 0.3s;
}
@keyframes highlight-animation {
  from {background-color: #FFAB00;}
  to {background-color: #e9edf1;}
}
</style>
