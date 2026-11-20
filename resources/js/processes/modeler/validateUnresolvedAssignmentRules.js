/**
 * Design-time validation for Rule Expression tasks whose assignees were
 * left unresolved after import (or otherwise have no user/group selected).
 */
const validateUnresolvedAssignmentRules = () => ProcessMaker.EventBus.$on("modeler-validate", (node, reporter) => {
  if (!["bpmn:Task", "bpmn:ManualTask", "bpmn:UserTask"].includes(node.$type)) {
    return;
  }

  if (node.assignment !== "rule_expression") {
    return;
  }

  let rules = [];
  try {
    rules = typeof node.assignmentRules === "string"
      ? JSON.parse(node.assignmentRules || "[]")
      : (node.assignmentRules || []);
  } catch (e) {
    return;
  }

  if (!Array.isArray(rules) || rules.length === 0) {
    return;
  }

  const hasUnresolved = rules.some((rule) => {
    if (rule && rule.unresolved === true) {
      return true;
    }
    if (!rule || (rule.type !== "user" && rule.type !== "group")) {
      return false;
    }
    return rule.assignee === null || rule.assignee === undefined || rule.assignee === "";
  });

  if (hasUnresolved) {
    reporter.report(node.id, "This task has an incomplete assignment. Select a user or group for each unresolved rule.");
  }
});

export default validateUnresolvedAssignmentRules;
