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

        //TODO: Analizar otras formas de agrupacion
        //  -agrupar o mostrar lista plana con tipo de relacion como dato adjunto
        //  -agrupar por categoria (como esta ahora)
        //  
        //agrupamos valores por categoria
        foreach ($elements as $delta => $entity) {
            if (!array_key_exists($values[$delta]['category_id'], $categorized)) {
                $categorized[$values[$delta]['category_id']] = array();
            }
            $categorized[$values[$delta]['category_id']][$delta] = $entity;
        }


        //TODO: Analizar otra formas de ordenacion 
        //  - ordenar peso de la taxonomia
        //  - ordenar por delta del campo multivaluado
        //  - ordendentro del grupo
//        usort($categorized, function($tid1, $tid2) {
//            $term1 = entity_load('taxonomy_term', $tid1);
//            $term2 = entity_load('taxonomy_term', $tid2);
//            return $term1->getWeight() > $term1->getWeight();
//        });

        foreach ($categorized as $category_id => $groupedElements) {
            $category_type = $this->getFieldSetting('category_type');
            $category = entity_load($category_type, $category_id);
            $returnElements[] = array(
                '#type' => 'html_tag',
                '#tag' => 'h3',
                '#value' => $this->t($category->label()),
            );
            foreach ($groupedElements as $delta => $entity) {
                $returnElements[] = $entity;
            }
        }
        return $returnElements;
    }

}
