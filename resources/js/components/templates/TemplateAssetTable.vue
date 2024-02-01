<template>
  <div class="mt-4 mb-5 data-card-container">
    <b-table-simple
      v-for="group in filteredAssetGroups"
      :id="`group-${group.type}table`"
      :key="group.type"
      :name="`${group.type}-table`"
      class="simple-table"
    >
      <colgroup><col><col></colgroup>
      <colgroup><col><col></colgroup>
      <colgroup><col><col></colgroup>
      <colgroup><col><col></colgroup>
      <b-thead>
        <b-tr>
          <b-td
            class="border-top-0 column-width"
            colspan="2"
          />
          <b-td
            v-for="action in actions"
            :key="action.value"
            class="border-top-0 text-center"
          >
            {{ action.label }}
          </b-td>
        </b-tr>
        <b-tr class="card-header border-left border-right">
          <b-th
            class="align-middle column-width"
            colspan="2"
          >
            <div>
              <i
                class="d-inline align-middle mr-1 fas"
                :class="group.icon"
              />
              <h5 class="d-inline align-middle">
                {{ formatName(group.type) }}
              </h5>
            </div>
          </b-th>
          <b-td
            v-for="action in actions"
            :key="`${group.type}-${action.value}`"
            class="text-center align-middle"
          >
            <b-form-group :id="`group-${group.type}-${action.value}-action`" />
            <b-form-radio
              v-model="group.mode"
              :value="action.value"
              :name="`${group.type}-${action.value}`"
              @change="setGroupAction(group, action)"
            />
          </b-td>
        </b-tr>
      </b-thead>
      <b-tbody>
        <b-tr
          v-for="asset in group.items"
          :key="asset.uuid"
          class="border-left border-right border-bottom"
        >
          <b-td
            class="align-middle"
            colspan="2"
          >
            {{ asset.name }}
          </b-td>
          <b-td
            v-for="action in actions"
            :key="`${group.type}-${asset.uuid}-${action.value}`"
            class="text-center align-middle"
          >
            <b-form-group>
              <b-form-radio
                v-model="asset.mode"
                :value="action.value"
                :name="`${group.type}-${asset.uuid}-${action.value}`"
                @change="setAssetAction(group, asset, action)"
              />
            </b-form-group>
          </b-td>
        </b-tr>
      </b-tbody>
    </b-table-simple>
  </div>
</template>

<script>
import ImportExportIcons from "../shared/ImportExportIcons";

export default {
  props: {
    assets: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      filteredAssetGroups: null,
      actions: [
        { label: "Update", value: "update" },
        { label: "Keep Previous", value: "discard" },
        { label: "Duplicate", value: "copy" },
      ],
    };
  },
  watch: {
    assets: {
      handler() {
        this.filteredAssetGroups = _.cloneDeep(this.filterAssetsByGroup());
      },
      deep: true,
    },
    filteredAssetGroups: {
      handler() {
        this.$emit("assetChanged", this.filteredAssetGroups);
      },
      deep: true,
    },
  },
  methods: {
    setGroupAction(group, action) {
      group.mode = action.value;
      group.items.forEach((item) => {
        item.mode = group.mode;
      });
    },
    setAssetAction(group, asset, action) {
      group.mode = null;
      asset.mode = action.value;
    },
    filterAssetsByGroup() {
      const groupedItems = [];

      this.assets.forEach((asset) => {
        const existingGroup = groupedItems.find((group) => group.type === asset.type);

        if (existingGroup) {
          existingGroup.items.push(asset);
          existingGroup.mode = "copy";
        } else {
          groupedItems.push({ type: asset.type, mode: "copy", items: [asset] });
        }
      });

      const icons = ImportExportIcons.ICONS;
      const groupedItemsWithIcons = groupedItems.map((item) => {
        const newItem = { ...item };
        const iconKey = icons[item.type];
        newItem.icon = iconKey;
        return newItem;
      });

      return groupedItemsWithIcons;
    },
    formatName(value) {
      return value.replace(/([a-z])([A-Z])/g, "$1 $2");
    },
  },
};
</script>

<style lang="scss" scoped>
.simple-table {
  margin-bottom: 2rem;
}

.column-width {
  width: 50%
}
</style>
