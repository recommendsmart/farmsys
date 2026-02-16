<?php

namespace Drupal\lrn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\user\Entity\Role;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;


class LRNConfigForm extends ConfigFormBase {

  public function getFormId() {
    return 'lrn_config';
  }
  
  // configuration form builder
  public function buildForm(array $form, FormStateInterface $form_state) {
    $weight = 0;
    $messenger = \Drupal::messenger();
    
    // get our configuration
    $config = $this->config('lrn.settings');
    $cf_firstname = $config->get('firstname_field');
    $cf_lastname = $config->get('lastname_field');
    $cf_middlename = $config->get('middlename_field');
    $cf_buildstring = $config->get('build_string');
    $cf_allownumbering = $config->get('allow_numbering');
    $cf_maxnumbering = $config->get('max_numbering');
    $cf_fnlowup = $config->get('fn_lowup');
    $cf_lnlowup = $config->get('ln_lowup');
    $cf_mnlowup = $config->get('mn_lowup');
    $cf_fnchange = $config->get('fn_change');
    $cf_lnchange = $config->get('ln_change');
    $cf_mnchange = $config->get('mn_change');
    $cf_fnmax = $config->get('fn_max');
    $cf_lnmax = $config->get('ln_max');
    $cf_mnmax = $config->get('mn_max');
    $cf_fntranslit = $config->get('fn_translit');
    $cf_lntranslit = $config->get('ln_translit');
    $cf_mntranslit = $config->get('mn_translit');
    $cf_fullmax = $config->get('full_max');

    $opt_ul = [
      0 => $this->t('Do not change'),
      1 => $this->t('All uppercase'),
      2 => $this->t('All lowercase'),
      3 => $this->t('First letter uppercase'),
    ];
    $opt_rewrite = [
      0 => $this->t('Do not change'),
      1 => $this->t('Remove all spaces, separators…'),
      2 => $this->t('Keep only first letter'),
      3 => $this->t('Keep first letter(s) (i.e. "Jean-Pierre" becomes "JP")'),
    ];

    // build our view/configuration form
    $form['intro'] = [
      '#markup' => "<div><b>Configuration panel for LRN module.</b></div>",
      '#allowed_tags' => ['div','b'],
      '#weight' => ($weight++),
    ];
    $form['firstname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Firstname field'),
      '#required' => FALSE,
      '#default_value' => $cf_firstname,
      '#size' => 512,
      '#description' => $this->t('The name of the field containing firstname (if empty: ignored)'),
      '#weight' => ($weight++),
    ];
    $form['fn_translit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Transliterate'),
      '#return_value' => 1,
      '#default_value' => $cf_fntranslit?1:0,
      '#description' => $this->t('Transliterate firstname'),
      '#weight' => ($weight++),
    ];
    $form['fn_lowup'] = [
      '#type' => 'select',
      '#title' => $this->t('Lower/uppercase rule'),
      '#options' => $opt_ul,
      '#default_value' => $cf_fnlowup,
      '#description' => $this->t('How to transform firstname letters'),
      '#weight' => ($weight++),
    ];
    $form['fn_change'] = [
      '#type' => 'select',
      '#title' => $this->t('Rewrite rule'),
      '#options' => $opt_rewrite,
      '#default_value' => $cf_fnchange,
      '#description' => $this->t('Firstname rewriting rule'),
      '#weight' => ($weight++),
    ];
    $form['fn_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Max length'),
      '#default_value' => $cf_fnmax,
      '#min' => 1,
      '#description' => $this->t('Max length of resulting firstname (after all changes)'),
      '#weight' => ($weight++),
    ];
    $form['lastname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lastname field'),
      '#required' => FALSE,
      '#default_value' => $cf_lastname,
      '#size' => 512,
      '#description' => $this->t('The name of the field containing lastname (if empty: ignored)'),
      '#weight' => ($weight++),
    ];
    $form['ln_translit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Transliterate'),
      '#return_value' => 1,
      '#default_value' => $cf_lntranslit?1:0,
      '#description' => $this->t('Transliterate lastname'),
      '#weight' => ($weight++),
    ];
    $form['ln_lowup'] = [
      '#type' => 'select',
      '#title' => $this->t('Lower/uppercase rule'),
      '#options' => $opt_ul,
      '#default_value' => $cf_lnlowup,
      '#description' => $this->t('How to transform lastname letters'),
      '#weight' => ($weight++),
    ];
    $form['ln_change'] = [
      '#type' => 'select',
      '#title' => $this->t('Rewrite rule'),
      '#options' => $opt_rewrite,
      '#default_value' => $cf_lnchange,
      '#description' => $this->t('Lastname rewriting rule'),
      '#weight' => ($weight++),
    ];
    $form['ln_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Max length'),
      '#default_value' => $cf_lnmax,
      '#min' => 1,
      '#description' => $this->t('Max length of resulting lastname (after all changes)'),
      '#weight' => ($weight++),
    ];
    $form['middlename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Middlename field'),
      '#required' => FALSE,
      '#default_value' => $cf_middlename,
      '#size' => 512,
      '#description' => $this->t('The name of the field containing middlename (if empty: ignored)'),
      '#weight' => ($weight++),
    ];
    $form['mn_translit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Transliterate'),
      '#return_value' => 1,
      '#default_value' => $cf_mntranslit?1:0,
      '#description' => $this->t('Transliterate middlename'),
      '#weight' => ($weight++),
    ];
    $form['mn_lowup'] = [
      '#type' => 'select',
      '#title' => $this->t('Lower/uppercase rule'),
      '#options' => $opt_ul,
      '#default_value' => $cf_fnlowup,
      '#description' => $this->t('How to transform middlename letters'),
      '#weight' => ($weight++),
    ];
    $form['mn_change'] = [
      '#type' => 'select',
      '#title' => $this->t('Rewrite rule'),
      '#options' => $opt_rewrite,
      '#default_value' => $cf_mnchange,
      '#description' => $this->t('Middlename rewriting rule'),
      '#weight' => ($weight++),
    ];
    $form['mn_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Max length'),
      '#default_value' => $cf_mnmax,
      '#min' => 1,
      '#description' => $this->t('Max length of resulting middlename (after all changes)'),
      '#weight' => ($weight++),
    ];
    $form['description'] = [
      '#markup' => "<p>The build string allow to describe how to compound name parts into a user name.</p>" .
                   "<p>Each part is coded by <b>{FN}</b>, <b>{LN}</b> or <b>{MN}</b> (resp. first, last and middle name).</p>" .
                   "<p>Each other caracter is directly used as output.</p>",
      '#allowed_tags' => ['p','div','b','ul','li'],
      '#weight' => ($weight++),
    ];
    $form['buildstring'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Build string'),
      '#required' => FALSE,
      '#default_value' => $cf_buildstring,
      '#size' => 512,
      '#required' => TRUE,
      '#description' => $this->t('The string that represents how to create username from name parts'),
      '#weight' => ($weight++),
    ];
    $form['full_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Final max length'),
      '#default_value' => $cf_fullmax,
      '#min' => 4,
      '#description' => $this->t('Max length of final resulting name'),
      '#weight' => ($weight++),
    ];
    $form['allownumbering'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow numbering'),
      '#default_value' => $cf_allownumbering?1:0,
      '#return_value' => 1,
      '#description' => $this->t('If selected homonyms in name will get a suffix (number)'),
      '#weight' => ($weight++),
    ];
    $form['maxnumbering'] = [
      '#type' => 'number',
      '#title' => $this->t('Max numbering'),
      '#default_value' => $cf_maxnumbering,
      '#min' => 2,
      '#description' => $this->t('If numbering allowed, the max value to allow (numbering starts at 2)'),
      '#weight' => ($weight++),
    ];

    // return the form
    $form2 = parent::buildForm($form, $form_state);
    // change submit button name (Note: can't figure how to *move' submit button
    // so I created an other one at the desired position)
    //$form2['actions']['submit']['#value'] = $this->t('Validate');
    return $form2;
  }


  // check data and submit change
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messenger = \Drupal::messenger();

    // get our configuration
    $config = $this->config('lrn.settings');
    
    // get each value and set it into config
    $config->set('firstname_field', $form_state->getValue('firstname'));
    $config->set('lastname_field', $form_state->getValue('lastname'));
    $config->set('middlename_field', $form_state->getValue('middlename'));
    $config->set('fn_translit', $form_state->getValue('fn_translit')==1?true:false);
    $config->set('ln_translit', $form_state->getValue('ln_translit')==1?true:false);
    $config->set('mn_translit', $form_state->getValue('mn_translit')==1?true:false);
    $config->set('fn_lowup', $form_state->getValue('fn_lowup'));
    $config->set('ln_lowup', $form_state->getValue('ln_lowup'));
    $config->set('mn_lowup', $form_state->getValue('mn_lowup'));
    $config->set('fn_change', $form_state->getValue('fn_change'));
    $config->set('ln_change', $form_state->getValue('ln_change'));
    $config->set('mn_change', $form_state->getValue('mn_change'));
    $config->set('fn_max', $form_state->getValue('fn_max'));
    $config->set('ln_max', $form_state->getValue('ln_max'));
    $config->set('mn_max', $form_state->getValue('mn_max'));
    $config->set('build_string', $form_state->getValue('buildstring'));
    $config->set('full_max', $form_state->getValue('full_max'));
    $config->set('allow_numbering', $form_state->getValue('allownumbering')==1?true:false);
    $config->set('max_numbering', $form_state->getValue('maxnumbering'));
    
    // save config
    $config->save();
    $messenger->addMessage($this->t('Configuration saved'), $messenger::TYPE_STATUS);
    return;
  }

  protected function getEditableConfigNames() {
    return ['lrn.settings'];
  }
}

