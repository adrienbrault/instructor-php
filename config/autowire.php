<?php
namespace Cognesy\config;

use Cognesy\Instructor\ApiClient\Contracts\CanCallApi;
use Cognesy\Instructor\ApiClient\Contracts\CanCallChatCompletion;
use Cognesy\Instructor\ApiClient\Contracts\CanCallJsonCompletion;
use Cognesy\Instructor\ApiClient\Contracts\CanCallTools;
use Cognesy\Instructor\Clients\Anthropic\AnthropicClient;
use Cognesy\Instructor\Clients\Mistral\MistralClient;
use Cognesy\Instructor\Clients\OpenAI\OpenAIClient;
use Cognesy\Instructor\Clients\OpenRouter\OpenRouterClient;
use Cognesy\Instructor\Configuration\Configuration;
use Cognesy\Instructor\Contracts\CanDeserializeClass;
use Cognesy\Instructor\Contracts\CanHandlePartialResponse;
use Cognesy\Instructor\Contracts\CanHandleRequest;
use Cognesy\Instructor\Contracts\CanHandleResponse;
use Cognesy\Instructor\Contracts\CanValidateObject;
use Cognesy\Instructor\Core\FunctionCallerFactory;
use Cognesy\Instructor\Core\RequestHandler;
use Cognesy\Instructor\Core\Response\PartialResponseHandler;
use Cognesy\Instructor\Core\Response\ResponseDeserializer;
use Cognesy\Instructor\Core\Response\ResponseHandler;
use Cognesy\Instructor\Core\Response\ResponseTransformer;
use Cognesy\Instructor\Core\Response\ResponseValidator;
use Cognesy\Instructor\Core\ResponseModelFactory;
use Cognesy\Instructor\Deserializers\Symfony\Deserializer;
use Cognesy\Instructor\Enums\Mode;
use Cognesy\Instructor\Events\EventDispatcher;
use Cognesy\Instructor\LLMs\ApiClient\JsonMode\ApiClientJsonCaller;
use Cognesy\Instructor\LLMs\ApiClient\MdJsonMode\ApiClientMdJsonCaller;
use Cognesy\Instructor\LLMs\ApiClient\ToolsMode\ApiClientToolCaller;
use Cognesy\Instructor\LLMs\OpenAI\JsonMode\OpenAIJsonCaller;
use Cognesy\Instructor\LLMs\OpenAI\MdJsonMode\OpenAIMdJsonCaller;
use Cognesy\Instructor\LLMs\OpenAI\ToolsMode\OpenAIToolCaller;
use Cognesy\Instructor\Schema\Factories\FunctionCallBuilder;
use Cognesy\Instructor\Schema\Factories\SchemaFactory;
use Cognesy\Instructor\Schema\Factories\TypeDetailsFactory;
use Cognesy\Instructor\Schema\PropertyMap;
use Cognesy\Instructor\Schema\SchemaMap;
use Cognesy\Instructor\Schema\Utils\ReferenceQueue;
use Cognesy\Instructor\Schema\Utils\SchemaBuilder;
use Cognesy\Instructor\Validators\Symfony\Validator;


function autowire(Configuration $config) : Configuration
{
    /// CORE /////////////////////////////////////////////////////////////////////////////////

    $config->declare(class: EventDispatcher::class);


    /// LLM CLIENTS //////////////////////////////////////////////////////////////////////////

    $config->declare(
        class: MistralClient::class,
        context: [
            'apiKey' => $_ENV['MISTRAL_API_KEY'] ?? '',
            'baseUri' => $_ENV['MISTRAL_BASE_URI'] ?? '',
            'connectTimeout' => 3,
            'requestTimeout' => 30,
            'metadata' => [],
            'events' => $config->reference(EventDispatcher::class),
        ],
    );

    $config->declare(
        class: OpenAIClient::class,
        name: CanCallApi::class,
        context: [
            'apiKey' => $_ENV['OPENAI_API_KEY'] ?? '',
            'baseUri' => $_ENV['OPENAI_BASE_URI'] ?? '',
            'connectTimeout' => 3,
            'requestTimeout' => 30,
            'metadata' => [],
            'events' => $config->reference(EventDispatcher::class),
        ],
    );

    $config->declare(
        class: OpenRouterClient::class,
        context: [
            'apiKey' => $_ENV['OPENAI_API_KEY'] ?? '',
            'baseUri' => $_ENV['OPENAI_BASE_URI'] ?? '',
            'connectTimeout' => 3,
            'requestTimeout' => 30,
            'metadata' => [],
            'events' => $config->reference(EventDispatcher::class),
        ],
    );

    $config->declare(
        class: AnthropicClient::class,
        context: [
            'apiKey' => $_ENV['OPENAI_API_KEY'] ?? '',
            'baseUri' => $_ENV['OPENAI_BASE_URI'] ?? '',
            'connectTimeout' => 3,
            'requestTimeout' => 30,
            'metadata' => [],
            'events' => $config->reference(EventDispatcher::class),
        ],
    );

    /// MODE SUPPORT /////////////////////////////////////////////////////////////////////////

    $config->declare(
        class: FunctionCallerFactory::class,
        context: [
            'modeHandlers' => [
                Mode::Tools->value => $config->reference(CanCallTools::class, true),
                Mode::Json->value => $config->reference(CanCallJsonCompletion::class, true),
                Mode::MdJson->value => $config->reference(CanCallChatCompletion::class, true),
            ],
            //'forceMode' => Mode::Tools,
        ]
    );

    $config->declare(
        class: ApiClientToolCaller::class,
        name: CanCallTools::class,
        context: [
            'events' => $config->reference(EventDispatcher::class),
            'client' => $config->reference(CanCallApi::class),
        ]
    );

    $config->declare(
        class: ApiClientJsonCaller::class,
        name: CanCallJsonCompletion::class,
        context: [
            'events' => $config->reference(EventDispatcher::class),
            'client' => $config->reference(CanCallApi::class),
        ]
    );

    $config->declare(
        class: ApiClientMdJsonCaller::class,
        name: CanCallChatCompletion::class,
        context: [
            'events' => $config->reference(EventDispatcher::class),
            'client' => $config->reference(CanCallApi::class),
        ]
    );


    /// SCHEMA MODEL HANDLING ////////////////////////////////////////////////////////////////

    $config->declare(
        class: ResponseModelFactory::class,
        context: [
            'functionCallFactory' => $config->reference(FunctionCallBuilder::class),
            'schemaFactory' => $config->reference(SchemaFactory::class),
            'schemaBuilder' => $config->reference(SchemaBuilder::class),
            'typeDetailsFactory' => $config->reference(TypeDetailsFactory::class),
            'events' => $config->reference(EventDispatcher::class),
        ]
    );

    $config->declare(
        class: SchemaFactory::class,
        context: [
            'schemaMap' => $config->reference(SchemaMap::class),
            'propertyMap' => $config->reference(PropertyMap::class),
            'typeDetailsFactory' => $config->reference(TypeDetailsFactory::class),
            'useObjectReferences' => false,
        ]
    );

    $config->declare(
        class: FunctionCallBuilder::class,
        context: [
            'schemaFactory' => $config->reference(SchemaFactory::class),
            'referenceQueue' => $config->reference(ReferenceQueue::class),
        ]
    );

    $config->declare(class: TypeDetailsFactory::class);

    $config->declare(class: SchemaBuilder::class);

    $config->declare(class: SchemaMap::class);

    $config->declare(class: PropertyMap::class);

    $config->declare(class: ReferenceQueue::class);


    /// REQUEST HANDLING /////////////////////////////////////////////////////////////////////

    $config->declare(
        class: RequestHandler::class,
        name: CanHandleRequest::class,
        context: [
            'functionCallerFactory' => $config->reference(FunctionCallerFactory::class),
            'responseModelFactory' => $config->reference(ResponseModelFactory::class),
            'events' => $config->reference(EventDispatcher::class),
            'responseHandler' => $config->reference(CanHandleResponse::class),
            'partialResponseHandler' => $config->reference(CanHandlePartialResponse::class),
        ]
    );


    /// RESPONSE HANDLING ////////////////////////////////////////////////////////////////////

    $config->declare(
        class: ResponseHandler::class,
        name: CanHandleResponse::class,
        context: [
            'responseDeserializer' => $config->reference(ResponseDeserializer::class),
            'responseValidator' => $config->reference(ResponseValidator::class),
            'responseTransformer' => $config->reference(ResponseTransformer::class),
        ]
    );

    $config->declare(
        class: PartialResponseHandler::class,
        name: CanHandlePartialResponse::class,
        context: [
            'responseDeserializer' => $config->reference(ResponseDeserializer::class),
            'responseTransformer' => $config->reference(ResponseTransformer::class),
        ]
    );

    $config->declare(
        class: ResponseDeserializer::class,
        context: [
            'deserializer' => $config->reference(CanDeserializeClass::class),
            'events' => $config->reference(EventDispatcher::class),
        ]
    );
    $config->declare(
        class: ResponseValidator::class,
        context: [
            'validator' => $config->reference(CanValidateObject::class),
            'events' => $config->reference(EventDispatcher::class),
        ]
    );

    $config->declare(
        class: ResponseTransformer::class,
        context: [
            'events' => $config->reference(EventDispatcher::class),
        ]
    );

    $config->declare(
        class: Deserializer::class,
        name: CanDeserializeClass::class,
    );

    $config->declare(
        class: Validator::class,
        name: CanValidateObject::class,
    );

    return $config;
}
