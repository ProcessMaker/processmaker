<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

class TriggerTranslator
{
    public function translate(string $code): string
    {
        $code = $this->convertPm3Assignments($code);
        $code = $this->convertPm3VariableReads($code);
        $code = $this->replacePm3ApiCalls($code);

        return $code;
    }

    public function pm3VariableToMustache(mixed $variable): string
    {
        if (!is_string($variable) || trim($variable) === '') {
            return '';
        }

        $variable = trim($variable);
        if (str_starts_with($variable, '@@')) {
            $variable = substr($variable, 2);
        } elseif (str_starts_with($variable, '@')) {
            $variable = substr($variable, 1);
        }

        if ($variable === ''
            || $variable === 'SYS_NEXT_USER_TO_BE_ASSIGNED'
            || $variable === 'SYS_GROUP_TO_BE_ASSIGNED') {
            return '';
        }

        return '{{ ' . $variable . ' }}';
    }

    public function replacePm3Variables(string $value): string
    {
        return preg_replace('/@@(\w+)/', '{{ $1 }}', $value) ?? $value;
    }

    private function convertPm3Assignments(string $code): string
    {
        return preg_replace('/@@(\w+)\s*=/', "\$data['$1'] =", $code) ?? $code;
    }

    private function convertPm3VariableReads(string $code): string
    {
        return preg_replace('/@@(\w+)/', "\$data['$1']", $code) ?? $code;
    }

    private function replacePm3ApiCalls(string $code): string
    {
        $replacements = [
            'PMFAssignUserToCase(' => '$api->assignUserToCase(',
            'PMFDerivateCase(' => '$api->derivateCase(',
            'PMFRedirectToStep(' => '$api->redirectToStep(',
            'PMFSendMessage(' => '$api->sendMessage(',
            'PMFExecuteTrigger(' => '$api->executeTrigger(',
            'PMFNewCase(' => '$api->newCase(',
            'PMFInfo(' => '$api->info(',
            'PMFDeleteCase(' => '$api->deleteCase(',
            'PMFGetCaseInfo(' => '$api->getCaseInfo(',
            'PMFUpdateCase(' => '$api->updateCase(',
            'PMFPauseCase(' => '$api->pauseCase(',
            'PMFUnpauseCase(' => '$api->unpauseCase(',
            'PMFCancelCase(' => '$api->cancelCase(',
            'executeQuery(' => '$api->executeQuery(',
            'wsSend(' => '$api->wsSend(',
            'G::LoadClass(' => '// G::LoadClass(',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $code);
    }
}
