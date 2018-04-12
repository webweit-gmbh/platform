<?php declare(strict_types=1);

namespace Shopware\Api\Context\Struct;

use Shopware\Api\Entity\Entity;
use Shopware\Context\Rule\Rule;

class ContextCartModifierBasicStruct extends Entity
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Rule|null
     */
    protected $rule;

    /**
     * @var string
     */
    protected $contextRuleId;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getContextRuleId(): string
    {
        return $this->contextRuleId;
    }

    public function setContextRuleId(string $contextRuleId): void
    {
        $this->contextRuleId = $contextRuleId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->absolute = $amount;
    }

    public function getRule(): ?Rule
    {
        return $this->rule;
    }

    public function setRule(?Rule $rule): void
    {
        $this->rule = $rule;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
