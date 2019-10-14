<?php
declare(strict_types=1);

namespace AmsterdamPHP\TrelloChecklister;

final class ExecutionResult
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var mixed[]
     */
    private $context;

    /**
     * @var bool
     */
    private $success;

    public function __construct(bool $success, string $message, array $context = [])
    {
        $this->message = $message;
        $this->context = $context;
        $this->success = $success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

}
