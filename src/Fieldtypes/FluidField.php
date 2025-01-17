<?php

namespace Expressionengine\Coilpack\Fieldtypes;

use Expressionengine\Coilpack\Api\Graph\Fields;
use Expressionengine\Coilpack\Api\Graph\Support\FieldtypeRegistrar;
use Expressionengine\Coilpack\Api\Graph\Support\GeneratedType;
use Expressionengine\Coilpack\Contracts\GeneratesGraphType;
use Expressionengine\Coilpack\Contracts\ListsGraphType;
use Expressionengine\Coilpack\FieldtypeManager;
use Expressionengine\Coilpack\FieldtypeOutput;
use Expressionengine\Coilpack\Fieldtypes\Presenters\FluidFieldPresenter;
use Expressionengine\Coilpack\Models\Channel\ChannelField;
use Expressionengine\Coilpack\Models\FieldContent;

class FluidField extends Fieldtype implements GeneratesGraphType, ListsGraphType
{
    protected $presenter;

    public function __construct(string $name, $id = null)
    {
        parent::__construct($name, $id);
        $this->presenter = new FluidFieldPresenter;
    }

    public function apply(FieldContent $content, array $parameters = [])
    {
        $data = $this->presenter->present($content, $parameters);

        return FieldtypeOutput::for($this)->value($data);
    }

    public function generateGraphType(ChannelField $field)
    {
        $fluidField = $field;

        // Get the fields referenced in this Fluid field
        $fields = app(FieldtypeManager::class)->allFields()->filter(function ($field) use ($fluidField) {
            return in_array($field->field_id, $fluidField->field_settings['field_channel_fields'] ?? []);
        });

        return new GeneratedType([
            'fields' => function () use ($fields) {
                $fields = $fields->flatmap(function ($field) {
                    return [
                        $field->field_name => new Fields\Fieldtype([
                            'type' => app(FieldtypeRegistrar::class)->getTypeForField($field) ?: \GraphQL\Type\Definition\Type::string(),
                            'field' => $field,
                            'selectable' => false,
                            'is_relation' => false,
                            'resolve' => function ($root, array $args) use ($field) {
                                if ($root->field->field_name != $field->field_name) {
                                    return null;
                                }

                                return $root;
                            },
                        ]),
                    ];
                })->toArray();

                $fields['_field_name'] = [
                    'type' => \GraphQL\Type\Definition\Type::string(),
                    'resolve' => function ($root, array $args) {
                        return $root->field->field_name;
                    },
                ];

                $fields['_field_type'] = [
                    'type' => \GraphQL\Type\Definition\Type::string(),
                    'resolve' => function ($root, array $args) {
                        return $root->field->field_type;
                    },
                ];

                return $fields;
            },
        ]);

        /*
        // This approach works for a union type response however this seems less desirable
        // from a consumer point of view.  The syntax to query is more verbose and
        // while the response can be smaller it doesn't seem worth the trade off.
        return new GeneratedUnionType([
            'types' => function() use($fields, $fluidField) {
                return $fields->flatmap(function ($field) use ($fluidField) {
                    $name = "{$fluidField->field_name}_{$field->field_name}";
                    $typeDefinition = new GeneratedType([
                        'name' => $name,
                        'fields' => function () use($field) {
                            return [
                                'value' => new Fields\Fieldtype([
                                    'type' => app(FieldtypeRegistrar::class)->getTypeForField($field) ?: \GraphQL\Type\Definition\Type::string(),
                                    'field' => $field,
                                    'selectable' => false,
                                    'is_relation' => false,
                                    'resolve' => function ($root, array $args) use ($field) {
                                        return $root;
                                    },
                                ])
                                ];
                        }
                    ]);
                    $type = GraphQL::addType($typeDefinition, $name);

                    return [
                        $name => GraphQL::type($name)
                    ];
                })->toArray();
            },
            'resolveType' => function($value) use($fluidField)
            {
                if(!$value instanceof FieldContent) {
                    return null;
                }
                $name = "{$fluidField->field_name}_{$value->field->field_name}";
                return GraphQL::type($name);
            }
        ]);
        */
    }
}
