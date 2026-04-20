<?php

declare(strict_types=1);

namespace App\Services\Ledger\Dto;

use App\Enums\BalanceType;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;

class CreateEntryDto
{
    private int $userId;

    private TransactionType $type;

    private BalanceType $balanceType;

    private string $amount;

    private ?Model $reference = null;

    private ?array $metadata = null;

    private ?string $idempotencyKey = null;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function setType(TransactionType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getBalanceType(): BalanceType
    {
        return $this->balanceType;
    }

    public function setBalanceType(BalanceType $balanceType): self
    {
        $this->balanceType = $balanceType;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getReference(): ?Model
    {
        return $this->reference;
    }

    public function setReference(?Model $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function setIdempotencyKey(?string $idempotencyKey): self
    {
        $this->idempotencyKey = $idempotencyKey;
        return $this;
    }
}
