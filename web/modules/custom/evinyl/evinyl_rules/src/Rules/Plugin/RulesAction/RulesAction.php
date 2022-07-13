<?php
/**
 * Provides custom rules actions.
 */
namespace Drupal\evinyl_rules\Controller;
use Drupal\rules\Core\RulesActionBase;

class EvinylRules extends RulesActionBase {

  /**
   * Executes the action with the given context.
   */
  protected function doExecute() {

    $data = file_get_contents('http://localhost/evinyl-cms/web/jsonapi/taxonomy_term/artists?include=field_user_photo.field_media_image&sort=name');
    if (file_put_contents("data.json", $data))
      echo "JSON file created successfully...";
    else
      echo "Oops! Error creating json file...";

  }

}

