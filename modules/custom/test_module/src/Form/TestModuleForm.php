<?php

namespace Drupal\test_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Implement form API
 */
class TestModule extends FormBase{
  /**
   * {@inheritdoc }
   */
  public function getFormId(){
    return 'test_module_form';
  }

  /**
   * {@inheritdoc }
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['surname']=[
      '#type' => 'textfield',
      '#title' => $this->t(string: 'surname'),
      '#description' => $this->t(string: 'Surname of participant'),
      '#atributes' => [
        'placeholder' => $this->t(string: 'Your surname'),
      ],
      '#required' => TRUE,
    ];
    $form['name']=[
      '#type' => 'textfield',
      '#title' => $this->t(string: 'name'),
      '#description' => $this->t(string: 'Name of participant'),
      '#atributes' => [
        'placeholder' => $this->t(string: 'Your Name'),
      ],
      '#required' => TRUE,
    ];
    $form['document_type'] = [
      '#type' => 'select',
      '#title' => $this->t(string: 'Document type'),
      '#required' => TRUE,
      '#empty_option' => $this->t(string: '- Select -'),
      '#options' => [],
    ];

    // Cargar y agregar las opciones desde el vocabulario de taxonomía aquí.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('document_type');
    foreach ($terms as $term) {
      $form['document_type']['#options'][$term->tid] = $term->name;
    }
    $form['identification_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t(string: 'Identification Number'),
      '#description' => $this->t(string: 'Enter your identification number'),
      '#attributes' => [
        'placeholder' => $this->t(string: 'Your Identification Number'),
      ],
      '#required' => TRUE,
    ];
    $form['email']=[
      '#type' => 'email',
      '#title' => $this->t(string: 'Email'),
      '#atributes' => [
        'placeholder' => $this->t(string: 'Your Email'),
      ],
      '#required' => TRUE,
    ];
    $form['mobile_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t(string: 'Mobile Number'),
      '#description' => $this->t(string: 'Enter your mobile number'),
      '#attributes' => [
        'placeholder' => $this->t(string: 'Your Mobile  Number'),
      ],
      '#required' => TRUE,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t(string: 'Country'),
      '#required' => TRUE,
      '#empty_option' => $this->t(string: '- Select -'),
      '#options' => [],
    ];

    // Cargar y agregar las opciones desde el vocabulario de taxonomía "country".
    $terms_country = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('country');
    foreach ($terms_country as $term) {
      $form['country']['#options'][$term->tid] = $term->name;
    }
    $form['actions']['type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t(string: 'Add'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc }
   */
  public function submitForm(array &$form, FormStateInterface $form_state){
    \Drupal::database()->insert(table: 'test_module')
    ->fields(['surname', 'name', 'document_type', 'identification_number', 'email', 'mobile_number', 'country'])
    ->values(array(
      $form_state->getValue(key: 'surname'),
      $form_state->getValue(key: 'name'),
      $form_state->getValue(key: 'document_type'),
      $form_state->getValue(key: 'identification_number'),
      $form_state->getValue(key: 'email'),
      $form_state->getValue(key: 'mobile_number'),
      $form_state->getValue(key: 'country'),
    ))
    ->execute();

    $this->messenger()->addStatus($this->t(string: 'Thanks for adding @email', 
    ['@email' => $form_state->getValue(key: 'email')]));
  }
}
