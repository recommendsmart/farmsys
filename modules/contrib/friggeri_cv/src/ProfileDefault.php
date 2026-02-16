<?php

namespace Drupal\friggeri_cv;

use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;

/**
 * The default profile generator class.
 */
class ProfileDefault {

  /**
   * Builds the default profile array values.
   *
   * @return array
   *   The profile array values.
   */
  protected static function profileValues() {
    return [
      'name'  => 'Ara MARTIROSYAN',
      'title' => 'Drupal Developer',
      'contact_box' => [
        [
          'heading' => 'Personal<br>Information',
          'font_awesome_icon' => NULL,
          'contacts' => '<p>Age: 35</p>',
        ],
        [
          'heading' => NULL,
          'font_awesome_icon' => 'fa fa-home',
          'contacts' => '<p>123 Avenue</p><p>City, State 12345</p><p>Country</p>',
        ],
        [
          'heading' => NULL,
          'font_awesome_icon' => 'fa fa-phone',
          'contacts' => '<p><strong>+0 (000) 111 1111</strong></p>',
        ],
        [
          'heading' => NULL,
          'font_awesome_icon' => 'fa fa-envelope',
          'contacts' => '<p><strong><a href="mailto:ara@aralmighty.com" target="_blank">ara@aralmighty.com</a></strong></p>',
        ],
        [
          'heading' => NULL,
          'font_awesome_icon' => 'fa fa-globe',
          'contacts' => '<p><strong><a href="https://aralmighty.com" target="_blank">www.aralmighty.com</a></strong></p>',
        ],
        [
          'heading' => NULL,
          'font_awesome_icon' => 'fa fa-linkedin',
          'contacts' => '<p><strong><a href="https://www.linkedin.com" target="_blank">/in/ara-martirosyan/</a></strong></p>',
        ],
        [
          'heading' => NULL,
          'font_awesome_icon' => 'fa fa-github',
          'contacts' => '<p><strong><a href="https://github.com/ara-martirosyan" target="_blank">ara-martirosyan</a></strong></p>',
        ],
        [
          'heading' => NULL,
          'font_awesome_icon' => 'fa fa-drupal',
          'contacts' => '<p><strong><a href="https://www.drupal.org/u/ara-martirosyan" target="_blank">ara-martirosyan</a></strong></p>',
        ],
      ],
      'sections' => self::generateSections(),
      'footer_col_1_title' => 'Languages',
      'footer_col_1_title_color' => '#9BC931',
      'footer_col_1_items' => self::generateFooterColumnItems(1),
      'footer_col_2_title' => "Hobbies",
      'footer_col_2_title_color' => '#B731C9',
      'footer_col_2_items' => self::generateFooterColumnItems(2),
    ];
  }

  /**
   * Builds the default profile's section array values.
   *
   * @return array
   *   The section array values.
   */
  protected static function sectionValues() {
    return [
      [
        'title' => 'Experience',
        'title_color' => '#F2A4A',
        'entity_box' => [
          [
            'tenure' => '2019-Now',
            'title' => 'COVID-19 tester',
            'employer' => 'World Health Organization (W.H.O.)',
            'domain' => 'Domain: Medical',
            'info' => '<p>I perform nasal and oral COVID-19 swab tests at testing sites, hospitals, nursing homes and offices.</p>',
          ],
          [
            'tenure' => '2016-2019',
            'title' => 'Drupal Devleoper',
            'employer' => 'NASA, USA',
            'domain' => 'Clients:',
            'info' => '<p>SpaceX</p>',
          ],
        ],
      ],
      [
        'title' => 'Education',
        'title_color' => '#07A0AB',
        'entity_box' => [
          [
            'tenure' => '2011-2014',
            'title' => 'Ph.D.',
            'employer' => 'Niels Bohr Institute, Copenhagen, Denmark',
            'domain' => 'Thesis: Holographic three-point functions',
            'info' => NULL,
          ],
          [
            'tenure' => '2010–2011',
            'title' => 'Research assistant',
            'employer' => 'Yerevan Physics Institute, Yerevan, Armenia',
            'domain' => 'Domain: Theoretical Physics',
            'info' => NULL,
          ],
          [
            'tenure' => '2008–2010',
            'title' => 'Military Service',
            'employer' => 'Armenia',
            'domain' => NULL,
            'info' => NULL,
          ],
          [
            'tenure' => '2006–2008',
            'title' => 'M.Sc.',
            'employer' => 'Yerevan State University, Yerevan, Armenia',
            'domain' => 'Domain: Theoretical Physics',
            'info' => NULL,
          ],
          [
            'tenure' => '2002–2006',
            'title' => 'B.Sc.',
            'employer' => 'Yerevan State University, Yerevan, Armenia',
            'domain' => 'Physics (Diploma with honor)',
            'info' => NULL,
          ],
        ],
      ],
      [
        'title' => 'Skills',
        'title_color' => '#E3B945',
        'entity_box' => [
          [
            'tenure' => 'Languages:',
            'title' => 'PHP, JavaScript',
            'employer' => NULL,
            'domain' => NULL,
            'info' => NULL,
          ],
          [
            'tenure' => 'CMS:',
            'title' => 'Drupal 7/8/9',
            'employer' => NULL,
            'domain' => NULL,
            'info' => NULL,
          ],
          [
            'tenure' => 'Tools:',
            'title' => 'PhpStorm, Drush/Drupal console, Composer, Docker, Git',
            'employer' => NULL,
            'domain' => NULL,
            'info' => NULL,
          ],
        ],
      ],
    ];
  }

  /**
   * Generates the default profile sections and returns their target ids.
   *
   * @return array
   *   The section entity target ids of the default profile.
   */
  protected static function generateSections() {
    $em = \Drupal::entityTypeManager();
    $storage = $em->getStorage('profile_section');
    $sections = [];
    foreach (self::sectionValues() as $section_value) {
      $section = $storage->create($section_value);
      $section->save();
      $sections[] = ['target_id' => $section->id()];
    }

    return $sections;
  }

  /**
   * Builds the default profile's footer column items array values.
   *
   * @param int $n
   *   The footer column number (1 or 2).
   *
   * @return array[]|null
   *   The footer column items array values.
   */
  protected static function footerItemValues(int $n) {
    switch ($n) {
      case 1:
        return [
          [
            'text' => 'Armenian',
            'picture' => self::generateMedia('/1/arm.jpg'),
          ],
          [
            'text' => 'English',
            'picture' => self::generateMedia('/1/eng.jpg'),
          ],
          [
            'text' => 'French',
            'picture' => self::generateMedia('/1/fr.jpg'),
          ],
          [
            'text' => 'Danish',
            'picture' => self::generateMedia('/1/dk.jpg'),
          ],
          [
            'text' => 'Russian',
            'picture' => self::generateMedia('/1/rus.jpg'),
          ],
        ];

      case 2:

        return [
          [
            'text' => 'Rhyming',
            'picture' => self::generateMedia('/2/writing.jpg'),
          ],
          [
            'text' => 'Video editing',
            'picture' => self::generateMedia('/2/videoediting.jpg'),
          ],
          [
            'text' => 'Blogging',
            'picture' => self::generateMedia('/2/blogging.jpg'),
          ],
          [
            'text' => 'Drawing',
            'picture' => self::generateMedia('/2/drawing.jpg'),
          ],
          [
            'text' => 'Cycling',
            'picture' => self::generateMedia('/2/bike.jpg'),
          ],
        ];

      default:
        return NULL;
    }
  }

  /**
   * Generates the media entity and returns its target_id array.
   *
   * @param string $name
   *   The file name within the (1st or 2nd) column folder.
   *
   * @return array
   *   The target id array of the generated Media.
   */
  protected static function generateMedia(string $name) {
    $path = drupal_get_path('module', 'friggeri_cv') . '/img/footer/column' . $name;
    $directory = 'public://media/image/';
    $fs = \Drupal::service('file_system');
    $fs->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $filename = basename($path);
    $file = file_save_data(file_get_contents($path), $directory . $filename);
    $media = Media::create([
      'bundle' => 'image',
      'thumbnail' => [
        'target_id' => $file->id(),
      ],
      'field_media_image' => [
        'target_id' => $file->id(),
      ],
    ]);
    $media->setName($filename)->setPublished(TRUE)->save();

    return ['target_id' => $media->id()];
  }

  /**
   * The generated profile_footer_item entity target_ids of default profile.
   *
   * @param int $n
   *   The footer column number.
   *
   * @return array
   *   The target_ids.
   */
  protected static function generateFooterColumnItems(int $n) {
    $em = \Drupal::entityTypeManager();
    $storage = $em->getStorage('profile_footer_item');
    $items = [];
    foreach (self::footerItemValues($n) as $item_value) {
      $item = $storage->create($item_value);
      $item->save();
      $items[] = ['target_id' => $item->id()];
    }

    return $items;
  }

  /**
   * Generates the default profile.
   *
   * @return \Drupal\friggeri_cv\ProfileInterface
   *   The default profile.
   */
  public static function generate() {
    $em = \Drupal::entityTypeManager();
    $storage = $em->getStorage('profile');
    $profile = $storage->create(self::profileValues());
    $profile->save();

    return $profile;
  }

}
