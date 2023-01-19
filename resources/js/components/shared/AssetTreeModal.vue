<template>
  <div>
    <b-modal id="asset-tree" title="Asset Tree" size="lg">
      <data-tree :data="tree()"/>
    </b-modal>
  </div>
</template>

<script>
import DataTree from "./DataTree.vue";

export default {
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
    treeNode(uuid, dependentType = null) {
      this.treeNodesVisited.add(uuid);
      const asset = this.$root.manifest[uuid];
      return {
        uuid: uuid,
        label: asset.name,
        objectType: asset.type,
        dependentType,
        children: asset.dependents.map((dependent) => {
          const uuid = dependent.uuid;
          const childDependentType = dependent.type;
          if (this.treeNodesVisited.has(uuid)) {
            // return a link instead so we don't end up in an infinite loop
            const visitedAsset = this.$root.manifest[uuid];
            return {
              link: uuid,
              dependentType: childDependentType,
              type: visitedAsset.type,
              label: visitedAsset.name,
              children: [],
            };
          }
          return this.treeNode(uuid, childDependentType);
        }),
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