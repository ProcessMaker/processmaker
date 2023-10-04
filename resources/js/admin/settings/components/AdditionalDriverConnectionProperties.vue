<template>
    <div>
        <excel-connection-properties v-if="driverKey === 'cdata.excel'" :formData="formData" @updateFormData="updateFormData"></excel-connection-properties>
        <github-connection-properties v-if="driverKey === 'cdata.github'" :formData="formData" @updateFormData="updateFormData"></github-connection-properties>
    </div>
</template>

<script>

import ExcelConnectionProperties from './ExcelConnectionProperties.vue';
import GithubConnectionProperties from './GithubConnectionProperties.vue';
export default {
    components: {ExcelConnectionProperties, GithubConnectionProperties},
    props: ['driverKey', 'formData'],   
    methods: {
        updateFormData(val) {
            this.$emit('updateFormData', val);
        }
    }
}
</script>