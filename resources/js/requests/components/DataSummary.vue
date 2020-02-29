<template>
    <div>
        <table class="vuetable table table-hover border-top-0 mb-0">
            <thead v-if="showHead">
                <tr>
                    <th class="border-top-0" scope="col">{{ $t('Key') }}</th>
                    <th class="border-top-0" scope="col">{{ $t('Value') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, key) in summary" :key="key">
                    <template v-if="shouldNest(item)">
                        <td colspan="2">
                            <div class="d-flex">
                                <div class="pr-2">{{ key }}</div>
                                <div class="border border-top-0 flex-grow-1">
                                    <data-summary :summary="item" :show-head="false"></data-summary>
                                </div>
                            </div>
                        </td>
                    </template>
                    <template v-else>
                        <td>{{ key }}</td>
                        <td>{{ item }}</td>
                    </template>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    props: {
        summary: [Object, Array],
        showHead: { type: Boolean, default: true }
    },
    data() {
        return {}
    },
    methods: {
        shouldNest(item) {
            // Arrays or Objects
            return item && typeof item === 'object';
        },
    }
}
</script>