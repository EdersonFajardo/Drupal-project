<?php
namespace Drupal\test_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * @block(
 *   id = "Test Module Form",
 *   admin_label = @translation("Test Module Form"),
 *   category = @translation("Test Module Form")
 * )
 */

class TestModule extends BlockBase {
  /**
   * {@inheritdoc }
   */
  public function build(){
  
    $form = \Drupal::formBuild()->getForm(form_arg: 'Drupal\test_module\Form\TestModuleForm');

    $database = \Drupal::database();
    $query = $database->select(table: 'test_module', alias: 'vgc');
    $query->fields(table:'vgc');
    $result = $query->execute()->fetchAll();

    return [
      '#theme' => 'test-module-form',
      '#title' => $this->t(string:'Formulario de Inscripción'),
      '#records' => $result,
      '#form' => $form,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }
}
