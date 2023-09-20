<template>
  <div>
    <div id="filesList">
      <div
        v-for="file of arrayFiles"
        :key="file.id"
      >
        <div
          type="button"
          class="card rounded-lg p-3 mb-2"
          data-toggle="modal"
          data-target="#fileModal"
          @click="modalData(file)"
        >
          <div class="d-flex justify-content-start align-items-center">
            <i class="fas fa-file text-primary mr-2" />
            <span>{{ file.file_name }}</span>
          </div>
        </div>
      </div>
    </div>

    <div
      id="fileModal"
      class="modal fade"
      role="dialog"
      aria-labelledby="fileModalLabel"
      style="display: none;"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-bottom w-100 mx-0 mb-0">
        <div class="modal-content">
          <div
            class="d-flex modal-header align-items-center py-2"
            style="background-color: #EFF5FF;"
          >
            <span> {{ $t("Details") }} </span>
            <button
              type="button"
              class="close"
              data-dismiss="modal"
              aria-label="Close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" style="padding: 0 !important;">
            <table
              role="table"
              class="table b-table m-0"
            >
              <tbody>
                <tr>
                  <td aria-colindex="1" role="cell">
                    <span class="font-weight-bold"> {{ $t("Name of the File") }} </span>
                  </td>
                  <td aria-colindex="2" role="cell">
                    {{ fileName }}
                  </td>
                </tr>
                <tr>
                  <td aria-colindex="1" role="cell">
                    <span class="font-weight-bold"> {{ $t("Uploaded") }} </span>
                  </td>
                  <td aria-colindex="2" role="cell">
                    {{ uploadDate }}
                  </td>
                </tr>
              </tbody>
            </table>
            <button type="button" class="btn btn-outline-primary">
              <i class="" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ["files", "request"],
  data() {
    return {
      arrayFiles: [],
      fileName: "",
      uploadDate: "",
    };
  },
  mounted() {
    this.arrayFiles = JSON.parse(this.files);
    console.log("xd", this.arrayFiles);
  },
  methods: {
    modalData(file) {
      this.fileName = file.file_name;
      this.uploadDate = file.created_at;
    },
  },
};
</script>
