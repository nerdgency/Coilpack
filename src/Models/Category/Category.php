<?php

namespace Expressionengine\Coilpack\Models\Category;

use Expressionengine\Coilpack\FieldtypeManager;
use Expressionengine\Coilpack\Model;
use Expressionengine\Coilpack\Models\Channel\ChannelEntry;
use Expressionengine\Coilpack\Models\FieldContent;

/**
 * Category Model
 */
class Category extends Model
{
    protected $primaryKey = 'cat_id';

    protected $table = 'categories';

    public function channelEntries()
    {
        return $this->belongsToMany(ChannelEntry::class, 'category_posts', 'cat_id', 'entry_id');
    }

    public function group()
    {
        return $this->belongsTo(CategoryGroup::class, 'group_id');
    }

    public function data()
    {
        $manager = app(FieldtypeManager::class);
        $fields = $manager->fieldsForCategoryGroup(($this->exists && $this->group) ? $this->group : null);
        $fields = $fields ?: $manager->allFields('category');

        return $this->hasOne(CategoryData::class, 'cat_id', 'cat_id')->customFields($fields);
    }

    public function scopeOrderByCustomField($query, $field, $direction = 'asc')
    {
        $manager = app(FieldtypeManager::class);

        if (! $manager->hasField($field, 'category')) {
            return $query;
        }

        $field = $manager->getField($field, 'category');
        $column = "field_id_{$field->field_id}";

        // If this field is not storing it's data on the category_field_data table we
        // will join the separate data table with a unique orderby_field_name alias
        if ($field->legacy_field_data === 'n' || $field->legacy_field_data === false) {
            $alias = "orderby_{$field->field_name}";
            $column = "$alias.$column";
            $this->scopeJoinFieldDataTable($query, $field, $alias);
        }

        return $query->orderBy($column, $direction);
    }

    public function scopeJoinFieldDataTable($query, $field, $alias = null)
    {
        if ($field->legacy_field_data == 'y' || $field->legacy_field_data === true) {
            return $query;
        }

        $table = $field->data_table_name;
        $joinTable = $alias ? "$table as $alias" : $table;
        $alias = $alias ?: $table;
        $query->leftJoin($joinTable, "$alias.cat_id", '=', $this->qualifyColumn('cat_id'));
        $query->select('*', $this->qualifyColumn('cat_id'));

        return $query;
    }

    public function files()
    {
        return $this->belongsToMany(File::class, 'file_categories', 'file_id', 'cat_id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function entries()
    {
        return $this->belongsToMany(ChannelEntry::class, 'category_posts', 'cat_id', 'entry_id');
    }

    public function __get($key)
    {
        // First we check if the model actually has a value for this attribute
        $value = parent::__get($key);

        // If we're trying to get a $key that corresponds to a Field::$field_name
        // we will forward the check to the entry's data
        if (is_null($value) && app(FieldtypeManager::class)->hasField($key, 'category')) {
            $this->getRelationValue('data');
            $fields = $this->data->fields($this);
            $value = ($fields->has($key)) ? $fields->get($key) : new FieldContent([
                // 'field' => $fields->get($key),
                'field' => app(FieldtypeManager::class)->getField($key),
                'data' => null,
                'entry' => $this,
            ]);

            // $value->setAttribute('data', 'null');
        }

        return $value;
    }
}
