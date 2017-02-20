<?php

namespace Drupal\entity_reference_categorized\Plugin\Field\FieldType;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldType(
 *   id = "entity_reference_categorized",
 *   label = @Translation("Entity reference categorized"),
 *   description = @Translation("An entity field containing an entity reference with a category."),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_categorized_autocomplete_widget",
 *   default_formatter = "entity_reference_categorized_formatter",
 *   list_class = "\Drupal\entity_reference_categorized\Plugin\Field\FieldType\EntityReferenceCategorizedFieldItemList",
 * 
 * )
 */
class EntityReferenceCategorized extends EntityReferenceItem {

    /**
     * {@inheritdoc}
     */
    public static function defaultStorageSettings() {
        return array(
            'category_type' => \Drupal::moduleHandler()->moduleExists('taxonomy_term') ? 'taxonomy_term' : 'user',
                ) + parent::defaultStorageSettings();
    }

    /**
     * {@inheritdoc}
     */
    public static function defaultFieldSettings() {
        return array(
            'category_taxonomy' => array(),
                ) + parent::defaultFieldSettings();
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties = parent::propertyDefinitions($field_definition);

        //TODO: obtener configuracion
        //$settings = $field_definition->getSettings();
        //$category_type_info = \Drupal::entityManager()->getDefinition($settings['category_type']);
        //$category_type = $settings['category_type'];
        $category_type = 'taxonomy_term'; //se va cuando sea configurable
        $category_type_info = \Drupal::entityManager()->getDefinition($category_type);

        $category_id_data_type = 'string';
        if ($category_type_info->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
            $id_definition = \Drupal::entityManager()->getBaseFieldDefinitions($category_type)[$category_type_info->getKey('id')];
            if ($id_definition->getType() === 'integer') {
                $category_id_data_type = 'integer';
            }
        }

        if ($category_id_data_type === 'integer') {
            $target_id_definition = DataReferenceTargetDefinition::create('integer')
                    ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $category_type_info->getLabel()]))
                    ->setSetting('unsigned', TRUE);
        } else {
            $target_id_definition = DataReferenceTargetDefinition::create('string')
                    ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $category_type_info->getLabel()]));
        }
        $target_id_definition->setRequired(TRUE);
        $properties['category_id'] = $target_id_definition;

        $properties['category_entity'] = DataReferenceDefinition::create('entity')
                ->setLabel($category_type_info->getLabel())
                ->setDescription(new TranslatableMarkup('The category entity'))
                // The entity object is computed out of the entity ID.
                ->setComputed(TRUE)
                ->setReadOnly(FALSE)
                ->setTargetDefinition(EntityDataDefinition::create($category_type))
                // We can add a constraint for the target entity type. The list of
                // referenceable bundles is a field setting, so the corresponding
                // constraint is added dynamically in ::getConstraints().
                ->addConstraint('EntityType', $category_type);

        return $properties;
    }

    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        $schema = parent::schema($field_definition);

        //$category_type = $field_definition->getSetting('target_type');
        $category_type = 'taxonomy_term';
        $category_type_info = \Drupal::entityManager()->getDefinition($category_type);
        $properties = static::propertyDefinitions($field_definition)['category_id'];
        if ($category_type_info->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface') && $properties->getDataType() === 'integer') {
            $columns = array(
                'category_id' => array(
                    'description' => 'The ID of the category entity.',
                    'type' => 'int',
                    'unsigned' => TRUE,
                ),
            );
        } else {
            $columns = array(
                'category_id' => array(
                    'description' => 'The ID of the category entity.',
                    'type' => 'varchar_ascii',
                    // If the target entities act as bundles for another entity type,
                    // their IDs should not exceed the maximum length for bundles.
                    'length' => $category_type_info->getBundleOf() ? EntityTypeInterface::BUNDLE_MAX_LENGTH : 255,
                ),
            );
        }
        $indexes = array(
            'category_id' => array('category_id')
        );

        $schema['columns'] = array_merge($schema['columns'], $columns);
        $schema['indexes'] = array_merge($schema['indexes'], $indexes);

        return $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
        $form = parent::fieldSettingsForm($form, $form_state);

        $field = $form_state->getFormObject()->getEntity();

        $category_type = 'taxonomy_term'; //$this->getSetting('target_type');
        //vocabularios
        $vocabularies = taxonomy_vocabulary_get_names();
        foreach ($vocabularies as $voc_id) {
            $vocab = entity_load('taxonomy_vocabulary', $voc_id);
            $options[$voc_id] = $vocab->label();
        }

        $form['category_taxonomy'] = array(
            '#type' => 'details',
            '#title' => t('Category type'),
            '#open' => TRUE,
            '#tree' => TRUE,
            '#process' => array(array(get_class($this), 'formProcessMergeParent')),
        );

        $form['category_taxonomy']['category_taxonomy'] = array(
            '#type' => 'select',
            '#title' => t('Category taxonomy'),
            '#options' => $options,
            '#default_value' => $field->getSetting('category_taxonomy'),
            '#required' => TRUE,
            '#ajax' => TRUE,
            '#limit_validation_errors' => array(),
        );

        //TODO: analizar dependenias al igual que EntityReference 

        return $form;
    }

}
