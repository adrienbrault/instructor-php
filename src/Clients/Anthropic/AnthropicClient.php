<?php
namespace Cognesy\Instructor\Clients\Anthropic;

use Cognesy\Instructor\ApiClient\ApiClient;
use Cognesy\Instructor\ApiClient\Contracts\CanCallChatCompletion;
use Cognesy\Instructor\ApiClient\Contracts\CanCallJsonCompletion;
use Cognesy\Instructor\ApiClient\Contracts\CanCallTools;
use Cognesy\Instructor\Clients\Anthropic\ChatCompletion\ChatCompletionRequest;
use Cognesy\Instructor\Clients\Anthropic\ChatCompletion\ChatCompletionResponse;
use Cognesy\Instructor\Clients\Anthropic\ChatCompletion\PartialChatCompletionResponse;
use Cognesy\Instructor\Clients\Anthropic\JsonCompletion\JsonCompletionRequest;
use Cognesy\Instructor\Clients\Anthropic\JsonCompletion\JsonCompletionResponse;
use Cognesy\Instructor\Clients\Anthropic\JsonCompletion\PartialJsonCompletionResponse;
use Cognesy\Instructor\Clients\Anthropic\ToolsCall\PartialToolsCallResponse;
use Cognesy\Instructor\Clients\Anthropic\ToolsCall\ToolsCallRequest;
use Cognesy\Instructor\Clients\Anthropic\ToolsCall\ToolsCallResponse;
use Cognesy\Instructor\Events\EventDispatcher;

class AnthropicClient extends ApiClient implements CanCallChatCompletion, CanCallJsonCompletion, CanCallTools
{
    public string $defaultModel = 'claude-3-haiku-20240307';
    public int $defaultMaxTokens = 256;

    public function __construct(
        protected $apiKey,
        protected $baseUri = '',
        protected $connectTimeout = 3,
        protected $requestTimeout = 30,
        protected $metadata = [],
        EventDispatcher $events = null,
    ) {
        parent::__construct($events);
        $this->connector = new AnthropicConnector(
            $apiKey,
            $baseUri,
            $connectTimeout,
            $requestTimeout,
            $metadata,
        );
    }

    /// PUBLIC API ////////////////////////////////////////////////////////////////////////////////

    public function chatCompletion(array $messages, string $model, array $options = []): static {
        $model = $model ?: $this->defaultModel;
        if (!isset($options['max_tokens'])) {
            $options['max_tokens'] = $this->defaultMaxTokens;
        }
        $this->request = new ChatCompletionRequest($messages, $model, $options);
        if ($this->request->isStreamed()) {
            $this->responseClass = PartialChatCompletionResponse::class;
        } else {
            $this->responseClass = ChatCompletionResponse::class;
        }
        return $this;
    }

    public function jsonCompletion(array $messages, array $responseFormat, string $model, array $options = []): static {
        $model = $model ?: $this->defaultModel;
        if (!isset($options['max_tokens'])) {
            $options['max_tokens'] = $this->defaultMaxTokens;
        }
        $this->request = new JsonCompletionRequest($messages, $responseFormat, $model, $options);
        if ($this->request->isStreamed()) {
            $this->responseClass = PartialJsonCompletionResponse::class;
        } else {
            $this->responseClass = JsonCompletionResponse::class;
        }
        return $this;
    }

    public function toolsCall(array $messages, string $model, array $tools, array $toolChoice, array $options = []): static {
        $model = $model ?: $this->defaultModel;
        if (!isset($options['max_tokens'])) {
            $options['max_tokens'] = $this->defaultMaxTokens;
        }
        $this->request = new ToolsCallRequest($messages, $model, $tools, $toolChoice, $options);
        if ($this->request->isStreamed()) {
            $this->responseClass = PartialToolsCallResponse::class;
        } else {
            $this->responseClass = ToolsCallResponse::class;
        }
        return $this;
    }

    /// INTERNAL //////////////////////////////////////////////////////////////////////////////////

    protected function isDone(string $data): bool {
        return $data === 'event: message_stop';
    }

    protected function getData(string $data): string {
        if (str_starts_with($data, 'data:')) {
            return trim(substr($data, 5));
        }
        // ignore event lines
        return '';
    }
}