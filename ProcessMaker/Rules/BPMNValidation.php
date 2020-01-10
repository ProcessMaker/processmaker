<?php

namespace ProcessMaker\Rules;

use DOMXPath;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Nayra\Storage\BpmnElement;
use ProcessMaker\Rules\BPMN\AssignPreviousUser;

class BPMNValidation implements Rule
{
    protected $rules = [
        AssignPreviousUser::class,
    ];

    protected $errors = [];

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $document
     * @return bool
     */
    public function passes($attribute, $document)
    {
        $nodes = [];
        $rules = [];
        $xpath = new DOMXPath($document);
        $childNodes = $xpath->query('//*[@id]');
        foreach ($childNodes as $node) {
            if ($node instanceof BpmnElement) {
                $nodes[$node->getAttribute('id')] = $node;
                $rules = $this->addRulesFor($node, $rules);
            }
        }
        $validator = Validator::make($nodes, $rules);
        $passes = $validator->passes();
        $this->errors = $validator->errors();
        return $passes;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'BPMN Validation failed';
    }

    public function errors($attribute, $document)
    {
        return $this->errors;
    }



    /**
     * Add rule for BPMN element $node
     *
     * @param BpmnElement $node
     * @param array $rules
     *
     * @return array
     */
    private function addRulesFor(BpmnElement $node, array $rules)
    {
        foreach ($this->rules as $rule) {
            if ($rule::applyTo($node)) {
                $rules[$node->getAttribute('id')][] = new $rule;
            }
        }
        return $rules;
    }
}
