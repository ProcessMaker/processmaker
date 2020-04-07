import Vue from 'vue';
import PrintableDiagram from './PrintableDiagram';

new Vue({
  el: '#documentation-view',
  components: {PrintableDiagram},
  template: `
      <div id="documentation-view">
          <PrintableDiagram
                  :processName="processName"
                  :updatedAt="updatedAt"
                  :author="author"
                  :svg="svg"
                  :bpmn="bpmn"
          />
      </div>`,
  data: {
    processName: window.ProcessMaker.modeler.processName,
    updatedAt: window.ProcessMaker.modeler.updatedAt,
    author: window.ProcessMaker.modeler.author,
    svg: window.ProcessMaker.modeler.svg,
    bpmn: window.ProcessMaker.modeler.bpmn,
  },
});
