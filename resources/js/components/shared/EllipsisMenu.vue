<template>
  <div class="dropdown-container">
    <b-dropdown
      variant="ellipsis"
      no-caret
      no-flip
      lazy
      class="ellipsis-dropdown-main"
    >
      <template #button-content>
        <i class="fas fa-ellipsis-h" />
      </template>
      <b-dropdown-item
        v-for="action in filterPermissions"
        v-show="action.conditional ? action.conditional : true"
        :key="action.value"
        class="ellipsis-dropdown-item pl-0 mb-1 mx-auto"
        @click="onClick(action, data)"
      >
        <div class="ellipsis-dropdown-content">
        <i class="pr-1" :class="action.icon"/>
        <span>{{ action.content }}</span>
        </div>
      </b-dropdown-item>
    </b-dropdown>
  </div>
</template>

<script>
export default {
  components: { },
  filters: { },
  mixins: [],
  props: ["actions", "permission", "data", "isDocumenterInstalled"],
  data() {
    return {
        active: false,
    };
  },
  computed: {
    filterPermissions() {
    const allActions = this.actions;
    const userPermissions = this.permission;
    const result = allActions.filter(item => userPermissions.includes(item.permission));
    return result;
    }
  },
  created() {
  },
  mounted() {
  },
  methods: {
    onClick(action, data) {
      this.$emit("navigate", action, data);
    },
  },
};
</script>

<style lang="scss">
@import "../../../sass/colors";

.ellipsis-dropdown-main {
  float: right;
}

.ellipsis-dropdown-item {
    border-radius: 4px;
    width: 95%;
}

.ellipsis-dropdown-content {
    color: #42526E;
    font-size: 14px;
    margin-left: -15px;
}

</style>
