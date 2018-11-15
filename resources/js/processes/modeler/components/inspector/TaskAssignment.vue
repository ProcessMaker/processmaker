<template>
    <div class="form-group">
        <label>{{label}}</label>
        <select class="form-control" :value="assignmentGetter" @input="assignmentSetter">
            <option value=""></option>
            <option value="requestor">To requestor</option>
        </select>
    </div>
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
        assignmentGetter() {
            const node = this.$parent.$parent.inspectorNode;
            const value = _.get(node, 'assignment');
            return value;
        },
        node () {
            return this.$parent.$parent.inspectorNode;
        }
    },
    methods: {
        /**
         * Update the event of the editer property
         */
        assignmentSetter (event) {
            Vue.set(this.node, 'assignment', event.target.value);
            this.$emit('input', this.value);
        },
    }
};
</script>

<style lang="scss" scoped>
</style>