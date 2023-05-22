<template>
  <div>
    <table role="table" class="table b-table">
      <thead role="rowgroup">
        <tr role="row">
          <th role="columnheader" scope="col" aria-colindex="1">
            <div>{{ $t('Key') }}</div>
          </th>
          <th role="columnheader" scope="col" aria-colindex="2">
            <div>{{ $t('Value') }}</div>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in formattedData" :key="row.key">
          <td aria-colindex="1" role="cell">{{ row.key }}</td>
          <td aria-colindex="2" role="cell">{{ row.value }}</td>
        </tr>
      </tbody>
    </table>
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