<template>
  <div class="dropdown-container">
    <b-dropdown
      variant="light"
      no-caret
      no-flip
      lazy
    >
      <template #button-content>
        <i class="fas fa-ellipsis-h" />
      </template>
      <b-dropdown-item
        v-for="action in filterPermissions"
        :key="action.value"
        class="dropdown-item pl-0 mb-1 mx-auto"
        @click="onClick(action, data)"
      >
        <div class="dropdown-content">
        <i class="pr-1" :class="action.icon"/>
        <span>{{ action.content }}</span>
        </div>
      </b-dropdown-item>
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
  props: ["actions", "permission", "data"],
  data() {
    return {

    };
  },
  computed: {
    filterPermissions() {
    const allActions = this.actions;
    //   console.log('allActions', allActions);
      const userPermissions = this.permission;
    //   console.log('userPermissions', userPermissions);
      const result = allActions.filter(item => userPermissions.includes(item.permission));
    //   console.log('result', result);
      return result;
    }
  },
  created() {
  },
  mounted() {
    // console.log('data in menu', this.data);
  },
  methods: {
    onClick(action, data) {
    //   console.log('action', action);
    //   console.log('data in button', data);
      this.$emit("navigate", action, data);
    },
  },
};
</script>

<style lang="scss" scoped>
.hover-class {
    color: #6C757D;
    background-color: #EBEEF2;
}

.active-class {
    color: #FFFFFF;
    background-color: #104A75;
}

.dropdown-item {
    border-radius: 4px;
    width: 95%;
}

.dropdown-content {
    // margin-left: 0;
    // padding-left: 0;
    color: #42526E;
    font-size: 14px;
    margin-left: -15px;
}

</style>
