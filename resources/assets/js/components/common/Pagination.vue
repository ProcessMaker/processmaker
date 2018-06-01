<template>
  <div v-show="tablePagination && tablePagination.last_page > 1" :class="css.wrapperClass">
    <div @click="loadPage(1)"
      :class="['pagination-nav-item', css.linkClass, isOnFirstPage ? css.disabledClass : '']">
        <i class="fas fa-angle-double-left"></i>
    </div>
    <div @click="loadPage('prev')"
      :class="['pagination-nav-item', css.linkClass, isOnFirstPage ? css.disabledClass : '']">
        <i class="fas fa-angle-left"></i>
    </div>
    <template v-if="notEnoughPages">
      <template v-for="n in totalPage">
        <div @click="loadPage(n)"
          :class="['pagination-nav-item', css.pageClass, isCurrentPage(n) ? css.activeClass : '']"
          v-html="n">
        </div>
      </template>
    </template>
    <template v-else>
      <template v-for="n in windowSize">
        <div @click="loadPage(windowStart+n-1)"
          :class="['pagination-nav-item', css.pageClass, isCurrentPage(windowStart+n-1) ? css.activeClass : '']"
          v-html="windowStart+n-1">
        </div>
      </template>
    </template>
    <div @click="loadPage('next')"
      :class="['pagination-nav-item', css.linkClass, isOnLastPage ? css.disabledClass : '']">
        <i class="fas fa-angle-right"></i>
    </div>
    <div @click="loadPage(totalPage)"
      :class="['pagination-nav-item', css.linkClass, isOnLastPage ? css.disabledClass : '']">
        <i class="fas fa-angle-double-right"></i>
    </div>
  </div>

</template>

<script>
import PaginationMixin from 'vuetable-2/src/components/VuetablePaginationMixin.vue'


export default {
    mixins: [
        PaginationMixin
    ]
}
</script>

<style lang="scss" scoped>
@import 'resources/assets/sass/_variables.scss';

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

    &:hover {
        background-color: #e9edf1;
    }
}



</style>


