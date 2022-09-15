<template>
    <div>
       {{ visibleCategoryList }}
        <div v-if="value.quantity > 0" v-b-tooltip.hover.top class="quanity-badge" :title="hiddenCategorylist" v-text="'+' + quantityIndicator"></div>
    </div>
</template>

<script>
export default {
    props: ['value'],
    computed: {
        visibleCategoryList() {
            if (!this.value) {
                return;
            }
            return this.value.visibleCategories;
        },
        hiddenCategorylist() {
            if (!this.value) {
                return;
            }
            return this.value.hiddenCategories;
        },
        quantityIndicator() {
            if (!this.value) {
                return;
            }
            return this.value.hiddenQuantityIndicator;
        }
    }
}
</script>

<style>
    .quanity-badge {
        margin-left: 3px;
        background: #e8f7fbcf;
        border-radius: 100%;
        width: 26px;
        height: 26px;
        display: inline-block;
        font-size: 12px;
        text-align: center;
        font-weight: bold;
        border: 1px solid white;
        line-height: 25px;
  }
</style>