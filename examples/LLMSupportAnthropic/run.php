# Support for Anthropic API


Mode compatibility:
- Mode::MdJson, Mode::Json - supported
- Mode::Tools - not supported


```php
<?php
$loader = require 'vendor/autoload.php';
$loader->add('Cognesy\\Instructor\\', __DIR__ . '../../src/');

use Cognesy\Instructor\HttpClient\Anthropic\AnthropicClient;
use Cognesy\Instructor\HttpClient\JsonPostRequest;
use Cognesy\Instructor\HttpClient\Mistral\JsonCompletionRequest;
use Cognesy\Instructor\HttpClient\Mistral\MistralClient;
use Cognesy\Instructor\Utils\Env;

enum UserType : string {
    case Guest = 'Guest';
    case User = 'User';
    case Admin = 'Admin';
}

class User {
    public int $age;
    public string $name;
    public string $username;
    public UserType $role;
    /** @var string[] */
    public array $hobbies;
}

// Anthropic instance params
$yourApiKey = Env::get('MISTRAL_API_KEY');
$yourBaseUri = 'https://api.mistral.ai/v1';

// Create instance of OpenAI client initialized with custom parameters
$client = new MistralClient(
    baseUri: $yourBaseUri,
    apiKey: $yourApiKey,
);

$response = $client->withRequest(new JsonCompletionRequest(
    messages: [
        ["role" => "user", "content" => "Hello, Mistral"],
    ],
    model: 'mistral-small-latest',
    options: ['stream' => true ]
))
->wiretap(fn($event) => $event->print())
->streamAll();
//dump($response);













//// Anthropic instance params
//$yourApiKey = Env::get('ANTHROPIC_API_KEY');
//$yourBaseUri = 'https://api.anthropic.com/v1';
//
//// Create instance of OpenAI client initialized with custom parameters
//$client = new AnthropicClient(
//    baseUri: $yourBaseUri,
//    apiKey: $yourApiKey,
//);
//
//$response = $client->withRequest(new JsonPostRequest(
//    payload: [
//        'model' => 'claude-3-haiku-20240307',
//        'max_tokens' => 100,
//        'messages' => [
//            ["role" => "user", "content" => "Hello, Claude"],
//        ],
//        //'stream' => true,
//    ],
//    endpoint: '/messages',
//))
//->wiretap(fn($event) => $event->print())
//->send();
//dump($response);










///// Get Instructor with the default client component overridden with your own
//$instructor = new Instructor([Client::class => $client]);
//
//print("Printing partial updates:\n\n");
//
////$models = ['open-mistral-7b', 'open-mixtral-8x7b', 'mistral-small-latest	', 'mistral-medium-latest', 'mistral-large-latest'];
//$model = 'mistral-small-latest';
//$executionMode = Mode::MdJson;
//
//$user = $instructor
//    ->onEvent(ResponseReceivedFromLLM::class, fn($event) => dump($event))
//    ->respond(
//        messages: "Jason (@jxnlco) is 25 years old and is the admin of this project. He likes playing football and reading books.",
//        responseModel: User::class,
//        model: $model,
//        mode: $executionMode,
//        options: ['stream' => true ]
//    );
//
//print("Completed response model:\n\n");
//dump($user);

?>
```
