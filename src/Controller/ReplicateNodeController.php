<?php

namespace Drupal\localgov_replicate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a controller for cloning nodes.
 */
class ReplicateNodeController extends ControllerBase {

  /**
   * Clones a node if the configuration is set.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to clone.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the cloned node or an access denied page.
   */
  public function replicateNode(Node $node) {
    // Get the Replicate service.
    $replicator = \Drupal::service('replicate.replicator');

    // Clone the node.
    $cloned_node = $replicator->replicateEntity($node);

    // Save the cloned node.
    $cloned_node->save();

    // Log the cloning operation.
    \Drupal::logger('localgov_replicate')->notice('Node with ID %original_nid has been cloned to new node with ID %cloned_nid.', [
      '%original_nid' => $node->id(),
      '%cloned_nid' => $cloned_node->id(),
    ]);

    // Redirect to the cloned node.
    return new RedirectResponse($cloned_node->toUrl()->toString());
  }
}
