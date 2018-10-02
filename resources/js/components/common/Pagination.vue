<template>
  <div class="row">
    <div class="col-md-6 col-sm-12 d-flex">
      <div v-if="tablePagination" class="meta">
        {{tablePagination.from + 1}} - {{tablePagination.to}} of {{tablePagination.total}} {{title}}
      </div>
    </div>
    <div class="col-md-6 col-sm-12 d-flex justify-content-end">
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
        <select v-if="perPageSelectEnabled" v-model="perPage" class="pagination-nav-item pagination-nav-drop">
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
        </select>
      </div>
    </div>
  </div>

</template>

<script>
    import PaginationMixin from 'vuetable-2/src/components/VuetablePaginationMixin.vue'


    export default {
        mixins: [
            PaginationMixin
        ],
        props: [
            'perPageSelectEnabled',
            'single',
            'plural'
        ],
        data() {
            return {
                perPage: 10
            }
        },
        computed: {
            title() {
                if(this.tablePagination.total == 1) {
                    return this.single
                }
                return this.plural
            }
        },
        watch: {
            perPage(value) {
                this.$emit('changePerPage', value);
            }
        }
    }
</script>

<style lang="scss" scoped>
 @import 'resources/sass/_variables.scss';

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


