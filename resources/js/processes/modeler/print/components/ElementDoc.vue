<template>
    <div class="card mb-3 mr-2 ml-2">
        <div class="card-header">
            <img v-if="icon.type === 'image'" v-bind="icon" height="14" />
            <i v-else-if="icon.type === 'icon'" v-bind="icon"></i>
            <!-- [{{ bpmnNode.type }}]:-->
            <strong>{{ bpmnNode.name }}</strong>
            ({{ bpmnNode.id }})
        </div>
        <div v-if="hasBodyText" class="card-body">
            <span v-if="bpmnNode.textHtml" v-html="bpmnNode.textHtml"/>
            <span v-if="bpmnNode.documentationHtml" v-html="bpmnNode.documentationHtml"/>
        </div>
        <div v-else class="card-body text-secondary">No Documentation Found.</div>
    </div>
</template>

<script>
  import icons from '../icons';

  export default {
    name: 'ElementDoc',
    props: ['bpmnNode'],
    computed: {
      hasBodyText: function() {
        return !!this.bpmnNode.textHtml || !!this.bpmnNode.documentationHtml;
      },
      icon: function() {
        const knownIcon = icons[this.bpmnNode.type];
        if (knownIcon) {
          return knownIcon;
        }
        return {type: 'unknown'};
      },
    },
  };
</script>
