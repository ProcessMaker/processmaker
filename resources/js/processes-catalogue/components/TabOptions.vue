<template>
  <b-dropdown class="ml-1"
              variant="secondary"
              no-caret>
    <template #button-content>
      <b-icon icon="three-dots"></b-icon>
    </template>
    <b-dropdown-item @click="$emit('onTabSettings',$event)">{{$t('Tab Settings')}}</b-dropdown-item>
    <b-dropdown-item @click="$emit('onDelete',$event)">{{$t('Delete')}}</b-dropdown-item>
  </b-dropdown>
</template>
