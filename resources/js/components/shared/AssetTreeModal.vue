<template>
  <div>
    <b-modal id="linked-assets-modal" title="Linked Assets" size="lg">
      <template v-slot:modal-title>
        <div>{{ $t('Linked assets') }}</div>
        <small class="text-muted subtitle">
          The following assets refer to <strong> {{ assetName }} </strong> as part of their design. If <strong> {{ assetName }} </strong> is updated with this import, it will also update for these linked assets.
        </small>
      </template>
      <div class="overflow-modal">
        <data-tree v-for="group in groups" :key="group.type" :data="formatGroupData(group)" :collapsable="false" :show-icon="true" :show-children-icon="false"/>
      </div>
    </b-modal>
  </div>
</template>

<script>
import DataTree from "./DataTree.vue";

export default {
  props: ["groups", "assetName"],
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
      return tree;
    },
    formatGroupData(group) {
      const formattedGroupChildren = group.items.map((item) => {
        item.html = `
          <div>Name: ${item.name}</div>
          <div>Last modified: ${item.updated_at} By: <a href="/profile/${item.lastModifiedById}">${item.lastModifiedBy}</a></div>
        `;
        if (item.assetLink) {
          item.html += `<div><a href="${item.assetLink}" target="_blank"><i class="mr-1 fas fa-external-link-alt"></i> View ${item.typeHuman}</a></div>`;
        }
        return item;
      });

      return {
        isRoot: true,
        icon: `${group.icon}`,
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
  .overflow-modal {
    max-height: 70vh;
    overflow-y: auto;
  }
</style>