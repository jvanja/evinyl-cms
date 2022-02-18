<?php

namespace Drupal\adv_varnish;

/**
 * Interface CookieManagerInterface.
 *
 * @package Drupal\adv_varnish
 */
interface CookieManagerInterface {

  /**
   * Header to store Varnish BIN.
   */
  const COOKIE_BIN = 'ADVBIN';

  /**
   * Header to set Varnish BIN header.
   */
  const COOKIE_INF = 'ADVINF';

  /**
   * Updates AdvVarnish user cookie if required.
   *
   * @return bool
   *   Defines if page needs to be reloaded after cookie is updated.
   */
  public function cookieUpdate();

}
