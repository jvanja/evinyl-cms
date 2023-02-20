<?php 
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\ArticleController.
 */
namespace Drupal\evinyl_album\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ArticleController {

  public $dom; 
  public $websiteData = array(); 
  // private $imageArr = array();

  public function update_link() {
    $statusMsg = 'success';
    $request = new Request();
    $request = Request::createFromGlobals();
    $parameters = json_decode($request->getContent(), true);
    $url = $parameters['url'];

    try { 
      // Initialize URL meta class 
      $this->initializeDom($url);

      // Get meta info from URL 
      $metaDataJson = $this->getWebsiteData(); 

      // Decode JSON data in array 
      $metaData = json_decode($metaDataJson); 
    } catch(\Exception $e) { 
      $statusMsg = $e->getMessage(); 
    } 

    $response = new JsonResponse();
    $response->setData([
      'status' => $statusMsg,
      'url' => $url,
      'data' => $metaData
    ]);

    return $response;

  }

  private function initializeDom($url){ 
    if($this->validateUrlFormat($url) == false){ 
      throw new \Exception("URL does not have a valid format."); 
    } 

    if (!$this->verifyUrlExists($url)){ 
      throw new \Exception("URL does not appear to exist."); 
    } 

    if(!empty($url)){ 
      $ch = curl_init(); 
      curl_setopt($ch, CURLOPT_HEADER, 0); 
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
      curl_setopt($ch, CURLOPT_URL, $url); 
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
      $data = curl_exec($ch); 
      curl_close($ch); 
      $this->dom = new \DOMDocument(); 
      @$this->dom->loadHTML($data); 
      $this->websiteData["url"] = $url; 
      return $this->dom; 
    } else { 
      throw new \Exception("No URL was supplied."); 
    } 
  } 

  public function getWebsiteData(){ 
    $this->websiteData["title"] = $this->getWebsiteTitle(); 
    // $this->websiteData["description"] = $this->getWebsiteDescription(); 
    $this->websiteData["image"] = $this->getWebsiteImages(); 
    return json_encode($this->websiteData); 
  } 

  protected function getWebsiteTitle(){ 
    $titleNode = $this->dom->getElementsByTagName("title"); 
    $titleValue = $titleNode->item(0)->nodeValue; 
    return $titleValue; 
  } 

  protected function getWebsiteDescription(){ 
    $descriptionNode = $this->dom->getElementsByTagName("meta"); 
    for ($i=0; $i < $descriptionNode->length; $i++) { 
      $descriptionItem = $descriptionNode->item($i); 
      if($descriptionItem->getAttribute('name') == "description"){ 
        return $descriptionItem->getAttribute('content'); 
      } 
    } 
  } 

  protected function getWebsiteImages(){ 
    // Check if meta image is exists 
    $ogimageNode = $this->dom->getElementsByTagName("meta"); 
    for ($i=0; $i < $ogimageNode->length; $i++) { 
      $ogimageItem = $ogimageNode->item($i); 
      if($ogimageItem->getAttribute('property') == "og:image"){ 
        return $ogimageItem->getAttribute('content'); 
      } 
    } 

    $imageNode = $this->dom->getElementsByTagName("img"); 
    for ($i=0; $i < $imageNode->length; $i++) { 
      $imageItem = $imageNode->item($i); 
      $imageSrc = $imageItem->getAttribute('src'); 
      if(!empty($imageSrc)){ 
        $url = $this->websiteData["url"]; 
        $url = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST); 
        $url = trim($url, '/'); 
        $imageSrc = (strpos($imageSrc, 'http') !== false)?$imageSrc:$url.'/'.$imageSrc; 
        return $imageSrc; 
      } 
    } 
  } 

  protected function validateUrlFormat($url){ 
    return filter_var($url, FILTER_VALIDATE_URL); 
  } 

  protected function verifyUrlExists($url){ 
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_NOBODY, true); 
    curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true); 
    curl_exec($ch); 
    $response = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
    curl_close($ch); 

    return (!empty($response) && $response != 404); 
  } 
}
?>
