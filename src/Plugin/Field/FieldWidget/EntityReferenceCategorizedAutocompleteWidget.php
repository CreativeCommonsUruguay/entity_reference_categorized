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
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);

    //TODO: Cargar caonfiguracion del campo
    //y en funcion de la taxonomia configurada y el tipo de 
    //widget crearlo aqui
    
    $widget['category_id'] = array(
      '#title' => $this->t('Category'),
      '#type' => 'number',
      '#default_value' => isset($items[$delta]) ? $items[$delta]->category_id : 1,
      '#weight' => 10,
    );

    return $widget;
  }
  
  //TODO: agregar configuracion de widget para 
}