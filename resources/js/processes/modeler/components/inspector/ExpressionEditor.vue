<template>
    <FormInput :value="propertyGetter" @input="propertySetter" :label="$t(label)" :helper="helper" />
</template>


<script>
export default {
    props: ["value", "label", "helper", "property"],
    data() {
        return {};
    },
    computed: {
        /**
         * Get the value of the edited property
         */
        propertyGetter() {
            const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
            const value = _.get(node, this.property);
            return value;
        }
    },
    methods: {
        /**
         * Update the value of the editer property
         */
        propertySetter (value) {
            const node = this.$parent.$parent.$parent.$parent.highlightedNode.definition;
            _.set(node, this.property, value);
            this.$emit('input', this.value);
        },
    }
};
</script>

<style lang="scss" scoped>
</style>