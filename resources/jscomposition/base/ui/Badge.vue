<template>
    <span :class="`tw-inline-flex tw-items-center
        tw-text-sm tw-border-1 tw-rounded-lg tw-border tw-border-${color}-300
        tw-bg-${color}-50 tw-px-2 tw-py-1 tw-text-${color}-500`">
        <slot></slot>
    </span>
</template>

<script>
import { defineComponent } from 'vue';
export default defineComponent({
    props:{
        color:{
            type:String,    
            default: ()=> 'gray'
        }
    },
    setup(){
        return{};
    }
})
</script>