<template>
  <div>
    <b-dropdown
      no-caret
      no-flip
      lazy
    >
      <template #button-content>
        <i class="fas fa-ellipsis-h" />
      </template>
       <b-dropdown-item href="#">Action</b-dropdown-item>
      <!-- <b-dropdown-item
        class="btn-white"
        v-for="action in actions"
        :key="action.value"
        :content="action.content"
      >
        <i class="fas fa-play fa-md fa-fw"/>
        {{ action.content }}
      </b-dropdown-item> -->
    </b-dropdown>
  </div>
</template>

<script>
//   import datatableMixin from "../../components/common/mixins/datatable";
//   import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
//   import { createUniqIdsMixin } from "vue-uniq-ids";
//   const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { },
  filters: { },
  mixins: [],
  props: ["actions"],
  data() {
    return {
    };
  },
  computed: {},
  created() {
  },
  methods: {
  },
};
</script>

<style lang="scss" scoped>
.btn-ellipsis {
    background-color: #EBEEF2;
    border-color: #EBEEF2;
}

</style>
