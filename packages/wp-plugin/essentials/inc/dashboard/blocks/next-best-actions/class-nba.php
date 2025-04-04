<?php

/**
 * This class represents the Next Best Action (NBA) model.
 */

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions;

enum ActionStatus
{
  case completed;
  case dismissed;
}

class NBA
{
  public const OPTION_NAME = 'ionos_nba_status';

  private static $option_value;

  private static array $actions = [];

  private function __construct(
    readonly string $id,
    readonly string $title,
    readonly string $description,
    readonly string $link,
    readonly string $anchor,
    private readonly bool $completed
  ) {
    self::$actions[$this->id] = $this;
  }

  public function __get($property)
  {
    // actions can be active (and thus shown to the user) for multiple reasons: they are not completed, they are not dismissed, they are not active yet, ...
    if ('active' === $property) {
      $status = $this->_get_status();
      if (isset($status['completed']) && $status['completed'] || isset($status['dismissed']) && $status['dismissed']) {
        return false;
      }
      return ! $this->completed;
    }
  }

  public function setStatus(ActionStatus $key, $value)
  {
    $id     = $this->id;
    $option = self::_get_option();

    $option[$id] ??= [];
    $option[$id][$key->name] = $value;
    return self::_set_option($option);
  }

  public static function getNBA($id): self|null
  {
    return self::$actions[$id];
  }

  public static function getActions(): array
  {
    return self::$actions;
  }

  public static function register($id, $title, $description, $link, $anchor, $completed = false): void
  {
    new self($id, $title, $description, $link, $anchor, $completed);
  }

  private static function _get_option()
  {
    if (! isset(self::$option_value)) {
      self::$option_value = \get_option(self::OPTION_NAME, []);
    }
    return self::$option_value;
  }

  private static function _set_option(array $option)
  {
    self::$option_value = $option;
    return \update_option(self::OPTION_NAME, $option);
  }

  private function _get_status()
  {
    $option = $this->_get_option();
    return $option[$this->id] ?? [];
  }
}

$data = \ionos_wordpress\essentials\dashboard\blocks\deep_links\get_deep_links_data();

if (null !== $data) {
  NBA::register(
    id: 'connect-domain',
    title: \esc_html__('Connect a Domain', 'ionos-essentials'),
    description: \esc_html__(
      'Connect your domain to your website to increase visibility and attract more visitors.',
      'ionos-essentials'
    ),
    link: \esc_url($data['domain']),
    anchor: \esc_html__('Connect Domain', 'ionos-essentials'),
    completed: false === strpos(home_url(), 'live-website.com') && false === strpos(home_url(), 'localhost'),
  );
}

NBA::register(
  id: 'edit-and-complete',
  title: \esc_html__('Edit & Complete Your Website', 'ionos-essentials'),
  description: \esc_html__(
    'Add pages, text, and images,  fine-tune your website with AI-powered tools or adjust colours and fonts',
    'ionos-essentials'
  ),
  link: '#',
  anchor: \esc_html__('Edit Website', 'ionos-essentials'),
  completed: false
);
NBA::register(
  id: 'help-center',
  title: \esc_html__('Discover Help Center', 'ionos-essentials'),
  description: \esc_html__(
    'Get instant support with Co-Pilot AI, explore our Knowledge Base, or take guided tours.',
    'ionos-essentials'
  ),
  link: '#',
  anchor: \esc_html__('Open Help Center', 'ionos-essentials'),
  completed: false
);
NBA::register(
  id: 'email-account',
  title: \esc_html__('Set Up Email', 'ionos-essentials'),
  description: \esc_html__(
    'Set up your included email account and integrate it with your website.',
    'ionos-essentials'
  ),
  link: '#',
  anchor: \esc_html__('Set Up Email', 'ionos-essentials'),
  completed: false
);
NBA::register(
  id: 'contact-form',
  title: \esc_html__('Set Up Contact Form', 'ionos-essentials'),
  description: \esc_html__('Create a contact form to stay connected with your visitors.', 'ionos-essentials'),
  link: '#',
  anchor: \esc_html__('Set Up Contact Form', 'ionos-essentials'),
  completed: false
);
NBA::register(
  id: 'woocommerce',
  title: \esc_html__('Set Up Your WooCommerce Store', 'ionos-essentials'),
  description: \esc_html__('Launch your online store now with a guided setup wizard.', 'ionos-essentials'),
  link: '#',
  anchor: \esc_html__('Start Setup', 'ionos-essentials'),
  completed: false
);
NBA::register(
  id: 'upload-logo',
  title: \esc_html__('Add Logo', 'ionos-essentials'),
  description: \esc_html__(
    'Ensure your website is branded with your unique logo for a professional look.',
    'ionos-essentials'
  ),
  link: \admin_url('options-general.php'),
  anchor: \esc_html__('Add Logo', 'ionos-essentials'),
  completed: 0 < intval(\get_option('site_icon', 0))
);
NBA::register(
  id: 'create-page',
  title: \esc_html__('Create a Page', 'ionos-essentials'),
  description: \esc_html__('Create and publish a page and share your story with the world.', 'ionos-essentials'),
  link: '#',
  anchor: \esc_html__('Create Page', 'ionos-essentials'),
  completed: false
);
NBA::register(
  id: 'social-media',
  title: \esc_html__('Social Media Setup', 'ionos-essentials'),
  description: \esc_html__(
    'Connect your social media profiles to your website and expand your online presence.',
    'ionos-essentials'
  ),
  link: '#',
  anchor: \esc_html__('Connect Social Media', 'ionos-essentials'),
  completed: false
);
NBA::register(
  id: 'favicon',
  title: \esc_html__('Add Favicon', 'ionos-essentials'),
  description: \esc_html__(
    'Add a favicon (website icon) to your website to enhance brand recognition and visibility.',
    'ionos-essentials'
  ),
  link: '#',
  anchor: \esc_html__('Add Favicon', 'ionos-essentials'),
  completed: false
);

\do_action('ionos_dashboard__register_nba_element');
