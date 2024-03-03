<template>
  <div>
    <splitpanes
      v-if="showPreview"
      ref="inspectorSplitPanes"
      class="splitpane default-theme"
      :dbl-click-splitter="false"
    >
      <pane style="opacity: 0;">
        <div />
      </pane>
      <pane
        class="pane-task-preview"
        :min-size="paneMinSize"
        size="50"
        max-size="99"
        style="background-color: white;"
      >
        <div
          id="tasks-preview"
          ref="tasks-preview"
          class="h-100 p-3"
        >
        <template v-if="!showQuickFillPreview">
          <div>
            <div v-if="!isSelectedTask" class="d-flex w-100 h-100 mb-3">
              <div class="my-1">
                <a class="lead text-secondary font-weight-bold">
                  {{ task.element_name }}
                </a>
              </div>
              <div class="ml-auto mr-0 text-right">
                <b-button 
                  class="icon-button"
                  :aria-label="$t('Quick fill')"
                  variant="light"
                  @click="goQuickFill()"
                >
                  <img src="../../../img/smartinbox-images/fill.svg">
                </b-button>
                <b-button
                  class="btn-light text-secondary"
                  :aria-label="$t('Previous Tasks')"
                  :disabled="!existPrev"
                  @click="goPrevNext('Prev')"
                >
                  <i class="fas fa-chevron-left" />
                  {{ $t("Prev") }}
                </b-button>
                <b-button
                  class="btn-light text-secondary"
                  :aria-label="$t('Next Tasks')"
                  :disabled="!existNext"
                  @click="goPrevNext('Next')"
                >
                  {{ $t("Next") }}
                  <i class="fas fa-chevron-right" />
                </b-button>
                <a class="text-secondary">|</a>
                <b-button
                  class="btn-light text-secondary"
                  :aria-label="$t('Open Task')"
                  :href="openTask()"
                >
                  <i class="fas fa-external-link-alt" />
                </b-button>
                <a class="text-secondary">|</a>
                <b-button
                  class="btn-light text-secondary"
                  :aria-label="$t('Close')"
                  @click="onClose()"
                >
                  <i class="fas fa-times" />
                </b-button>
              </div>
            </div>
            <div v-else>
              <div class="d-flex w-100 h-100 mb-3" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="my-1">
                  <b-button
                    variant="secondary"
                    @click="onClose()"
                  >
                    <i class="fas fa-arrow-left"></i>
                  </b-button>
                  <a class="lead text-secondary font-weight-bold">
                    {{ task.data._request.case_title }}
                  </a>
                </div>
                <b-button 
                  class="btn-this-data"
                  @click="copyFormData()"
                >{{ $t('USE THIS TASK DATA') }}
                </b-button>
                <!-- <div style="display: none;"> -->

              <b-embed
                    v-if="showFrame3"
                    id="tasksFrame3"
                    width="100%"
                    :src="selectedTaskLink"
                  />
              </div>
              
              <!-- </div> -->
            </div>
            <div class="frame-container">
              <b-embed
                v-if="showFrame1"
                id="tasksFrame1"
                width="100%"
                :class="showFrame2 ? 'loadingFrame' : ''"
                :src="linkTasks1"
                @load="frameLoaded()"
              />
              <b-embed
                v-if="showFrame2"
                id="tasksFrame2"
                width="100%"
                :class="showFrame1 ? 'loadingFrame' : ''"
                :src="linkTasks2"
                @load="frameLoaded()"
              />
              
              <task-loading
                v-show="stopFrame"
                class="load-frame"
              />
            </div>
          </div>
        </template>
        <quick-fill-preview 
          v-if="showQuickFillPreview" 
          :showQuickFillPreview="showQuickFillPreview"
          :task="task"
          :data="data"
        ></quick-fill-preview>
        </div>
      </pane>
    </splitpanes>
  </div>
</template>

<script>
import { Splitpanes, Pane } from "splitpanes";
import TaskLoading from "./TaskLoading.vue";
import PreviewMixin from "./PreviewMixin";
import QuickFillPreview from "./QuickFillPreview.vue"
import "splitpanes/dist/splitpanes.css";

export default {
  components: { Splitpanes, Pane, TaskLoading, QuickFillPreview },
  mixins: [PreviewMixin],
  // mounted() {
  //   this.$root.$on("fillData", (arg) => {
  //     this.setDataInPreview(arg.data);
  //   });
  // },
  mounted() {
    this.$root.$on("selectedTaskForQuickFill", (val) => {
      this.showQuickFillPreview = false;
      this.isSelectedTask = true;
      this.selectedTaskLink = val.selectedTask;
                this.showFrame3=true;
      console.log("nuevo task: ", this.selectedTaskLink);
      console.log("val.task: ", val.task, " val.data.data: ", val.data.data);
      this.showSideBar(val.task, val.data.data, true);
    });
  },
  updated() {
    const resizeOb = new ResizeObserver((entries) => {
      const { width } = entries[0].contentRect;
      this.setPaneMinSize(width, 480);
    });
    if (this.$refs.inspectorSplitPanes) {
      resizeOb.observe(this.$refs.inspectorSplitPanes.container);
    }
  },
  methods: {
    setDataInPreview(data){
      console.log("en setDataPreview: ", this.$refs);
      //if(this.$refs.tasksFrame1){
      if(this.$refs) {
        console.log("en tasksFrame1");
        this.$refs.tasksFrame1.contentWindow.postMessage(data, "*");
      }
      if(this.$refs.tasksFrame2) {
        console.log("en tasksFrame2");
        this.$refs.tasksFrame2.contentWindow.postMessage(data, "*");
      }
    },
    copyFormData() {
      const frame1 = document.getElementById("tasksFrame3");
      const frame3 = document.getElementById("tasksFrame1");
      
      if (frame1 && frame3) {
        const iframeDoc1 = frame1.contentDocument || frame1.contentWindow.document;
        const iframeDoc2 = frame3.contentDocument || frame3.contentWindow.document;

        if (iframeDoc1 && iframeDoc2) {
          // Copiar valores de los inputs
          const inputsFrame1 = iframeDoc1.querySelectorAll('input:not([readonly])');
          const inputsFrame3 = iframeDoc2.querySelectorAll('input:not([readonly])');
console.log("inputsFrame3.length: ", inputsFrame3.length);
console.log("inputsFrame1.length: ", inputsFrame1.length);
          if (inputsFrame3.length === inputsFrame1.length) {
            inputsFrame3.forEach((input, index) => {
              input.value = inputsFrame1[index].value;
            });
          } else {
            console.error("La cantidad de inputs en ambos iframes no es la misma.");
          }

          // Copiar valores de otros tipos de campos (si los hubiera)
          // Puedes hacer lo mismo para otros tipos de campos (select, textarea, etc.) si es necesario
        } else {
          console.error("No se pudo acceder al contenido de uno de los iframes.");
        }
      } else {
        console.error("No se encontraron uno o ambos iframes.");
      }
    },
    copyFormDataAll() {
      const frame1 = document.getElementById("tasksFrame1");
      const frame3 = document.getElementById("tasksFrame3");
      
      if (frame1 && frame3) {
        const iframeDoc1 = frame1.contentDocument || frame1.contentWindow.document;
        const iframeDoc2 = frame3.contentDocument || frame3.contentWindow.document;

        if (iframeDoc1 && iframeDoc2) {
          // Copiar valores de los inputs
          const inputsFrame1 = iframeDoc1.querySelectorAll('input:not([readonly]), textarea:not([readonly]), select:not([readonly])');
          const inputsFrame3 = iframeDoc2.querySelectorAll('input:not([readonly]), textarea:not([readonly]), select:not([readonly])');

          if (inputsFrame3.length === inputsFrame1.length) {
            inputsFrame3.forEach((input, index) => {
              if (input.type === 'checkbox') {
                input.checked = inputsFrame1[index].checked;
              } else if (input.type === 'select-one' || input.type === 'select-multiple') {
                // Copiar opciones seleccionadas para los elementos select
                inputsFrame3[index].querySelectorAll('option').forEach(option => {
                  if (option.selected) {
                    input.querySelector(`option[value="${option.value}"]`).selected = true;
                  }
                });
              } else {
                input.value = inputsFrame3[index].value;
              }
            });
          } else {
            console.error("La cantidad de campos en ambos iframes no es la misma.");
          }

          // Copiar valores de otros tipos de campos
          // Puedes agregar más lógica aquí para copiar otros tipos de campos si es necesario
        } else {
          console.error("No se pudo acceder al contenido de uno de los iframes.");
        }
      } else {
        console.error("No se encontraron uno o ambos iframes.");
      }
    },
  },
};
</script>

<style>
.splitpane {
  top: 0;
  min-height: 80vh;
  width: 99%;
  position: absolute;
}
.pane-task-preview {
  flex-grow: 1;
  overflow-y: auto;
}
#tasks-preview {
  box-sizing: border-box;
  display: block;
  overflow: hidden;
}
.loadingFrame {
  opacity: 0.5;
}
.frame-container {
  display: grid;
  height: 70vh;
}
.embed-responsive,
.load-frame {
  position: relative;
  display: block;
  width: 100%;
  padding: 0;
  overflow: auto;
  grid-row-start: 1;
  grid-column-start: 1;
}
.icon-button {
  display: inline-block;
  width: 46px;
  height: 36px;
  border: 1px solid #ccc;
  background-color: #fff;
  padding: 10px;
  border-radius: 5px;
  justify-content: center;
  align-items: center;
  vertical-align: unset;
}

.icon-button img {
  width: 16px;
  height: 16px;
}
</style>
