<?php

namespace Drupal\adv_varnish\Response;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class ESIResponse.
 *
 * @package Drupal\adv_varnish\Response
 */
class ESIResponse extends CacheableResponse {

  /**
   * Entity of response.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Get current entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Returns current entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Set current entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to be set.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

}
