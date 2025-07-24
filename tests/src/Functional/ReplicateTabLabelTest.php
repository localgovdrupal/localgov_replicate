<?php

namespace Drupal\Tests\localgov_replicate\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Functional tests for LocalGov Replicate tab labeling.
 */
class ReplicateTabLabelTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'localgov';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_replicate',
    'replicate',
    'replicate_ui',
    'node',
    'user',
  ];

  /**
   * A test user with editor permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testNode;

  /**
   * Set up tests.
   */
  protected function setUp(): void {
    parent::setUp();

    // Use Claro for testing as there are problems with the Gin theme.
    // See https://github.com/localgovdrupal/localgov/issues/731
    $this->assertTrue(\Drupal::service('theme_installer')->install(['claro']));
    $this->container->get('config.factory')
      ->getEditable('system.theme')
      ->set('default', 'claro')
      ->set('admin', 'claro')
      ->save();

    // Create a content type for testing.
    $node_type = NodeType::create([
      'type' => 'test_content',
      'name' => 'Test Content',
    ]);
    $node_type->save();

    // Create a user with editor role and replicate permissions.
    $this->editorUser = $this->drupalCreateUser([
      'replicate entities',
      'create test_content content',
      'edit own test_content content',
      'access content',
    ]);

    // Create a test node.
    $this->testNode = Node::create([
      'type' => 'test_content',
      'title' => 'Test Node for Replication',
      'uid' => $this->editorUser->id(),
      'status' => 1,
    ]);
    $this->testNode->save();
  }

  /**
   * Test that the replicate tab shows "Clone" instead of "Replicate".
   */
  public function testReplicateTabLabel(): void {
    // Log in as the editor user.
    $this->drupalLogin($this->editorUser);

    // Navigate to the test node.
    $this->drupalGet($this->testNode->toUrl());

    // Check that the page loads successfully.
    $this->assertSession()->statusCodeEquals(200);

    // Check that the "Clone" tab is present instead of "Replicate".
    $this->assertSession()->linkExists('Clone');

    // Verify that the old "Replicate" text is not present in the tab.
    $this->assertSession()->linkNotExists('Replicate');

    // Check that the Clone tab links to the correct route.
    $clone_link = $this->getSession()->getPage()->findLink('Clone');
    $this->assertNotNull($clone_link, 'Clone link should be found on the page');

    // Verify the href contains the replicate route.
    $href = $clone_link->getAttribute('href');
    $this->assertStringContainsString('/replicate', $href, 'Clone link should point to the replicate route');
  }

  /**
   * Test that the replicate tab label is correctly set on the replicate page.
   */
  public function testReplicateTabLabelOnReplicatePage(): void {
    // Log in as the editor user.
    $this->drupalLogin($this->editorUser);

    // Navigate directly to the replicate page.
    $this->drupalGet($this->testNode->toUrl('replicate'));

    // Check that the page loads successfully.
    $this->assertSession()->statusCodeEquals(200);

    // Check that the "Clone" tab is active and present.
    $this->assertSession()->linkExists('Clone');

    // Verify that the old "Replicate" text is not present in the tab.
    $this->assertSession()->linkNotExists('Replicate');
  }

}
