<template>
  <div>
    <b-modal v-model="shown" :title="title">
      <div>
        <p>{{ $t('You are about to export all \{\{ group \}\} settings.', {group}) }}</p>
        <p>{{ $t('This file can be saved for later use or imported into another ProcessMaker 4 system.') }}</p>
      </div>
      <div slot="modal-footer" class="w-100 m-0 d-flex">
        <button type="button" class="btn btn-outline-secondary ml-auto" @click="onCancel">
            {{ $t('Cancel') }}
        </button>
        <a class="btn btn-secondary ml-3" :href="url" @click="onExport">
            {{ $t('Export')}}
        </a>
      </div>
    </b-modal>
  </div>
</template>

<script>
export default {
  props: ['group'],
  data() {
    return {
      shown: false,
    };
  },
  computed: {
    title() {
      return this.$t('Export') + ' ' + this.group + ' ' + this.$t('Settings');
    },
    url() {
      return `/admin/settings/export?group=${this.group}`;
    },
  },
  methods: {
    onCancel() {
      this.shown = false;
    },
    onExport() {
      ProcessMaker.alert(this.$t("The settings were exported."), "success");
      this.shown = false;
    },
    show() {
      this.shown = true;
    },
  }
};
</script>

<style lang="scss">
@import '../../../../sass/colors';
</style>
