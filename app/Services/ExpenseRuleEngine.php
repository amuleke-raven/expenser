<?php

namespace App\Services;

use App\Enums\RuleDimension;
use App\Enums\RuleOperator;
use App\Models\Expense;
use App\Models\ExpenseRule;

class ExpenseRuleEngine
{
    public function evaluate(Expense $expense): object
    {
        $expense->load([
            'expenseType.rules',
            'expenseType.expenseGroup.rules',
            'user.country',
            'user.roles',
        ]);

        $rules = $expense->expenseType->rules
            ->merge($expense->expenseType->expenseGroup->rules);

        $failedRules = [];

        foreach ($rules as $rule) {
            if (! $this->evaluateRule($rule, $expense)) {
                $failedRules[] = $rule;
            }
        }

        return (object) [
            'passes' => empty($failedRules),
            'failedRules' => $failedRules,
        ];
    }

    private function evaluateRule(ExpenseRule $rule, Expense $expense): bool
    {
        return match ($rule->dimension) {
            RuleDimension::Amount => $this->evaluateAmount($rule, $expense),
            RuleDimension::Country => $this->evaluateCountry($rule, $expense),
            RuleDimension::Role => $this->evaluateRole($rule, $expense),
        };
    }

    private function evaluateAmount(ExpenseRule $rule, Expense $expense): bool
    {
        $amount = (float) $expense->total_amount;
        $threshold = (float) ($rule->value['amount'] ?? 0);

        return match ($rule->operator) {
            RuleOperator::Gte => $amount >= $threshold,
            RuleOperator::Lte => $amount <= $threshold,
            RuleOperator::Eq => $amount == $threshold,
            RuleOperator::In => in_array($amount, (array) ($rule->value['amounts'] ?? [])),
        };
    }

    private function evaluateCountry(ExpenseRule $rule, Expense $expense): bool
    {
        $countryId = $expense->user?->country_id;
        $allowed = (array) ($rule->value['countries'] ?? []);

        return in_array($countryId, $allowed);
    }

    private function evaluateRole(ExpenseRule $rule, Expense $expense): bool
    {
        $roles = (array) ($rule->value['roles'] ?? []);

        return $expense->user?->hasAnyRole($roles) ?? false;
    }
}
