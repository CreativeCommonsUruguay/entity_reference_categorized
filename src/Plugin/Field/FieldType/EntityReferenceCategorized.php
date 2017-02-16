<?php

namespace Drupal\entity_reference_categorized\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * @FieldType(
 *   id = "entity_reference_categorized",
 *   label = @Translation("Entity reference categorized"),
 *   description = @Translation("An entity field containing an entity reference with a category."),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_categorized_autocomplete_widget",
 *   default_formatter = "entity_reference_categorized_formatter",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class EntityReferenceCategorized extends EntityReferenceItem  {

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties = parent::propertyDefinitions($field_definition);
        $category_definition = DataDefinition::create('integer')
                ->setLabel(new TranslatableMarkup('Category'))
                ->setRequired(TRUE);

        //TODO: evaluar si aplica agregar validaciones or estricciones https://www.drupal.org/node/2015613

        $properties['category_id'] = $category_definition;

        return $properties;
    }

    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        $schema = parent::schema($field_definition);
        $schema['columns']['category_id'] = array(
            'type' => 'int',
            'description' => 'The ID of the category entity.',
            'unsigned' => TRUE,
        );

        return $schema;
    }
    
    //TODO: agregar configuracion campo
    //  Seleccionar taxonomia
    //  
    //  TIP: Basarse en la calase entityreference
}
