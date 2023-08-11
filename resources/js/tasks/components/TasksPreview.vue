<template>
  <div>
    <b-sidebar
      id="tasks-preview"
      ref="tasks-preview"
      v-model="showPreview"
      :right="showRight"
      shadow
      no-header
    >
      <template #default="{ hide }">
        <div class="p-3">
          <div class="d-flex w-100 h-100 mb-3">
            <div class="my-1">
              <a class="lead text-secondary font-weight-bold">{{ task.element_name }}</a>
            </div>
            <div class="ml-auto mr-0 text-right">
              <b-button class="btn-light text-secondary" aria-label="$t('Previous Tasks')" @click="goPrevious()">
                <i class="fas fa-chevron-left"></i>
                {{$t('Prev')}}
              </b-button>
              <b-button class="btn-light text-secondary" aria-label="$t('Next Tasks')" @click="goNext()">
                {{$t('Next')}}
                <i class="fas fa-chevron-right"></i>
              </b-button>
              <a class="text-secondary">|</a>
              <b-button class="btn-light text-secondary" aria-label="$t('Close')" @click="hide">
                <i class="fas fa-times"></i>
              </b-button>
            </div>
          </div>
          <div>
            <b-embed
              type="iframe"
              :src="linkTasks"
            />
          </div>
        </div>
      </template>
    </b-sidebar>
  </div>
</template>

<script>
export default {
  data() {
    return {
      showPreview: false,
      showRight: true,
      linkTasks: "",
      task: {},
    };
  },
  methods: {
    /**
     * Show the sidebar
     */
    showSideBar(info) {
      this.task = info;
      this.linkTasks = `/tasks/${info.id}/edit/preview`;
      this.showPreview = true;
    },
  },
};
</script>

<style>
#tasks-preview {
  top: 11%;
  width: 50%;
}
</style>
