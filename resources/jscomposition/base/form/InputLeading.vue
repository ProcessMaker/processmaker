<template>
    <div class="tw-relative tw-rounded-lg tw-shadow-sm tw-text-gray-500">
        <div class="tw-pointer-events-none tw-absolute tw-inset-y-0 tw-left-0 tw-flex tw-items-center tw-pl-3">
            <slot name="icon">
                <i :class="icon"></i>
            </slot>
        </div>
        <input
            class="tw-block tw-w-full tw-rounded-md tw-border-0 tw-py-2 tw-pl-10 
                tw-text-gray-900 tw-ring-1 tw-ring-inset tw-ring-gray-300 
                placeholder:tw-text-gray-400 focus:tw-ring-2 focus:tw-ring-inset
                 focus-visible:tw-outline-none
                 sm:tw-text-sm sm:tw-leading-6" :placeholder="placeholder">
    </div>
</template>
<script>
import { defineComponent } from 'vue';

export default defineComponent({
    props: {
        placeholder: {
            type: String,
            default: () => ""
        },
        icon: {
            type: String,
            default: () => ("fa-solid fa-magnifying-glass")
        }
    },
    setup(props, {emit}) {
        return {};
    }
});
</script>