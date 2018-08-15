<template>
    <div id="designer-toolbar">
        <nav class="navbar navbar-expand-md override">
            <span>{{ title }}</span>

    <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a href="#" title="Full Screen"><i class="fas fa-arrows-alt"></i></a>
        </li>
        <li class="nav-item ">
          <a href="#" title="Undo"><i class="fas fa-undo"></i></a>
        </li>
        <li class="nav-item ">
          <a href="#" title="Redo"><i class="fas fa-redo"></i></a>
        </li>
        <li class="nav-item ">
          <a href="#" title="Save Process" @click="saveBPMN($event)"><i class="fas fa-save"></i></a>
        </li>
        <li class="nav-item ">
          <a @click="uploadBPMN($event)" title="Upload BPMN"><i class="fas fa-upload"></i></a>
          <input id="uploadBPMN" type="file" @change="handleFileChange" style="display: none" />
        </li>
        <li class="nav-item ">
          <a href="#" title="Help"><i class="fas fa-info"></i></a>
        </li>
        <li class="nav-item ">
          <a href="#" title="Close Designer"><i class="fas fa-times"></i></a>
        </li>
      </ul>
    </div>
  </nav>
</div>
</template>

<script>
    import EventBus from "../lib/event-bus"
    import actions from "../actions/"
    export default {
        props: [ 'title' ],
        methods: {
            uploadBPMN (value) {
                let inputFile = this.$el.querySelector("#uploadBPMN")
                if (inputFile) {
                    inputFile.click()
                }
                return inputFile
            },
            saveBPMN (value) {
                let action = actions.bpmn.save(value)
                EventBus.$emit(action.type, action.payload)
            },
            handleFileChange(e){
                let file = e && e.target ? e.target.files[0] : null
                if (file) {
                    let that = this;
                    let reader = new FileReader()
                    reader.onerror = this.errorFileHandler
                    reader.onabort = function (e) {
                        console.error(__('File read cancelled'));
                    }
                    reader.onload = function (ev) {
                        let action = actions.designer.bpmn.update(ev.target.result)
                        EventBus.$emit(action.type, action.payload)
                    }
                    reader.readAsText(file);
                    e.preventDefault()
                }
            },
            errorFileHandler(evt){
                switch (evt.target.error.code) {
                    case evt.target.error.NOT_FOUND_ERR:
                        console.error('File Not Found!')
                        break;
                    case evt.target.error.NOT_READABLE_ERR:
                        console.error('File is not readable')
                        break;
                    case evt.target.error.ABORT_ERR:
                        break; // noop
                    default:
                        console.error('An error occurred reading this file.')
                }
            }
        }
        reader.onload = function(ev) {
          let action = actions.designer.bpmn.update(ev.target.result)
          EventBus.$emit(action.type, action.payload)
        }
        reader.readAsText(file);
        e.preventDefault()
      }
    },
    errorFileHandler(evt) {
      switch (evt.target.error.code) {
        case evt.target.error.NOT_FOUND_ERR:
          console.error('File Not Found!')
          break;
        case evt.target.error.NOT_READABLE_ERR:
          console.error('File is not readable')
          break;
        case evt.target.error.ABORT_ERR:
          break; // noop
        default:
          console.error('An error occurred reading this file.')
      }
    }
  }
};
</script>

<style lang='scss' scoped>
#designer-toolbar {

    .override {
        background-color: #b6bfc6;
        padding: 10px;
        height: 40px;
        font-size: 18px;
        font-weight: 400;
        font-style: normal;
        font-stretch: normal;
        line-height: normal;
        letter-spacing: normal;
        text-align: left;
        color: #ffffff;
    }

    a {
        color: white;
        padding-right: 15px;
    }

    .nav-item {
        padding-top: 0;
    }

}
</style>
