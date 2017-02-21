<?php


namespace Drupal\entity_reference_categorized\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use \Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines a item list class for entity reference fields.
 * 
 * Extendemos la clase para agregarle un helper y reimplementar algunas cosas
 */
class EntityReferenceCategorizedFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function referencedCategoryEntities() {
    if (empty($this->list)) {
      return array();
    }

    // Collect the IDs of existing entities to load, and directly grab the
    // "autocreate" entities that are already populated in $item->entity.
    $category_entities = $ids = array();
    foreach ($this->list as $delta => $item) {
      if ($item->category_id !== NULL) {
        $ids[$delta] = $item->category_id;
      }
      elseif ($item->hasNewEntity()) {
        $category_entities[$delta] = $item->category_entity;
      }
    }

    // Load and add the existing entities.
    if ($ids) {
      $category_type = 'taxonomy_term';//$this->getFieldDefinition()->getSetting('target_type');
      $entities = \Drupal::entityManager()->getStorage($category_type)->loadMultiple($ids);
      foreach ($ids as $delta => $category_id) {
        if (isset($entities[$category_id])) {
          $category_entities[$delta] = $entities[$category_id];
        }
      }
      // Ensure the returned array is ordered by deltas.
      ksort($category_entities);
    }

    return $category_entities;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);

    if ($default_value) {
      // Convert UUIDs to numeric IDs.
      $uuids = array();
      foreach ($default_value as $delta => $properties) {
        if (isset($properties['category_uuid'])) {
          $uuids[$delta] = $properties['category_uuid'];
        }
      }
      if ($uuids) {
        $category_type = 'taxonomy_term';//$definition->getSetting('category_type');
        $entity_ids = \Drupal::entityQuery($category_type)
          ->condition('uuid', $uuids, 'IN')
          ->execute();
        $entities = \Drupal::entityManager()
          ->getStorage($category_type)
          ->loadMultiple($entity_ids);

        $entity_uuids = array();
        foreach ($entities as $id => $entity) {
          $entity_uuids[$entity->uuid()] = $id;
        }
        foreach ($uuids as $delta => $uuid) {
          if (isset($entity_uuids[$uuid])) {
            $default_value[$delta]['category_id'] = $entity_uuids[$uuid];
            unset($default_value[$delta]['category_uuid']);
          }
          else {
            unset($default_value[$delta]);
          }
        }
      }

      // Ensure we return consecutive deltas, in case we removed unknown UUIDs.
      $default_value = array_values($default_value);
    }
    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $default_value = parent::defaultValuesFormSubmit($element, $form, $form_state);

    // Convert numeric IDs to UUIDs to ensure config deployability.
    $ids = array();
    foreach ($default_value as $delta => $properties) {
      if (isset($properties['category_entity']) && $properties['category_entity']->isNew()) {
        // This may be a newly created term.
        $properties['category_entity']->save();
        $default_value[$delta]['category_id'] = $properties['category_entity']->id();
        unset($default_value[$delta]['category_entity']);
      }
      $ids[] = $default_value[$delta]['category_id'];
    }
    $entities = \Drupal::entityManager()
      ->getStorage('taxonomy_term' )//$this->getSetting('target_type'))
      ->loadMultiple($ids);

    foreach ($default_value as $delta => $properties) {
      unset($default_value[$delta]['category_id']);
      $default_value[$delta]['category_uuid'] = $entities[$properties['category_id']]->uuid();
    }
    return $default_value;
  }

}
