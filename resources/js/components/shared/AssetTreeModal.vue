<template>
  <div>
    <b-modal id="asset-tree" title="Asset Tree" size="lg">
      <data-tree v-for="group in groups" :key="group.type" :data="formatGroupData(group)" :collapsable="false" :show-icon="true" :show-children-icon="false"/>
    </b-modal>
  </div>
</template>

<script>
import DataTree from "./DataTree.vue";

export default {
  props: ["groups"],
  components: {
    DataTree,
  },
  data() {
    return {
      treeNodesVisited: new Set(),
    };
  },
  methods: {
    tree() {
      const tree = this.treeNode(this.$root.rootUuid);
      tree.isRoot = true;
      console.log('tree', tree);
      return tree;
    },
    formatGroupData(group) {
      const formattedGroupChildren = group.items.map((item) => {
        item.html = `
          <div>Name: ${item.name}</div>
          <div>Last modified: ${item.updated_at} By: <a href="/profile/${item.lastModifiedById}">${item.lastModifiedBy}</a></div>
          <div><a href="/profile/${item.lastModifiedById}">View ${item.typeHuman} <i class="ml-1 fas fa-external-link-alt"></i></a></div>
        `;
        return item;
      });

      return {
        isRoot: true,
        icon: `fas fa-${group.icon}`,
        label: group.typeHumanPlural,
        children: formattedGroupChildren,
      };
    },
  },
};
</script>

<style lang="scss" scoped>
  .box {
    height: 500px;
    overflow: auto;
    border: 1px solid #ccc;
  }
</style>