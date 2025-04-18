<?php

namespace emagine\emstats\models;

use Craft;
use craft\base\Model;

/**
 * Em Stats settings
 */
class Settings extends Model
{
	public $languageSettings = []; // This will store an array of language settings


	// Add a property for the languageMap
    public array $languageMap = [
        'en' => ['name' => 'English', 'color' => 'bg-amber-500', 'hex' => '#f59e0b'],
        // ... other languages
    ];

    public function rules(): array // Ensure this method returns an array
    {
        $rules = parent::rules();
		$rules[] = ['languageSettings', 'each', 'rule' => ['validateLanguageSetting']];

        return $rules;
    }

    // Custom validation method to ensure each language entry is valid
    public function validateLanguageSetting($attribute, $params, $validator)
{
    foreach ($this->$attribute as $index => $languageSetting) {
        if (!isset($languageSetting['code'], $languageSetting['name'], $languageSetting['color'], $languageSetting['hex'])) {
            $this->addError($attribute . '[' . $index . ']', 'Each language setting must include a code, name, color, and hex.');
        }
    }
}
}
