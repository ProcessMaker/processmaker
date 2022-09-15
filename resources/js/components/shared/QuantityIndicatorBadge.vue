<template>
    <div>
       {{ visibleList }}
        <div 
            v-if="quantityIndicator > 0" 
            v-b-tooltip.hover.top="hiddenList" 
            class="quantity-badge"
        >
            {{quantityIndicator}}
        </div>
    </div>
</template>

<script>
export default {
    props: ['value'],
    computed: {
        visibleList() {
            if (!this.value) {
                return;
            }
            return this.value.visible;
        },
        hiddenList() {
            if (!this.value) {
                return;
            }
            return this.value.hidden;
        },
        quantityIndicator() {
            if (!this.value) {
                return;
            }
            return '+' + this.value.hiddenQuantityIndicator;
        }
    }
}
</script>

<style>
    .quantity-badge {
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