<template>
  <div>
    <div class="row">
      <div class="col-lg-6">
        <dl class="row mb-0">
          <div class="col-sm-4 text-sm-right"><dt>Created:</dt> </div>
          <div class="col-sm-8 text-sm-left"><dd class="mb-1">{{formatDate(tokenCreated)}}</dd></div>
        </dl>
        <dl class="row mb-0">
          <div class="col-sm-4 text-sm-right"><dt>Completed:</dt> </div>
          <div class="col-sm-8 text-sm-left"><dd class="mb-1">{{formatDate(tokenCompleted)}}</dd> </div>
        </dl>
        <dl class="row mb-0">
          <div class="col-sm-4 text-sm-right"><dt>Completed by:</dt> </div>
          <div class="col-sm-8 text-sm-left">
            <dd class="mb-1">
              <i v-if="!userAvatar" class="fa fa-user rounded-user"></i>
              <img v-else v-bind:src="userAvatar" class="rounded-user"> {{userName}}
            </dd>
          </div>
        </dl>
      </div>
    </div>
    <vue-form-renderer v-model="formData" v-bind:config="json" />
    <a class="btn btn-primary" :href="statusURL">Back</a>
  </div>
</template>

<script>

  import VueFormRenderer from "@processmaker/vue-form-builder/src/components/vue-form-renderer";
  import moment from "moment";

  export default {
    components: {
      VueFormRenderer
    },
    props: [
      'data',
      'formId',
      'instanceId',
      'processId',
      'tokenCompleted',
      'tokenCreated',
      'tokenId',
      'userAvatar',
      'userName',
      'userId',
    ],
    data() {
      return {
        json: [
          {
            name: "Default",
            items: []
          }
        ],
        formData: this.data,
        user: {
          avatar: '',
          fullname: ''
        },
        statusURL: '/requests/' + this.instanceId + '/status'
      };
    },
    watch: {
      /**
       * Disables data changes in the form
       */
      formData: {
        deep: true,
        handler(newValue) {
          if (JSON.stringify(newValue) !== JSON.stringify(this.data)) {
            this.formData = JSON.parse(JSON.stringify(this.data));
          }
        }
      }
    },
    mounted() {
      this.fetch();
    },
    methods: {
      /**
       * Format the date.
       *
       * @param {iso8601} date
       * @returns {string}
       */
      formatDate(date) {
        return moment(date).format('YYYY-MM-DD hh:mm');
      },
      /**
       * Disable the form items.
       *
       * @param {array|object} json
       * @returns {array|object}
       */
      disableForm(json) {
        if (json instanceof Array) {
          for (let item of json) {
            if (item.component==='FormButton' && item.config.event==='submit') {
              json.splice(json.indexOf(item), 1);
            } else {
              this.disableForm(item);
            }
          }
        }
        if (json.config !== undefined) {
          json.config.disabled = true;
        }
        if (json.items !== undefined) {
          this.disableForm(json.items);
        }
        return json;
      },
      fetch() {
        this.loading = true;

        // Load JSON from our api client
        ProcessMaker.apiClient
                .get("process/" + this.processId + "/form/" + this.formId)
                .then(response => {
                  this.json = this.disableForm(response.data.content);
                  this.loading = false;
                });
      }
    }
  }

</script>

<style lang="scss" scoped>
  .rounded-user {
      border-radius: 50%!important;
      height: 1.5em;
      margin-right: 0.5em;
  }
</style>
