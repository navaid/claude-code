<?php
declare(strict_types=1);

namespace Vendor\Shiprestriction\Plugin\Ui\Form\Element;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Ui\Component\Form\Element\Select;

/**
 * Magento 2.4.8-p4 introduced a strict array type-hint on Sanitizer::sanitize().
 * AbstractOptionsField::prepare() calls sanitize($option) expecting each option to be an array.
 * CarriersMethodsOptions and other Amasty tree option providers may return a flat associative
 * array of the form ['carrier_method' => 'Label string', ...], causing a TypeError at line 76.
 * This plugin normalises options into proper ['value' => ..., 'label' => ...] arrays before
 * prepare() runs so the sanitizer receives the expected structure.
 */
class SelectPlugin
{
    public function beforePrepare(Select $subject): void
    {
        $config = $subject->getData('config');

        if (empty($config['options'])) {
            return;
        }

        $options = $config['options'];

        if ($options instanceof OptionSourceInterface) {
            $options = $options->toOptionArray();
        }

        if (!is_array($options)) {
            return;
        }

        $normalized = $this->normalizeOptions($options);

        if ($normalized !== $options) {
            $config['options'] = $normalized;
            $subject->setData('config', $config);
        }
    }

    private function normalizeOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $key => $option) {
            if (is_string($option)) {
                // Flat associative format: 'method_code' => 'Label' → proper option array
                $normalized[] = ['value' => (string) $key, 'label' => $option];
            } elseif (is_array($option)) {
                // Recursively normalise nested option groups
                if (isset($option['optgroup']) && is_array($option['optgroup'])) {
                    $option['optgroup'] = $this->normalizeOptions($option['optgroup']);
                }
                if (isset($option['value']) && is_array($option['value'])) {
                    $option['value'] = $this->normalizeOptions($option['value']);
                }
                $normalized[] = $option;
            }
        }

        return $normalized;
    }
}
