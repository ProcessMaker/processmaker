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
                <tr v-for="(item, key) in formattedData" :key="key">
                    <td>{{ key }}</td>
                    <td>{{ item }}</td>
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
        return {
            formattedData: {}
        }
    },
    mounted() {
        this.formatData("", this.summary);
    },
    methods: {
        formatData(prefix, items) {
            for (const key in items) {
                const item = items[key];
                if (this.isObject(item)) {
                    this.formatData(prefix + key + ".", item);
                } else {
                    this.formattedData[prefix + key] = item
                }
            }
        },
        isObject(item) {
            // Arrays or Objects
            return item && typeof item === 'object';
        },
    }
}
</script>