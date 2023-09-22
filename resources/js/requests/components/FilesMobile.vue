<template>
  <div>
    <div id="filesList">
      <div
        v-for="file of arrayFiles"
        :key="`files-${file.id}`"
      >
        <div
          type="button"
          class="card rounded-lg p-3 mb-2"
          data-toggle="modal"
          data-target="#fileModal"
          @click="modalData(file)"
        >
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex justify-content-start align-items-center">
              <i class="fas fa-file text-primary mr-2" />
              <span>{{ file.file_name }}</span>
            </div>
            <avatar-image
              id="avatarMenu"
              ref="userMenuButton"
              class-container="d-flex"
              size="30"
              class-image="m-0"
              :input-data="file.createdBy"
              hide-name="true"
              popover
            />
          </div>
        </div>
      </div>
    </div>

    <div
      id="fileModal"
      class="modal fade"
      role="dialog"
      aria-labelledby="fileModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog custo m-0">
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
              class="table b-table m-0"
              aria-label="fileDetails"
              role="table"
            >
              <!-- Need header for Sonar problems, it is not needed in the view-->
              <thead>
                <tr>
                  <th class="p-0 m-0 border-0"></th>
                  <th class="p-0 m-0 border-0"></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td aria-colindex="1" role="cell">
                    <span class="font-weight-bold"> {{ $t("Name of the File") }}: </span>
                  </td>
                  <td aria-colindex="2" role="cell">
                    {{ fileName }}
                  </td>
                </tr>
                <tr>
                  <td aria-colindex="1" role="cell">
                    <span class="font-weight-bold"> {{ $t("Uploaded") }}: </span>
                  </td>
                  <td aria-colindex="2" role="cell">
                    {{ moment(uploadDate).format()}}
                  </td>
                </tr>
                <tr>
                  <td aria-colindex="1" role="cell">
                    <span class="font-weight-bold"> {{ $t("Uploaded By") }}: </span>
                  </td>
                  <td aria-colindex="2" role="cell">
                    <avatar-image
                      id="avatarMenu"
                      ref="userMenuButton"
                      class-container="d-flex"
                      size="30"
                      class-image="m-0"
                      :input-data="information"
                      hide-name="true"
                      popover
                    />
                  </td>
                </tr>
              </tbody>
            </table>
            <div class="w-100 py-2 px-3">
              <button
                type="button"
                class="btn btn-outline-primary w-100"
                @click="fileUrl(fileId)"
              >
                <i class="fas fa-download mr-2" />
                {{ $t("Download") }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AvatarImage from "../../components/AvatarImage.vue";

Vue.component("AvatarImage", AvatarImage);

export default {
  props: ["files", "request"],
  data() {
    return {
      arrayFiles: [],
      fileName: "",
      uploadDate: "",
      fileId: "",
      userInformation: [],
      information: [],
    };
  },
  mounted() {
    this.createNewFilesArray(JSON.parse(this.files));
  },
  methods: {
    /*
    * Fixing the files array to get the user
    */
    createNewFilesArray(files) {
      files.forEach((file) => {
        ProcessMaker.apiClient.get(`/users/${file.custom_properties.createdBy}`).then((response) => {
          if (response.data) {
            this.arrayFiles.push({
              id: file.id,
              created_at: file.created_at,
              file_name: file.file_name,
              createdBy: [
                {
                  id: response.data.id,
                  tooltip: response.data.fullname,
                  src: response.data.avatar
                    ? `${response.data.avatar}?${new Date().getTime()}`
                    : response.data.avatar,
                  title: "",
                  initials:
                  response.data.firstname && response.data.lastname
                    ? response.data.firstname.match(/./u)[0] + response.data.lastname.match(/./u)[0]
                    : "",
                },
              ],
            });
          }
        })
          .catch((error) => {
            user = [];
          });
      });
    },
    /*
    * Updates the data displayed in the modal
    */
    modalData(file) {
      this.fileName = file.file_name;
      this.uploadDate = file.created_at;
      this.fileId = file.id;
      this.information = file.createdBy;
    },
    /*
    * Return url to download file
    */
    fileUrl(file) {
      window.location = `/request/${this.request.id}/files/${file}`;
    },
  },
};
</script>

<style>
  .modal-dialog .custom {
    position: absolute;
    bottom: 0;
    min-width: 100%;
  }
</style>
