<template>
  <div class="mt-4 mb-5 data-card-container">
    <b-table-simple
      v-for="groupType in groupAssets"
      :key="groupType.type"
      class="simple-table"
    >
      <colgroup><col><col></colgroup>
      <colgroup><col><col></colgroup>
      <colgroup><col><col></colgroup>
      <colgroup><col><col></colgroup>
      <b-thead>
        <b-tr>
          <b-td class="border-top-0" colspan="2"/>
          <b-td class="border-top-0 text-center">Update</b-td>
          <b-td class="border-top-0 text-center">Keep Previous</b-td>
          <b-td class="border-top-0 text-center">Duplicate</b-td>
        </b-tr>
        <b-tr class="card-header border-left border-right">
          <b-th class="align-middle" colspan="2">
            <div>
              <i class="fas fa-file-alt d-inline align-middle mr-1" />
              <h5 class="d-inline align-middle">{{ formatName(groupType[0].type) }}</h5>
            </div>
          </b-th>
          <b-td class="text-center align-middle">
            <b-form-group>
              <b-form-radio-group
                v-model="selected"
                :options="optionUpdate"
              />
            </b-form-group>
          </b-td>
          <b-td class="text-center align-middle">
            <b-form-group>
              <b-form-radio-group
                v-model="selected"
                :options="optionKeep"
              />
            </b-form-group>
          </b-td>
          <b-td class="text-center align-middle">
            <b-form-group>
              <b-form-radio-group
                v-model="selected"
                :options="optionDuplicate"
              />
            </b-form-group>
          </b-td>
        </b-tr>
      </b-thead>
      <b-tbody>
        <b-tr
          v-for="asset in groupType"
          :key="asset.name"
          class="border-left border-right border-bottom"
        >
          <b-td class="align-middle" colspan="2">
            {{ asset.name }}
          </b-td>
          <b-td class="text-center align-middle">
            <b-form-group>
              <b-form-radio-group
                v-model="selected"
                :options="optionUpdate"
              />
            </b-form-group>
          </b-td>
          <b-td class="text-center align-middle">
            <b-form-group>
              <b-form-radio-group
                v-model="selected"
                :options="optionKeep"
              />
            </b-form-group>
          </b-td>
          <b-td class="text-center align-middle">
            <b-form-group>
              <b-form-radio-group
                v-model="selected"
                :options="optionDuplicate"
              />
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
      fields: ["Update", "Keep Previous", "Duplicate"],
      selected: 'duplicate',
      optionUpdate: [
        { text: "", value: 'update' },
      ],
      optionKeep: [
        { text: "", value: 'keep' },
      ],
      optionDuplicate: [
        { text: "", value: 'duplicate' },
      ],
    };
  },
  computed: {
    groupAssets() {
      const assets = JSON.parse(JSON.stringify(this.assets));

      const groupType = assets.reduce((group, asset) => {
        const { type } = asset;
        group[type] = group[type] ?? [];
        group[type].push(asset);
        return group;
      }, {});

      console.log('GROUPTYPE', groupType);
      return groupType;
    },
  },
  mounted() {
  },
  methods: {
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
