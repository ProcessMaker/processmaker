<template>
  <div class="mt-4 mb-5 data-card-container">
    <b-table-simple>
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
              <h5 class="d-inline align-middle">Screens</h5>
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
        <b-tr class="border-left border-right border-bottom">
          <b-td class="align-middle" colspan="2">
            Lorem Ipsum Other Latin Words
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
  props: [],
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
  },
  mounted() {
  },
  methods: {
  },
};
</script>

<style lang="scss" scoped>

.low-elevation {
    box-shadow: 0px 1px 2px 0px rgb(60 64 67 / 25%), 0px 2px 6px 2px rgb(60 64 67 / 10%);
}

.data-card-header {
    display: inline;
}

.data-card-metadata {
    padding-left: 0;
}

.export-all {
    float: right;
}

.export-status-label {
    padding-right: 0;
}
</style>
