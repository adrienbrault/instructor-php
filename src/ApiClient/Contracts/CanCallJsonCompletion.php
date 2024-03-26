<?php

namespace Cognesy\Instructor\ApiClient\Contracts;

interface CanCallJsonCompletion extends CanCallApi
{
    public function jsonCompletion(array $messages, string $model, array $options = []): static;
}