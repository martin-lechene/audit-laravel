<?php

namespace MartinLechene\AuditSuite\Services;

use MartinLechene\AuditSuite\Contracts\AuditRule;
use Illuminate\Support\Collection;

class RuleEngine
{
    private array $rules = [];

    /**
     * Enregistre une règle
     */
    public function register(string $name, string $ruleClass): void
    {
        $this->rules[$name] = $ruleClass;
    }

    /**
     * Enregistre une règle via instance
     */
    public function registerRule(AuditRule $rule): void
    {
        $this->rules[$rule->getName()] = $rule;
    }

    /**
     * Récupère une règle par son nom
     */
    public function getRule(string $name): ?AuditRule
    {
        if (!isset($this->rules[$name])) {
            return null;
        }

        $rule = $this->rules[$name];

        if (is_string($rule)) {
            $rule = app($rule);
            $this->rules[$name] = $rule;
        }

        return $rule;
    }

    /**
     * Récupère toutes les règles d'une catégorie
     */
    public function getRulesByCategory(string $category): Collection
    {
        return collect($this->rules)->filter(function ($rule) use ($category) {
            $ruleInstance = is_string($rule) ? app($rule) : $rule;
            return $ruleInstance->getCategory() === $category;
        })->map(function ($rule) {
            return is_string($rule) ? app($rule) : $rule;
        });
    }

    /**
     * Récupère toutes les règles
     */
    public function getAllRules(): Collection
    {
        return collect($this->rules)->map(function ($rule) {
            return is_string($rule) ? app($rule) : $rule;
        });
    }

    /**
     * Vérifie si une règle existe
     */
    public function hasRule(string $name): bool
    {
        return isset($this->rules[$name]);
    }
}

