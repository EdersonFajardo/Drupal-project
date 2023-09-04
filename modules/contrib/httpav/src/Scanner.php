<?php

namespace Drupal\httpav;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\FileInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service class for the GovCMS AV scanner.
 */
class Scanner {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The current request context.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  const FILE_IS_UNCHECKED = -1;
  const FILE_IS_CLEAN = 0;
  const FILE_IS_INFECTED = 1;

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   The guzzle client for HTTP requests.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface.
   */
  public function __construct(RequestStack $stack, Client $client, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->request = $stack->getCurrentRequest();
    $this->client = $client;
    $this->config = $config_factory->get('httpav.settings');
    $this->logger = $logger_factory->get('httpav');
  }

  /**
   * Get the return key.
   *
   * @return string
   *   The return key value.
   */
  public function getReturnKey() : string {
    $return_key = getenv('HTTPAV_RETURN_KEY');
    if (!empty($return_key)) {
      return $return_key;
    }
    return $this->config->get('return_key') ?: 'results';
  }

  /**
   * Get the payload key.
   *
   * @return string
   *   The payload key value.
   */
  public function getPayloadKey() : string {
    $payload_key = getenv('HTTP_PAYLOAD_KEY');
    if (!empty($payload_key)) {
      return $payload_key;
    }
    return $this->config->get('payload_key') ?: 'malware';
  }

  /**
   * Determine if the AV is enabled or not.
   *
   * @return bool
   */
  public function isEnabled() : bool {
    return $this->config->get('enabled') ?: TRUE;
  }

  /**
   * Get the AV service endpoint.
   *
   * @return string
   *   The HTTP address for the scanner.
   */
  public function getEndpoint() : string {
    $endpoint = getenv('HTTPAV_ENDPOINT');
    if (!empty($endpoint)) {
      return $endpoint;
    }

    return $this->config->get('endpoint') ?: 'https://localhost';
  }

  /**
   * Allow uploaded file to bypass AV.
   *
   * @return bool
   *   If connection failures are treated as valid.
   */
  public function isAllowedUnchecked() : bool {
    return $this->config->get('allow_unchecked') ?: FALSE;
  }

  /**
   * Determine if the file is scannable.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file instance.
   *
   * @return bool
   *   TRUE if the file should be scanned.
   */
  public function isScannable(FileInterface $file) : bool {
    // Check whether this stream-wrapper scheme is scannable.
    if (!empty($file->destination)) {
      $scheme = \Drupal::service('stream_wrapper_manager')->getScheme($file->destination);
    }
    else {
      $scheme = \Drupal::service('stream_wrapper_manager')->getScheme($file->getFileUri());
    }

    if (empty($scheme)) {
      return TRUE;
    }

    // By default all local schemes should be scannable.
    $mgr = \Drupal::service('stream_wrapper_manager');
    $local_schemes = array_keys($mgr->getWrappers(StreamWrapperInterface::LOCAL));
    $scheme_is_local = in_array($scheme, $local_schemes);

    return $scheme_is_local;
  }

  /**
   * Send the file to the scanner.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file instance.
   *
   * @return int
   *   File scan status.
   */
  public function scan(FileInterface $file) : int {
    $replacements = [
      '%filename' => $file->getFilename(),
      '%fileuri' => $file->getFileUri(),
      '%return_key' => $this->getReturnKey(),
      '%virusname' => null,
    ];

    // Empty files cannot be infected.
    if ($file->getSize() === 0) {
      $this->logger->info('Uploaded file %fileuri checked and found clean.', $replacements);
      return self::FILE_IS_CLEAN;
    }

    // Read the contents of the file.
    $file_handler = @fopen($file->getFileUri(), 'r');

    if (!is_resource($file_handler)) {
      // @see Drupal\ckeditor5\Controller\CKEditor5ImageController
      $upload = $this->request->files->get('upload');
      $file_handler = fopen($upload->getRealPath(), 'r');
    }

    $payload = [
      'multipart' => [
        [
          'name' => $this->getPayloadKey(),
          'contents' => $file_handler,
          'filename' => $file->getFilename(),
        ]
      ]
    ];

    try {
      $response = $this->client->post($this->getEndpoint(), $payload);
    } catch (\Exception $e) {
      $this->logger->notice('File %fileuri could not be scanned', $replacements);
      return self::FILE_IS_UNCHECKED;
    }

    $body = $response->getBody()->getContents();
    $body = json_decode($body, TRUE);

    if (empty($body[$this->getReturnKey()])) {
      $this->logger->notice('File %fileuri could not be scanned, invalid return key %return_key used.', $replacements);
      return self::FILE_IS_UNCHECKED;
    }

    if (!isset($body[$this->getReturnKey()]['infected']) && $this->isAllowedUnchecked()) {
      $this->logger->notice('File %fileuri could not be scanned, invalid return signature missing "infected".', $replacements);
      return self::FILE_IS_UNCHECKED;
    }

    if (!empty($body[$this->getReturnKey()]['infected'])) {
      $replacements['%virusname'] = $body[$this->getReturnKey()]['result'];
      $this->logger->error('Virus %virusname detected in uploaded file %fileuri.', $replacements);
      return self::FILE_IS_INFECTED;
    }

    $this->logger->info('Uploaded file %fileuri checked and found clean.', $replacements);
    return self::FILE_IS_CLEAN;
  }

}
