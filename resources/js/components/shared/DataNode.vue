<template>
  <div v-if="node.isRoot">
    <div class="flex-column d-inline-flex">
      <span>
        <i v-if="node.children && node.children.length" class="fa fa-caret-down"/> 
        <i v-if="icon" class="text-secondary" :class="icon"/> {{ node.label }}
      </span>
    </div>

    <ul v-if="node.children && node.children.length" class="tree ml-4">
      <node v-for="(child, key) in node.children" :key="key" :node="child" />
    </ul>
  </div>

  <li v-else >
    <div class="flex-column d-inline-flex" :id="node.uuid" :class="highlightedNode === node.uuid ? 'highlight m-0 py-2 px-3 rounded-sm' : ''">
      <span :class="highlightedNode === node.uuid ? 'mb-1' : 'mb-2'">
        <i v-if="node.children && node.children.length" class="fa fa-caret-down"/>
        <i v-if="icon" class="text-secondary" :class="icon"/> {{ node.label }}
      </span>
    </div>

    <ul v-if="node.children && node.children.length" class="tree ml-4">
      <node v-for="(child, key) in node.children" :key="key" :node="child" />
    </ul>
    <sup v-if="node.link">
      <b-link @click="highlightNode(node.link)" :href="'#' + node.link">
        <i class="mr-1 fas fa-external-link-alt"/>View {{ node.type }}
      </b-link>
    </sup>
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
      icons: {
        Screen: "fas fa-file-alt",
        CommentConfiguration: "fas fa-comments",
        DataSource: "fas fa-cog",
        Collection: "fas fa-database",
        Vocabulary: "fas fa-book",
        Group: "fas fa-users",
        User: "fas fa-user",
        Process: "fas fa-play-circle",
        Script: "fas fa-code",
        SavedSearch: "fas fa-table",
        SavedSearchChart: "fas fa-chart-line",
        SavedSearchOption: "fas",
        SavedSearchReport: "fas fa-clock",
      },
    };
  },
  created() {
    window.ProcessMaker.EventBus.$on("highlight-node", (link) => {
      this.highlightedNode = "";
      this.highlightedNode = link;
    });
  },
  computed: {
    icon() {
      return this.icons[this.node.objectType];
    },
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
    animation-name: highlight-animation;
    animation-duration: 0.3s;
}

@keyframes highlight-animation {
  from {background-color: #FFAB00;}
  to {background-color: #e9edf1;}
}
</style>
