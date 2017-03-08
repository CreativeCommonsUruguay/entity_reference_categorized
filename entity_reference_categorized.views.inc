<?php

use Drupal\field\FieldStorageConfigInterface;
use Drupal\entity_reference_categorized\Plugin\Field\FieldType\EntityReferenceCategorized;


/**
 * Implements hook_field_views_data().
 *
 * The function implements the hook in behalf of 'core' because it adds a
 * relationship and a reverse relationship to entity_reference field type, which
 * is provided by core.
 * 
 * Copiado de: core/modules/views/views.views.inc -> core_field_views_data(...)
 * el core declara las relaciones solo para el tipo de contenido entity_reference 
 * por ende aunque se extiendan las clases que definen este tipo de entidad no 
 * no se generan las relaciones. Por ende se copia el codigo
 * 
 */
function entity_reference_categorized_field_views_data(FieldStorageConfigInterface $field_storage) {

    if ($field_storage->getType() != 'entity_reference_categorized') {
       $data = views_field_default_views_data($field_storage);
    } else {
       //aprovechamos helpper definido en clase base.
       $data = EntityReferenceCategorized::create_field_views_data($field_storage);
    }

    return $data;
}
