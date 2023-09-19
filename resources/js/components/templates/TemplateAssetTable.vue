<template>
  <div class="mt-4 mb-5 data-card-container">
    <b-table-simple v-for="group in filteredAssetGroups" :key="group.type" class="simple-table" :name="group.type + '-table'">
      <colgroup><col><col></colgroup>
      <colgroup><col><col></colgroup>
      <colgroup><col><col></colgroup>
      <colgroup><col><col></colgroup>
      <b-thead>
        <b-tr>
          <b-td class="border-top-0" colspan="2"/>
          <b-td v-for="action in actions" class="border-top-0 text-center" :key="action.value">
              {{ action.label }}
          </b-td>
        </b-tr>
        <b-tr class="card-header border-left border-right">
          <b-th class="align-middle" colspan="2">
            <div>
              <i class="fas fa-file-alt d-inline align-middle mr-1" />
              <h5 class="d-inline align-middle">{{ formatName(group.type) }}</h5>
            </div>
          </b-th>
          <b-td class="text-center align-middle" v-for="action in actions" :key="group.type + '-' + action.value">
            <b-form-group id="group-action">
              <b-form-radio v-model="group.mode" @input="setGroupActionInput(group, action)" @change="setGroupAction(group, action)" :value="action.value" :name="group.type + '-' + action.value"></b-form-radio>
            </b-form-group>
          </b-td>
        </b-tr>
      </b-thead>
      <b-tbody>
        <b-tr
          v-for="asset in group.items"
          :key="asset.name"
          class="border-left border-right border-bottom"
        >
          <b-td class="align-middle" colspan="2">
            {{ asset.name }}
          </b-td>
          <b-td class="text-center align-middle" v-for="action in actions" :key="group.type + '-' + asset.name + '-' + action.value">
            <b-form-group>
              <b-form-radio v-model="asset.mode" :value="action.value" :name="group.type + '-' + asset.name + '-' + action.value"></b-form-radio>
            </b-form-group>
          </b-td>
        </b-tr>
      </b-tbody>
    </b-table-simple>
  </div>
</template>

<script>

export default {
  components: {
  },
  mixins: [],
  props: ["assets"],
  data() {
    return {
      selected: 'duplicate',
      actions: [ 
        {label: "Update", value:'update'},
        {label: "Keep Previous", value:'keep'},
        {label: "Duplicate", value:'copy'},
      ],
    };
  },
  computed: {
    filteredAssetGroups() {
     return this.filterAssetsByGroup();
    },
  },
  watch: {
    filteredAssetGroups: {
      handler() {
        console.log("filteredAssetGroups", this.filteredAssetGroups);
        this.filteredAssetGroups.forEach(group => {
          console.log("ASSET TYPE", group);
        if (group.mode) {
          group.items.forEach(item => {
            console.log("ITEM", item);
            item.mode = group.mode;
          });
        }
      });
    },
    deep: true
   }
  },
  methods: {
    isGroupActionSelected(group, action) {
      console.log('check for default', action.value, group.mode);
      if (group.mode === action.value) {
        console.log('return true', group)
        return true;
      }
      return false;
    },
    setGroupActionInput(group, action){
      console.log('setGroupActionInput', group, action);
      group.mode = action.value;
    },
    setGroupAction(group, action) {
      console.log('group chnaged', group, action);
      group.mode = action.value;
      group.items.forEach(item => {
        item.mode = group.mode;
      });
    },
    filterAssetsByGroup() {
      // Initialize an empty array to store items grouped by 'type'
      const groupedItems = [];

      this.assets.forEach(asset => {
        // Check if the 'type' already exists in groupedItems
        const existingGroup = groupedItems.find(group => group.type === asset.type);

        if (existingGroup) {
          // If the 'type' exists, push the item into its items array
          existingGroup.items.push(asset);
          existingGroup.mode = 'copy';
        } else {
          // If the 'type' doesn't exist, create a new group and push the item
          groupedItems.push({ type: asset.type, mode: 'copy', items: [asset] });
        }
      });

      // Set the result in data property
      return groupedItems;
    },
    formatName(value) {
      return value.replace(/([a-z])([A-Z])/g, '$1 $2');
    },
  },
};
</script>

<style lang="scss" scoped>
.simple-table {
  margin-bottom: 2rem;
}
</style>
