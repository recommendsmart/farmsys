<?php

namespace Drupal\storage;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the Storage entity type.
 */
class StorageViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['storage_field_data']['table']['base']['weight'] = -10;
    $data['storage_field_data']['table']['base']['access query tag'] = 'storage_access';
    $data['storage_field_data']['table']['wizard_id'] = 'storage';

    $data['storage_field_data']['id']['argument'] = [
      'id' => 'storage_id',
      'name field' => 'name',
      'numeric' => TRUE,
      'validate type' => 'id',
    ];

    $data['storage_field_data']['name']['field']['default_formatter_settings'] = ['link_to_entity' => TRUE];

    $data['storage_field_data']['name']['field']['link_to_storage default'] = TRUE;

    $data['storage_field_data']['type']['argument']['id'] = 'storage_type';

    $data['storage_field_data']['langcode']['help'] = $this->t('The language of the data or translation.');

    $data['storage_field_data']['status']['filter']['label'] = $this->t('Published status');
    $data['storage_field_data']['status']['filter']['type'] = 'yes-no';
    // Use status = 1 instead of status <> 0 in WHERE statement.
    $data['storage_field_data']['status']['filter']['use_equal'] = TRUE;

    $data['storage_field_data']['status_extra'] = [
      'title' => $this->t('Published status or admin user'),
      'help' => $this->t('Filters out unpublished data if the current user cannot view it.'),
      'filter' => [
        'field' => 'status',
        'id' => 'storage_status',
        'label' => $this->t('Published status or admin user'),
      ],
    ];

    $data['storage_field_data']['promote']['help'] = $this->t('A boolean indicating whether the storage is visible on the front page.');
    $data['storage_field_data']['promote']['filter']['label'] = $this->t('Promoted to front page status');
    $data['storage_field_data']['promote']['filter']['type'] = 'yes-no';

    $data['storage_field_data']['sticky']['help'] = $this->t('A boolean indicating whether the storage should sort to the top of data lists.');
    $data['storage_field_data']['sticky']['filter']['label'] = $this->t('Sticky status');
    $data['storage_field_data']['sticky']['filter']['type'] = 'yes-no';
    $data['storage_field_data']['sticky']['sort']['help'] = $this->t('Whether or not the data is sticky. To list sticky data first, set this to descending.');

    $data['storage']['storage_bulk_form'] = [
      'title' => $this->t('Storage operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple storages.'),
      'field' => [
        'id' => 'storage_bulk_form',
      ],
    ];

    // Bogus fields for aliasing purposes.

    // @todo Add similar support to any date field
    // @see https://www.drupal.org/storage/2337507
    $data['storage_field_data']['created_fulldate'] = [
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_fulldate',
      ],
    ];

    $data['storage_field_data']['created_year_month'] = [
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    $data['storage_field_data']['created_year'] = [
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];

    $data['storage_field_data']['created_month'] = [
      'title' => $this->t('Created month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_month',
      ],
    ];

    $data['storage_field_data']['created_day'] = [
      'title' => $this->t('Created day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_day',
      ],
    ];

    $data['storage_field_data']['created_week'] = [
      'title' => $this->t('Created week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_week',
      ],
    ];

    $data['storage_field_data']['changed_fulldate'] = [
      'title' => $this->t('Updated date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_fulldate',
      ],
    ];

    $data['storage_field_data']['changed_year_month'] = [
      'title' => $this->t('Updated year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year_month',
      ],
    ];

    $data['storage_field_data']['changed_year'] = [
      'title' => $this->t('Updated year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year',
      ],
    ];

    $data['storage_field_data']['changed_month'] = [
      'title' => $this->t('Updated month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_month',
      ],
    ];

    $data['storage_field_data']['changed_day'] = [
      'title' => $this->t('Updated day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_day',
      ],
    ];

    $data['storage_field_data']['changed_week'] = [
      'title' => $this->t('Updated week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_week',
      ],
    ];

    $data['storage_field_data']['uid']['help'] = $this->t('The user authoring the data. If you need more fields than the uid add the data: author relationship');
    $data['storage_field_data']['uid']['filter']['id'] = 'user_name';
    $data['storage_field_data']['uid']['relationship']['title'] = $this->t('Data author');
    $data['storage_field_data']['uid']['relationship']['help'] = $this->t('Relate data to the user who created it.');
    $data['storage_field_data']['uid']['relationship']['label'] = $this->t('author');

    $data['storage']['storage_listing_empty'] = [
      'title' => $this->t('Empty Storage Frontpage behavior'),
      'help' => $this->t('Provides a link to the storage add overview page.'),
      'area' => [
        'id' => 'storage_listing_empty',
      ],
    ];

    $data['storage_field_data']['uid_revision']['title'] = $this->t('User has a revision');
    $data['storage_field_data']['uid_revision']['help'] = $this->t('All storages where a certain user has a revision');
    $data['storage_field_data']['uid_revision']['real field'] = 'id';
    $data['storage_field_data']['uid_revision']['filter']['id'] = 'storage_uid_revision';
    $data['storage_field_data']['uid_revision']['argument']['id'] = 'storage_uid_revision';

    $data['storage_field_revision']['table']['wizard_id'] = 'storage_revision';

    // Advertise this table as a possible base table.
    $data['storage_field_revision']['table']['base']['help'] = $this->t('Data revision is a history of changes to data.');
    $data['storage_field_revision']['table']['base']['defaults']['title'] = 'title';

    $data['storage_field_revision']['id']['argument'] = [
      'id' => 'storage_id',
      'numeric' => TRUE,
    ];
    // @todo the NID field needs different behavior on revision/non-revision
    //   tables. It would be neat if this could be encoded in the base field
    //   definition.
    $data['storage_field_revision']['id']['relationship']['id'] = 'standard';
    $data['storage_field_revision']['id']['relationship']['base'] = 'storage_field_data';
    $data['storage_field_revision']['id']['relationship']['base field'] = 'id';
    $data['storage_field_revision']['id']['relationship']['title'] = $this->t('Content');
    $data['storage_field_revision']['id']['relationship']['label'] = $this->t('Get the actual data from a data revision.');
    $data['storage_field_revision']['id']['relationship']['extra'][] = [
      'field' => 'langcode',
      'left_field' => 'langcode',
    ];

    $data['storage_field_revision']['vid'] = [
      'argument' => [
        'id' => 'storage_vid',
        'numeric' => TRUE,
      ],
      'relationship' => [
        'id' => 'standard',
        'base' => 'storage_field_data',
        'base field' => 'vid',
        'title' => $this->t('Content'),
        'label' => $this->t('Get the actual data from a data revision.'),
        'extra' => [
          [
            'field' => 'langcode',
            'left_field' => 'langcode',
          ],
        ],
      ],
    ] + $data['storage_field_revision']['vid'];

    $data['storage_field_revision']['langcode']['help'] = $this->t('The language the original data is in.');

    $data['storage_revision']['revision_uid']['help'] = $this->t('The user who created the revision.');
    $data['storage_revision']['revision_uid']['relationship']['label'] = $this->t('revision user');
    $data['storage_revision']['revision_uid']['filter']['id'] = 'user_name';

    $data['storage_revision']['table']['join']['storage_field_data']['left_field'] = 'vid';
    $data['storage_revision']['table']['join']['storage_field_data']['field'] = 'vid';

    $data['storage_field_revision']['table']['wizard_id'] = 'storage_field_revision';

    $data['storage_field_revision']['status']['filter']['label'] = $this->t('Published');
    $data['storage_field_revision']['status']['filter']['type'] = 'yes-no';
    $data['storage_field_revision']['status']['filter']['use_equal'] = TRUE;

    $data['storage_field_revision']['promote']['help'] = $this->t('A boolean indicating whether the storage is visible on the front page.');

    $data['storage_field_revision']['sticky']['help'] = $this->t('A boolean indicating whether the storage should sort to the top of data lists.');

    $data['storage_field_revision']['langcode']['help'] = $this->t('The language of the data or translation.');

    $data['storage_field_revision']['link_to_revision'] = [
      'field' => [
        'title' => $this->t('Link to revision'),
        'help' => $this->t('Provide a simple link to the revision.'),
        'id' => 'storage_revision_link',
        'click sortable' => FALSE,
      ],
    ];

    $data['storage_field_revision']['revert_revision'] = [
      'field' => [
        'title' => $this->t('Link to revert revision'),
        'help' => $this->t('Provide a simple link to revert to the revision.'),
        'id' => 'storage_revision_link_revert',
        'click sortable' => FALSE,
      ],
    ];

    $data['storage_field_revision']['delete_revision'] = [
      'field' => [
        'title' => $this->t('Link to delete revision'),
        'help' => $this->t('Provide a simple link to delete the data revision.'),
        'id' => 'storage_revision_link_delete',
        'click sortable' => FALSE,
      ],
    ];

    // Define the base group of this table. Fields that don't have a group defined
    // will go into this field by default.
    $data['storage_access']['table']['group'] = $this->t('Content access');

    // For other base tables, explain how we join.
    $data['storage_access']['table']['join'] = [
      'storage_field_data' => [
        'left_field' => 'id',
        'field' => 'id',
      ],
    ];
    $data['storage_access']['id'] = [
      'title' => $this->t('Access'),
      'help' => $this->t('Filter by access.'),
      'filter' => [
        'id' => 'storage_access',
        'help' => $this->t('Filter for data by view access. <strong>Not necessary if you are using storage as your base table.</strong>'),
      ],
    ];

    // Add search table, fields, filters, etc., but only if a page using the
    // storage_search plugin is enabled.
    if (\Drupal::moduleHandler()->moduleExists('search')) {
      $enabled = FALSE;
      $search_page_repository = \Drupal::service('search.search_page_repository');
      foreach ($search_page_repository->getActiveSearchpages() as $page) {
        if ($page->getPlugin()->getPluginId() == 'storage_search') {
          $enabled = TRUE;
          break;
        }
      }

      if ($enabled) {
        $data['storage_search_index']['table']['group'] = $this->t('Search');

        // Automatically join to the storage table (or actually, storage_field_data).
        // Use a Views table alias to allow other modules to use this table too,
        // if they use the search index.
        $data['storage_search_index']['table']['join'] = [
          'storage_field_data' => [
            'left_field' => 'id',
            'field' => 'sid',
            'table' => 'search_index',
            'extra' => "storage_search_index.type = 'storage_search' AND storage_search_index.langcode = storage_field_data.langcode",
          ],
        ];

        $data['storage_search_total']['table']['join'] = [
          'storage_search_index' => [
            'left_field' => 'word',
            'field' => 'word',
          ],
        ];

        $data['storage_search_dataset']['table']['join'] = [
          'storage_field_data' => [
            'left_field' => 'sid',
            'left_table' => 'storage_search_index',
            'field' => 'sid',
            'table' => 'search_dataset',
            'extra' => 'storage_search_index.type = storage_search_dataset.type AND storage_search_index.langcode = storage_search_dataset.langcode',
            'type' => 'INNER',
          ],
        ];

        $data['storage_search_index']['score'] = [
          'title' => $this->t('Score'),
          'help' => $this->t('The score of the search item. This will not be used if the search filter is not also present.'),
          'field' => [
            'id' => 'search_score',
            'float' => TRUE,
            'no group by' => TRUE,
          ],
          'sort' => [
            'id' => 'search_score',
            'no group by' => TRUE,
          ],
        ];

        $data['storage_search_index']['keys'] = [
          'title' => $this->t('Search Keywords'),
          'help' => $this->t('The keywords to search for.'),
          'filter' => [
            'id' => 'search_keywords',
            'no group by' => TRUE,
            'search_type' => 'storage_search',
          ],
          'argument' => [
            'id' => 'search',
            'no group by' => TRUE,
            'search_type' => 'storage_search',
          ],
        ];

      }
    }

    return $data;
  }

}
