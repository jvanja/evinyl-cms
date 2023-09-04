<?php

/**
 * @file
 * Contains \Drupal\evinyl_lyrics\Controller\EvinylLyricsController.
 */

namespace Drupal\evinyl_lyrics\Controller;

class EvinylLyricsController
{
  public function content()
  {
    return array(
      '#type' => 'markup',
      '#markup' => t('Hello, World!'),
    );
  }
}
