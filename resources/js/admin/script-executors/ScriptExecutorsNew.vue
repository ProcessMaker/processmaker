<script setup>
import {BButton, BTable} from 'bootstrap-vue-next'

const items = [
  { name: 'John', age: 20, city: 'New York' },
  { name: 'Jane', age: 21, city: 'Los Angeles' },
  { name: 'Jim', age: 22, city: 'Chicago' },
]

const fields = ['name', 'age', 'city']
</script>

<template>
  <div>
    <div class="card card-body">
      <h1><i class="fas fa-plug" /> {{ $t('About ProcessMaker') }}</h1>
      <BButton variant="success"><i class="fas fa-plus" /> {{ $t('Add New Script Executor') }}</BButton>
    </div>
    <BTable :items="items" :fields="fields" />
  </div>
</template>