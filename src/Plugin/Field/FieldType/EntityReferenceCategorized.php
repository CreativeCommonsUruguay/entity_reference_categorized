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
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 *  
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
        //Por el momento no se puede cambiar el tipo de entidad. 
        //podria ser una mejora permitir cualquier tipo de entidad para 
        //representar una categoria
        return array(
            'category_type' => \Drupal::moduleHandler()->moduleExists('taxonomy') ? 'taxonomy_term' : 'user',
                ) + parent::defaultStorageSettings();
    }

    /**
     * {@inheritdoc}
     */
    public static function defaultFieldSettings() {
        return array(
            'category_bundle' => array(),
                ) + parent::defaultFieldSettings();
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties = parent::propertyDefinitions($field_definition);

        $category_type = $field_definition->getSetting('category_type');
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

        $category_type = $field_definition->getSetting('category_type');
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

        //anexamos nuestro esquema para la configuracion al de EntiryReference
        $schema['columns'] += $columns;
        $schema['indexes'] += $indexes;

        return $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
        $form = parent::fieldSettingsForm($form, $form_state);

        $field = $form_state->getFormObject()->getEntity();
        $category_type = $this->getSetting('category_type');

        //obtenemos todos los vocabularios para listar
        //TODO: forma de obtener deberia ser dependiente del $catgory_type. Ej  EntityTypeBundleInfo::getBundleInfo($category_type);
        $vocabularies = taxonomy_vocabulary_get_names();
        foreach ($vocabularies as $voc_id) {
            $vocab = entity_load('taxonomy_vocabulary', $voc_id);
            $options[$voc_id] = $vocab->label();
        }

        $form['category_bundle'] = array(
            '#type' => 'details',
            '#title' => t('Category type'),
            '#open' => TRUE,
            '#tree' => TRUE,
            '#process' => array(array(get_class($this), 'formProcessMergeParent')),
        );

        $form['category_bundle']['category_bundle'] = array(
            '#type' => 'select',
            '#title' => t('Category taxonomy'),
            '#options' => $options,
            '#default_value' => $field->getSetting('category_bundle'),
            '#required' => TRUE,
            '#ajax' => TRUE,
            '#limit_validation_errors' => array(),
        );

        //TODO: analizar dependenias al igual que EntityReference 
        //TODO: permitir crear entidades si no existen para usar una taxonomia libre. 
        //      Ver configuracion de auto_create de ER

        return $form;
    }

    /**
     * Helpper that defines the relationships field available on views relationship.
     * 
     * This helper is intended to be used on hook_field_views_data(..) for this class
     * and other posible inherited clasess.
     * 
     * 
     * @param Drupal\field\FieldStorageConfigInterface $field_storage
     * @return array views data definition array for the field
     */
    public static function create_field_views_data(FieldStorageConfigInterface $field_storage) {
        $data = views_field_default_views_data($field_storage);
        $entity_manager = \Drupal::entityManager();
        $category_type_id = $field_storage->getTargetEntityTypeId();
        /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
        $table_mapping = $entity_manager->getStorage($category_type_id)->getTableMapping();

        foreach ($data as $table_name => $table_data) {
            // Add a relationship to the target entity type.
            $target_entity_type_id = $field_storage->getSetting('target_type');
            $target_entity_type = $entity_manager->getDefinition($target_entity_type_id);
            $entity_type_id = $field_storage->getTargetEntityTypeId();
            $entity_type = $entity_manager->getDefinition($entity_type_id);
            $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();
            $field_name = $field_storage->getName();

            // Provide a relationship for the entity type with the entity reference
            // field.
            $args = array(
                '@label' => $target_entity_type->getLabel(),
                '@field_name' => $field_name,
            );
            $data[$table_name][$field_name]['relationship'] = array(
                'title' => t('@label referenced from @field_name', $args),
                'label' => t('@field_name: @label', $args),
                'group' => $entity_type->getLabel(),
                'help' => t('Appears in: @bundles.', array('@bundles' => implode(', ', $field_storage->getBundles()))),
                'id' => 'standard',
                'base' => $target_base_table,
                'entity type' => $target_entity_type_id,
                'base field' => $target_entity_type->getKey('id'),
                'relationship field' => $field_name . '_target_id',
            );
            // Provide a reverse relationship for the entity type that is referenced by
            // the field.
            $args['@entity'] = $entity_type->getLabel();
            $args['@label'] = $target_entity_type->getLowercaseLabel();
            $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
            $data[$target_base_table][$pseudo_field_name]['relationship'] = array(
                'title' => t('@entity using @field_name', $args),
                'label' => t('@field_name', array('@field_name' => $field_name)),
                'group' => $target_entity_type->getLabel(),
                'help' => t('Relate each @entity with a @field_name set to the @label.', $args),
                'id' => 'entity_reverse',
                'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
                'entity_type' => $entity_type_id,
                'base field' => $entity_type->getKey('id'),
                'field_name' => $field_name,
                'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
                'field field' => $field_name . '_target_id',
                'join_extra' => array(
                    array(
                        'field' => 'deleted',
                        'value' => 0,
                        'numeric' => TRUE,
                    ),
                ),
            );

            //TODO: agregar relacion para categoria. hay que tener cuidado si se copia
            //codigo anterior porque $field_storage no esta preparado para la cateogria
        }

        return $data;
    }

    /**
     * Do not want preconfigured options (at least for the moment)
     * @return type
     */
    public static function getPreconfiguredOptions() {
        return null;
    }
    
    //TODO: Calcular dependencias como entity reference sumando las de la taxonomia mas la category type.
    

}
