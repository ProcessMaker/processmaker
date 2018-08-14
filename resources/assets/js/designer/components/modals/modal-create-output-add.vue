<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" title="Create Output Document">
      <form>
        <div class="form-group">
          <label for="title" v-model="title">{{title}}</label>
          <input type="text" class="form-control" id="title">
        </div>

        <div class="form-group">
          <label for="filenameGenerated">{{filenameGenerated}}</label>
          <div class="d-flex">
            <input type="text" class="form-control inline-input" id="filenameGenerated">
            <button type="submit" class="btn inline-button text-light">@@</button>
          </div>
        </div>

        <div class="form-group">
          <label for="description">{{description}}</label>
          <textarea class="form-control" id="description" rows="3"></textarea>
        </div>

        <div class="form-group">
          <label for="reportGenerator" v-model="reportGenerator">{{reportGenerator}}</label>
          <select class="form-control" id="reportGenerator" v-model="reportGeneratorSelect">
          <option  v-for="select in reportGeneratorSelectOptions">{{select}}</option>
          </select>
        </div>

        <div class="d-flex justify-content-between">
          <div class="form-group">
            <label for="media" v-model="media">{{media}}</label>
            <select class="form-control input-and-select" id="media" v-model="mediaSelect">
            <option v-for="select in mediaSelectOptions">{{select}}</option>
            </select>
          </div>

          <div class="form-group">
            <label for="orientation" v-model="orientation">{{orientation}}</label>
            <select class="form-control input-and-select" id="orientation" v-model="orientationSelect">
            <option v-for="select in orientationSelectOptions">{{select}}</option>
            </select>
          </div>
        </div>

        <div class="d-flex justify-content-between">
          <div class="form-group">
            <label for="left" v-model="left">{{left}}</label>
            <input type="text" class="form-control input-and-select" id="left">
          </div>

          <div class="form-group">
            <label for="right" v-model="right">{{right}}</label>
            <input type="text" class="form-control input-and-select" id="right">
          </div>
        </div>

        <div class="d-flex justify-content-between">
          <div class="form-group">
            <label for="top" v-model="top">{{top}}</label>
            <input type="text" class="form-control input-and-select" id="top">
          </div>

          <div class="form-group">
            <label for="bottom" v-model="bottom">{{bottom}}</label>
            <input type="text" class="form-control input-and-select" id="bottom">
          </div>
        </div>

        <div class="form-group">
          <label for="outputDocumentGenerate" v-model="outputDocumentGenerate">{{outputDocumentGenerate}}</label>
          <select class="form-control" id="outputDocumentGenerate" v-model="outputDocumentGenerateSelect">
          <option v-for="select in outputDocumentGenerateSelectOptions">{{select}}</option>
          </select>
        </div>

        <div class="d-flex justify-content-between">
          <div class="form-group">
            <label for="pdfSecurity" v-model="pdfSecurity">{{pdfSecurity}}</label>
            <select class="form-control input-and-select" id="pdfSecurity" v-model="pdfSecuritySelect">
            <option v-for="select in pdfSecuritySelectOptions">{{select}}</option>
            </select>
          </div>

          <div class="form-group">
            <label for="enableVersioning" v-model="enableVersioning">{{enableVersioning}}</label>
            <select class="form-control input-and-select" id="enableVersioning" v-model="enableVersioningSelect">
            <option v-for="select in enableVersioningSelectOptions">{{select}}</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="destinationPath">{{destinationPath}}</label>
          <div class="d-flex">
            <input type="text" class="form-control inline-input" id="destinationPath">
            <button type="submit" class="btn inline-button text-light">@@</button>
          </div>
        </div>

        <div class="form-group">
          <label for="tags">{{tags}}</label>
          <div class="d-flex">
            <input type="text" class="form-control inline-input" id="tags">
            <button type="submit" class="btn inline-button text-light">@@</button>
          </div>
        </div>

        <div class="form-group">
          <label for="generatedLink" v-model="generatedLink">{{generatedLink}}</label>
          <select class="form-control" id="generatedLink" v-model="generatedLinkSelect">
          <option  v-for="select in generatedLinkSelectOptions">{{select}}</option>
          </select>
        </div>

    </form>

    <div slot="modal-footer">
        <b-button @click="onCancel" class="btn btn-outline-success btn-md">
            CANCEL
        </b-button>
        <b-button class="btn btn-success btn-sm text-uppercase">
            SAVE
        </b-button>
    </div>

  </b-modal>
</template>

<script>
export default {
  data() {
    return {
      // form models here
      "title": 'Title',
      'filenameGenerated': "Filename Generated",
      'reportGenerator': "Report Generator",
      'reportGeneratorSelect': "All",
      'reportGeneratorSelectOptions':[
        'All','Assigned','Unassigned','Completed','Paused'
      ],
      'enableVersioning': "Enable Versioning",
      'enableVersioningSelect': "All",
      'enableVersioningSelectOptions':[
        'All','Assigned','Unassigned','Completed','Paused'
      ],
      'description':"Description",
      'originTask':"Origin Task",
      'originSelect': "All",
      'originSelectOptions':[
        'All','Task 1', 'Task 2'
      ],
      'media':"Media",
      'mediaSelect': "All",
      'mediaSelectOptions':[
        'All','Task 1', 'Task 2'
      ],
      'orientation':"Orientation",
      'orientationSelect': "All",
      'orientationSelectOptions':[
        'All','Task 1', 'Task 2'
      ],
      'left': "Left",
      'right': "Right",
      "top":'Top',
      "bottom":'Bottom',
      'pdfSecurity':"PDF Security",
      'pdfSecuritySelect': "All",
      'pdfSecuritySelectOptions':[
        'All','Task 1', 'Task 2'
      ],
      'enableVersioning':"Enable Versioning",
      'enableVersioningSelect': "All",
      'enableVersioningSelectOptions':[
        'All','Task 1', 'Task 2'
      ],
      'destinationPath': "Destination Path",
      'tags': "Tags",
      'participationRequired':"Participation Required?",
      "type": 'Type',
      "maximumFileSize": 'Maximum File Size',
      'outputDocumentGenerate':"Output Document to Generate",
      'outputDocumentGenerateSelect': 'View',
      'outputDocumentGenerateSelectOptions':[
        'View', 'Block'
      ],
      'generatedLink':"By clicking on the generated file link",
      'generatedLinkSelect': 'View',
      'generatedLinkSelectOptions':[
        'View', 'Block'
      ]
    }
  },
  methods:{
    onHidden() {
      this.$emit('hidden')
    },
    onCancel() {
      this.$refs.modal.hide()
    }
  },
  mounted() {
    // Show our modal as soon as we're created
    this.$refs.modal.show();
  }
};
</script>
<style lang="scss" scoped>
.inline-input{
  margin-right: 6px;
}
.inline-button{
  background-color: rgb(109,124,136);
  font-weight: 100;
}
.input-and-select{
  width:212px;
}
</style>
