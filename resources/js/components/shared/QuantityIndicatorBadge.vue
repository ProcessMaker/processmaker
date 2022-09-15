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

</style>