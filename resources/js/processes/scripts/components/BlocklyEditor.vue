<template>
  <div class="blockly-editor">
    <div ref="blocklyContainer" class="blockly-container"/>
  </div>
</template>

<script>
import Blockly from 'blockly';
import 'blockly/javascript';

const blocklyToolbox = `
  <xml ref="blocklyToolbox">
    <block type="controls_if"></block>
    <block type="controls_repeat_ext"></block>
    <block type="logic_compare"></block>
    <block type="math_number"></block>
    <block type="math_arithmetic"></block>
    <block type="text"></block>
    <block type="text_print"></block>
  </xml>
`;

export default {
  name: "BlocklyEditor",
  mounted() {
    const { blocklyContainer } = this.$refs;
    const workspace = Blockly.inject(blocklyContainer, { toolbox: blocklyToolbox });
    const onresize = () => {
      const { width, height } = this.$el.getBoundingClientRect();
      blocklyContainer.style.width = width + 'px';
      blocklyContainer.style.height = height + 'px';
      Blockly.svgResize(workspace);
    };

    workspace.addChangeListener(() => {
      this.$emit('input', Blockly.JavaScript.workspaceToCode(workspace));
    });

    window.addEventListener('resize', onresize, false);
    onresize();
  }
};
</script>

<style>
.blockly-editor {
  height: 100%;
  width: 100%;
  position: relative;
}

.blockly-container {
  position: absolute;
  left: 0;
  top: 0;
}
</style>
