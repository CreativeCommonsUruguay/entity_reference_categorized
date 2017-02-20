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
        $category_type = 'taxonomy_term';
        $category_taxonomy = $this->getFieldSetting('category_taxonomy');
        
        //lista de categorias de las referencias
        $cagtegory_entities = $items->referencedCategoryEntities();

        //configuracion necesaria para autocomplete, basado en entityreference
        // Append the match operation to the selection settings.
        $selection_settings = $this->getFieldSetting('handler_settings') + ['match_operator' => $this->getSetting('match_operator')];
        $selection_settings = array(
            'target_bundles' => array(
                'tipo_de_autoria' => $category_taxonomy
            ),
            'sort' => array(
                'field' => '_none'
            ),
            'auto_create' => 0,
            'auto_create_bundle' => '',
            'match_operator' => 'CONTAINS',
        );

        $element['category_id'] = array(
            '#type' => 'entity_autocomplete',
            '#target_type' => $category_type,
            '#selection_settings' => $selection_settings,
            '#validate_reference' => FALSE,
            '#maxlength' => 1024,
            '#default_value' => isset($cagtegory_entities[$delta]) ? $cagtegory_entities[$delta] : NULL,
            '#size' => 20,//$this->getSetting('size'),
            '#placeholder' => 'Ingresar categoria',
        );

        if ($this->getSelectionHandlerSetting('auto_create') && ($bundle = $this->getAutocreateBundle())) {
            $element['category_id']['#autocreate'] = array(
                'bundle' => $bundle,
                'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()->id()
            );
        }

        return $element;
    }

}
