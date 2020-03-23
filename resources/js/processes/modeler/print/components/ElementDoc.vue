<template>
    <div class="card mb-3 mr-2 ml-2">
        <div class="card-header">
            <bpmn-type-image v-if="icon.type === 'image'" :icon="icon" height="14"/>
            <bpmn-type-icon v-else-if="icon.type === 'icon'" :icon="icon" />
            [{{ bpmnNode.type }}]:
            <strong>{{ bpmnNode.name }}</strong>
            ({{ bpmnNode.id }})
        </div>
        <div v-if="hasBodyText" class="card-body">
            <span v-if="bpmnNode.textHtml" v-html="bpmnNode.textHtml" />
            <span v-if="bpmnNode.documentationHtml" v-html="bpmnNode.documentationHtml" />
        </div>
        <div v-else class="card-body text-secondary">No Documentation Found.</div>
    </div>
</template>

<script>
  import BpmnTypeImage from "./BpmnTypeImage";
  import BpmnTypeIcon from "./BpmnTypeIcon";
  import icons from "../icons";

  export default {
    name: "ElementDoc",
    props: ["bpmnNode"],
    computed: {
      hasBodyText: function() {
        return !!this.bpmnNode.textHtml || !!this.bpmnNode.documentationHtml;
      },
      icon: function() {
        const knownIcon = icons[this.bpmnNode.type];
        if (knownIcon) {
          return knownIcon;
        }
        return { type: "unknown" };
      }
    },
    components: { BpmnTypeImage, BpmnTypeIcon }
  };
</script>
