<template>
  <div class="ml-3">
    <b-row>
      <b-col class="count priority">
        {{ $t('Priority') }}
        <div>{{ data.message.priority }}</div>
      </b-col>
      <b-col class="count reassign">
        {{ $t('Reassign') }}
        <div>{{ data.message.reassigned }}</div>
      </b-col>
    </b-row>
    <b-row>
      <b-col class="count draft">
        {{ $t('Save As Draft') }}
        <div>{{ data.message.draft }}</div>
      </b-col>
      <b-col class="count submit">
        {{ $t('Submit') }}
        <div>{{ data.message.submit }}</div>
      </b-col>
    </b-row>
  </div>
</template>

<script>
export default {
  props: {
    data: {
      type: Object,
      required: true,
    },
  },
};
</script>

<style scoped>
.count {
  margin: 0.3em;
  border-left: 3px solid;
  vertical-align: middle;
  text-transform: uppercase;
  font-weight: bold;
  font-size: 12px;
}

.count > div {
  float: right;
  background-color: gray;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  text-align: center;
  vertical-align: middle;
  padding-top: 1px;
}

.priority {
  border-color: #ED4858;
}

.priority > div {
  color: #ED4858;
  background-color: #FBD1D5;
}

.reassign {
  border-color: #7101A6;
}

.reassign > div {
  color: #7101A6;
  background-color: #E5D1FF;
}

.draft {
  border-color: #1572C2;
}

.draft > div {
  color: #1572C2;
  background-color: #C4DCF0;
}

.submit {
  border-color: #4EA075;
}

.submit > div {
  color: #4EA075;
  background-color: #D3E7DC;
}
</style>