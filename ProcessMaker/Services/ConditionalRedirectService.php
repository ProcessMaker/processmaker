<?php

namespace ProcessMaker\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use ProcessMaker\Contracts\ConditionalRedirectServiceInterface;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\FormalExpression;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;

/**
 * ConditionalRedirectService
 *
 * This service handles the evaluation of conditional redirects in ProcessMaker workflows.
 * It processes a set of conditions and returns the first condition that evaluates to true,
 * along with its associated redirect configuration.
 *
 * The service uses FEEL (Friendly Enough Expression Language) expressions to evaluate
 * conditions against process data, allowing for dynamic routing based on runtime data.
 *
 * @since 4.0.0
 */
class ConditionalRedirectService implements ConditionalRedirectServiceInterface
{
    /**
     * @var FormalExpression
     */
    private FormalExpression $feel;

    /**
     * @var DataManager
     */
    private DataManager $dataManager;

    private array $errors = [];

    /**
     * Constructor
     *
     * Initializes the service with required dependencies for expression evaluation
     * and data management.
     */
    public function __construct()
    {
        $this->feel = new FormalExpression();
        $this->dataManager = new DataManager();
    }

    /**
     * Process a set of conditional redirects and return the first condition that evaluates to true
     *
     * This method iterates through an array of conditional redirect configurations,
     * evaluating each condition using FEEL expressions against the provided data.
     * Returns the first condition that evaluates to true, or null if no conditions match.
     *
     * @param array $conditionalRedirect Array of conditional redirect configurations
     *                                  Each item must contain a 'condition' key with a FEEL expression
     *                                  Example: [
     *                                      [
     *                                          'condition' => 'amount > 1000',
     *                                          'type' => 'externalURL',
     *                                          'value' => 'https://example.com/approval'
     *                                      ],
     *                                      [
     *                                          'condition' => 'status = "urgent"',
     *                                          'type' => 'taskList',
     *                                          'value' => null
     *                                      ]
     *                                  ]
     * @param array $data Process data to evaluate conditions against
     *                    Contains variables from the process instance
     *                    Example: ['amount' => 1500, 'status' => 'urgent', 'user' => 'john']
     *
     * @return array|null The first matching conditional redirect configuration, or null if none match
     *
     * @throws InvalidArgumentException When a condition item is missing the required 'condition' key
     *
     * @example
     * ```php
     * $service = new ConditionalRedirectService();
     *
     * $conditionalRedirect = [
     *     [
     *         'condition' => 'amount > 1000',
     *         'type' => 'externalURL',
     *         'value' => 'https://example.com/approval'
     *     ],
     *     [
     *         'condition' => 'amount <= 1000',
     *         'type' => 'taskList',
     *         'value' => null
     *     ]
     * ];
     *
     * $data = ['amount' => 1500, 'status' => 'pending'];
     *
     * $result = $service->resolve($conditionalRedirect, $data);
     * // Returns: ['condition' => 'amount > 1000', 'type' => 'externalURL', 'value' => 'https://example.com/approval']
     * ```
     */
    public function resolve(array $conditionalRedirect, array $data): ?array
    {
        $this->errors = [];
        $data = $this->normalizeDataForFeel($data);

        foreach ($conditionalRedirect as $item) {
            if (!isset($item['condition'])) {
                throw new InvalidArgumentException('Condition is required');
            }

            $condition = $item['condition'];

            $this->feel->setBody($condition);
            try {
                $result = ($this->feel)($data);
            } catch (\Throwable $e) {
                $this->errors[] = $e->getMessage();
                continue;
            }
            if ($result) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Normalize data so FEEL comparisons like form_input_1==0 work when form sends string "0".
     * Converts numeric strings to int/float so that the first matching condition is the intended one.
     *
     * @param array $data
     * @return array
     */
    private function normalizeDataForFeel(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = $this->normalizeDataForFeel($value);
            } elseif (is_string($value) && is_numeric($value)) {
                $normalized[$key] = str_contains($value, '.') ? (float) $value : (int) $value;
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Process conditional redirects for a specific process request token
     *
     * This method is a convenience wrapper that automatically retrieves process data
     * from a ProcessRequestToken and evaluates conditional redirects against that data.
     * It's commonly used when you have a token and want to determine the appropriate
     * redirect based on the current process state and data, it also considers
     * multi-instance tasks.
     *
     * @param array $conditionalRedirect Array of conditional redirect configurations
     *                                  Each item must contain a 'condition' key with a FEEL expression
     *                                  Example: [
     *                                      [
     *                                          'condition' => 'taskStatus = "completed"',
     *                                          'type' => 'homepageDashboard',
     *                                          'value' => null
     *                                      ],
     *                                      [
     *                                          'condition' => 'taskStatus = "pending"',
     *                                          'type' => 'taskList',
     *                                          'value' => null
     *                                      ]
     *                                  ]
     * @param ProcessRequestToken $token The process request token to evaluate conditions against
     *                                   The token contains the process instance data and context
     *
     * @return array|null The first matching conditional redirect configuration, or null if none match
     *
     * @throws InvalidArgumentException When a condition item is missing the required 'condition' key
     *
     * @example
     * ```php
     * $service = new ConditionalRedirectService();
     * $token = ProcessRequestToken::find(123);
     *
     * $conditionalRedirect = [
     *     [
     *         'condition' => 'taskStatus = "completed"',
     *         'type' => 'homepageDashboard',
     *         'value' => null
     *     ],
     *     [
     *         'condition' => 'taskStatus = "pending"',
     *         'type' => 'taskList',
     *         'value' => null
     *     ]
     * ];
     *
     * $result = $service->resolveForToken($conditionalRedirect, $token);
     * // Returns the appropriate redirect configuration based on the token's data
     * ```
     *
     * @see resolve() For detailed parameter documentation
     */
    public function resolveForToken(array $conditionalRedirect, ProcessRequestToken $token): ?array
    {
        $data = $this->dataManager->getData($token);
        $result = $this->resolve($conditionalRedirect, $data);
        if ($this->errors) {
            $case_number = $this->getCaseNumber($token);
            foreach ($this->errors as $error) {
                $this->logError($token, $error, $case_number);
            }
        }

        return $result;
    }

    private function getCaseNumber(ProcessRequestToken $token): ?int
    {
        // get process request from relationship if loaded, otherwise get from database
        if ($token->relationLoaded('processRequest')) {
            $case_number = $token->processRequest->case_number;
        } else {
            // get case_number only to avoid to hidrate all the process request data
            $case_number = ProcessRequest::where('id', $token->process_request_id)->value('case_number');
        }

        return $case_number;
    }

    /**
     * Log an error when evaluating conditional redirects
     *
     * @param ProcessRequestToken $token
     * @param string $error
     * @param string $case_number
     */
    private function logError(ProcessRequestToken $token, string $error, int $case_number)
    {
        Log::error('Conditional Redirect: ', ['error' => $error, 'case_number' => $case_number, 'token' => $token->id]);
    }
}
