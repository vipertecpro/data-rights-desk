<?php

namespace Vipertecpro\DataRightsDesk\Support;

/**
 * The checker's findings. Errors block (exit code 1); warnings are worth a look.
 */
final readonly class CheckReport
{
    /**
     * @param  list<array{code: string, message: string}>  $errorList
     * @param  list<array{code: string, message: string}>  $warningList
     */
    public function __construct(
        private array $errorList,
        private array $warningList,
    ) {}

    /** @return list<array{code: string, message: string}> */
    public function errors(): array
    {
        return $this->errorList;
    }

    /** @return list<array{code: string, message: string}> */
    public function warnings(): array
    {
        return $this->warningList;
    }

    public function passed(): bool
    {
        return $this->errorList === [];
    }

    public function hasError(string $code): bool
    {
        return in_array($code, array_column($this->errorList, 'code'), true);
    }

    public function hasWarning(string $code): bool
    {
        return in_array($code, array_column($this->warningList, 'code'), true);
    }

    /** @return array{passed: bool, errors: list<array{code: string, message: string}>, warnings: list<array{code: string, message: string}>} */
    public function toArray(): array
    {
        return [
            'passed' => $this->passed(),
            'errors' => $this->errorList,
            'warnings' => $this->warningList,
        ];
    }
}
