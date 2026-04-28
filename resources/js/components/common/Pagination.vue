<template>
  <div
    v-if="tablePagination && tablePagination.last_page > 0"
    class="w-100 d-flex my-2 px-2"
  >
    <div class="pt-1 mr-auto">
      <div
        v-if="tablePagination"
        class="pagination"
      >
        {{ tablePagination.from + 1 }} - {{ tablePagination.to }} {{ $t('of') }} {{ tablePagination.total }} {{ title }}
      </div>
      <div
        v-if="tablePagination && tablePagination.last_page < 1"
        class="pagination"
      >
        {{ tablePagination.total }} {{ title }}
      </div>
    </div>
    <div class="justify-content-end button-pagination">
      <div
        v-show="tablePagination"
        :class="css.wrapperClass"
        role="navigation"
        :aria-label="$t('Pagination')"
      >
        <div
          :class="['pagination-nav-item', css.linkClass, isOnFirstPage ? css.disabledClass : '']"
          @click="loadPage(1)"
        >
          <i class="fas fa-angle-double-left" />
        </div>
        <div
          :class="['pagination-nav-item', css.linkClass, isOnFirstPage ? css.disabledClass : '']"
          @click="loadPage('prev')"
        >
          <i class="fas fa-angle-left" />
        </div>
        <template v-if="notEnoughPages">
          <template v-for="n in totalPage">
            <div
              :class="['pagination-nav-item', css.pageClass, isCurrentPage(n) ? css.activeClass : '']"
              :aria-current="(isCurrentPage(n) ? $t('Page') : '')"
              @click="loadPage(n)"
              v-html="n"
            />
          </template>
        </template>
        <template v-else>
          <template v-for="n in windowSize">
            <div
              :class="['pagination-nav-item', css.pageClass, isCurrentPage(windowStart+n-1) ? css.activeClass : '']"
              :aria-current="(isCurrentPage(windowStart+n-1) ? $t('Page') : '')"
              @click="loadPage(windowStart+n-1)"
              v-html="windowStart+n-1"
            />
          </template>
        </template>
        <div
          :class="['pagination-nav-item', css.linkClass, isOnLastPage ? css.disabledClass : '']"
          @click="loadPage('next')"
        >
          <i class="fas fa-angle-right" />
        </div>
        <div
          :class="['pagination-nav-item', css.linkClass, isOnLastPage ? css.disabledClass : '']"
          @click="loadPage(totalPage)"
        >
          <i class="fas fa-angle-double-right" />
        </div>
        <select
          v-if="perPageSelectEnabled"
          v-model="perPage"
          class="pagination-nav-item pagination-nav-drop"
          :aria-label="$t('Per page')"
        >
          <option value="15">
            15
          </option>
          <option value="30">
            30
          </option>
          <option value="50">
            50
          </option>
        </select>
      </div>
    </div>
  </div>
</template>

<script>
import PaginationMixin from "./mixins/vuetablePaginationMixin";

export default {
  mixins: [PaginationMixin],
  props: ["perPageSelectEnabled", "single", "plural"],
  data() {
    return {
      perPage: 15,
    };
  },
  computed: {
    title() {
      if (this.tablePagination.total == 1) {
        return this.single;
      }
      return this.plural;
    },
  },
  watch: {
    perPage(value) {
      this.$emit("changePerPage", value);
    },
  },
};
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";
.meta {
  font-size: 12px;
  color: #788793;
}
.meta {
  font-size: 12px;
  color: #788793;
}
.pagination-nav-item {
  background-color: $body-bg;
  width: 29px;
  height: 29px;
  margin: 1px;
  font-size: 12px;
  line-height: 29px;
  text-align: center;
  cursor: pointer;
  border-radius: 2px;
  color: #788793;
  &.active {
    background-color: #e9edf1;
  }
  &.disabled {
    cursor: not-allowed;
  }
  &:hover {
    background-color: white;
  }
}
.pagination-nav-drop {
  width: 40px;
}
</style>
