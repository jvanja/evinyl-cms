<?php

namespace Drupal\evinyl_deezer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\Utils;
// use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;


/**
 * Class MyController.
 */
class EvinylDeezerController extends ControllerBase {

  /**
   * Guzzle\Client instance.
   *
   * @var \Guzzle\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    // $this->httpClient = \Drupal::httpClient();

    // $clientFactory = \Drupal::service('http_client_factory');
    // $this->httpClient = $clientFactory->fromOptions(['base_uri' => 'https://api.deezer.com/']);

    $this->httpClient = new Client([
      // Base URI is used with relative requests
      'base_uri' => 'https://api.deezer.com/album/',
    ]);
  }

  // /**
  //  * {@inheritdoc}
  //  */
  // public static function create(ContainerInterface $container) {
  //   return new static(
  //     $container->get('http_client')
  //   );
  // }

  /**
   * Posts route callback.
   *
   * @param array $ids
   *   The IDs array
   *
   * @return array
   *   A render array used to show the Posts list.
   */
  public function posts($ids) {
    // CONCURRENTLY
    // http://docs.guzzlephp.org/en/latest/quickstart.html#concurrent-requests
    $promises = [];

    foreach ($ids as $id) {
      $cleanId = trim($id);
      $promises[] = $this->httpClient->getAsync($cleanId);
    }

    // Wait for the requests to complete; throws a ConnectException
    // if any of the requests fail
    try {
      $responses = Utils::unwrap($promises);
    } catch (ConnectException $e) {
      return FALSE;
    }

    foreach ($responses as $response) {
      if ($response->getStatusCode() !== 200) {
        return $build;
      }
      $posts = $response->getBody()->getContents();

      // create albums
      $postObject = json_decode($posts);

      if ($postObject->error) {
        return $build;
      }
      $album = $this->createAlbums($postObject);
    }

    return TRUE;
  }

  /**
   * creates new taxonomy term if it doesnt exist
   * otherwise it returns the existing tid.
   *
   * @param string $voc
   *   Vocabulary machine name
   * @param string $term
   *   Term name
   *
   * @return string
   *   Term ID
   */
  protected function addTaxonomyTerm($voc, $termsArray) {
    $terms = [];

    foreach ($termsArray as $deezerTerm) {
      $deezerTermName = ($voc === 'labels') ? $deezerTerm : $deezerTerm->name;
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $deezerTermName, 'vid' => $voc]);
      $term = reset($term);

      if (empty($term)) {
        $new_term = Term::create([
          'vid' => $voc,
          'name' => $deezerTermName,
        ]);
        $new_term->save();
        $terms[] = ['target_id' => $new_term->id()];
      } else {
        $terms[] = ['target_id' => $term->id()];
      }
    }

    return $terms;
  }

  protected function createAlbums($albumData) {
    $path_parts = pathinfo($albumData->cover_xl);
    $rename_filename = \Drupal::service('pathauto.alias_cleaner')->cleanString($albumData->title) . '.' . $path_parts['extension'];
    $albumCover = system_retrieve_file($albumData->cover_xl, 'public://covers/' . $rename_filename, TRUE, 0);
    $artistTerms = $this->addTaxonomyTerm('artists', [$albumData->artist]);
    $labelTerms = $this->addTaxonomyTerm('labels', [$albumData->label]);
    $genreTerms = $this->addTaxonomyTerm('genre', $albumData->genres->data);
    $aSideTracks = $albumData->tracks->data;
    // $bSideTracks = array_filter($albumData->tracklist, function($track) {
    //   return ($track->position[0] == 'B');
    // });
    $aSideSongs = $this->createSongsParagraphs('a_side_songs', $aSideTracks);
    // $bSideSongs = $this->createSongsParagraphs('b_side_songs', $bSideTracks);
    // $credits = $this->createAlbumsCredits($albumData->extraartists);
    $node = Node::create([
      'type' => 'album',
      'status' => 0,
      'title' => $albumData->title,
      'field_image' => $albumCover,
      // 'body'               => nl2br($albumData->notes),
      'field_artist_term' => $artistTerms,
      'field_label' => $labelTerms,
      'field_genre' => $genreTerms,
      'field_a_side_songs' => $aSideSongs,
      // 'field_b_side_songs' => $bSideSongs,
      'field_release_year' => explode('-', $albumData->release_date)[0],
      // 'field_credits'      => $credits,
    ]);
    $node->save();

    $url = $node->toUrl('edit-form');
    $link = Link::fromTextAndUrl($albumData->title, $url);

    return $link->toString();
  }

  protected function createAlbumsCredits($albumCredits) {
    // Drums – Max M. Weinberg* (tracks: A1 to A4, B2, B4)

    if (\count($albumCredits) > 0) {
      $credits = [];
      $creditsString = '';

      foreach ($albumCredits as $extraArtist) {
        $role = $extraArtist->role;
        $tracks = empty($extraArtist->tracks) ? '' : ' (' . $extraArtist->tracks . ')';
        $artistName = $extraArtist->name . $tracks;

        if (\array_key_exists($role, $credits)) {
          $credits[$role] .= ', ' . $artistName;
        } else {
          $credits[$role] = $artistName;
        }
      }

      foreach ($credits as $role => $name) {
        $creditsString .= $role . ' - ' . $name . '<br>';
      }

      return $creditsString;
    }

    return '';
  }

  /**
   * creates new field_a_side_songs paragraph and returns id.
   *
   * @param string $paragraphName
   *   'a_side_songs' or 'b_side_songs'
   * @param array $tracksArray for the following fields
   *  field_song
   *  field_song_duration
   *  field_song_name // required
   *  field_song_writers
   *  field_track_plays
   *
   * @return array
   *   List of paragraphs
   */
  protected function createSongsParagraphs($paragraphName, $tracksArray) {
    $paragraphs = [];

    foreach ($tracksArray as $track) {
      $credits = [];
      $creditsString = '';
      $track_local_url = $this->download_mp3_to_public_files($track->preview, $track->title);
      if (!$track_local_url) $track_local_url = '';

      $song_paragraph = Paragraph::create([
        'type' => $paragraphName,
        'field_song_name' => $track->title,
        'field_song_duration' => $track->duration,
        'field_song_preview_url' => $track_local_url,
        'field_song_credits' => $track->artist->name,
      ]);
      $paragraphs[] = $song_paragraph;
    }

    return $paragraphs;
  }

  /**
   * Downloads an MP3 file from a given URL and saves it to the public files directory.
   *
   * @param string $url The URL of the MP3 file to download.
   * @param string $title The title of the MP3 file.
   *
   * @return string|null The URL of the saved file, or NULL if the download failed.
   */
  protected function download_mp3_to_public_files(string $url, string $title) {
    // Get the file system and file repository services.
    $file_system = \Drupal::service('file_system');
    $file_repository = \Drupal::service('file.repository');
    $file_url_generator = \Drupal::service('file_url_generator');


    // Define the destination path in the public files directory.
    $destination_dir = 'public://audio';
    $file_name = str_replace(" ", "-", strtolower($title));
    $file_name = preg_replace('/[^a-zA-Z0-9_-]+/', '', $file_name);
    $destination = 'public://audio/' . $file_name . '.mp3';

    try {
      // Ensure the destination directory exists.
      $file_system->prepareDirectory($destination_dir, FileSystemInterface::CREATE_DIRECTORY);

      // Download the file data.
      $data = file_get_contents($url);
      if ($data === FALSE) {
        throw new \Exception('Failed to download MP3 file.');
      }

      // Write the data to the file repository.
      $file = $file_repository->writeData($data, $destination, FileSystemInterface::EXISTS_REPLACE);

      if (!$file) {
        throw new \Exception('Failed to save MP3 file.');
      }

      // Convert the file URI to a URL.
      return $file_url_generator->generateAbsoluteString($file->getFileUri());
    } catch (\Exception $e) {
      \Drupal::logger('custom_module')->error($e->getMessage());
      return NULL;
    }
  }

  private function hhmmss_to_seconds($str_time = '0:0') {
    $str_time = preg_replace('/^([\\d]{1,2})\\:([\\d]{2})$/', '00:$1:$2', $str_time);
    sscanf($str_time, '%d:%d:%d', $hours, $minutes, $seconds);

    return $hours * 3600 + $minutes * 60 + $seconds;
  }
}
