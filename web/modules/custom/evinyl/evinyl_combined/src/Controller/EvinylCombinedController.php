<?php

namespace Drupal\evinyl_combined\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\node\Entity\Node;
use \Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\paragraphs\Entity\Paragraph;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;

/**
 * Class MyController.
 *
 * @package Drupal\evinyl_combined\Controller
 */
class EvinylCombinedController extends ControllerBase {

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
    // $this->httpClient = $clientFactory->fromOptions(['base_uri' => 'https://api.discogs.com/']);

    // $this->httpClient = new Client([
    //   'base_uri' => 'https://api.discogs.com/releases/'
    // ]);
    $this->httpClient = new Client();

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
   * @return array
   *   A render array used to show the Posts list.
   */
  public function posts($ids) {

    // CONCURRENTLY
    // http://docs.guzzlephp.org/en/latest/quickstart.html#concurrent-requests
    $promises = [
      'discogs' => $this->httpClient->getAsync('https://api.discogs.com/releases/' . $ids['discogsId']),
      'deezer' => $this->httpClient->getAsync('https://api.deezer.com/album/' . $ids['deezerId'])
    ];


    // Wait for the requests to complete; throws a ConnectException
    // if any of the requests fail
    try {
      $responses = Promise\unwrap($promises);
      // $responses = Promise\settle($promises)->wait();
    } catch (ClientException $e) {
      return [
        'status' => false,
        '#message' => $e
      ];
    } catch (ConnectException $e) {
      return [
        'status' => false,
        '#message' => $e
      ];
    }


    if ($responses['discogs']->getStatusCode() != 200) {
      return [
      'status' => false,
      '#theme' => 'evinyl_combined',
      '#message' => 'Wrong Discogs ID'
      ];
    }
    if ($responses['deezer']->getStatusCode() != 200) {
      return [
      'status' => false,
      '#theme' => 'evinyl_combined',
      '#message' => 'Wrong Deezer ID'
      ];
    }
    // if ($responses['discogs']['state'] != 'fullfiled') {
    //   return 'Discogs import failed';
    // }
    //
    // if ($responses['deezer']['state'] != 'fullfiled') {
    //   return 'Deezer import failed';
    // }


    $discogsData = $responses['discogs']->getBody()->getContents();
    $deezerData = $responses['deezer']->getBody()->getContents();

    // create albums
    $discogsObject = json_decode($discogsData);
    $deezerObject = json_decode($deezerData);
    $album = $this->createAlbums( $discogsObject, $deezerObject );


    return [
      'status' => true
    ];
  }


  protected function createAlbums($albumData, $deezerData) {
    $albumCover = system_retrieve_file($deezerData->cover_xl, 'public://covers/', TRUE);
    $artistTerms = $this->addTaxonomyTerm('artists', $albumData->artists);
    $labelTerms = $this->addTaxonomyTerm('labels', $albumData->labels);
    $genreTerms = $this->addTaxonomyTerm('genre', $albumData->genres);
    $aSideTracks = array_filter($albumData->tracklist, function($track) {
      return ($track->position[0] == 'A');
    });
    $bSideTracks = array_filter($albumData->tracklist, function($track) {
      return ($track->position[0] == 'B');
    });
    $cSideTracks = array_filter($albumData->tracklist, function($track) {
      return ($track->position[0] == 'C');
    });
    $dSideTracks = array_filter($albumData->tracklist, function($track) {
      return ($track->position[0] == 'D');
    });
    $deezerTracksData = $deezerData->tracks->data;

    $aSideSongs = $this->createSongsParagraphs('a_side_songs', $aSideTracks, $deezerTracksData);
    $bSideSongs = $this->createSongsParagraphs('a_side_songs', $bSideTracks, $deezerTracksData);
    $cSideSongs = $this->createSongsParagraphs('a_side_songs', $cSideTracks, $deezerTracksData);
    $dSideSongs = $this->createSongsParagraphs('a_side_songs', $dSideTracks, $deezerTracksData);
    $credits = $this->createAlbumsCredits($albumData->extraartists);
    $node = Node::create([
      'type'               => 'album',
      'status'             => 0,
      'title'              => $albumData->title,
      'body'               => nl2br($albumData->notes),
      'field_image'        => $albumCover,
      'field_artist_term'  => $artistTerms,
      'field_label'        => $labelTerms,
      'field_genre'        => $genreTerms,
      'field_a_side_songs' => $aSideSongs,
      'field_b_side_songs' => $bSideSongs,
      'field_c_side_songs' => $cSideSongs,
      'field_d_side_songs' => $dSideSongs,
      'field_release_year' => $albumData->year,
      'field_credits'      => $credits,
    ]);
    $node->save();

    $url = $node->toUrl('edit-form');
    $link = Link::fromTextAndUrl($albumData->title, $url);

    return $link->toString();
  }

  /**
   * creates new taxonomy term if it doesnt exist
   * otherwise it returns the existing tid
   *
   * @param string $voc
   *   Vocabulary machine name
   * @param string $term
   *   Term name
   * @return string
   *   Term ID
   */
  protected function addTaxonomyTerm($voc, $termsArray) {
    $terms = [];
    foreach($termsArray as $discogsTerm) {
      $discogsTermName = ($voc == 'genre') ? $discogsTerm : $discogsTerm->name;
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $discogsTermName, 'vid' => $voc]);
      $term = reset($term);
      if (empty($term)) {
        $new_term = Term::create([
            'vid' => $voc,
            'name' => $discogsTermName,
        ]);
        $new_term->save();
        $terms[] = ['target_id' => $new_term->id()];
      } else {
        $terms[] = ['target_id' => $term->id()];
      }
    }
    return $terms;
  }

  /**
   * creates new field_a_side_songs paragraph and returns id
   *
   * @param string $paragraphName
   *   'a_side_songs'
   * @param array $tracksArray for the following fields
   *  field_song
   *  field_song_duration
   *  field_song_name // required
   *  field_song_writers
   *  field_track_plays
   * @return array
   *   List of paragraphs
   */
  protected function createSongsParagraphs($paragraphName, $tracksArray, $deezerTracksData) {
    $paragraphs = [];
    foreach($tracksArray as $track) {
      $credits = [];
      $creditsString = '';
      foreach ($track->extraartists as $extraArtist) {
        $role = $extraArtist->role;
        $artistName = $extraArtist->name;
        if (array_key_exists($role, $credits)) {
          $credits[$role] .= ', ' . $artistName;
        } else {
          $credits[$role] = $artistName;
        }
      }
      foreach ($credits as $role => $name) {
        $creditsString .= $role . ' - ' . $name . '<br>';
      }

      $deezerTitles = array_column($deezerTracksData, 'title');
      $key = array_search($track->title, $deezerTitles);
      if(is_int($key)){
        $deezerPreview = $deezerTracksData[$key]->preview;
      } else {
        $deezerPreview = '';
      }

      $song_paragraph = Paragraph::create([
        'type'                   => $paragraphName,
        'field_song_duration'    => $this->hhmmss_to_seconds($track->duration),
        'field_song_name'        => $track->title,
        'field_song_credits'     => $creditsString,
        'field_song_preview_url' => $deezerPreview,
      ]);
      $paragraphs[] = $song_paragraph;
    }
    return $paragraphs;
  }

  protected function createAlbumsCredits($albumCredits) {
    // Drums – Max M. Weinberg* (tracks: A1 to A4, B2, B4)

    if (count($albumCredits) > 0) {
      $credits = [];
      $creditsString = '';
      foreach ($albumCredits as $extraArtist) {
        $role = $extraArtist->role;
        $tracks = empty($extraArtist->tracks) ? '' : ' (' . $extraArtist->tracks . ')';
        $artistName = $extraArtist->name . $tracks;
        if (array_key_exists($role, $credits)) {
          $credits[$role] .= ', ' . $artistName;
        } else {
          $credits[$role] = $artistName;
        }
      }
      foreach ($credits as $role => $name) {
        $creditsString .= $role . ' - ' . $name . '<br>';
      }
      return $creditsString;
    } else {
      return '';
    }
  }

  private function hhmmss_to_seconds($str_time = '0:0') {
    $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $str_time);
    sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
    $time_seconds = $hours * 3600 + $minutes * 60 + $seconds;
    return $time_seconds;
  }
}
