<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Processes;

use DOMDocument;
use DOMElement;
use DOMXPath;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Rules\BPMNValidation;
use Throwable;

/**
 * Schema + lightweight semantic checks for MCP process tools.
 */
final class ProcessBpmnValidator
{
    private const PM_NS = 'http://processmaker.com/BPMN/2.0/Schema.xsd';

    private const BPMN_NS = 'http://www.omg.org/spec/BPMN/20100524/MODEL';

    private const DI_NS = 'http://www.omg.org/spec/BPMN/20100524/DI';

    /**
     * @return array{ok: bool, errors: list<array{nodeId: ?string, code: string, message: string}>, warnings: list<array{nodeId: ?string, code: string, message: string}>}
     */
    public function validate(?string $bpmn): array
    {
        $errors = [];
        $warnings = [];

        if ($bpmn === null || trim($bpmn) === '') {
            $errors[] = $this->issue(null, 'empty_bpmn', 'BPMN XML is required for validation.');

            return $this->result($errors, $warnings);
        }

        $bpmn = BpmnDocument::replaceHtmlEntities($bpmn);
        $schemaIssues = $this->schemaErrors($bpmn);
        foreach ($schemaIssues as $message) {
            $errors[] = $this->issue(null, 'schema', $message);
        }

        $dom = new DOMDocument();
        if (@$dom->loadXML($bpmn) !== true) {
            $errors[] = $this->issue(null, 'malformed_xml', 'BPMN XML could not be parsed.');

            return $this->result($errors, $warnings);
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('bpmn', self::BPMN_NS);
        $xpath->registerNamespace('bpmndi', self::DI_NS);
        $xpath->registerNamespace('pm', self::PM_NS);

        $this->collectSemanticIssues($xpath, $errors, $warnings);

        return $this->result($errors, $warnings);
    }

    /**
     * @return list<string>
     */
    public function schemaErrors(string $bpmn): array
    {
        $schemaErrors = [];
        $document = new BpmnDocument();
        try {
            $document->loadXML($bpmn);
        } catch (Throwable $e) {
            return [$e->getMessage()];
        }

        try {
            $document->validateBPMNSchema(public_path('definitions/ProcessMaker.xsd'));
        } catch (Throwable $e) {
            $schemaErrors = $document->getValidationErrors();
            $schemaErrors[] = $e->getMessage();
        }

        $diagrams = $document->getElementsByTagNameNS(self::DI_NS, 'BPMNDiagram');
        if ($diagrams->length > 1) {
            $schemaErrors[] = __('Multiple diagrams are not supported');
        }

        $rulesValidation = new BPMNValidation();
        if (!$rulesValidation->passes('document', $document)) {
            $schemaErrors[] = 'BPMN Validation failed: some bpmn elements do not comply with the validation.';
        }

        return array_values(array_filter(array_map(
            static fn (mixed $error): string => is_string($error) ? $error : json_encode($error),
            $schemaErrors,
        )));
    }

    /**
     * @param list<array{nodeId: ?string, code: string, message: string}> $errors
     * @param list<array{nodeId: ?string, code: string, message: string}> $warnings
     */
    private function collectSemanticIssues(DOMXPath $xpath, array &$errors, array &$warnings): void
    {
        if ($xpath->query('//bpmn:startEvent')->length === 0) {
            $warnings[] = $this->issue(null, 'missing_start_event', 'Process has no start event; it cannot be started.');
        }

        $this->requireFlowWiring($xpath, $errors);
        $this->requireInterstitialScreen($xpath, $errors);

        $this->requireAttr($xpath, '//bpmn:task', 'screenRef', 'missing_screen_ref', 'Form Task requires pm:screenRef.', $errors);
        $this->requireAttr($xpath, '//bpmn:manualTask', 'screenRef', 'missing_screen_ref', 'Manual Task requires pm:screenRef.', $errors);
        $this->requireAttr($xpath, '//bpmn:scriptTask', 'scriptRef', 'missing_script_ref', 'Script Task requires pm:scriptRef.', $errors);
        $this->rejectUnresolvedPortableRefs($xpath, $errors);

        foreach ($xpath->query('//bpmn:callActivity') ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $called = trim($node->getAttribute('calledElement'));
            if ($called === '') {
                $errors[] = $this->issue(
                    $node->getAttribute('id') ?: null,
                    'missing_called_element',
                    'Call Activity requires calledElement.',
                );
            }
        }

        foreach ($xpath->query('//bpmn:exclusiveGateway') ?: [] as $gateway) {
            if (!$gateway instanceof DOMElement) {
                continue;
            }
            $id = $gateway->getAttribute('id');
            $default = $gateway->getAttribute('default');
            $outflows = $xpath->query('//bpmn:sequenceFlow[@sourceRef="' . $id . '"]');
            if ($outflows === false || $outflows->length < 2) {
                continue;
            }
            $hasCondition = false;
            foreach ($outflows as $flow) {
                if (!$flow instanceof DOMElement) {
                    continue;
                }
                if ($flow->getElementsByTagNameNS(self::BPMN_NS, 'conditionExpression')->length > 0) {
                    $hasCondition = true;
                    break;
                }
            }
            if ($default === '' && !$hasCondition) {
                $warnings[] = $this->issue(
                    $id !== '' ? $id : null,
                    'xor_no_default',
                    'Exclusive gateway with multiple outflows has no default and no conditions.',
                );
            }
        }
    }

    /**
     * allowInterstitial=true without interstitialScreenRef leaves the task UI stuck loading.
     *
     * @param list<array{nodeId: ?string, code: string, message: string}> $errors
     */
    private function requireInterstitialScreen(DOMXPath $xpath, array &$errors): void
    {
        foreach ($xpath->query('//*[@pm:allowInterstitial]') ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $allow = strtolower(trim($node->getAttributeNS(self::PM_NS, 'allowInterstitial')));
            if ($allow === '') {
                $allow = strtolower(trim($node->getAttribute('pm:allowInterstitial')));
            }
            if ($allow !== 'true' && $allow !== '1') {
                continue;
            }
            $ref = trim($node->getAttributeNS(self::PM_NS, 'interstitialScreenRef'));
            if ($ref === '') {
                $ref = trim($node->getAttribute('pm:interstitialScreenRef'));
            }
            if ($ref === '') {
                $errors[] = $this->issue(
                    $node->getAttribute('id') ?: null,
                    'missing_interstitial_screen',
                    'allowInterstitial=true requires pm:interstitialScreenRef (DISPLAY screen; system key "interstitial" is valid).',
                );
            }
        }
    }

    /**
     * Nayra wires runtime flows from bpmn:outgoing / bpmn:incoming children.
     * sourceRef/targetRef alone leave Start Events with 0 outgoing flows.
     *
     * @param list<array{nodeId: ?string, code: string, message: string}> $errors
     */
    private function requireFlowWiring(DOMXPath $xpath, array &$errors): void
    {
        foreach ($xpath->query('//bpmn:sequenceFlow') ?: [] as $flow) {
            if (!$flow instanceof DOMElement) {
                continue;
            }
            $flowId = $flow->getAttribute('id');
            $sourceId = $flow->getAttribute('sourceRef');
            $targetId = $flow->getAttribute('targetRef');
            if ($flowId === '' || $sourceId === '' || $targetId === '') {
                continue;
            }

            $source = $xpath->query('//*[@id="' . $sourceId . '"]')->item(0);
            if ($source instanceof DOMElement
                && $source->getElementsByTagNameNS(self::BPMN_NS, 'outgoing')->length === 0
            ) {
                $errors[] = $this->issue(
                    $sourceId,
                    'missing_outgoing',
                    'Node is source of sequence flow ' . $flowId . ' but has no bpmn:outgoing child; Nayra will not leave this node.',
                );
            } elseif ($source instanceof DOMElement) {
                $hasOutgoing = false;
                foreach ($source->getElementsByTagNameNS(self::BPMN_NS, 'outgoing') as $outgoing) {
                    if (trim($outgoing->textContent) === $flowId) {
                        $hasOutgoing = true;
                        break;
                    }
                }
                if (!$hasOutgoing) {
                    $errors[] = $this->issue(
                        $sourceId,
                        'missing_outgoing_ref',
                        'Node is source of sequence flow ' . $flowId . ' but bpmn:outgoing does not reference it.',
                    );
                }
            }

            $target = $xpath->query('//*[@id="' . $targetId . '"]')->item(0);
            if ($target instanceof DOMElement
                && $target->getElementsByTagNameNS(self::BPMN_NS, 'incoming')->length === 0
            ) {
                $errors[] = $this->issue(
                    $targetId,
                    'missing_incoming',
                    'Node is target of sequence flow ' . $flowId . ' but has no bpmn:incoming child; Nayra will not enter this node.',
                );
            } elseif ($target instanceof DOMElement) {
                $hasIncoming = false;
                foreach ($target->getElementsByTagNameNS(self::BPMN_NS, 'incoming') as $incoming) {
                    if (trim($incoming->textContent) === $flowId) {
                        $hasIncoming = true;
                        break;
                    }
                }
                if (!$hasIncoming) {
                    $errors[] = $this->issue(
                        $targetId,
                        'missing_incoming_ref',
                        'Node is target of sequence flow ' . $flowId . ' but bpmn:incoming does not reference it.',
                    );
                }
            }
        }
    }

    /**
     * MCP create/update-process does not resolve ${screen:title} / ${script:title} / ${process:name}.
     * Those portable refs resolve via core deploy-process. Leave them as hard errors here.
     *
     * @param list<array{nodeId: ?string, code: string, message: string}> $errors
     */
    private function rejectUnresolvedPortableRefs(DOMXPath $xpath, array &$errors): void
    {
        $checks = [
            ['//bpmn:task', 'screenRef', 'unresolved_screen_ref', 'Form Task pm:screenRef still uses ${screen:title}. Resolve to a numeric screen id (list-screens / create-screen) before create-process or update-process.'],
            ['//bpmn:manualTask', 'screenRef', 'unresolved_screen_ref', 'Manual Task pm:screenRef still uses ${screen:title}. Resolve to a numeric screen id before create-process or update-process.'],
            ['//bpmn:scriptTask', 'scriptRef', 'unresolved_script_ref', 'Script Task pm:scriptRef still uses ${script:title}. Resolve to a numeric script id before create-process or update-process.'],
            ['//bpmn:callActivity', 'calledElement', 'unresolved_process_ref', 'Call Activity calledElement still uses ${process:name}. Resolve to the child process bpmn id before create-process or update-process.'],
        ];

        foreach ($checks as [$query, $localName, $code, $message]) {
            foreach ($xpath->query($query) ?: [] as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }
                $value = $node->getAttributeNS(self::PM_NS, $localName);
                if ($value === '') {
                    $value = $node->getAttribute('pm:' . $localName);
                }
                if ($value === '' && $localName === 'calledElement') {
                    $value = $node->getAttribute('calledElement');
                }
                if (str_contains($value, '${')) {
                    $errors[] = $this->issue($node->getAttribute('id') ?: null, $code, $message);
                }
            }
        }

        foreach ($xpath->query('//*[@pm:interstitialScreenRef]') ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $value = $node->getAttributeNS(self::PM_NS, 'interstitialScreenRef');
            if ($value === '') {
                $value = $node->getAttribute('pm:interstitialScreenRef');
            }
            if (str_contains($value, '${')) {
                $errors[] = $this->issue(
                    $node->getAttribute('id') ?: null,
                    'unresolved_screen_ref',
                    'pm:interstitialScreenRef still uses ${screen:title}. Use numeric id or system key interstitial.',
                );
            }
        }
    }

    /**
     * @param list<array{nodeId: ?string, code: string, message: string}> $errors
     */
    private function requireAttr(
        DOMXPath $xpath,
        string $query,
        string $localName,
        string $code,
        string $message,
        array &$errors,
    ): void {
        foreach ($xpath->query($query) ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $value = $node->getAttributeNS(self::PM_NS, $localName);
            if ($value === '') {
                $value = $node->getAttribute('pm:' . $localName);
            }
            if (trim($value) === '') {
                $errors[] = $this->issue($node->getAttribute('id') ?: null, $code, $message);
            }
        }
    }

    /**
     * @return array{nodeId: ?string, code: string, message: string}
     */
    private function issue(?string $nodeId, string $code, string $message): array
    {
        return [
            'nodeId' => $nodeId,
            'code' => $code,
            'message' => $message,
        ];
    }

    /**
     * @param list<array{nodeId: ?string, code: string, message: string}> $errors
     * @param list<array{nodeId: ?string, code: string, message: string}> $warnings
     * @return array{ok: bool, errors: list<array{nodeId: ?string, code: string, message: string}>, warnings: list<array{nodeId: ?string, code: string, message: string}>}
     */
    private function result(array $errors, array $warnings): array
    {
        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
