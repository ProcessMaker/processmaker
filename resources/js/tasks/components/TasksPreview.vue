<template>
  <div>
    <b-sidebar
      id="tasks-preview"
      ref="tasks-preview"
      v-model="showPreview"
      :right="showRight"
      shadow
    >
      <template #default="{ hide }">
        <div class="p-3">
          <div>
            <!-- Set Controls to navigate -->
            <i
              class="fa fa-close"
              @click="hide"
            />
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
