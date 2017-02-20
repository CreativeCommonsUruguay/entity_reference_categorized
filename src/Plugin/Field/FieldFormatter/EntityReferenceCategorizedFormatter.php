<?php

namespace Drupal\entity_reference_categorized\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * @FieldFormatter(
 *   id = "entity_reference_categorized_formatter",
 *   label = @Translation("Entity label and category"),
 *   description = @Translation("Display referenced entities  as a list items gruped by category"),
 *   field_types = {
 *     "entity_reference_categorized"
 *   }
 * )
 */
class EntityReferenceCategorizedFormatter extends EntityReferenceLabelFormatter {

    public function viewElements(FieldItemListInterface $items, $langcode) {
        $elements = parent::viewElements($items, $langcode);
        $values = $items->getValue();
        $returnElements = array();
        $categorized = array();

        //agrupamos valores por categoria
        foreach ($elements as $delta => $entity) {
            if (!array_key_exists($values[$delta]['category_id'], $categorized)) {
                $categorized[$values[$delta]['category_id']] = array();
            }
            $categorized[$values[$delta]['category_id']][$delta] = $entity;
        }

        foreach ($categorized as $category_id => $groupedElements) {
            $term = entity_load('taxonomy_term', $category_id);    
            $returnElements[] = array(
                '#type' => 'html_tag',
                '#tag' => 'h3',
                '#value' => $this->t($term->label()),
            );
            foreach ($groupedElements as $delta => $entity) {
                $returnElements[] = $entity;
            }
        }
        return $returnElements;
    }

}

//TODO: crear mas opciones de formatters