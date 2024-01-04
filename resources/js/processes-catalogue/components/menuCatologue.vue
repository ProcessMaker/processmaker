<template>
  <div>
    <div
      v-b-toggle.category-menu
      block
      variant="light"
      class="m-1"
    >
      <div class="d-flex justify-content-between pl-3 pr-3">
        <i :class="preicon" />
        {{ $t(title) }}
        <i class="fas fa-sort-down" />
      </div>
    </div>
    <b-collapse
      id="category-menu"
      visible
    >
      <b-list-group id="infinite-list">
        <b-list-group-item
          v-for="item in data"
          :key="item.id"
          :ref="item.name"
          class="list-item"
          @click="selectItem(item)"
        >
          {{ item.name }}
        </b-list-group-item>
      </b-list-group>
    </b-collapse>
    <div
      v-b-toggle.collapse-3
      block
      variant="light"
      class="m-1"
    >
      <div class="d-flex justify-content-between pl-3 pr-3">
        <img src="../../../img/template-icon.svg" alt="Template Icon">
        {{ $t("Add From Templates") }}
        <i class="fas fa-sort-down" />
      </div>
    </div>
    <b-collapse
      id="collapse-3"
      visible
    >
      <b-list-group>
        <b-list-group-item
          class="list-item"
          @click="wizardLinkSelected"
        >
          {{ $t("Guided Templates") }}
        </b-list-group-item>
      </b-list-group>
    </b-collapse>
  </div>
</template>

<script>
export default {
  props: ["data", "select", "title", "preicon"],
  mounted() {
    const listElm = document.querySelector("#infinite-list");
    listElm.addEventListener("scroll", () => {
      if (listElm.scrollTop + listElm.clientHeight >= listElm.scrollHeight) {
        this.loadMore();
      }
    });
  },
  methods: {
    /**
     * Adding categories
     */
    loadMore() {
      this.$emit("addCategories");
    },
    selectItem(item) {
      this.setSelectItem(item.name || item);
      this.select(item);
    },
    setSelectItem(item) {
      for (const item in this.$refs) {
        this.$refs[item][0].className = "list-item";
      }
      this.$refs[item][0].className = "list-item list-item-selected";
    },
    wizardLinkSelected() {
      this.$emit("wizardLinkSelect");
    },
  },
};
</script>

<style lang="scss" scoped>
@import url("../../../sass/_scrollbar.scss");
i {
  font-size: 20px;
  color: #6A7888;
}
.list-group {
  max-height: 37vh;
  min-height: 37vh;
  overflow-y: auto;
}
.list-group-item {
  background: #f7f9fb;
  border: none;
}
.list-item {
  cursor: pointer;
  padding-bottom: 0.25rem;
  padding-top: 0.25rem;
  padding-left: 1rem;
  margin-left: 1rem;
  margin-bottom: 0.25rem;
  color: #4F606D;
  font-weight: 400;
}
.list-item:hover {
  background: #E5EDF3;
}
.list-item-selected {
  background: #E5EDF3;
  color: #1572C2;
  font-weight: 700;
}
.fade-enter-active, .fade-leave-active {
  transition: opacity .5s
}
.fade-enter, .fade-leave-to {
  opacity: 0
}
</style>
