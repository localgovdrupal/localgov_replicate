<?php

namespace Drupal\Tests\localgov_replicate\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\localgov_roles\RolesHelper;

/**
 * Functional tests for LocalGov Replicate tab access control.
 */
class ReplicateTabAccessTest extends BrowserTestBase {

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
    'localgov_roles',
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
   * A test user with only authenticated permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

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

    // Ensure the localgov_editor role exists and has the replicate permission.
    $editor_role = Role::load(RolesHelper::EDITOR_ROLE);
    if (!$editor_role) {
      $editor_role = Role::create([
        'id' => RolesHelper::EDITOR_ROLE,
        'label' => 'Editor',
      ]);
      $editor_role->save();
    }
    $editor_role->grantPermission('replicate entities');
    $editor_role->save();

    // Create a user with editor role and basic permissions.
    $this->editorUser = $this->drupalCreateUser([
      'create test_content content',
      'edit own test_content content',
      'access content',
    ]);
    $this->editorUser->addRole(RolesHelper::EDITOR_ROLE);
    $this->editorUser->save();

    // Create a user with only authenticated permissions (no editor role).
    $this->authenticatedUser = $this->drupalCreateUser([
      'create test_content content',
      'edit own test_content content',
      'access content',
    ]);

    // Create a test node owned by the editor user.
    $this->testNode = Node::create([
      'type' => 'test_content',
      'title' => 'Test Node for Replication',
      'uid' => $this->editorUser->id(),
      'status' => 1,
    ]);
    $this->testNode->save();
  }

  /**
   * Test that the localgov_editor role has access to the clone tab.
   */
  public function testEditorHasCloneTabAccess(): void {
    // Log in as the editor user.
    $this->drupalLogin($this->editorUser);

    // Navigate to the test node.
    $this->drupalGet($this->testNode->toUrl());

    // Check that the page loads successfully.
    $this->assertSession()->statusCodeEquals(200);

    // Check that the "Clone" tab is present for editor users.
    $this->assertSession()->linkExists('Clone');

    // Check that the Clone tab links to the correct route.
    $clone_link = $this->getSession()->getPage()->findLink('Clone');
    $this->assertNotNull($clone_link, 'Clone link should be found on the page for editor users');

    // Verify the href contains the replicate route.
    $href = $clone_link->getAttribute('href');
    $this->assertStringContainsString('/replicate', $href, 'Clone link should point to the replicate route');

    // Navigate directly to the replicate page to ensure access is granted.
    $this->drupalGet($this->testNode->toUrl('replicate'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Access denied');
  }

  /**
   * Test that the authenticated role does not have access to the clone tab.
   */
  public function testAuthenticatedUserDoesNotHaveCloneTabAccess(): void {
    // Log in as the authenticated user (without editor role).
    $this->drupalLogin($this->authenticatedUser);

    // Navigate to the test node.
    $this->drupalGet($this->testNode->toUrl());

    // Check that the page loads successfully.
    $this->assertSession()->statusCodeEquals(200);

    // Check that the "Clone" tab is NOT present for authenticated users without editor role.
    $this->assertSession()->linkNotExists('Clone');

    // Verify that direct access to the replicate page is denied.
    $this->drupalGet($this->testNode->toUrl('replicate'));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test that anonymous users do not have access to the clone tab.
   */
  public function testAnonymousUserDoesNotHaveCloneTabAccess(): void {
    // Make sure we're not logged in.
    $this->drupalLogout();

    // Navigate to the test node.
    $this->drupalGet($this->testNode->toUrl());

    // Check that the page loads successfully.
    $this->assertSession()->statusCodeEquals(200);

    // Check that the "Clone" tab is NOT present for anonymous users.
    $this->assertSession()->linkNotExists('Clone');

    // Verify that direct access to the replicate page is denied.
    $this->drupalGet($this->testNode->toUrl('replicate'));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test role-based access with different content ownership.
   */
  public function testCloneTabAccessWithDifferentContentOwnership(): void {
    // Create a node owned by the authenticated user.
    $node_by_authenticated_user = Node::create([
      'type' => 'test_content',
      'title' => 'Test Node by Authenticated User',
      'uid' => $this->authenticatedUser->id(),
      'status' => 1,
    ]);
    $node_by_authenticated_user->save();

    // Test editor access to content they don't own.
    $this->drupalLogin($this->editorUser);
    $this->drupalGet($node_by_authenticated_user->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Editor should still have Clone tab access even for content they don't own.
    $this->assertSession()->linkExists('Clone');

    // Test authenticated user access to content they own.
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet($node_by_authenticated_user->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Authenticated user should NOT have Clone tab access even for their own content.
    $this->assertSession()->linkNotExists('Clone');
  }

  /**
   * Test that removing replicate permission from editor role removes access.
   */
  public function testRolePermissionRemoval(): void {
    // Remove replicate permission from editor role.
    $editor_role = Role::load(RolesHelper::EDITOR_ROLE);
    $editor_role->revokePermission('replicate entities');
    $editor_role->save();

    // Log in as the editor user.
    $this->drupalLogin($this->editorUser);

    // Navigate to the test node.
    $this->drupalGet($this->testNode->toUrl());

    // Check that the page loads successfully.
    $this->assertSession()->statusCodeEquals(200);

    // Check that the "Clone" tab is NOT present when permission is removed.
    $this->assertSession()->linkNotExists('Clone');

    // Verify that direct access to the replicate page is denied.
    $this->drupalGet($this->testNode->toUrl('replicate'));
    $this->assertSession()->statusCodeEquals(403);
  }

}
