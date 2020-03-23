<template>
    <div class="page-content ml-2" id="processPrint">
        <h1>{{processName}}</h1>
        <p><i class="far fa-clock"></i> Updated {{updatedAt}} by {{author}}</p>
        <div id="diagramContainer" class="printable-svg bg-white w-50 m-auto mb-5">
            <div v-html="svg"/>
        </div>

        <h4 class="mt-5">Process Elements</h4>
        <div id="diagram-documentation">
            <ElementDoc v-for="node in documentNodes" :bpmnNode="node" :key="node.id"/>
        </div>
    </div>
</template>

<script>
  import ElementDoc from './components/ElementDoc';
  import documentableBpmnNodes from './parseBpmnDocumentation';

  export default {
    name: 'PrintableDiagram',
    props: [
      'processName',
      'updatedAt',
      'author',
      'svg',
      'bpmn',
    ],
    components: {
      ElementDoc,
    },
    data: function() {
      return {
        bpmnString: this.bpmn,
        documentNodes: documentableBpmnNodes(this.bpmn),
      };
    },
  };
</script>
