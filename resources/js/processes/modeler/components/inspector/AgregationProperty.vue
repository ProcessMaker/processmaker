<template>
  <div>
    <span class="text-lg font-bold mb-2">
      {{ $t("Stage Aggregation") }}
    </span>
    <p class="text-sm mb-4">
      {{ $t("You can display an aggregation of a variable across the stages") }}
    </p>
    <label>{{ $t("Variable Name") }}</label>
    <input
      class="form-control"
      :class="{ 'is-invalid': !stateAggregationVariable }"
      type="text"
      :placeholder="$t('Total Amount')"
      :value="agregationVariable"
      @input="saveVariableDebounced"
    >
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { debounce } from "lodash";
const javascriptReservedKeywords = 'null,break,case,catch,continue,debugger,default,delete,do,else,finally,for,function,if,in,instanceof,new,return,switch,this,throw,try,typeof,var,void,while,with,class,const,enum,export,extends,import,super,true,false';

const agregationVariable = ref("");
const processId = ref(window.ProcessMaker?.modeler?.process?.id);
const stateAggregationVariable = ref(true);

const isValidVariableName = (name) => {
  const validVariableNameExp = RegExp('^[A-Za-z][0-9a-zA-Z_$]*$');
  const nameParts = name.split(".");
  let valid = true;
  nameParts.forEach((item) => {
    if (!validVariableNameExp.test(item)) {
      valid = false;
    }
    if (javascriptReservedKeywords.split(",").includes(item)) {
      valid = false;
    }
  });

  return valid;
};

const saveVariable = (event) => {
  agregationVariable.value = event.target.value;

  if (agregationVariable.value.trim() && isValidVariableName(agregationVariable.value)) {
    stateAggregationVariable.value = true;
    ProcessMaker.apiClient.post(`processes/${processId.value}/aggregation`, {
      aggregation: event.target.value,
    }).then(() => { });
  } else {
    stateAggregationVariable.value = false;
  }
};

const saveVariableDebounced = debounce(saveVariable, 1000);

onMounted(() => {
  ProcessMaker.apiClient
    .get(`/processes/${processId.value}/aggregation`)
    .then((response) => {
      agregationVariable.value = response.data.data;
    });
});

</script>
