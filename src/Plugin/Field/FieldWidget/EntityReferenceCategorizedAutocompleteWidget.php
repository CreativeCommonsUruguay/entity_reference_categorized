<?php

namespace Drupal\entity_reference_categorized\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "entity_reference_categorized_autocomplete_widget",
 *   label = @Translation("Autocomplete w/Category"),
 *   description = @Translation("One autocomplete text field to select the entity and anothet to select the category."),
 *   field_types = {
 *     "entity_reference_categorized"
 *   }
 * )
 */
class EntityReferenceCategorizedAutocompleteWidget extends EntityReferenceAutocompleteWidget {

    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);

        //configuracion
        $category_type = $this->getFieldSetting('category_type');
        $category_bundle = $this->getFieldSetting('category_bundle');
        $vocab = entity_load('taxonomy_vocabulary', $category_bundle);

        //lista de categorias de las referencias
        $cagtegory_entities = $items->referencedCategoryEntities();

        $widget_type='list'; //TODO: configurable
        
        if ($widget_type == 'list') {
            //lista de seleccion
            if(!empty($category_bundle)){
                $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($category_bundle);
                foreach ($terms as $term) {
                     $opt[$term->tid]=$term->name;
                }
            }else{
                $opt=array();
            }
            $element['category_id'] = array(
                '#title' => (isset($vocab) ? $vocab->label() : 'Categoria'),
                '#type' => 'select',
                '#options' => $opt,
                '#default_value' => isset($cagtegory_entities[$delta]) ? $cagtegory_entities[$delta]->tid->value : null,
                // Do not display a 'multiple' select box if there is only one option.
                '#multiple' => $this->multiple && count($this->options) > 1,
                '#weight' => '100',
            );
 
 
        } else {
            //autocompletar
            
            // configuracion necesaria para autocomplete, basado en EntityReference
            // Append the match operation to the selection settings.
            // Emulamos estructura que crea EntityReference con la llamada: $this->getFieldSetting('handler_settings') + ['match_operator' => $this->getSetting('match_operator')];
            $selection_settings = array(
                'target_bundles' => array(
                    'tipo_de_autoria' => $category_bundle
                ),
                'sort' => array(
                    'field' => '_none'
                ),
                'auto_create' => 0,
                'auto_create_bundle' => '',
                'match_operator' => 'CONTAINS',
            );

            $element['category_id'] = array(
                '#title' => (isset($vocab) ? $vocab->label() : 'Categoria'),
                '#type' => 'entity_autocomplete',
                '#target_type' => $category_type,
                '#selection_settings' => $selection_settings,
                '#validate_reference' => FALSE,
                '#maxlength' => 1024,
                '#default_value' => isset($cagtegory_entities[$delta]) ? $cagtegory_entities[$delta] : (isset($cagtegory_entities[0]) ? $cagtegory_entities[0] : null),
                '#size' => 20, //$this->getSetting('size'),
                '#placeholder' => t('Taxonomy term'),
                '#weight' => '100',
                '#element_validate' => array(
                    array($this, 'validate'),
                )
            );
        }

        return $element;
    }

    /**
     * Validamos que tenga 
     * @param type $element
     * @param FormStateInterface $form_state
     * @return type
     */
    public function validate($element, FormStateInterface $form_state) {
        //TODO: validar tipo de referencia. 
    }
    
}
