<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;
use yii\db\ActiveQueryInterface;

/**
 * Class Entry record.
 *
 * @property int $id ID
 * @property int|null $sectionId Section ID
 * @property int|null $fieldId Field ID
 * @property int $primaryOwnerId Primary owner ID
 * @property int $typeId Type ID
 * @property string|null $postDate Post date
 * @property string|null $expiryDate Expiry date
 * @property string $status Live
 * @property Element $element Element
 * @property Section $section Section
 * @property EntryType $type Type
 * @property User $author Author
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Entry extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::ENTRIES;
    }

    /**
     * Returns the entry’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the entry’s section.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSection(): ActiveQueryInterface
    {
        return $this->hasOne(Section::class, ['id' => 'sectionId']);
    }

    /**
     * Returns the entry’s type.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getType(): ActiveQueryInterface
    {
        return $this->hasOne(EntryType::class, ['id' => 'typeId']);
    }
}
