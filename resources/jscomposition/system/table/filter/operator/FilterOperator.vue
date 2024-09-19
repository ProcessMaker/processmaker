<template>
  <div class="tw-space-y-2 tw-text-xs">
    <slot name="header"></slot>

    <Dropdown
      v-model="operator"
      @change="onChangeOperator"
      :options="operatorsModel">
    </Dropdown>

    <component
      placeholder="Type value"
      class="tw-flex-1"
      :is="operatorType?.component()"
      @change="(e) => $emit('change', e)" />
  </div>
</template>
<script>
import { Dropdown } from "../../../../base";
import { defineComponent, computed, ref, onMounted } from "vue";
import { getOperatorType } from "./operatorConfig";

const defaultOperators = [
  {
    value: "=",
    label: "=",
  },
  {
    value: ">",
    label: ">",
  },
  {
    value: ">=",
    label: ">=",
  },
  {
    value: "<",
    label: ">",
  },
  {
    value: "<=",
    label: "<=",
  },
  {
    value: "between",
    label: "between",
  },
  {
    value: "in",
    label: "in",
  },
  {
    value: "contains",
    label: "contains",
  },
  {
    value: "regex",
    label: "regex",
  },
];

export default defineComponent({
  components: {
    Dropdown,
  },
  props: {
    /**
     * operator: "="
     * value: null
     * type: 'string' || 'number'
     * 
     * {
        operator: String,
        value: Object,
        type: String,
      }
     */
    value: {
      type: [Array, Object],
    },
    operators: {
      type: Array,
      default: () => [],
    },
  },
  setup(props, { emit }) {
    const operatorsModel = ref(defaultOperators);

    const model = computed(() => {
      return props.value;
    });

    const operator = ref({
      value: "=",
      label: "=",
    });

    const operatorType = ref();

    const onChangeOperator = (val) => {
      operatorType.value = getOperatorType(val.value, "string");
    };

    onMounted(() => {
      operatorType.value = getOperatorType();

      operatorsModel.value = 
      defaultOperators.filter((op) => {
        if (!props.operators.length) {
          return op;
        }

        return props.operators.includes(op.value);
      });
    });

    return {
      operator,
      operatorType,
      operatorsModel,
      onChangeOperator,
    };
  },
});
</script>
