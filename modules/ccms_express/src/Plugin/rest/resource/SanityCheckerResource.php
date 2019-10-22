<?php

namespace Drupal\ccms_express\Plugin\rest\resource;


use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Psr\Log\LoggerInterface;
use Drupal\ccms_express\Controller\SanityChecker;
use Drupal\ccms_express\Controller\SendExpressRequest;
use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\HttpFoundation\Response;
use Drupal\rest\ModifiedResourceResponse;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "sanity_check_resource",
 *   label = @Translation("Sanity Checker resource"),
 *   uri_paths = {
 *     "canonical" = "/api/sanitycheck",
 *     "https://www.drupal.org/link-relations/create" = "/api/sanitycheck"
 *   }
 * )
 */
class SanityCheckerResource extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user')
    );
  }
  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post($nids) {
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    $SendExpressRequest = new SendExpressRequest;
    $data = [];
    foreach ($nids as $nid => $request) {
      $data[] = $SendExpressRequest->sendRequest($nid, $request);
    }

    $data = [ 'message' => "Missing Nids Recieved." ];
    $response = new ModifiedResourceResponse($data, 200);
    return $response;
  }
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function get() {
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    $sanityChecker = new SanityChecker;
    $data = $sanityChecker->returnNids();
    $response = new ResourceResponse($data, 200);
    $response->addCacheableDependency($data);
    return $response;
  }
}
