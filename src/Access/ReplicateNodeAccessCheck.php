<?php

namespace Drupal\localgov_replicate\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;

/**
 * Provides an access check for the Replicate Node tab.
 */
class ReplicateNodeAccessCheck implements AccessInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ReplicateNodeAccessCheck object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Checks access for the Replicate Node tab.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(NodeInterface $node, AccountInterface $account) {

    $config = $this->configFactory->get('localgov_replicate.settings');
    $enabled_node_types = $config->get('node_types') ?: [];

    // Check if the node's content type is in the enabled node types array.
    if (in_array($node->getType(), $enabled_node_types)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
