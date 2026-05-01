<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Model;

class SortRulesByPriority
{
    /**
     * @var CanShowMessageOnce
     */
    private $canShowMessageOnce;

    public function __construct(CanShowMessageOnce $canShowMessageOnce)
    {
        $this->canShowMessageOnce = $canShowMessageOnce;
    }

    /**
     * Rules with "Show Restriction Message Once" enabled have higher priority than regular rules.
     * Additionally, older rules have higher priority than newer ones.
     *
     * @param Rule[] $rules
     * @param string $carrierCode
     * @return Rule[]
     */
    public function execute(array $rules, string $carrierCode): array
    {
        $showMessageOnceRules = [];
        $regularRules = [];

        foreach ($rules as $rule) {
            if ($this->canShowMessageOnce->execute($rule, $carrierCode)) {
                $showMessageOnceRules[] = $rule;
                continue;
            }

            $regularRules[] = $rule;
        }

        $this->sortRulesById($showMessageOnceRules);
        $this->sortRulesById($regularRules);

        return array_merge($showMessageOnceRules, $regularRules);
    }

    /**
     * @param Rule[] $rules
     * @return void
     */
    private function sortRulesById(array &$rules): void
    {
        usort($rules, function (Rule $ruleA, Rule $ruleB) {
            return (int) $ruleA->getId() <=> (int) $ruleB->getId();
        });
    }
}
