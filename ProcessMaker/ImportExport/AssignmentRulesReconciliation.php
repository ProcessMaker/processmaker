<?php

namespace ProcessMaker\ImportExport;

use DOMElement;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\WorkflowServiceProvider;

/**
 * Import-only reconciliation for pm:assignmentRules (rule_expression tasks).
 *
 * The numeric assignee in an exported rule is a source-tenant ID and is never
 * trusted on its own. Rules already carry a display identity in
 * assignmentName, so import rebinds by that identity when it uniquely matches a
 * target record; otherwise the rule is marked unresolved and its stale
 * assignee is cleared.
 */
class AssignmentRulesReconciliation
{
    public const ATTRIBUTE = 'assignmentRules';

    /** Set on a rule when import could not bind a target user/group. */
    public const KEY_UNRESOLVED = 'unresolved';

    private const HUMAN_TASK_TAGS = ['task', 'userTask', 'manualTask'];

    /**
     * Cached labels for SelectUserGroup's inactive suffix ($t('Inactive')).
     *
     * @var array<int, string>|null
     */
    private static ?array $inactiveStatusLabels = null;

    /**
     * @return array<int, object|array<string, mixed>>
     */
    public static function decodeRules(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $list = json_decode($json);
        if (!is_array($list)) {
            return [];
        }

        return $list;
    }

    /**
     * @param array<int, object|array<string, mixed>> $rules
     */
    public static function encodeRules(array $rules): string
    {
        return json_encode(array_values($rules), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<int, object|array<string, mixed>> $rules
     * @return array<int, object|array<string, mixed>>
     */
    public static function reconcileRulesAfterImport(array $rules): array
    {
        $reconciled = [];
        foreach ($rules as $rule) {
            $reconciled[] = self::reconcileRuleAfterImport($rule);
        }

        return $reconciled;
    }

    /**
     * @param object|array<string, mixed> $rule
     * @return object|array<string, mixed>
     */
    public static function reconcileRuleAfterImport(object|array $rule): object|array
    {
        $type = self::ruleGet($rule, 'type');
        $assignee = null;

        if ($type === 'user') {
            $username = self::usernameFromAssignmentName(self::ruleGet($rule, 'assignmentName'));
            // username is unique in the users table and is the same key
            // UserExporter's fallback matching already trusts on import.
            $assignee = $username ? User::where('username', $username)->value('id') : null;
        } elseif ($type === 'group') {
            $name = self::ruleGet($rule, 'assignmentName');
            $name = is_string($name) ? trim($name) : '';
            // Group names are not unique in the schema, so only an
            // unambiguous match may be rebound.
            $groups = $name !== '' ? Group::where('name', $name)->get() : collect();
            $assignee = $groups->count() === 1 ? $groups->first()->id : null;
        } else {
            return $rule;
        }

        return $assignee === null
            ? self::markRuleUnresolved($rule)
            : self::markRuleResolved($rule, $assignee);
    }

    /**
     * Extract the username from "Full Name (username)", tolerating the
     * inactive suffix SelectUserGroup appends via $t('Inactive')
     * The assignmentName was written in whatever locale the designer used,
     * so import must recognize every available translation of that key.
     */
    public static function usernameFromAssignmentName(mixed $assignmentName): ?string
    {
        if (!is_string($assignmentName) || !preg_match('/\(([^()]*)\)\s*$/', trim($assignmentName), $matches)) {
            return null;
        }

        $username = self::stripInactiveStatusSuffix(trim($matches[1]));

        return $username !== '' ? $username : null;
    }

    /**
     * Labels used by SelectUserGroup for inactive users: " - " + $t('Inactive').
     *
     * @return array<int, string>
     */
    public static function inactiveStatusLabels(): array
    {
        if (self::$inactiveStatusLabels !== null) {
            return self::$inactiveStatusLabels;
        }

        $labels = ['Inactive'];
        foreach (glob(resource_path('lang/*.json')) ?: [] as $file) {
            $locale = basename($file, '.json');
            $translated = trans('Inactive', [], $locale);
            if (is_string($translated) && $translated !== '') {
                $labels[] = $translated;
            }
        }

        self::$inactiveStatusLabels = array_values(array_unique($labels));

        return self::$inactiveStatusLabels;
    }

    /**
     * Reset cached inactive labels (for tests).
     */
    public static function clearInactiveStatusLabelCache(): void
    {
        self::$inactiveStatusLabels = null;
    }

    private static function stripInactiveStatusSuffix(string $username): string
    {
        foreach (self::inactiveStatusLabels() as $label) {
            $quoted = preg_quote($label, '/');
            $stripped = preg_replace('/\s+-\s+' . $quoted . '$/iu', '', $username);
            if (is_string($stripped) && $stripped !== $username) {
                return trim($stripped);
            }
        }

        return $username;
    }

    /**
     * @param object|array<string, mixed> $rule
     * @return object|array<string, mixed>
     */
    public static function markRuleUnresolved(object|array $rule): object|array
    {
        $rule = self::ruleSet($rule, 'assignee', null);
        $rule = self::ruleSet($rule, self::KEY_UNRESOLVED, true);

        return $rule;
    }

    /**
     * Whether a rule was left unbound after import (or equivalent).
     * Matching unresolved rules must not assign or fall through to the Process Manager.
     *
     * @param object|array<string, mixed> $rule
     */
    public static function isUnresolvedRule(object|array $rule): bool
    {
        if (self::ruleGet($rule, self::KEY_UNRESOLVED) === true) {
            return true;
        }

        $type = self::ruleGet($rule, 'type');
        if ($type !== 'user' && $type !== 'group') {
            return false;
        }

        $assignee = self::ruleGet($rule, 'assignee');

        return $assignee === null || $assignee === '';
    }

    /**
     * @return iterable<int, array{element: DOMElement, path: string, rules: array<int, object|array<string, mixed>>}>
     */
    public static function collectAssignmentRulesFromDocument(\DOMDocument $document): iterable
    {
        $ns = WorkflowServiceProvider::PROCESS_MAKER_NS;
        foreach (self::HUMAN_TASK_TAGS as $tag) {
            foreach ($document->getElementsByTagName($tag) as $element) {
                if (!$element instanceof DOMElement) {
                    continue;
                }
                if ($element->getAttributeNS($ns, 'assignment') !== 'rule_expression') {
                    continue;
                }
                $raw = $element->getAttributeNS($ns, self::ATTRIBUTE);
                if ($raw === '') {
                    continue;
                }
                yield [
                    'element' => $element,
                    'path' => $element->getNodePath(),
                    'rules' => self::decodeRules($raw),
                ];
            }
        }
    }

    public static function writeRulesOnElement(DOMElement $element, array $rules): void
    {
        $ns = WorkflowServiceProvider::PROCESS_MAKER_NS;
        $element->setAttributeNS($ns, self::ATTRIBUTE, self::encodeRules($rules));
    }

    /**
     * Reconcile every rule_expression assignmentRules block on a BPMN document.
     */
    public static function reconcileAssignmentRulesInDocument(\DOMDocument $document): bool
    {
        $updated = false;

        foreach (self::collectAssignmentRulesFromDocument($document) as $item) {
            $reconciled = self::reconcileRulesAfterImport($item['rules']);
            self::writeRulesOnElement($item['element'], $reconciled);
            $updated = true;
        }

        return $updated;
    }

    /**
     * @param object|array<string, mixed> $rule
     * @return object|array<string, mixed>
     */
    private static function markRuleResolved(object|array $rule, mixed $assignee): object|array
    {
        $rule = self::ruleSet($rule, 'assignee', $assignee);

        if (is_array($rule)) {
            unset($rule[self::KEY_UNRESOLVED]);

            return $rule;
        }

        if (property_exists($rule, self::KEY_UNRESOLVED)) {
            unset($rule->{self::KEY_UNRESOLVED});
        }

        return $rule;
    }

    /**
     * @param object|array<string, mixed> $rule
     */
    private static function ruleGet(object|array $rule, string $key): mixed
    {
        if (is_array($rule)) {
            return $rule[$key] ?? null;
        }

        return $rule->$key ?? null;
    }

    /**
     * @param object|array<string, mixed> $rule
     * @return object|array<string, mixed>
     */
    private static function ruleSet(object|array $rule, string $key, mixed $value): object|array
    {
        if (is_array($rule)) {
            $rule[$key] = $value;

            return $rule;
        }

        $rule->$key = $value;

        return $rule;
    }
}
