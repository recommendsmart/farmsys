<?php

namespace Drupal\cycle_news_display\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'CycleNewsDisplay' Block.
 *
 * @Block(
 * id = "cycle_news_display",
 * admin_label = @Translation("Cycle News Display"),
 * )
 */
class CycleNewsDisplayBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    if (!empty($config['cnd_speed'])) {
      $cnd_speed = $config['cnd_speed'];
    }
    else {
      $cnd_speed = 700;
    }

    if (!empty($config['cnd_timeout'])) {
      $cnd_timeout = $config['cnd_timeout'];
    }
    else {
      $cnd_timeout = 5000;
    }

    if (!empty($config['cnd_direction'])) {
      $cnd_direction = $config['cnd_direction'];
    }
    else {
      $cnd_direction = "scrollLeft";
    }

    if (!empty($config['cnd_link'])) {
      $cnd_link = $config['cnd_link'];
    }
    else {
      $cnd_link = "_blank";
    }

    if (!empty($config['cnd_announcement'])) {
      $cnd_announcement = $config['cnd_announcement'];
    }
    else {
      $cnd_announcement = "";
    }

    $output[]['#cache']['max-age'] = 0;
    $values = [
      'cnd_speed' => $cnd_speed,
      'cnd_timeout' => $cnd_timeout,
      'cnd_direction' => $cnd_direction,
      'cnd_link' => $cnd_link,
      'cnd_announcement' => $cnd_announcement,
    ];

    $markup = $this->cndBlock($values);
    $output[] = [
      '#markup' => $markup,
      '#allowed_tags' => ['script', 'span', 'div', 'a', 'img', 'p'],
    ];
    $output['#attached']['library'][] = 'cycle_news_display/cycle_news_display';

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  private function cndBlock(array $values) {

    $cnd_speed = $values['cnd_speed'];
    $cnd_timeout = $values['cnd_timeout'];
    $cnd_direction = $values['cnd_direction'];
    $cnd_link = $values['cnd_link'];
    $cnd_announcement = $values['cnd_announcement'];

    if (!is_numeric($cnd_speed)) {
      $cnd_speed = 700;
    }

    if (!is_numeric($cnd_timeout)) {
      $cnd_timeout = 5000;
    }

    if ($cnd_direction <> "scrollLeft" && $cnd_direction <> "scrollRight" && $cnd_direction <> "scrollUp" && $cnd_direction <> "scrollDown") {
      $cnd_direction = "scrollLeft";
    }

    if ($cnd_link <> "_blank" && $cnd_link <> "_self") {
      $cnd_link = "_blank";
    }

    $cnt = 0;
    $marquee = "";
    $hsa = "";

    $cnd_announcement_arry = explode("\n", $cnd_announcement);
    $cnd_announcement_arry = array_filter($cnd_announcement_arry, 'trim');
    foreach ($cnd_announcement_arry as $cnd_announcement) {
      $cnd_announcement = preg_replace("/\r|\n/", "", $cnd_announcement);

      if (strpos($cnd_announcement, ']-[') !== FALSE) {
        $cnd_announcement = explode("]-[", $cnd_announcement);
        $announcement_txt = $cnd_announcement[0];
        $announcement_lnk = $cnd_announcement[1];
      }
      elseif (strpos($cnd_announcement, '] - [') !== FALSE) {
        $cnd_announcement = explode("] - [", $cnd_announcement);
        $announcement_txt = $cnd_announcement[0];
        $announcement_lnk = $cnd_announcement[1];
      }
      elseif (strpos($cnd_announcement, ']- [') !== FALSE) {
        $cnd_announcement = explode("]- [", $cnd_announcement);
        $announcement_txt = $cnd_announcement[0];
        $announcement_lnk = $cnd_announcement[1];
      }
      elseif (strpos($cnd_announcement, '] -[') !== FALSE) {
        $cnd_announcement = explode("] -[", $cnd_announcement);
        $announcement_txt = $cnd_announcement[0];
        $announcement_lnk = $cnd_announcement[1];
      }
      else {
        if ($cnd_announcement <> "") {
          $announcement_txt = $cnd_announcement;
          $announcement_lnk = "";
        }
      }

      $announcement_txt = ltrim($announcement_txt, '[');
      $announcement_txt = rtrim($announcement_txt, ']');
      $announcement_lnk = ltrim($announcement_lnk, '[');
      $announcement_lnk = rtrim($announcement_lnk, ']');

      $hsa = $hsa . '<p>';
      if ($announcement_lnk != "") {
        $hsa = $hsa . "<a target='" . $cnd_link . "' href='" . $announcement_lnk . "'>";
      }
      $hsa = $hsa . stripslashes($announcement_txt);
      if ($announcement_lnk != "") {
        $hsa = $hsa . "</a>";
      }

      $hsa = $hsa . '</p>';
      $cnt = $cnt + 1;
    }

    $rand_number = rand(1, 9);
    $marquee = $marquee . '<div id="cycle-news-display-' . $rand_number . '">';
    $marquee = $marquee . $hsa;
    $marquee = $marquee . '</div>';
    $marquee = $marquee . '<script type="text/javascript">';
    $marquee = $marquee . 'jQuery.noConflict(); ';
    $marquee = $marquee . 'jQuery(function() { ';
    $marquee = $marquee . "jQuery('#cycle-news-display-" . $rand_number . "').cycle({ ";
    $marquee = $marquee . "fx: '" . $cnd_direction . "', ";
    $marquee = $marquee . "speed: " . $cnd_speed . ", ";
    $marquee = $marquee . "timeout: " . $cnd_timeout . " ";
    $marquee = $marquee . '}); ';
    $marquee = $marquee . '}); ';
    $marquee = $marquee . '</script>';

    return $marquee;

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['cnd_speed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Speed'),
      '#description' => $this->t('Please enter cycle speed. (Example: 700)'),
      '#default_value' => isset($config['cnd_speed']) ? $config['cnd_speed'] : '700',
    ];

    $form['cnd_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeout'),
      '#description' => $this->t('Please enter cycle timeout. (Example: 5000)'),
      '#default_value' => isset($config['cnd_timeout']) ? $config['cnd_timeout'] : '5000',
    ];

    $options = [
      'scrollLeft' => $this->t('Scroll left'),
      'scrollRight' => $this->t('Scroll right'),
      'scrollUp' => $this->t('Scroll up'),
      'scrollDown' => $this->t('Scroll down'),
    ];

    $form['cnd_direction'] = [
      '#type' => 'radios',
      '#title' => $this->t('Scroll direction'),
      '#options' => $options,
      '#description' => $this->t('Please select your direction for news display.'),
      '#default_value' => isset($config['cnd_direction']) ? $config['cnd_direction'] : 'scrollLeft',
    ];

    $options1 = [
      '_blank' => $this->t('Open in new window'),
      '_self' => $this->t('Open in same window'),
    ];

    $form['cnd_link'] = [
      '#type' => 'radios',
      '#title' => $this->t('Link'),
      '#options' => $options1,
      '#description' => $this->t('Please select your link setting.'),
      '#default_value' => isset($config['cnd_link']) ? $config['cnd_link'] : '_blank',
    ];

    if (!isset($config['cnd_announcement'])) {
      $default_announcement = "[This is first announcement]-[http://www.gopiplus.com/extensions/]";
      $default_announcement = $default_announcement . ',' . "[This is second announcement]-[http://www.gopiplus.com/extensions/]";
      $default_announcement = str_replace(',', "\n", $default_announcement);
    }

    $form['cnd_announcement'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Announcementc text'),
      '#description' => $this->t('Please enter your cycle announcement text. One announcement per line. <br /> [Your announcement text]-[When someone clicks on the announcement, where do you want to send them] <br /> Example : [Congratulations, you just completed configure]-[http://www.gopiplus.com/]'),
      '#default_value' => isset($config['cnd_announcement']) ? $config['cnd_announcement'] : $default_announcement,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['cnd_speed'] = $form_state->getValue('cnd_speed');
    $this->configuration['cnd_timeout'] = $form_state->getValue('cnd_timeout');
    $this->configuration['cnd_direction'] = $form_state->getValue('cnd_direction');
    $this->configuration['cnd_link'] = $form_state->getValue('cnd_link');
    $this->configuration['cnd_announcement'] = $form_state->getValue('cnd_announcement');
  }

}
