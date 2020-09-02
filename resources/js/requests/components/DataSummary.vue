<template>
    <div>
        <b-table :items="formattedData" :fields="fields"></b-table>
    </div>
</template>

<script>
export default {
    props: {
        summary: [Object, Array],
    },
    data() {
        return {
            formattedData: [],
            fields: [
                {
                    key: 'key',
                    label: this.$t('Key'),
                },
                {
                    key: 'value',
                    label: this.$t('Value'),
                }
            ]
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
                    this.formattedData.push({
                        key: prefix + key,
                        value: item,
                    });
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